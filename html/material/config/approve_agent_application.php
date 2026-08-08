<?php
session_start();
require 'pdo.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/account_provisioning.php';

header('Content-Type: application/json');

if (!sessionHasAuthoritativeRole(['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'agent_management')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload and try again.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$applicationId = isset($data['application_id']) ? (int)$data['application_id'] : 0;
$decision = $data['decision'] ?? '';

if (!$applicationId || !in_array($decision, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM agent_applications WHERE id = ? AND status IN ('submitted','under_review') FOR UPDATE");
    $stmt->execute([$applicationId]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        throw new Exception('Application not found or already reviewed');
    }

    if ($decision === 'reject') {
        $pdo->prepare(
            "UPDATE agent_applications SET status = 'rejected', reviewed_by = ?, review_date = NOW(), rejection_reason = ? WHERE id = ?"
        )->execute([$_SESSION['user_id'], $data['reason'] ?? null, $applicationId]);

        $pdo->commit();
        logActivity((int)$_SESSION['user_id'], 'AGENT_APPLICATION_REJECTED', "Application {$app['application_number']} rejected", 'agent_form');
        echo json_encode(['success' => true, 'status' => 'rejected']);
        exit;
    }

    /* ---------- APPROVE: promote into a live agents row ---------- */
    $check = $pdo->prepare("SELECT id, role FROM admin_users WHERE email = ?");
    $check->execute([$app['email']]);
    $existingUser = $check->fetch(PDO::FETCH_ASSOC);
    $userId = $existingUser['id'] ?? null;

    if ($existingUser && $existingUser['role'] !== 'agent_coordinator') {
        throw new Exception('That email belongs to a different portal role');
    }

    if (!$userId) {
        $usernameBase = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '.', $app['first_name'] . '.' . $app['surname'])), '.');
        $usernameBase = $usernameBase !== '' ? $usernameBase : 'agent';
        $username = $usernameBase;
        $suffix = 1;
        $usernameCheck = $pdo->prepare('SELECT 1 FROM admin_users WHERE username = ?');
        while (true) {
            $usernameCheck->execute([$username]);
            if (!$usernameCheck->fetchColumn()) break;
            $username = $usernameBase . '.' . (++$suffix);
        }
        $insertUser = $pdo->prepare(
            "INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, created_at)
             VALUES (?, ?, ?, ?, 'agent_coordinator', 1, NOW())"
        );
        $temporaryPassword = generateTemporaryPassword();
        $fullName = trim($app['first_name'] . ' ' . $app['surname']);
        $insertUser->execute([
            $username, $app['email'], password_hash($temporaryPassword, PASSWORD_DEFAULT),
            $fullName,
        ]);
        queueTemporaryCredentialEmail($app['email'], $fullName, $temporaryPassword);
        $userId = $pdo->lastInsertId();
    }

    do {
        $agentId = 'AGT' . date('Y') . random_int(1000, 9999);
        $exists = $pdo->prepare("SELECT id FROM agents WHERE agent_id = ?");
        $exists->execute([$agentId]);
    } while ($exists->fetchColumn());

    $insertAgent = $pdo->prepare("
        INSERT INTO agents (
            agent_id, application_id, user_id,
            surname, first_name, middle_name, maiden_name,
            date_of_birth, id_type, id_number, nationality, gender,
            email, mobile_number, company_name, job_title,
            gross_income, net_pay, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
    ");
    $insertAgent->execute([
        $agentId, $app['application_number'], $userId,
        $app['surname'], $app['first_name'], $app['middle_name'], $app['maiden_name'],
        $app['date_of_birth'], $app['id_type'], $app['id_number'], $app['nationality'], $app['gender'],
        $app['email'], $app['mobile_number'], $app['company_name'], $app['job_title'],
        $app['gross_income'], $app['net_pay'],
    ]);
    $newAgentDbId = (int)$pdo->lastInsertId();

    // Carry over uploaded documents from the application to the new agent record
    $docMap = [
        'id_document'     => 'id_document_url',
        'proof_residence' => 'proof_residence_url',
        'agency_ffc'      => 'agency_ffc_url',
        'agent_neab'      => 'agent_neab_url',
        'agent_ffc'       => 'agent_ffc_url',
    ];
    $docs = $pdo->prepare("SELECT document_type, file_path FROM agent_documents WHERE application_id = ?");
    $docs->execute([$applicationId]);
    foreach ($docs->fetchAll(PDO::FETCH_ASSOC) as $doc) {
        $column = $docMap[$doc['document_type']] ?? null;
        if ($column) {
            $pdo->prepare("UPDATE agents SET {$column} = ? WHERE id = ?")->execute([$doc['file_path'], $newAgentDbId]);
        }
    }

    $pdo->prepare(
        "UPDATE agent_applications SET status = 'approved', reviewed_by = ?, review_date = NOW(), promoted_agent_id = ? WHERE id = ?"
    )->execute([$_SESSION['user_id'], $newAgentDbId, $applicationId]);

    $pdo->commit();

    logActivity((int)$_SESSION['user_id'], 'AGENT_APPLICATION_APPROVED', "Application {$app['application_number']} promoted to agent {$agentId}", 'agent_form');

    session_write_close();
    finishResponseAndDeliverQueuedCredentialEmails((string)json_encode([
        'success' => true,
        'status' => 'approved',
        'agent_id' => $agentId,
    ]));

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Agent application review failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process the application. Please try again later.']);
}

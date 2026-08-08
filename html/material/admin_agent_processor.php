<?php
/**
 * File: admin_agent_processor.php
 * Purpose: Handle staff-entered agent registration form submission
 */

session_start();
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/config/account_provisioning.php';
require_once __DIR__ . '/api/security_integration.php';

// Log errors for debugging, but never echo them into the response - this
// endpoint returns JSON, and a PHP warning/notice printed mid-response
// breaks the JSON output while also leaking file paths and line numbers.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Utility: Debug log
function debugLog($msg, $level = 'INFO') {
    if (getenv('NURU_DEBUG_LOG') !== '1'
        || strtolower((string)(getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
        return;
    }
    $time = date('Y-m-d H:i:s');
    $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', (string)$msg), 0, 1000);
    @error_log("[$time] [$level] $safeMessage\n", 3, __DIR__ . '/logs/debug.log');
}

// Utility: Generate agent_id and application_id
function generateAgentIds(PDO $pdo) {
    do {
        $agentId = 'AGT' . date('Y') . random_int(1000, 9999);
        $check = $pdo->prepare("SELECT id FROM agents WHERE agent_id = ?");
        $check->execute([$agentId]);
    } while ($check->fetchColumn());

    $applicationId = 'AGENT-' . date('Y') . '-' . random_int(1000, 9999);

    return [$agentId, $applicationId];
}

// Utility: Create admin user for the agent if not exists
function createAgentUserIfNotExists(PDO $pdo, array $data) {
    if (empty($data['email'])) {
        return null;
    }

    $check = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
    $check->execute([$data['email']]);
    $existing = $check->fetchColumn();
    if ($existing) {
        return (int)$existing;
    }

    $username = strtolower(preg_replace('/\s+/', '.', ($data['first_name'] ?? 'Agent') . '.' . ($data['surname'] ?? '')));
    if (empty(trim($username, '.'))) {
        $username = 'agent.' . uniqid();
    }

    $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['surname'] ?? ''));
    $temporaryPassword = generateTemporaryPassword();
    $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, agent_id, remember_token, remember_token_expires, created_at)
        VALUES (?, ?, ?, ?, 'agent_coordinator', 1, '', '', '', NOW())
    ");
    $stmt->execute([$username, $data['email'], $passwordHash, $fullName]);
    if (!sendTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword)) {
        error_log('Agent account provisioned but credential email could not be sent for user ' . $username);
    }

    return (int)$pdo->lastInsertId();
}

// The extension was already being taken from pathinfo() rather than the raw
// filename (closing the path-traversal angle), but nothing checked it against
// an allowlist - a file named "shell.php" would be saved as "..._shell.php"
// er, as "<agentId>_<field>_<time>.php" into the web-servable uploads/ tree.
// Utility: Save uploaded documents, return column => path map
function saveUploadedDocuments(string $agentId): array {
    $fileFields = [
        'id_document'     => 'id_document_url',
        'proof_residence' => 'proof_residence_url',
        'agency_ffc'      => 'agency_ffc_url',
        'agent_neab'      => 'agent_neab_url',
        'agent_ffc'       => 'agent_ffc_url',
    ];

    $uploadDir = __DIR__ . '/uploads/agent/' . $agentId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }

    $paths = [];
    foreach ($fileFields as $inputName => $column) {
        if (!empty($_FILES[$inputName]['name']) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$inputName];
            $validation = SecurityIntegration::validateFileUpload($file);
            if (!$validation['success']) {
                debugLog("Rejected {$inputName} upload: " . implode(', ', $validation['errors']), 'WARNING');
                continue;
            }
            $filename = $agentId . '_' . $inputName . '_' . $validation['safe_filename'];
            $target = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $paths[$column] = 'uploads/agent/' . $agentId . '/' . $filename;
            }
        }
    }

    return $paths;
}

try {
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    if (!sessionHasAuthoritativeRole(['admin', 'manager'])) {
        http_response_code(403);
        throw new Exception('You are not authorized to submit this form');
    }

    $data = $_POST;
    if (empty($data)) {
        throw new Exception("No form data submitted");
    }

    if (!validCsrfToken($data['csrf_token'] ?? null, 'staff_agent_form')) {
        http_response_code(419);
        throw new Exception('Your session has expired. Please reload and try again.');
    }

    // Server-side email validation - there was previously none at all, only
    // the client-side <input type="email">, which is trivially bypassed by
    // posting directly to this endpoint. An unvalidated email also becomes
    // an email-header-injection vector (CRLF in the value) the moment a
    // working SMTP relay is configured, so this matters even though mail()
    // isn't currently delivering anything in this environment.
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        debugLog("Rejected invalid email: {$data['email']}");
        throw new Exception("Please provide a valid email address");
    }

    debugLog('Agent form submitted');

    $pdo->beginTransaction();

    [$agentId, $applicationId] = generateAgentIds($pdo);

    $grossIncome = isset($data['gross_income']) ? (float)$data['gross_income'] : 0;
    $deductions = isset($data['total_deductions']) ? (float)$data['total_deductions'] : 0;
    $netPay = isset($data['net_pay']) ? (float)$data['net_pay'] : ($grossIncome - $deductions);

    $userId = createAgentUserIfNotExists($pdo, $data);

    $stmt = $pdo->prepare("
        INSERT INTO agents (
            agent_id, application_id, user_id,
            surname, first_name, middle_name, maiden_name,
            date_of_birth, id_type, id_number, nationality, gender,
            email, mobile_number, company_name, job_title,
            gross_income, net_pay, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $agentId, $applicationId, $userId,
        $data['surname'] ?? '', $data['first_name'] ?? '', $data['middle_name'] ?? null, $data['maiden_name'] ?? null,
        $data['date_of_birth'] ?? null, $data['id_type'] ?? '', $data['id_number'] ?? '', $data['nationality'] ?? '', $data['gender'] ?? '',
        $data['email'] ?? '', $data['mobile_number'] ?? '', $data['company_name'] ?? '', $data['job_title'] ?? '',
        $grossIncome, $netPay,
    ]);

    $agentDbId = (int)$pdo->lastInsertId();

    // Address / next of kin details go into their dedicated tables
    if (!empty($data['residential_town'])) {
        $stmt = $pdo->prepare("
            INSERT INTO agent_addresses (agent_id, town, region, street_name, suburb, location, po_box)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $agentDbId,
            $data['residential_town'] ?? null,
            $data['residential_region'] ?? null,
            $data['residential_street_name'] ?? null,
            $data['residential_suburb'] ?? null,
            $data['residential_location'] ?? null,
            $data['po_box'] ?? null,
        ]);
    }

    if (!empty($data['kin_surname']) || !empty($data['kin_first_name'])) {
        $stmt = $pdo->prepare("
            INSERT INTO agent_next_of_kin (agent_id, surname, first_name, contact_number, email, town, region, street_name, suburb, location)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $agentDbId,
            $data['kin_surname'] ?? '',
            $data['kin_first_name'] ?? '',
            $data['kin_contact_number'] ?? '',
            $data['kin_email'] ?? null,
            $data['kin_town'] ?? null,
            $data['kin_region'] ?? null,
            $data['kin_street_name'] ?? null,
            $data['kin_suburb'] ?? null,
            $data['kin_location'] ?? null,
        ]);
    }

    // Documents
    $docPaths = saveUploadedDocuments($agentId);
    if (!empty($docPaths)) {
        $setClauses = [];
        $params = [];
        foreach ($docPaths as $column => $path) {
            $setClauses[] = "$column = ?";
            $params[] = $path;
        }
        $params[] = $agentDbId;
        $pdo->prepare("UPDATE agents SET " . implode(', ', $setClauses) . " WHERE id = ?")->execute($params);
    }

    $pdo->commit();

    debugLog("Agent created successfully: " . $agentId);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'agent_id' => $agentId,
        'application_id' => $applicationId,
        'message' => 'Agent registered successfully',
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    debugLog("ERROR: " . $e->getMessage(), 'ERROR');

    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to register the agent. Please review the form and try again.',
    ]);
}

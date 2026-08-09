<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Router;
use App\Models\Agent;

/**
 * Internal staff-facing agent management: list/create/view agents, review
 * agent applications, and change agent status. Ported from
 * agent_list.php + agenttable_list.php, agent_admin_form.php,
 * admin_agent_processor.php, agent_profile.php, and the JSON endpoints
 * config/approve_agent_application.php, config/delete_agent.php,
 * config/update_agent_status.php. The write/processor methods keep their
 * original procedural logic close to verbatim (same queries, same
 * validation, same status codes) rather than being re-modeled, matching
 * how App\Controllers\Admin\AuthController preserves its security-sensitive
 * flows. This is a completely separate, staff-only surface from the public
 * agent application form under html/material/agent/ (App\Controllers\Admin
 * is never used there).
 */
final class AgentController extends \App\Core\Controller
{
    public function list(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager']);
        require_once \NURU_MATERIAL . '/config/id_tokens.php';

        $model = new Agent($this->pdo);

        $this->render('admin.agents.list', [
            'agents' => $model->all(),
            'pendingApps' => $model->pendingApplications(),
            'csrfToken' => Auth::csrfToken('agent_management'),
            'baseUrl' => Router::basePath(),
        ]);
    }

    public function form(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager']);

        $this->render('admin.agents.form', [
            'csrfToken' => Auth::csrfToken('staff_agent_form'),
            'baseUrl' => Router::basePath(),
        ]);
    }

    public function profile(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        require_once \NURU_MATERIAL . '/config/id_tokens.php';

        $agentId = \portalDecodeId($_GET['id'] ?? null);
        if ($agentId === null) {
            die('Invalid agent ID');
        }
        if (Auth::currentRole() === 'agent_coordinator'
            && \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) !== $agentId) {
            http_response_code(403);
            exit('You may only view your own agent profile.');
        }

        $agent = (new Agent($this->pdo))->find($agentId);
        if ($agent === null) {
            die('Agent not found');
        }

        $this->render('admin.agents.profile', [
            'agent' => $agent,
            'csrfToken' => Auth::csrfToken('agent_management'),
            'baseUrl' => Router::basePath(),
        ]);
    }

    /**
     * Staff-entered agent registration form submission. Ported near-verbatim
     * from admin_agent_processor.php - same transaction shape, same
     * generated agent_id/application_id scheme, same upload validation.
     */
    public function store(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        require_once \NURU_MATERIAL . '/config/account_provisioning.php';
        require_once \NURU_MATERIAL . '/api/security_integration.php';
        Auth::boot();

        // Log errors for debugging, but never echo them into the response - this
        // endpoint returns JSON, and a PHP warning/notice printed mid-response
        // breaks the JSON output while also leaking file paths and line numbers.
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        $pdo = $this->pdo;

        try {
            if (!$pdo) {
                throw new \Exception('Database connection failed');
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Invalid request method');
            }

            if (!\sessionHasAuthoritativeRole(['admin', 'manager'])) {
                http_response_code(403);
                throw new \Exception('You are not authorized to submit this form');
            }

            $data = $_POST;
            if (empty($data)) {
                throw new \Exception('No form data submitted');
            }

            if (!\validCsrfToken($data['csrf_token'] ?? null, 'staff_agent_form')) {
                http_response_code(419);
                throw new \Exception('Your session has expired. Please reload and try again.');
            }

            // Server-side email validation - there was previously none at all, only
            // the client-side <input type="email">, which is trivially bypassed by
            // posting directly to this endpoint. An unvalidated email also becomes
            // an email-header-injection vector (CRLF in the value) the moment a
            // working SMTP relay is configured, so this matters even though mail()
            // isn't currently delivering anything in this environment.
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->agentStoreDebugLog("Rejected invalid email: {$data['email']}");
                throw new \Exception('Please provide a valid email address');
            }

            $this->agentStoreDebugLog('Agent form submitted');

            $pdo->beginTransaction();

            [$agentId, $applicationId] = $this->generateAgentIds($pdo);

            $grossIncome = isset($data['gross_income']) ? (float) $data['gross_income'] : 0;
            $deductions = isset($data['total_deductions']) ? (float) $data['total_deductions'] : 0;
            $netPay = isset($data['net_pay']) ? (float) $data['net_pay'] : ($grossIncome - $deductions);

            $userId = $this->createAgentUserIfNotExists($pdo, $data);

            $stmt = $pdo->prepare('
                INSERT INTO agents (
                    agent_id, application_id, user_id,
                    surname, first_name, middle_name, maiden_name,
                    date_of_birth, id_type, id_number, nationality, gender,
                    email, mobile_number, company_name, job_title,
                    gross_income, net_pay, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([
                $agentId, $applicationId, $userId,
                $data['surname'] ?? '', $data['first_name'] ?? '', $data['middle_name'] ?? null, $data['maiden_name'] ?? null,
                $data['date_of_birth'] ?? null, $data['id_type'] ?? '', $data['id_number'] ?? '', $data['nationality'] ?? '', $data['gender'] ?? '',
                $data['email'] ?? '', $data['mobile_number'] ?? '', $data['company_name'] ?? '', $data['job_title'] ?? '',
                $grossIncome, $netPay,
            ]);

            $agentDbId = (int) $pdo->lastInsertId();

            // Address / next of kin details go into their dedicated tables
            if (!empty($data['residential_town'])) {
                $stmt = $pdo->prepare('
                    INSERT INTO agent_addresses (agent_id, town, region, street_name, suburb, location, po_box)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
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
                $stmt = $pdo->prepare('
                    INSERT INTO agent_next_of_kin (agent_id, surname, first_name, contact_number, email, town, region, street_name, suburb, location)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
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
            $docPaths = $this->saveUploadedDocuments($agentId);
            if (!empty($docPaths)) {
                $setClauses = [];
                $params = [];
                foreach ($docPaths as $column => $path) {
                    $setClauses[] = "$column = ?";
                    $params[] = $path;
                }
                $params[] = $agentDbId;
                $pdo->prepare('UPDATE agents SET ' . implode(', ', $setClauses) . ' WHERE id = ?')->execute($params);
            }

            $pdo->commit();

            $this->agentStoreDebugLog('Agent created successfully: ' . $agentId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'agent_id' => $agentId,
                'application_id' => $applicationId,
                'message' => 'Agent registered successfully',
            ]);
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->agentStoreDebugLog('ERROR: ' . $e->getMessage(), 'ERROR');

            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Unable to register the agent. Please review the form and try again.',
            ]);
        }
    }

    private function agentStoreDebugLog(string $msg, string $level = 'INFO'): void
    {
        if (getenv('NURU_DEBUG_LOG') !== '1'
            || strtolower((string) (getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
            return;
        }
        $time = date('Y-m-d H:i:s');
        $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $msg), 0, 1000);
        @error_log("[$time] [$level] $safeMessage\n", 3, \NURU_MATERIAL . '/logs/debug.log');
    }

    private function generateAgentIds(\PDO $pdo): array
    {
        do {
            $agentId = 'AGT' . date('Y') . random_int(1000, 9999);
            $check = $pdo->prepare('SELECT id FROM agents WHERE agent_id = ?');
            $check->execute([$agentId]);
        } while ($check->fetchColumn());

        $applicationId = 'AGENT-' . date('Y') . '-' . random_int(1000, 9999);

        return [$agentId, $applicationId];
    }

    private function createAgentUserIfNotExists(\PDO $pdo, array $data): ?int
    {
        if (empty($data['email'])) {
            return null;
        }

        $check = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
        $check->execute([$data['email']]);
        $existing = $check->fetchColumn();
        if ($existing) {
            return (int) $existing;
        }

        $username = strtolower(preg_replace('/\s+/', '.', ($data['first_name'] ?? 'Agent') . '.' . ($data['surname'] ?? '')));
        if (empty(trim($username, '.'))) {
            $username = 'agent.' . uniqid();
        }

        $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['surname'] ?? ''));
        $temporaryPassword = \generateTemporaryPassword();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, agent_id, remember_token, remember_token_expires, created_at)
            VALUES (?, ?, ?, ?, 'agent_coordinator', 1, '', '', '', NOW())
        ");
        $stmt->execute([$username, $data['email'], $passwordHash, $fullName]);
        if (!\sendTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword)) {
            error_log('Agent account provisioned but credential email could not be sent for user ' . $username);
        }

        return (int) $pdo->lastInsertId();
    }

    /**
     * The extension was already being taken from pathinfo() rather than the raw
     * filename (closing the path-traversal angle), but nothing checked it against
     * an allowlist - a file named "shell.php" would be saved as "..._shell.php"
     * er, as "<agentId>_<field>_<time>.php" into the web-servable uploads/ tree.
     */
    private function saveUploadedDocuments(string $agentId): array
    {
        $fileFields = [
            'id_document' => 'id_document_url',
            'proof_residence' => 'proof_residence_url',
            'agency_ffc' => 'agency_ffc_url',
            'agent_neab' => 'agent_neab_url',
            'agent_ffc' => 'agent_ffc_url',
        ];

        $uploadDir = \NURU_MATERIAL . '/uploads/agent/' . $agentId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $paths = [];
        foreach ($fileFields as $inputName => $column) {
            if (!empty($_FILES[$inputName]['name']) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$inputName];
                $validation = \SecurityIntegration::validateFileUpload($file);
                if (!$validation['success']) {
                    $this->agentStoreDebugLog("Rejected {$inputName} upload: " . implode(', ', $validation['errors']), 'WARNING');
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

    /** Ported near-verbatim from config/update_agent_status.php. */
    public function updateStatus(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');
        require_once \NURU_MATERIAL . '/includes/functions.php';
        Auth::boot();
        $this->requireRole(['admin', 'manager']);

        $pdo = $this->pdo;

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->agentStatusResponse(405, false, 'Method not allowed.');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || !\validCsrfToken($data['csrf_token'] ?? null, 'agent_management')) {
            $this->agentStatusResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }
        $agentId = filter_var($data['agent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = (string) ($data['status'] ?? '');
        $allowed = ['pending', 'approved', 'active', 'suspended', 'rejected'];
        if ($agentId === false || !in_array($status, $allowed, true)) {
            $this->agentStatusResponse(422, false, 'Invalid agent status update.');
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, user_id, status FROM agents WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => (int) $agentId]);
            $agent = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$agent) {
                $pdo->rollBack();
                $this->agentStatusResponse(404, false, 'Agent not found.');
            }
            if ($agent['status'] === $status) {
                $pdo->rollBack();
                $this->agentStatusResponse(200, true, 'Agent status is unchanged.');
            }
            $pdo->prepare('UPDATE agents SET status = :status WHERE id = :id')->execute([':status' => $status, ':id' => (int) $agentId]);
            if (!empty($agent['user_id'])) {
                $isActive = in_array($status, ['approved', 'active'], true) ? 1 : 0;
                $pdo->prepare("UPDATE admin_users SET is_active = :active WHERE id = :user_id AND role = 'agent_coordinator'")
                    ->execute([':active' => $isActive, ':user_id' => (int) $agent['user_id']]);
            }
            $pdo->commit();
            \logActivity((int) $_SESSION['user_id'], 'AGENT_STATUS_CHANGED', "Agent #{$agentId} changed from {$agent['status']} to {$status}", 'agent_form');
            $this->agentStatusResponse(200, true, 'Agent status updated.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Agent status update failed: ' . $e->getMessage());
            $this->agentStatusResponse(500, false, 'The agent status could not be updated.');
        }
    }

    private function agentStatusResponse(int $status, bool $success, string $message): never
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Ported near-verbatim from config/delete_agent.php (soft delete via status = 'rejected'). */
    public function delete(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        require_once \NURU_MATERIAL . '/includes/functions.php';
        Auth::boot();
        header('Content-Type: application/json');

        if (!\sessionHasAuthoritativeRole(['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!\validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'agent_management')) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload and try again.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $agentId = isset($data['agent_id']) ? (int) $data['agent_id'] : 0;

        if ($agentId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid agent ID']);
            exit;
        }

        $sql = "UPDATE agents a LEFT JOIN admin_users u ON u.id = a.user_id
                SET a.status = 'rejected', u.is_active = CASE WHEN u.role = 'agent_coordinator' THEN 0 ELSE u.is_active END
                WHERE a.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $agentId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Agent not found or already removed']);
            exit;
        }

        \logActivity((int) $_SESSION['user_id'], 'AGENT_REMOVED', "Agent #{$agentId} removed", 'agent_form', 'warning');
        echo json_encode(['success' => true]);
    }

    /** Ported near-verbatim from config/approve_agent_application.php. */
    public function approveApplication(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        require_once \NURU_MATERIAL . '/includes/functions.php';
        require_once \NURU_MATERIAL . '/config/account_provisioning.php';
        Auth::boot();
        header('Content-Type: application/json');

        $pdo = $this->pdo;

        if (!\sessionHasAuthoritativeRole(['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!\validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'agent_management')) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload and try again.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $applicationId = isset($data['application_id']) ? (int) $data['application_id'] : 0;
        $decision = $data['decision'] ?? '';

        if (!$applicationId || !in_array($decision, ['approve', 'reject'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM agent_applications WHERE id = ? AND status IN ('submitted','under_review') FOR UPDATE");
            $stmt->execute([$applicationId]);
            $app = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$app) {
                throw new \Exception('Application not found or already reviewed');
            }

            if ($decision === 'reject') {
                $pdo->prepare(
                    "UPDATE agent_applications SET status = 'rejected', reviewed_by = ?, review_date = NOW(), rejection_reason = ? WHERE id = ?"
                )->execute([$_SESSION['user_id'], $data['reason'] ?? null, $applicationId]);

                $pdo->commit();
                \logActivity((int) $_SESSION['user_id'], 'AGENT_APPLICATION_REJECTED', "Application {$app['application_number']} rejected", 'agent_form');
                echo json_encode(['success' => true, 'status' => 'rejected']);
                exit;
            }

            /* ---------- APPROVE: promote into a live agents row ---------- */
            $check = $pdo->prepare('SELECT id, role FROM admin_users WHERE email = ?');
            $check->execute([$app['email']]);
            $existingUser = $check->fetch(\PDO::FETCH_ASSOC);
            $userId = $existingUser['id'] ?? null;

            if ($existingUser && $existingUser['role'] !== 'agent_coordinator') {
                throw new \Exception('That email belongs to a different portal role');
            }

            if (!$userId) {
                $usernameBase = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '.', $app['first_name'] . '.' . $app['surname'])), '.');
                $usernameBase = $usernameBase !== '' ? $usernameBase : 'agent';
                $username = $usernameBase;
                $suffix = 1;
                $usernameCheck = $pdo->prepare('SELECT 1 FROM admin_users WHERE username = ?');
                while (true) {
                    $usernameCheck->execute([$username]);
                    if (!$usernameCheck->fetchColumn()) {
                        break;
                    }
                    $username = $usernameBase . '.' . (++$suffix);
                }
                $insertUser = $pdo->prepare(
                    "INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, created_at)
                     VALUES (?, ?, ?, ?, 'agent_coordinator', 1, NOW())"
                );
                $temporaryPassword = \generateTemporaryPassword();
                $fullName = trim($app['first_name'] . ' ' . $app['surname']);
                $insertUser->execute([
                    $username, $app['email'], password_hash($temporaryPassword, PASSWORD_DEFAULT),
                    $fullName,
                ]);
                \queueTemporaryCredentialEmail($app['email'], $fullName, $temporaryPassword);
                $userId = $pdo->lastInsertId();
            }

            do {
                $agentId = 'AGT' . date('Y') . random_int(1000, 9999);
                $exists = $pdo->prepare('SELECT id FROM agents WHERE agent_id = ?');
                $exists->execute([$agentId]);
            } while ($exists->fetchColumn());

            $insertAgent = $pdo->prepare('
                INSERT INTO agents (
                    agent_id, application_id, user_id,
                    surname, first_name, middle_name, maiden_name,
                    date_of_birth, id_type, id_number, nationality, gender,
                    email, mobile_number, company_name, job_title,
                    gross_income, net_pay, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'approved\')
            ');
            $insertAgent->execute([
                $agentId, $app['application_number'], $userId,
                $app['surname'], $app['first_name'], $app['middle_name'], $app['maiden_name'],
                $app['date_of_birth'], $app['id_type'], $app['id_number'], $app['nationality'], $app['gender'],
                $app['email'], $app['mobile_number'], $app['company_name'], $app['job_title'],
                $app['gross_income'], $app['net_pay'],
            ]);
            $newAgentDbId = (int) $pdo->lastInsertId();

            // Carry over uploaded documents from the application to the new agent record
            $docMap = [
                'id_document' => 'id_document_url',
                'proof_residence' => 'proof_residence_url',
                'agency_ffc' => 'agency_ffc_url',
                'agent_neab' => 'agent_neab_url',
                'agent_ffc' => 'agent_ffc_url',
            ];
            $docs = $pdo->prepare('SELECT document_type, file_path FROM agent_documents WHERE application_id = ?');
            $docs->execute([$applicationId]);
            foreach ($docs->fetchAll(\PDO::FETCH_ASSOC) as $doc) {
                $column = $docMap[$doc['document_type']] ?? null;
                if ($column) {
                    $pdo->prepare("UPDATE agents SET {$column} = ? WHERE id = ?")->execute([$doc['file_path'], $newAgentDbId]);
                }
            }

            $pdo->prepare(
                "UPDATE agent_applications SET status = 'approved', reviewed_by = ?, review_date = NOW(), promoted_agent_id = ? WHERE id = ?"
            )->execute([$_SESSION['user_id'], $newAgentDbId, $applicationId]);

            $pdo->commit();

            \logActivity((int) $_SESSION['user_id'], 'AGENT_APPLICATION_APPROVED', "Application {$app['application_number']} promoted to agent {$agentId}", 'agent_form');

            session_write_close();
            \finishResponseAndDeliverQueuedCredentialEmails((string) json_encode([
                'success' => true,
                'status' => 'approved',
                'agent_id' => $agentId,
            ]));
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Agent application review failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to process the application. Please try again later.']);
        }
    }
}

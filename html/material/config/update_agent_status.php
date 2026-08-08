<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'manager']);

function agentStatusResponse(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    agentStatusResponse(405, false, 'Method not allowed.');
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !validCsrfToken($data['csrf_token'] ?? null, 'agent_management')) {
    agentStatusResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}
$agentId = filter_var($data['agent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = (string)($data['status'] ?? '');
$allowed = ['pending', 'approved', 'active', 'suspended', 'rejected'];
if ($agentId === false || !in_array($status, $allowed, true)) {
    agentStatusResponse(422, false, 'Invalid agent status update.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, user_id, status FROM agents WHERE id = :id FOR UPDATE');
    $stmt->execute([':id' => (int)$agentId]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agent) {
        $pdo->rollBack();
        agentStatusResponse(404, false, 'Agent not found.');
    }
    if ($agent['status'] === $status) {
        $pdo->rollBack();
        agentStatusResponse(200, true, 'Agent status is unchanged.');
    }
    $pdo->prepare('UPDATE agents SET status = :status WHERE id = :id')->execute([':status' => $status, ':id' => (int)$agentId]);
    if (!empty($agent['user_id'])) {
        $isActive = in_array($status, ['approved', 'active'], true) ? 1 : 0;
        $pdo->prepare("UPDATE admin_users SET is_active = :active WHERE id = :user_id AND role = 'agent_coordinator'")
            ->execute([':active' => $isActive, ':user_id' => (int)$agent['user_id']]);
    }
    $pdo->commit();
    logActivity((int)$_SESSION['user_id'], 'AGENT_STATUS_CHANGED', "Agent #{$agentId} changed from {$agent['status']} to {$status}", 'agent_form');
    agentStatusResponse(200, true, 'Agent status updated.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Agent status update failed: ' . $e->getMessage());
    agentStatusResponse(500, false, 'The agent status could not be updated.');
}

<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager']);

function reassignResponse(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reassignResponse(405, false, 'Method not allowed.');
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    reassignResponse(400, false, 'Invalid request body.');
}
if (!validCsrfToken($data['csrf_token'] ?? null, 'reassign_task')) {
    reassignResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}
$taskId = filter_var($data['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$agentId = filter_var($data['agent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($taskId === false || $agentId === false) {
    reassignResponse(422, false, 'Select a valid task and agent.');
}

try {
    $pdo->beginTransaction();
    $agentStmt = $pdo->prepare("SELECT id FROM agents WHERE id = :id AND status IN ('approved', 'active')");
    $agentStmt->execute([':id' => (int)$agentId]);
    if (!$agentStmt->fetchColumn()) {
        $pdo->rollBack();
        reassignResponse(422, false, 'The selected agent is not active.');
    }
    $taskStmt = $pdo->prepare("SELECT id, agent_id, status, progress_percentage FROM agent_task_allocations WHERE id = :id FOR UPDATE");
    $taskStmt->execute([':id' => (int)$taskId]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        $pdo->rollBack();
        reassignResponse(404, false, 'Task not found.');
    }
    if (!in_array($task['status'], ['assigned', 'on_hold', 'cancelled'], true)) {
        $pdo->rollBack();
        reassignResponse(409, false, 'Only pending, held, or cancelled tasks can be reassigned.');
    }
    if ((int)$task['agent_id'] === (int)$agentId && $task['status'] === 'assigned') {
        $pdo->rollBack();
        reassignResponse(409, false, 'This task is already assigned to that agent.');
    }

    $update = $pdo->prepare("UPDATE agent_task_allocations SET agent_id = :agent_id, status = 'assigned',
        assigned_by = :assigned_by, assigned_at = NOW(), started_at = NULL, completed_at = NULL,
        progress_percentage = 0, updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
    $update->execute([':agent_id' => (int)$agentId, ':assigned_by' => (int)$_SESSION['user_id'], ':updated_by' => (int)$_SESSION['user_id'], ':id' => (int)$taskId]);

    $history = $pdo->prepare("INSERT INTO agent_task_history
        (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent)
        VALUES (:allocation_id, :agent_id, 'reassigned', :old_values, :new_values, :performed_by, :ip_address, :user_agent)");
    $history->execute([
        ':allocation_id' => (int)$taskId, ':agent_id' => (int)$agentId,
        ':old_values' => json_encode(['agent_id' => (int)$task['agent_id'], 'status' => $task['status'], 'progress_percentage' => (int)$task['progress_percentage']], JSON_THROW_ON_ERROR),
        ':new_values' => json_encode(['agent_id' => (int)$agentId, 'status' => 'assigned', 'progress_percentage' => 0], JSON_THROW_ON_ERROR),
        ':performed_by' => (int)$_SESSION['user_id'], ':ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
    ]);
    $pdo->commit();
    reassignResponse(200, true, 'Task reassigned.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Task reassignment failed: ' . $e->getMessage());
    reassignResponse(500, false, 'The task could not be reassigned. Please try again.');
}

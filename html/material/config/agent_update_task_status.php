<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager', 'agent_coordinator']);

function taskStatusResponse(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    taskStatusResponse(405, false, 'Method not allowed.');
}

$allocationId = filter_var($_POST['allocation_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$action = (string)($_POST['action'] ?? '');
$notes = trim((string)($_POST['notes'] ?? ''));

if (!validCsrfToken($_POST['csrf_token'] ?? null, 'agent_task_status')) {
    taskStatusResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}
if ($allocationId === false || !in_array($action, ['in_progress', 'hold', 'cancel'], true)) {
    taskStatusResponse(422, false, 'Invalid task action.');
}
if (mb_strlen($notes) > 1000) {
    taskStatusResponse(422, false, 'Notes may not exceed 1,000 characters.');
}

$statusMap = [
    'in_progress' => 'in_progress',
    'hold' => 'on_hold',
    'cancel' => 'cancelled',
];
$newStatus = $statusMap[$action];
$allowedTransitions = [
    'assigned' => ['in_progress', 'on_hold', 'cancelled'],
    'on_hold' => ['in_progress', 'cancelled'],
    'in_progress' => ['on_hold', 'cancelled'],
];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id, status, agent_id FROM agent_task_allocations WHERE id = ? FOR UPDATE');
    $stmt->execute([(int)$allocationId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        $pdo->rollBack();
        taskStatusResponse(404, false, 'Task not found.');
    }

    if (currentRole() === 'agent_coordinator') {
        $ownAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
        if ($ownAgentId === null || (int)$task['agent_id'] !== $ownAgentId) {
            $pdo->rollBack();
            taskStatusResponse(403, false, 'You cannot update a task assigned to another agent.');
        }
    }

    $currentStatus = (string)$task['status'];
    if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
        $pdo->rollBack();
        taskStatusResponse(409, false, 'This task can no longer make that status change.');
    }

    $startedAtSql = $newStatus === 'in_progress' ? 'started_at = COALESCE(started_at, NOW()),' : '';
    $update = $pdo->prepare("UPDATE agent_task_allocations
        SET status = :status,
            {$startedAtSql}
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id AND status = :old_status");
    $update->execute([
        ':status' => $newStatus,
        ':updated_by' => (int)$_SESSION['user_id'],
        ':id' => (int)$allocationId,
        ':old_status' => $currentStatus,
    ]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Task status changed concurrently.');
    }

    $audit = $pdo->prepare("INSERT INTO agent_task_history
        (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, notes)
        VALUES (:allocation_id, :agent_id, 'status_changed', :old_values, :new_values, :performed_by, :ip_address, :user_agent, :notes)");
    $audit->execute([
        ':allocation_id' => (int)$allocationId,
        ':agent_id' => (int)$task['agent_id'],
        ':old_values' => json_encode(['status' => $currentStatus], JSON_THROW_ON_ERROR),
        ':new_values' => json_encode(['status' => $newStatus], JSON_THROW_ON_ERROR),
        ':performed_by' => (int)$_SESSION['user_id'],
        ':ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
        ':notes' => $notes !== '' ? $notes : null,
    ]);

    $pdo->commit();
    taskStatusResponse(200, true, $newStatus === 'in_progress' ? 'Task moved to In Progress.' : ($newStatus === 'on_hold' ? 'Task placed on hold.' : 'Task cancelled.'));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Task status update failed: ' . $e->getMessage());
    taskStatusResponse(500, false, 'The task could not be updated. Please try again.');
}

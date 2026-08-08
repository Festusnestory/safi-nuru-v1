<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['agent_coordinator']);

function progressResponse(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    progressResponse(405, false, 'Method not allowed.');
}
if (!validCsrfToken($_POST['csrf_token'] ?? null, 'task_progress')) {
    progressResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}

$taskId = filter_var($_POST['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$progress = filter_var($_POST['progress'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
$notes = trim((string)($_POST['notes'] ?? ''));
if ($taskId === false || $progress === false || mb_strlen($notes) > 2000) {
    progressResponse(422, false, 'Enter progress from 0 to 100 and notes no longer than 2,000 characters.');
}

$agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
if ($agentId === null) {
    progressResponse(403, false, 'Your account is not linked to an active agent profile.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, agent_id, allocation_type, status, progress_percentage, agent_notes FROM agent_task_allocations WHERE id = :id FOR UPDATE');
    $stmt->execute([':id' => (int)$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        $pdo->rollBack();
        progressResponse(404, false, 'Task not found.');
    }
    if ((int)$task['agent_id'] !== $agentId) {
        $pdo->rollBack();
        progressResponse(403, false, 'You cannot update another agent’s task.');
    }
    if ($task['status'] !== 'in_progress') {
        $pdo->rollBack();
        progressResponse(409, false, 'Only in-progress tasks can be updated.');
    }
    if ($task['allocation_type'] === 'seller') {
        $pdo->rollBack();
        progressResponse(409, false, 'Seller task progress is controlled by its property checklist.');
    }

    $isComplete = (int)$progress === 100;
    $update = $pdo->prepare("UPDATE agent_task_allocations
        SET progress_percentage = :progress,
            agent_notes = :notes,
            status = CASE WHEN :complete_status = 1 THEN 'completed' ELSE status END,
            completed_at = CASE WHEN :complete_date = 1 THEN NOW() ELSE NULL END,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id AND status = 'in_progress'");
    $update->execute([
        ':progress' => (int)$progress,
        ':notes' => $notes !== '' ? $notes : $task['agent_notes'],
        ':complete_status' => $isComplete ? 1 : 0,
        ':complete_date' => $isComplete ? 1 : 0,
        ':updated_by' => (int)$_SESSION['user_id'],
        ':id' => (int)$taskId,
    ]);

    $history = $pdo->prepare("INSERT INTO agent_task_history
        (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, notes)
        VALUES (:allocation_id, :agent_id, :action_type, :old_values, :new_values, :performed_by, :ip_address, :user_agent, :notes)");
    $history->execute([
        ':allocation_id' => (int)$taskId,
        ':agent_id' => $agentId,
        ':action_type' => $isComplete ? 'task_completed' : 'progress_updated',
        ':old_values' => json_encode(['progress_percentage' => (int)$task['progress_percentage'], 'status' => 'in_progress'], JSON_THROW_ON_ERROR),
        ':new_values' => json_encode(['progress_percentage' => (int)$progress, 'status' => $isComplete ? 'completed' : 'in_progress'], JSON_THROW_ON_ERROR),
        ':performed_by' => (int)$_SESSION['user_id'],
        ':ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
        ':notes' => $notes !== '' ? $notes : null,
    ]);
    $pdo->commit();
    progressResponse(200, true, $isComplete ? 'Task completed.' : 'Task progress updated.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Task progress update failed: ' . $e->getMessage());
    progressResponse(500, false, 'The task could not be updated. Please try again.');
}

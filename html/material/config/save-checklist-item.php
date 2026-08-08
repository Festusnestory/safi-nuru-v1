<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager', 'agent_coordinator']);

function checklistResponse(int $status, bool $success, string $message, array $data = []): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    checklistResponse(405, false, 'Method not allowed.');
}
if (currentRole() !== 'agent_coordinator') {
    checklistResponse(403, false, 'Only the assigned agent can update this checklist.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
if (!validCsrfToken($input['csrf_token'] ?? null, 'property_checklist')) {
    checklistResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}

$itemId = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$propertyId = filter_var($input['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$completedRaw = $input['is_completed'] ?? null;
if ($itemId === false || $propertyId === false || $taskId === false || !in_array((string)$completedRaw, ['0', '1'], true)) {
    checklistResponse(422, false, 'Invalid checklist update.');
}
$isCompleted = (int)$completedRaw;
$agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
if ($agentId === null) {
    checklistResponse(403, false, 'Your account is not linked to an active agent profile.');
}

try {
    $pdo->beginTransaction();

    $taskStmt = $pdo->prepare("SELECT id, status, progress_percentage
        FROM agent_task_allocations
        WHERE agent_id = :agent_id
          AND id = :task_id
          AND allocation_type = 'seller'
          AND entity_id = :property_id
          AND status = 'in_progress'
        LIMIT 1 FOR UPDATE");
    $taskStmt->execute([':agent_id' => $agentId, ':task_id' => (int)$taskId, ':property_id' => (int)$propertyId]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        $pdo->rollBack();
        checklistResponse(403, false, 'This property does not have an in-progress task assigned to you.');
    }

    $itemStmt = $pdo->prepare('SELECT ci.item_name, cs.stage_name
        FROM checklist_items ci
        JOIN checklist_stages cs ON cs.id = ci.stage_id
        WHERE ci.id = :item_id AND cs.is_active = 1');
    $itemStmt->execute([':item_id' => (int)$itemId]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        $pdo->rollBack();
        checklistResponse(422, false, 'Checklist item not found.');
    }

    $save = $pdo->prepare("INSERT INTO property_checklist_status
        (property_id, allocation_id, checklist_item_id, is_completed, completed_at, completed_by)
        VALUES (:property_id, :allocation_id, :item_id, :is_completed, :completed_at, :completed_by)
        ON DUPLICATE KEY UPDATE
            is_completed = VALUES(is_completed),
            completed_at = VALUES(completed_at),
            completed_by = VALUES(completed_by)");
    $save->execute([
        ':property_id' => (int)$propertyId,
        ':allocation_id' => (int)$task['id'],
        ':item_id' => (int)$itemId,
        ':is_completed' => $isCompleted,
        ':completed_at' => $isCompleted === 1 ? date('Y-m-d H:i:s') : null,
        ':completed_by' => (int)$_SESSION['user_id'],
    ]);

    $progressStmt = $pdo->prepare("SELECT COUNT(*) AS total,
            SUM(CASE WHEN COALESCE(pcs.is_completed, 0) = 1 THEN 1 ELSE 0 END) AS completed
        FROM checklist_items ci
        JOIN checklist_stages cs ON cs.id = ci.stage_id AND cs.is_active = 1
        LEFT JOIN property_checklist_status pcs
          ON pcs.checklist_item_id = ci.id AND pcs.allocation_id = :allocation_id");
    $progressStmt->execute([':allocation_id' => (int)$task['id']]);
    $counts = $progressStmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)($counts['total'] ?? 0);
    $completed = (int)($counts['completed'] ?? 0);
    $percentage = $total > 0 ? (int)round(($completed / $total) * 100) : 0;
    $taskCompleted = $total > 0 && $completed === $total;

    $updateTask = $pdo->prepare("UPDATE agent_task_allocations
        SET progress_percentage = :progress,
            status = CASE WHEN :is_complete_status = 1 THEN 'completed' ELSE status END,
            completed_at = CASE WHEN :is_complete_date = 1 THEN NOW() ELSE NULL END,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id AND status = 'in_progress'");
    $updateTask->execute([
        ':progress' => $percentage,
        ':is_complete_status' => $taskCompleted ? 1 : 0,
        ':is_complete_date' => $taskCompleted ? 1 : 0,
        ':updated_by' => (int)$_SESSION['user_id'],
        ':id' => (int)$task['id'],
    ]);

    $oldProgress = (int)$task['progress_percentage'];
    if ($oldProgress !== $percentage || $taskCompleted) {
        $history = $pdo->prepare("INSERT INTO agent_task_history
            (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, notes)
            VALUES (:allocation_id, :agent_id, :action_type, :old_values, :new_values, :performed_by, :ip_address, :user_agent, :notes)");
        $history->execute([
            ':allocation_id' => (int)$task['id'],
            ':agent_id' => $agentId,
            ':action_type' => $taskCompleted ? 'task_completed' : 'progress_updated',
            ':old_values' => json_encode(['progress_percentage' => $oldProgress, 'status' => 'in_progress'], JSON_THROW_ON_ERROR),
            ':new_values' => json_encode(['progress_percentage' => $percentage, 'status' => $taskCompleted ? 'completed' : 'in_progress'], JSON_THROW_ON_ERROR),
            ':performed_by' => (int)$_SESSION['user_id'],
            ':ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
            ':notes' => $item['stage_name'] . ': ' . $item['item_name'],
        ]);
    }

    $pdo->commit();
    checklistResponse(200, true, $taskCompleted ? 'Checklist complete. The task has been completed.' : 'Checklist item updated.', [
        'item_id' => (int)$itemId,
        'is_completed' => $isCompleted,
        'completed_items' => $completed,
        'total_items' => $total,
        'progress_percentage' => $percentage,
        'task_completed' => $taskCompleted,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Checklist update failed: ' . $e->getMessage());
    checklistResponse(500, false, 'The checklist could not be updated. Please try again.');
}

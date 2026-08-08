<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager']);

function assignmentResponse(int $status, bool $success, string $message, array $extra = []): never
{
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    assignmentResponse(405, false, 'Method not allowed.');
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    assignmentResponse(400, false, 'Invalid request body.');
}
if (!validCsrfToken($data['csrf_token'] ?? null, 'assign_agent_task')) {
    assignmentResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}

$propertyId = filter_var($data['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$agentId = filter_var($data['agent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$dueDate = (string)($data['due_date'] ?? '');
$due = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
$today = new DateTimeImmutable('today');
if ($propertyId === false || $agentId === false || !$due || $due->format('Y-m-d') !== $dueDate || $due < $today || $due > $today->modify('+365 days')) {
    assignmentResponse(422, false, 'Select a valid agent and a due date within the next year.');
}

try {
    $pdo->beginTransaction();

    $agentStmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', surname) AS agent_name FROM agents WHERE id = :id AND status IN ('approved', 'active') LIMIT 1");
    $agentStmt->execute([':id' => (int)$agentId]);
    $agent = $agentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$agent) {
        $pdo->rollBack();
        assignmentResponse(422, false, 'The selected agent is not active.');
    }

    $propertyStmt = $pdo->prepare("SELECT sp.id, sp.property_status, sa.application_number
        FROM seller_properties sp
        JOIN seller_applications sa ON sa.id = sp.application_id
        WHERE sp.id = :id FOR UPDATE");
    $propertyStmt->execute([':id' => (int)$propertyId]);
    $property = $propertyStmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) {
        $pdo->rollBack();
        assignmentResponse(404, false, 'Property not found.');
    }
    if ($property['property_status'] !== 'under_offer') {
        $pdo->rollBack();
        assignmentResponse(409, false, 'Only a property under offer can receive a transaction task.');
    }

    $taskStmt = $pdo->prepare("SELECT * FROM agent_task_allocations
        WHERE allocation_type = 'seller' AND entity_id = :property_id AND status IN ('assigned', 'in_progress', 'on_hold', 'cancelled')
        ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $taskStmt->execute([':property_id' => (int)$propertyId]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        $update = $pdo->prepare("UPDATE agent_task_allocations SET agent_id = :agent_id, status = 'assigned', due_date = :due_date,
            assigned_at = NOW(), assigned_by = :assigned_by, started_at = NULL, completed_at = NULL, progress_percentage = 0,
            updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
        $update->execute([
            ':agent_id' => (int)$agentId, ':due_date' => $dueDate, ':assigned_by' => (int)$_SESSION['user_id'],
            ':updated_by' => (int)$_SESSION['user_id'], ':id' => (int)$task['id'],
        ]);
        $allocationId = (int)$task['id'];
        $oldValues = ['agent_id' => (int)$task['agent_id'], 'status' => $task['status'], 'due_date' => $task['due_date']];
        $actionType = 'reassigned';
    } else {
        $insert = $pdo->prepare("INSERT INTO agent_task_allocations
            (agent_id, allocation_type, entity_id, entity_reference, task_type, priority, status, assigned_by, assigned_at,
             due_date, task_description, progress_percentage, created_by, updated_by)
            VALUES (:agent_id, 'seller', :entity_id, :entity_reference, 'transaction_management', 'high', 'assigned',
             :assigned_by, NOW(), :due_date, 'Manage the matched property transaction through transfer and completion.', 0, :created_by, :updated_by)");
        $insert->execute([
            ':agent_id' => (int)$agentId, ':entity_id' => (int)$propertyId,
            ':entity_reference' => $property['application_number'], ':assigned_by' => (int)$_SESSION['user_id'],
            ':due_date' => $dueDate, ':created_by' => (int)$_SESSION['user_id'], ':updated_by' => (int)$_SESSION['user_id'],
        ]);
        $allocationId = (int)$pdo->lastInsertId();
        $oldValues = null;
        $actionType = 'assigned';
    }

    $history = $pdo->prepare("INSERT INTO agent_task_history
        (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent)
        VALUES (:allocation_id, :agent_id, :action_type, :old_values, :new_values, :performed_by, :ip_address, :user_agent)");
    $history->execute([
        ':allocation_id' => $allocationId, ':agent_id' => (int)$agentId, ':action_type' => $actionType,
        ':old_values' => $oldValues ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
        ':new_values' => json_encode(['agent_id' => (int)$agentId, 'status' => 'assigned', 'due_date' => $dueDate], JSON_THROW_ON_ERROR),
        ':performed_by' => (int)$_SESSION['user_id'], ':ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
    ]);
    $pdo->commit();
    assignmentResponse(200, true, 'Task assigned.', ['task_id' => $allocationId, 'agent_label' => $agent['agent_name']]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Task assignment failed: ' . $e->getMessage());
    assignmentResponse(500, false, 'The task could not be assigned. Please try again.');
}

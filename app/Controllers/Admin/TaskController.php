<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Controller;
use App\Core\Router;
use App\Models\Task;

/**
 * Agent task allocation lists/detail (tasks-pending.php, tasks-in-progress.php,
 * tasks-completed.php, tasks-cancelled.php, agent-tasks-pending.php,
 * agent_tasks-in-progress.php, task_view.php) plus their JSON mutation
 * endpoints (config/assign_agent_task.php, config/reassign_task.php,
 * config/update_task_progress.php, config/agent_update_task_status.php), and
 * the property checklist/stage system (checklist.php, items.php,
 * add_items.php, stages.php, add_stages.php, config/save_stage.php,
 * config/get_stages.php, config/save-checklist-item.php,
 * config/save_checklist_item.php). Ported near-verbatim - same queries, same
 * validation, same JSON envelopes as each source file.
 */
final class TaskController extends Controller
{
    // ------------------------------------------------------------------
    // Task lists
    // ------------------------------------------------------------------

    /** tasks-pending.php */
    public function pending(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        $reassignTaskCsrf = Auth::csrfToken('reassign_task');

        $model = new Task($this->pdo);
        $pendingTasks = $model->byStatus('assigned', $this->coordinatorAgentFilter());

        $this->render('admin.tasks.pending', [
            'pendingTasks' => $pendingTasks,
            'reassignTaskCsrf' => $reassignTaskCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** tasks-in-progress.php */
    public function inProgress(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $model = new Task($this->pdo);
        $tasks = $model->inProgress($this->coordinatorAgentFilter());

        $this->render('admin.tasks.in-progress', [
            'tasks' => $tasks,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** tasks-completed.php */
    public function completed(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $model = new Task($this->pdo);
        $tasks = $model->completed($this->coordinatorAgentFilter());

        $this->render('admin.tasks.completed', [
            'tasks' => $tasks,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** tasks-cancelled.php */
    public function cancelled(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        $reassignTaskCsrf = Auth::csrfToken('reassign_task');

        $model = new Task($this->pdo);
        $pendingTasks = $model->byStatus('cancelled', $this->coordinatorAgentFilter());

        $this->render('admin.tasks.cancelled', [
            'pendingTasks' => $pendingTasks,
            'reassignTaskCsrf' => $reassignTaskCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** agent-tasks-pending.php */
    public function agentPending(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);
        $taskStatusCsrf = Auth::csrfToken('agent_task_status');

        $model = new Task($this->pdo);
        $pendingTasks = $model->agentPending($myAgentId ?? 0);

        $this->render('admin.tasks.agent-pending', [
            'pendingTasks' => $pendingTasks,
            'taskStatusCsrf' => $taskStatusCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** agent_tasks-in-progress.php */
    public function agentInProgress(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);

        $model = new Task($this->pdo);
        $pendingTasks = $model->agentInProgress($myAgentId ?? 0);

        $this->render('admin.tasks.agent-in-progress', [
            'pendingTasks' => $pendingTasks,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** task_view.php */
    public function view(): void
    {
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $taskId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$taskId) {
            http_response_code(404);
            exit('Task not found.');
        }

        $isCoordinator = Auth::currentRole() === 'agent_coordinator';
        $agentFilter = null;
        if ($isCoordinator) {
            $agentFilter = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
        }

        $model = new Task($this->pdo);
        $task = $model->find((int) $taskId, $agentFilter);
        if (!$task) {
            http_response_code(404);
            exit('Task not found.');
        }

        $history = $model->history((int) $taskId);

        $progressCsrf = Auth::csrfToken('task_progress');
        $entityName = $task['allocation_type'] === 'seller'
            ? trim(($task['property_detail_type'] ?: 'Property') . ' — ' . ($task['property_region'] ?: 'Unknown region') . ', ' . ($task['property_town'] ?: 'Unknown town'))
            : ($task['buyer_name'] ? 'Buyer — ' . $task['buyer_name'] : 'Buyer record unavailable');
        $homeRoute = $isCoordinator ? 'dashboard_3.php' : (Auth::currentRole() === 'manager' ? 'dashboard_2.php' : 'admin.php');

        $this->render('admin.tasks.view', [
            'task' => $task,
            'history' => $history,
            'taskId' => (int) $taskId,
            'progressCsrf' => $progressCsrf,
            'isCoordinator' => $isCoordinator,
            'entityName' => $entityName,
            'homeRoute' => $homeRoute,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** Resolves the agent_coordinator filter shared by pending()/inProgress()/completed()/cancelled(): null (no filter) for admin/manager, else the caller's own agent id (0 fallback). */
    private function coordinatorAgentFilter(): ?int
    {
        if (Auth::currentRole() !== 'agent_coordinator') {
            return null;
        }
        return \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
    }

    // ------------------------------------------------------------------
    // Task mutation endpoints (JSON)
    // ------------------------------------------------------------------

    private function jsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');
        header('X-Content-Type-Options: nosniff');
    }

    /** config/assign_agent_task.php */
    public function assign(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['admin', 'manager']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->assignmentResponse(405, false, 'Method not allowed.');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->assignmentResponse(400, false, 'Invalid request body.');
        }
        if (!Auth::validCsrfToken($data['csrf_token'] ?? null, 'assign_agent_task')) {
            $this->assignmentResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }

        $propertyId = filter_var($data['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $agentId = filter_var($data['agent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $dueDate = (string) ($data['due_date'] ?? '');
        $due = \DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        $today = new \DateTimeImmutable('today');
        if ($propertyId === false || $agentId === false || !$due || $due->format('Y-m-d') !== $dueDate || $due < $today || $due > $today->modify('+365 days')) {
            $this->assignmentResponse(422, false, 'Select a valid agent and a due date within the next year.');
        }

        $pdo = $this->pdo;
        try {
            $pdo->beginTransaction();

            $agentStmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', surname) AS agent_name FROM agents WHERE id = :id AND status IN ('approved', 'active') LIMIT 1");
            $agentStmt->execute([':id' => (int) $agentId]);
            $agent = $agentStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$agent) {
                $pdo->rollBack();
                $this->assignmentResponse(422, false, 'The selected agent is not active.');
            }

            $propertyStmt = $pdo->prepare("SELECT sp.id, sp.property_status, sa.application_number
                FROM seller_properties sp
                JOIN seller_applications sa ON sa.id = sp.application_id
                WHERE sp.id = :id FOR UPDATE");
            $propertyStmt->execute([':id' => (int) $propertyId]);
            $property = $propertyStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$property) {
                $pdo->rollBack();
                $this->assignmentResponse(404, false, 'Property not found.');
            }
            if ($property['property_status'] !== 'under_offer') {
                $pdo->rollBack();
                $this->assignmentResponse(409, false, 'Only a property under offer can receive a transaction task.');
            }

            $taskStmt = $pdo->prepare("SELECT * FROM agent_task_allocations
                WHERE allocation_type = 'seller' AND entity_id = :property_id AND status IN ('assigned', 'in_progress', 'on_hold', 'cancelled')
                ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $taskStmt->execute([':property_id' => (int) $propertyId]);
            $task = $taskStmt->fetch(\PDO::FETCH_ASSOC);

            if ($task) {
                $update = $pdo->prepare("UPDATE agent_task_allocations SET agent_id = :agent_id, status = 'assigned', due_date = :due_date,
                    assigned_at = NOW(), assigned_by = :assigned_by, started_at = NULL, completed_at = NULL, progress_percentage = 0,
                    updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
                $update->execute([
                    ':agent_id' => (int) $agentId, ':due_date' => $dueDate, ':assigned_by' => (int) $_SESSION['user_id'],
                    ':updated_by' => (int) $_SESSION['user_id'], ':id' => (int) $task['id'],
                ]);
                $allocationId = (int) $task['id'];
                $oldValues = ['agent_id' => (int) $task['agent_id'], 'status' => $task['status'], 'due_date' => $task['due_date']];
                $actionType = 'reassigned';
            } else {
                $insert = $pdo->prepare("INSERT INTO agent_task_allocations
                    (agent_id, allocation_type, entity_id, entity_reference, task_type, priority, status, assigned_by, assigned_at,
                     due_date, task_description, progress_percentage, created_by, updated_by)
                    VALUES (:agent_id, 'seller', :entity_id, :entity_reference, 'transaction_management', 'high', 'assigned',
                     :assigned_by, NOW(), :due_date, 'Manage the matched property transaction through transfer and completion.', 0, :created_by, :updated_by)");
                $insert->execute([
                    ':agent_id' => (int) $agentId, ':entity_id' => (int) $propertyId,
                    ':entity_reference' => $property['application_number'], ':assigned_by' => (int) $_SESSION['user_id'],
                    ':due_date' => $dueDate, ':created_by' => (int) $_SESSION['user_id'], ':updated_by' => (int) $_SESSION['user_id'],
                ]);
                $allocationId = (int) $pdo->lastInsertId();
                $oldValues = null;
                $actionType = 'assigned';
            }

            $history = $pdo->prepare("INSERT INTO agent_task_history
                (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent)
                VALUES (:allocation_id, :agent_id, :action_type, :old_values, :new_values, :performed_by, :ip_address, :user_agent)");
            $history->execute([
                ':allocation_id' => $allocationId, ':agent_id' => (int) $agentId, ':action_type' => $actionType,
                ':old_values' => $oldValues ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
                ':new_values' => json_encode(['agent_id' => (int) $agentId, 'status' => 'assigned', 'due_date' => $dueDate], JSON_THROW_ON_ERROR),
                ':performed_by' => (int) $_SESSION['user_id'], ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
            ]);
            $pdo->commit();
            $this->assignmentResponse(200, true, 'Task assigned.', ['task_id' => $allocationId, 'agent_label' => $agent['agent_name']]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Task assignment failed: ' . $e->getMessage());
            $this->assignmentResponse(500, false, 'The task could not be assigned. Please try again.');
        }
    }

    private function assignmentResponse(int $status, bool $success, string $message, array $extra = []): never
    {
        http_response_code($status);
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** config/reassign_task.php */
    public function reassign(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['admin', 'manager']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->reassignResponse(405, false, 'Method not allowed.');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->reassignResponse(400, false, 'Invalid request body.');
        }
        if (!Auth::validCsrfToken($data['csrf_token'] ?? null, 'reassign_task')) {
            $this->reassignResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }
        $taskId = filter_var($data['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $agentId = filter_var($data['agent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($taskId === false || $agentId === false) {
            $this->reassignResponse(422, false, 'Select a valid task and agent.');
        }

        $pdo = $this->pdo;
        try {
            $pdo->beginTransaction();
            $agentStmt = $pdo->prepare("SELECT id FROM agents WHERE id = :id AND status IN ('approved', 'active')");
            $agentStmt->execute([':id' => (int) $agentId]);
            if (!$agentStmt->fetchColumn()) {
                $pdo->rollBack();
                $this->reassignResponse(422, false, 'The selected agent is not active.');
            }
            $taskStmt = $pdo->prepare("SELECT id, agent_id, status, progress_percentage FROM agent_task_allocations WHERE id = :id FOR UPDATE");
            $taskStmt->execute([':id' => (int) $taskId]);
            $task = $taskStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$task) {
                $pdo->rollBack();
                $this->reassignResponse(404, false, 'Task not found.');
            }
            if (!in_array($task['status'], ['assigned', 'on_hold', 'cancelled'], true)) {
                $pdo->rollBack();
                $this->reassignResponse(409, false, 'Only pending, held, or cancelled tasks can be reassigned.');
            }
            if ((int) $task['agent_id'] === (int) $agentId && $task['status'] === 'assigned') {
                $pdo->rollBack();
                $this->reassignResponse(409, false, 'This task is already assigned to that agent.');
            }

            $update = $pdo->prepare("UPDATE agent_task_allocations SET agent_id = :agent_id, status = 'assigned',
                assigned_by = :assigned_by, assigned_at = NOW(), started_at = NULL, completed_at = NULL,
                progress_percentage = 0, updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
            $update->execute([':agent_id' => (int) $agentId, ':assigned_by' => (int) $_SESSION['user_id'], ':updated_by' => (int) $_SESSION['user_id'], ':id' => (int) $taskId]);

            $history = $pdo->prepare("INSERT INTO agent_task_history
                (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent)
                VALUES (:allocation_id, :agent_id, 'reassigned', :old_values, :new_values, :performed_by, :ip_address, :user_agent)");
            $history->execute([
                ':allocation_id' => (int) $taskId, ':agent_id' => (int) $agentId,
                ':old_values' => json_encode(['agent_id' => (int) $task['agent_id'], 'status' => $task['status'], 'progress_percentage' => (int) $task['progress_percentage']], JSON_THROW_ON_ERROR),
                ':new_values' => json_encode(['agent_id' => (int) $agentId, 'status' => 'assigned', 'progress_percentage' => 0], JSON_THROW_ON_ERROR),
                ':performed_by' => (int) $_SESSION['user_id'], ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
            ]);
            $pdo->commit();
            $this->reassignResponse(200, true, 'Task reassigned.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Task reassignment failed: ' . $e->getMessage());
            $this->reassignResponse(500, false, 'The task could not be reassigned. Please try again.');
        }
    }

    private function reassignResponse(int $status, bool $success, string $message): never
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** config/update_task_progress.php */
    public function updateProgress(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['agent_coordinator']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->progressResponse(405, false, 'Method not allowed.');
        }
        if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'task_progress')) {
            $this->progressResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }

        $taskId = filter_var($_POST['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $progress = filter_var($_POST['progress'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        $notes = trim((string) ($_POST['notes'] ?? ''));
        if ($taskId === false || $progress === false || mb_strlen($notes) > 2000) {
            $this->progressResponse(422, false, 'Enter progress from 0 to 100 and notes no longer than 2,000 characters.');
        }

        $agentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);
        if ($agentId === null) {
            $this->progressResponse(403, false, 'Your account is not linked to an active agent profile.');
        }

        $pdo = $this->pdo;
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, agent_id, allocation_type, status, progress_percentage, agent_notes FROM agent_task_allocations WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => (int) $taskId]);
            $task = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$task) {
                $pdo->rollBack();
                $this->progressResponse(404, false, 'Task not found.');
            }
            if ((int) $task['agent_id'] !== $agentId) {
                $pdo->rollBack();
                $this->progressResponse(403, false, 'You cannot update another agent’s task.');
            }
            if ($task['status'] !== 'in_progress') {
                $pdo->rollBack();
                $this->progressResponse(409, false, 'Only in-progress tasks can be updated.');
            }
            if ($task['allocation_type'] === 'seller') {
                $pdo->rollBack();
                $this->progressResponse(409, false, 'Seller task progress is controlled by its property checklist.');
            }

            $isComplete = (int) $progress === 100;
            $update = $pdo->prepare("UPDATE agent_task_allocations
                SET progress_percentage = :progress,
                    agent_notes = :notes,
                    status = CASE WHEN :complete_status = 1 THEN 'completed' ELSE status END,
                    completed_at = CASE WHEN :complete_date = 1 THEN NOW() ELSE NULL END,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id AND status = 'in_progress'");
            $update->execute([
                ':progress' => (int) $progress,
                ':notes' => $notes !== '' ? $notes : $task['agent_notes'],
                ':complete_status' => $isComplete ? 1 : 0,
                ':complete_date' => $isComplete ? 1 : 0,
                ':updated_by' => (int) $_SESSION['user_id'],
                ':id' => (int) $taskId,
            ]);

            $history = $pdo->prepare("INSERT INTO agent_task_history
                (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, notes)
                VALUES (:allocation_id, :agent_id, :action_type, :old_values, :new_values, :performed_by, :ip_address, :user_agent, :notes)");
            $history->execute([
                ':allocation_id' => (int) $taskId,
                ':agent_id' => $agentId,
                ':action_type' => $isComplete ? 'task_completed' : 'progress_updated',
                ':old_values' => json_encode(['progress_percentage' => (int) $task['progress_percentage'], 'status' => 'in_progress'], JSON_THROW_ON_ERROR),
                ':new_values' => json_encode(['progress_percentage' => (int) $progress, 'status' => $isComplete ? 'completed' : 'in_progress'], JSON_THROW_ON_ERROR),
                ':performed_by' => (int) $_SESSION['user_id'],
                ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
                ':notes' => $notes !== '' ? $notes : null,
            ]);
            $pdo->commit();
            $this->progressResponse(200, true, $isComplete ? 'Task completed.' : 'Task progress updated.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Task progress update failed: ' . $e->getMessage());
            $this->progressResponse(500, false, 'The task could not be updated. Please try again.');
        }
    }

    private function progressResponse(int $status, bool $success, string $message): never
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** config/agent_update_task_status.php */
    public function updateStatus(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->taskStatusResponse(405, false, 'Method not allowed.');
        }

        $allocationId = filter_var($_POST['allocation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $action = (string) ($_POST['action'] ?? '');
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'agent_task_status')) {
            $this->taskStatusResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }
        if ($allocationId === false || !in_array($action, ['in_progress', 'hold', 'cancel'], true)) {
            $this->taskStatusResponse(422, false, 'Invalid task action.');
        }
        if (mb_strlen($notes) > 1000) {
            $this->taskStatusResponse(422, false, 'Notes may not exceed 1,000 characters.');
        }

        $statusMap = ['in_progress' => 'in_progress', 'hold' => 'on_hold', 'cancel' => 'cancelled'];
        $newStatus = $statusMap[$action];
        $allowedTransitions = [
            'assigned' => ['in_progress', 'on_hold', 'cancelled'],
            'on_hold' => ['in_progress', 'cancelled'],
            'in_progress' => ['on_hold', 'cancelled'],
        ];

        $pdo = $this->pdo;
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id, status, agent_id FROM agent_task_allocations WHERE id = ? FOR UPDATE');
            $stmt->execute([(int) $allocationId]);
            $task = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$task) {
                $pdo->rollBack();
                $this->taskStatusResponse(404, false, 'Task not found.');
            }

            if (Auth::currentRole() === 'agent_coordinator') {
                $ownAgentId = \resolveAgentId($pdo, (int) $_SESSION['user_id']);
                if ($ownAgentId === null || (int) $task['agent_id'] !== $ownAgentId) {
                    $pdo->rollBack();
                    $this->taskStatusResponse(403, false, 'You cannot update a task assigned to another agent.');
                }
            }

            $currentStatus = (string) $task['status'];
            if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
                $pdo->rollBack();
                $this->taskStatusResponse(409, false, 'This task can no longer make that status change.');
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
                ':updated_by' => (int) $_SESSION['user_id'],
                ':id' => (int) $allocationId,
                ':old_status' => $currentStatus,
            ]);
            if ($update->rowCount() !== 1) {
                throw new \RuntimeException('Task status changed concurrently.');
            }

            $audit = $pdo->prepare("INSERT INTO agent_task_history
                (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, notes)
                VALUES (:allocation_id, :agent_id, 'status_changed', :old_values, :new_values, :performed_by, :ip_address, :user_agent, :notes)");
            $audit->execute([
                ':allocation_id' => (int) $allocationId,
                ':agent_id' => (int) $task['agent_id'],
                ':old_values' => json_encode(['status' => $currentStatus], JSON_THROW_ON_ERROR),
                ':new_values' => json_encode(['status' => $newStatus], JSON_THROW_ON_ERROR),
                ':performed_by' => (int) $_SESSION['user_id'],
                ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
                ':notes' => $notes !== '' ? $notes : null,
            ]);

            $pdo->commit();
            $this->taskStatusResponse(200, true, $newStatus === 'in_progress' ? 'Task moved to In Progress.' : ($newStatus === 'on_hold' ? 'Task placed on hold.' : 'Task cancelled.'));
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Task status update failed: ' . $e->getMessage());
            $this->taskStatusResponse(500, false, 'The task could not be updated. Please try again.');
        }
    }

    private function taskStatusResponse(int $status, bool $success, string $message): never
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ------------------------------------------------------------------
    // Property checklist wizard
    // ------------------------------------------------------------------

    /** checklist.php */
    public function checklist(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $propertyId = (int) ($_GET['property_id'] ?? 0);
        $requestedTaskId = (int) ($_GET['task_id'] ?? 0);
        $checklistCsrf = Auth::csrfToken('property_checklist');
        $canEditChecklist = Auth::currentRole() === 'agent_coordinator';

        if ($propertyId < 1) {
            http_response_code(404);
            exit('Property not found.');
        }

        $model = new Task($this->pdo);

        $myAgentId = null;
        if ($canEditChecklist) {
            $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
            if (!$model->agentOwnsProperty($myAgentId, $propertyId)) {
                $this->redirect(\portalPath('agent-tasks-pending.php'));
            }
        }

        $checklistTask = $model->findChecklistTask($propertyId, $requestedTaskId, $canEditChecklist ? $myAgentId : null);
        if (!$checklistTask) {
            http_response_code(404);
            exit('Checklist task not found.');
        }
        $checklistAllocationId = (int) $checklistTask['id'];

        $rows = $model->checklistRows($checklistAllocationId);

        $stages = [];
        $stageOrder = [];
        foreach ($rows as $row) {
            $sid = $row['stage_id'];
            if (!isset($stages[$sid])) {
                $stages[$sid] = [
                    'stage_name' => $row['stage_name'],
                    'stage_order' => $row['stage_order'],
                    'description' => $row['description'],
                    'items' => [],
                    'completed' => true,
                ];
                $stageOrder[] = $sid;
            }
            if (!$row['is_completed']) {
                $stages[$sid]['completed'] = false;
            }
            $stages[$sid]['items'][] = $row;
        }

        usort($stageOrder, function ($a, $b) use ($stages) {
            return $stages[$a]['stage_order'] - $stages[$b]['stage_order'];
        });

        $this->render('admin.tasks.checklist', [
            'stages' => $stages,
            'stageOrder' => $stageOrder,
            'propertyId' => $propertyId,
            'checklistAllocationId' => $checklistAllocationId,
            'checklistCsrf' => $checklistCsrf,
            'canEditChecklist' => $canEditChecklist,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** config/save-checklist-item.php - toggles one checklist item's completion for the wizard (checklist.php). */
    public function saveChecklistItemProgress(): void
    {
        $this->jsonHeaders();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->checklistResponse(405, false, 'Method not allowed.');
        }
        if (Auth::currentRole() !== 'agent_coordinator') {
            $this->checklistResponse(403, false, 'Only the assigned agent can update this checklist.');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        if (!Auth::validCsrfToken($input['csrf_token'] ?? null, 'property_checklist')) {
            $this->checklistResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }

        $itemId = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $propertyId = filter_var($input['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $completedRaw = $input['is_completed'] ?? null;
        if ($itemId === false || $propertyId === false || $taskId === false || !in_array((string) $completedRaw, ['0', '1'], true)) {
            $this->checklistResponse(422, false, 'Invalid checklist update.');
        }
        $isCompleted = (int) $completedRaw;
        $agentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);
        if ($agentId === null) {
            $this->checklistResponse(403, false, 'Your account is not linked to an active agent profile.');
        }

        $pdo = $this->pdo;
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
            $taskStmt->execute([':agent_id' => $agentId, ':task_id' => (int) $taskId, ':property_id' => (int) $propertyId]);
            $task = $taskStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$task) {
                $pdo->rollBack();
                $this->checklistResponse(403, false, 'This property does not have an in-progress task assigned to you.');
            }

            $itemStmt = $pdo->prepare('SELECT ci.item_name, cs.stage_name
                FROM checklist_items ci
                JOIN checklist_stages cs ON cs.id = ci.stage_id
                WHERE ci.id = :item_id AND cs.is_active = 1');
            $itemStmt->execute([':item_id' => (int) $itemId]);
            $item = $itemStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$item) {
                $pdo->rollBack();
                $this->checklistResponse(422, false, 'Checklist item not found.');
            }

            $save = $pdo->prepare("INSERT INTO property_checklist_status
                (property_id, allocation_id, checklist_item_id, is_completed, completed_at, completed_by)
                VALUES (:property_id, :allocation_id, :item_id, :is_completed, :completed_at, :completed_by)
                ON DUPLICATE KEY UPDATE
                    is_completed = VALUES(is_completed),
                    completed_at = VALUES(completed_at),
                    completed_by = VALUES(completed_by)");
            $save->execute([
                ':property_id' => (int) $propertyId,
                ':allocation_id' => (int) $task['id'],
                ':item_id' => (int) $itemId,
                ':is_completed' => $isCompleted,
                ':completed_at' => $isCompleted === 1 ? date('Y-m-d H:i:s') : null,
                ':completed_by' => (int) $_SESSION['user_id'],
            ]);

            $progressStmt = $pdo->prepare("SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(pcs.is_completed, 0) = 1 THEN 1 ELSE 0 END) AS completed
                FROM checklist_items ci
                JOIN checklist_stages cs ON cs.id = ci.stage_id AND cs.is_active = 1
                LEFT JOIN property_checklist_status pcs
                  ON pcs.checklist_item_id = ci.id AND pcs.allocation_id = :allocation_id");
            $progressStmt->execute([':allocation_id' => (int) $task['id']]);
            $counts = $progressStmt->fetch(\PDO::FETCH_ASSOC);
            $total = (int) ($counts['total'] ?? 0);
            $completed = (int) ($counts['completed'] ?? 0);
            $percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
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
                ':updated_by' => (int) $_SESSION['user_id'],
                ':id' => (int) $task['id'],
            ]);

            $oldProgress = (int) $task['progress_percentage'];
            if ($oldProgress !== $percentage || $taskCompleted) {
                $history = $pdo->prepare("INSERT INTO agent_task_history
                    (allocation_id, agent_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, notes)
                    VALUES (:allocation_id, :agent_id, :action_type, :old_values, :new_values, :performed_by, :ip_address, :user_agent, :notes)");
                $history->execute([
                    ':allocation_id' => (int) $task['id'],
                    ':agent_id' => $agentId,
                    ':action_type' => $taskCompleted ? 'task_completed' : 'progress_updated',
                    ':old_values' => json_encode(['progress_percentage' => $oldProgress, 'status' => 'in_progress'], JSON_THROW_ON_ERROR),
                    ':new_values' => json_encode(['progress_percentage' => $percentage, 'status' => $taskCompleted ? 'completed' : 'in_progress'], JSON_THROW_ON_ERROR),
                    ':performed_by' => (int) $_SESSION['user_id'],
                    ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                    ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
                    ':notes' => $item['stage_name'] . ': ' . $item['item_name'],
                ]);
            }

            $pdo->commit();
            $this->checklistResponse(200, true, $taskCompleted ? 'Checklist complete. The task has been completed.' : 'Checklist item updated.', [
                'item_id' => (int) $itemId,
                'is_completed' => $isCompleted,
                'completed_items' => $completed,
                'total_items' => $total,
                'progress_percentage' => $percentage,
                'task_completed' => $taskCompleted,
            ]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Checklist update failed: ' . $e->getMessage());
            $this->checklistResponse(500, false, 'The checklist could not be updated. Please try again.');
        }
    }

    private function checklistResponse(int $status, bool $success, string $message, array $data = []): never
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ------------------------------------------------------------------
    // Checklist stage/item configuration (admin, manager)
    // ------------------------------------------------------------------

    /** items.php */
    public function items(): void
    {
        $this->requireRole(['admin', 'manager']);
        $checklistConfigCsrf = Auth::csrfToken('checklist_configuration');

        $this->render('admin.tasks.items', [
            'checklistConfigCsrf' => $checklistConfigCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** add_items.php - legacy fragment only ever rendered when included from items.php with NURU_ITEM_FORM_INCLUDE defined; standalone access always 404s. Preserved for URL parity. */
    public function addItemsFragment(): void
    {
        http_response_code(404);
        exit('Not found');
    }

    /** stages.php */
    public function stages(): void
    {
        $this->requireRole(['admin', 'manager']);
        $checklistConfigCsrf = Auth::csrfToken('checklist_configuration');

        $this->render('admin.tasks.stages', [
            'checklistConfigCsrf' => $checklistConfigCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** add_stages.php - same standalone-404 fragment behavior as add_items.php. */
    public function addStagesFragment(): void
    {
        http_response_code(404);
        exit('Not found');
    }

    /** config/save_stage.php */
    public function saveStage(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['admin', 'manager']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->stageResponse(405, 'error', 'Method not allowed.');
        }
        if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'checklist_configuration')) {
            $this->stageResponse(403, 'error', 'Your session token has expired. Refresh the page and try again.');
        }

        $stageName = trim((string) ($_POST['stage_name'] ?? ''));
        $stageOrder = filter_var($_POST['stage_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        $description = trim((string) ($_POST['description'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($stageName === '' || mb_strlen($stageName) > 150 || $stageOrder === false || mb_strlen($description) > 2000) {
            $this->stageResponse(422, 'error', 'Enter a valid stage name, order, and description.');
        }

        try {
            $duplicate = $this->pdo->prepare('SELECT id FROM checklist_stages WHERE stage_order = ? OR LOWER(stage_name) = LOWER(?) LIMIT 1');
            $duplicate->execute([(int) $stageOrder, $stageName]);
            if ($duplicate->fetchColumn()) {
                $this->stageResponse(409, 'error', 'A stage with that name or order already exists.');
            }
            $statement = $this->pdo->prepare('INSERT INTO checklist_stages (stage_name, stage_order, description, is_active) VALUES (?, ?, ?, ?)');
            $statement->execute([$stageName, (int) $stageOrder, $description !== '' ? $description : null, $isActive]);
            $this->stageResponse(201, 'success', 'Stage saved successfully.');
        } catch (\Throwable $error) {
            error_log('Checklist stage creation failed: ' . $error->getMessage());
            $this->stageResponse(500, 'error', 'The stage could not be saved.');
        }
    }

    private function stageResponse(int $code, string $status, string $message): never
    {
        http_response_code($code);
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    /** config/get_stages.php */
    public function getStages(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['admin', 'manager']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            echo json_encode(['error' => 'Method not allowed.']);
            exit;
        }
        $model = new Task($this->pdo);
        echo json_encode($model->activeStages());
    }

    /** config/save_checklist_item.php - admin/manager defines a new checklist item under a stage (used by items.php). */
    public function createChecklistItem(): void
    {
        $this->jsonHeaders();
        $this->requireRole(['admin', 'manager']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->itemResponse(405, 'error', 'Method not allowed.');
        }
        if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'checklist_configuration')) {
            $this->itemResponse(403, 'error', 'Your session token has expired. Refresh the page and try again.');
        }

        $stageId = filter_var($_POST['stage_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        $itemOrder = filter_var($_POST['item_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        $isRequired = isset($_POST['is_required']) ? 1 : 0;
        if ($stageId === false || $itemName === '' || mb_strlen($itemName) > 255 || $itemOrder === false) {
            $this->itemResponse(422, 'error', 'Enter a valid stage, checklist item, and order.');
        }

        try {
            $stage = $this->pdo->prepare('SELECT id FROM checklist_stages WHERE id = ? AND is_active = 1');
            $stage->execute([(int) $stageId]);
            if (!$stage->fetchColumn()) {
                $this->itemResponse(422, 'error', 'Select an active checklist stage.');
            }
            $duplicate = $this->pdo->prepare('SELECT id FROM checklist_items WHERE stage_id = ? AND (item_order = ? OR LOWER(item_name) = LOWER(?)) LIMIT 1');
            $duplicate->execute([(int) $stageId, (int) $itemOrder, $itemName]);
            if ($duplicate->fetchColumn()) {
                $this->itemResponse(409, 'error', 'That stage already has an item with the same name or order.');
            }
            $statement = $this->pdo->prepare('INSERT INTO checklist_items (stage_id, item_name, item_order, is_required) VALUES (?, ?, ?, ?)');
            $statement->execute([(int) $stageId, $itemName, (int) $itemOrder, $isRequired]);
            $this->itemResponse(201, 'success', 'Checklist item added successfully.');
        } catch (\Throwable $error) {
            error_log('Checklist item creation failed: ' . $error->getMessage());
            $this->itemResponse(500, 'error', 'The checklist item could not be saved.');
        }
    }

    private function itemResponse(int $code, string $status, string $message): never
    {
        http_response_code($code);
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }
}

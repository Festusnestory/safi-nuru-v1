<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager', 'agent_coordinator']);
require __DIR__ . '/config/pdo.php';

$taskId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$taskId) {
    http_response_code(404);
    exit('Task not found.');
}

$sql = "SELECT ata.*,
        CONCAT(a.first_name, ' ', a.surname) AS agent_name,
        a.company_name,
        sp.property_region, sp.property_town, sp.property_detail_type,
        b.full_name AS buyer_name, b.region AS buyer_region, b.town AS buyer_town,
        COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_number
    FROM agent_task_allocations ata
    JOIN agents a ON a.id = ata.agent_id
    LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
    LEFT JOIN seller_applications sa ON sp.application_id = sa.id
    LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
    WHERE ata.id = :id";
$params = [':id' => (int)$taskId];
if (currentRole() === 'agent_coordinator') {
    $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $sql .= ' AND ata.agent_id = :agent_id';
    $params[':agent_id'] = $agentId ?? 0;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) {
    http_response_code(404);
    exit('Task not found.');
}

$historyStmt = $pdo->prepare("SELECT h.*, COALESCE(u.full_name, u.email, 'System user') AS performer
    FROM agent_task_history h
    LEFT JOIN admin_users u ON u.id = h.performed_by
    WHERE h.allocation_id = :id ORDER BY h.created_at DESC, h.id DESC");
$historyStmt->execute([':id' => (int)$taskId]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Task ' . $task['entity_reference'];
$progressCsrf = csrfToken('task_progress');
$isCoordinator = currentRole() === 'agent_coordinator';
$entityName = $task['allocation_type'] === 'seller'
    ? trim(($task['property_detail_type'] ?: 'Property') . ' — ' . ($task['property_region'] ?: 'Unknown region') . ', ' . ($task['property_town'] ?: 'Unknown town'))
    : ($task['buyer_name'] ? 'Buyer — ' . $task['buyer_name'] : 'Buyer record unavailable');
$homeRoute = $isCoordinator ? 'dashboard_3.php' : (currentRole() === 'manager' ? 'dashboard_2.php' : 'admin.php');
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head><?php include('_page_head.php'); ?></head>
<body>
<div id="main-wrapper">
<?php include('top-bar.php'); ?>
<?php include($isCoordinator ? 'agent_nemu.php' : 'left-sidebar.php'); ?>
<div class="page-wrapper">
  <div class="row page-titles"><div class="col-12"><ol class="breadcrumb mb-0 p-0 bg-transparent"><li class="breadcrumb-item"><a href="<?= $homeRoute ?>">Home</a></li><li class="breadcrumb-item active">Task details</li></ol></div></div>
  <div class="container-fluid"><div class="row">
    <div class="col-lg-8"><div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div><h3 class="mb-1"><?= htmlspecialchars($task['entity_reference']) ?></h3><div class="text-muted"><?= htmlspecialchars($entityName) ?></div></div>
        <span class="badge bg-<?= $task['status'] === 'completed' ? 'success' : ($task['status'] === 'cancelled' ? 'danger' : 'primary') ?> fs-6"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))) ?></span>
      </div>
      <div class="row g-3">
        <div class="col-md-6"><strong>Application</strong><div><?= htmlspecialchars($task['application_number']) ?></div></div>
        <div class="col-md-6"><strong>Assigned agent</strong><div><?= htmlspecialchars($task['agent_name']) ?><?= $task['company_name'] ? ' — ' . htmlspecialchars($task['company_name']) : '' ?></div></div>
        <div class="col-md-4"><strong>Task type</strong><div><?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['task_type']))) ?></div></div>
        <div class="col-md-4"><strong>Priority</strong><div><?= htmlspecialchars(ucfirst($task['priority'])) ?></div></div>
        <div class="col-md-4"><strong>Due date</strong><div><?= $task['due_date'] ? date('d M Y', strtotime($task['due_date'])) : 'Not set' ?></div></div>
        <div class="col-12"><strong>Description</strong><div><?= nl2br(htmlspecialchars($task['task_description'] ?: 'No description supplied.')) ?></div></div>
      </div>
      <hr>
      <div class="fw-bold mb-2">Progress</div>
      <div class="progress mb-3" aria-label="Task progress"><div class="progress-bar" role="progressbar" style="width:<?= (int)$task['progress_percentage'] ?>%" aria-valuenow="<?= (int)$task['progress_percentage'] ?>" aria-valuemin="0" aria-valuemax="100"><?= (int)$task['progress_percentage'] ?>%</div></div>
      <?php if ($task['agent_notes']): ?><div class="alert alert-light"><strong>Agent notes</strong><br><?= nl2br(htmlspecialchars($task['agent_notes'])) ?></div><?php endif; ?>

      <?php if ($isCoordinator && $task['allocation_type'] === 'seller' && in_array($task['status'], ['in_progress', 'completed'], true)): ?>
        <a class="btn btn-primary" href="checklist.php?property_id=<?= (int)$task['entity_id'] ?>&amp;task_id=<?= (int)$taskId ?>"><?= $task['status'] === 'completed' ? 'View property checklist' : 'Continue property checklist' ?></a>
      <?php elseif ($isCoordinator && $task['allocation_type'] !== 'seller' && $task['status'] === 'in_progress'): ?>
        <form id="progressForm" class="mt-3" novalidate>
          <input type="hidden" name="task_id" value="<?= (int)$taskId ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($progressCsrf) ?>">
          <div class="mb-3"><label class="form-label" for="progress">Progress percentage</label><input class="form-control" id="progress" name="progress" type="number" min="0" max="100" value="<?= (int)$task['progress_percentage'] ?>" required></div>
          <div class="mb-3"><label class="form-label" for="notes">Work notes</label><textarea class="form-control" id="notes" name="notes" maxlength="2000" rows="4"><?= htmlspecialchars($task['agent_notes'] ?? '') ?></textarea></div>
          <button class="btn btn-primary" type="submit">Save progress</button><div id="progressMessage" class="mt-3" role="status" aria-live="polite"></div>
        </form>
      <?php endif; ?>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h4 class="card-title">Activity history</h4>
      <?php if (!$history): ?><p class="text-muted">No history recorded.</p><?php endif; ?>
      <?php foreach ($history as $event): ?><div class="border-bottom py-3"><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $event['action_type']))) ?></strong><div class="small text-muted"><?= htmlspecialchars($event['performer']) ?> · <?= date('d M Y H:i', strtotime($event['created_at'])) ?></div><?php if ($event['notes']): ?><div class="mt-1"><?= htmlspecialchars($event['notes']) ?></div><?php endif; ?></div><?php endforeach; ?>
    </div></div></div>
  </div></div>
  <footer class="footer">All Rights Reserved by Nuru.</footer>
</div></div>
<?php include('_page_scripts.php'); ?>
<?php if ($isCoordinator && $task['allocation_type'] !== 'seller' && $task['status'] === 'in_progress'): ?>
<script>
document.getElementById('progressForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const button = this.querySelector('button[type="submit"]'); const message = document.getElementById('progressMessage');
  button.disabled = true; message.className = 'mt-3 text-muted'; message.textContent = 'Saving…';
  try {
    const response = await fetch('./config/update_task_progress.php', {method: 'POST', body: new FormData(this)});
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Update failed.');
    message.className = 'mt-3 text-success'; message.textContent = data.message; setTimeout(() => location.reload(), 500);
  } catch (error) { message.className = 'mt-3 text-danger'; message.textContent = error.message || 'Update failed.'; button.disabled = false; }
});
</script>
<?php endif; ?>
</body></html>

<?php
/** @var array $task */
/** @var array $history */
/** @var int $taskId */
/** @var string $progressCsrf */
/** @var bool $isCoordinator */
/** @var string $entityName */
/** @var string $homeRoute */
/** @var string $baseUrl */
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head><?php require NURU_MATERIAL . '/_page_head.php'; ?></head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php require NURU_MATERIAL . '/' . ($isCoordinator ? 'agent_nemu.php' : 'left-sidebar.php'); ?>
<div class="page-wrapper">
  <div class="row page-titles"><div class="col-12"><ol class="breadcrumb mb-0 p-0 bg-transparent"><li class="breadcrumb-item"><a href="<?= htmlspecialchars(\portalPath($homeRoute)) ?>">Home</a></li><li class="breadcrumb-item active">Task details</li></ol></div></div>
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
        <a class="btn btn-primary" href="<?= htmlspecialchars(\portalPath('checklist.php?property_id=' . (int)$task['entity_id'] . '&task_id=' . $taskId)) ?>"><?= $task['status'] === 'completed' ? 'View property checklist' : 'Continue property checklist' ?></a>
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
<?php if ($isCoordinator && $task['allocation_type'] !== 'seller' && $task['status'] === 'in_progress'): ?>
<script>
document.getElementById('progressForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const button = this.querySelector('button[type="submit"]'); const message = document.getElementById('progressMessage');
  button.disabled = true; message.className = 'mt-3 text-muted'; message.textContent = 'Saving…';
  try {
    const response = await fetch(<?= json_encode(\portalPath('config/update_task_progress.php')) ?>, {method: 'POST', body: new FormData(this)});
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Update failed.');
    message.className = 'mt-3 text-success'; message.textContent = data.message; setTimeout(() => location.reload(), 500);
  } catch (error) { message.className = 'mt-3 text-danger'; message.textContent = error.message || 'Update failed.'; button.disabled = false; }
});
</script>
<?php endif; ?>
</body></html>

<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'agent_coordinator') {
    header("location: authentication-login.php");
    exit;
}
include("./config/pdo.php");
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['agent_coordinator']);
$pageTitle = 'Agent Dashboard';

$myAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);

$openTasks = 0;
$overdueTasks = 0;
$completedTasks = 0;
$myBuyers = 0;
$mySellers = 0;
$upcomingTasks = [];

if ($myAgentId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agent_task_allocations WHERE agent_id = ? AND status IN ('assigned','in_progress')");
    $stmt->execute([$myAgentId]);
    $openTasks = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agent_task_allocations WHERE agent_id = ? AND status IN ('assigned','in_progress') AND due_date IS NOT NULL AND due_date < CURDATE()");
    $stmt->execute([$myAgentId]);
    $overdueTasks = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agent_task_allocations WHERE agent_id = ? AND status = 'completed' AND completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute([$myAgentId]);
    $completedTasks = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT ata.id AS task_id, ata.due_date, ata.status, ata.entity_reference,
               COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_id,
               COALESCE(sp.property_region, b.region) AS property_region,
               COALESCE(sp.property_town, b.town) AS property_town
        FROM agent_task_allocations ata
        LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
        LEFT JOIN seller_applications sa ON sa.id = sp.application_id
        LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
        WHERE ata.agent_id = ? AND ata.status IN ('assigned','in_progress')
        ORDER BY ata.due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$myAgentId]);
    $upcomingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM buyers b WHERE b.loaded_by = :user_id OR EXISTS (
    SELECT 1 FROM agent_task_allocations ata WHERE ata.agent_id = :agent_id AND ata.allocation_type = 'buyer' AND ata.entity_id = b.id
)");
$stmt->execute([':user_id' => (int)$_SESSION['user_id'], ':agent_id' => $myAgentId ?? 0]);
$myBuyers = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT sa.id) FROM seller_applications sa
    LEFT JOIN seller_personal_details spd ON spd.application_id = sa.id
    WHERE spd.loaded_by = :user_id OR EXISTS (
        SELECT 1 FROM seller_properties sp
        JOIN agent_task_allocations ata ON ata.allocation_type = 'seller' AND ata.entity_id = sp.id
        WHERE sp.application_id = sa.id AND ata.agent_id = :agent_id
    )");
$stmt->execute([':user_id' => (int)$_SESSION['user_id'], ':agent_id' => $myAgentId ?? 0]);
$mySellers = (int)$stmt->fetchColumn();

require_once __DIR__ . '/config/property_lifecycle.php';
expireOverdueProperties($pdo);

$myExpiring = [];
if ($myAgentId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT sp.id, sa.application_number AS application_id, sp.property_region, sp.property_town,
               sp.property_status, sp.status_deadline
        FROM agent_task_allocations ata
        JOIN seller_properties sp ON sp.id = ata.entity_id
        JOIN seller_applications sa ON sa.id = sp.application_id
        WHERE ata.agent_id = ? AND sp.property_status IN ('under_offer','sold')
          AND sp.status_deadline IS NOT NULL
        ORDER BY sp.status_deadline ASC
        LIMIT 5
    ");
    $stmt->execute([$myAgentId]);
    $myExpiring = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php include('_page_head.php'); ?>
</head>
<body>
<div id="main-wrapper">
<?php include("top-bar.php"); ?>
<?php include("agent_nemu.php"); ?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Agent Dashboard</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php renderAccessDeniedNotice(); ?>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $openTasks ?></h2>
                    <p class="text-muted mb-0">Open Tasks</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0 <?= $overdueTasks > 0 ? 'text-danger' : '' ?>"><?= $overdueTasks ?></h2>
                    <p class="text-muted mb-0">Overdue</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $completedTasks ?></h2>
                    <p class="text-muted mb-0">Completed This Month</p>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $myBuyers ?></h2>
                    <p class="text-muted mb-0">My Buyers</p>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $mySellers ?></h2>
                    <p class="text-muted mb-0">My Sellers</p>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Upcoming Due Dates</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Application No</th><th>Region</th><th>Town</th><th>Due Date</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($upcomingTasks as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['application_id']) ?></td>
                                            <td><?= htmlspecialchars($t['property_region']) ?></td>
                                            <td><?= htmlspecialchars($t['property_town']) ?></td>
                                            <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—' ?></td>
                                            <td><?= htmlspecialchars($t['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($upcomingTasks)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No open tasks.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">My Deals — Countdown</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Application No</th><th>Region</th><th>Town</th><th>Status</th><th>Countdown</th></tr></thead>
                                <tbody>
                                    <?php foreach ($myExpiring as $d): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d['application_id']) ?></td>
                                            <td><?= htmlspecialchars($d['property_region']) ?></td>
                                            <td><?= htmlspecialchars($d['property_town']) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $d['property_status'])) ?></td>
                                            <td><?= renderCountdownBadge($d['property_status'], $d['status_deadline']) ?: '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($myExpiring)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No deals currently under offer or awaiting transfer.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">All Rights Reserved by Nuru.</footer>
</div>
</div>
<?php include('_page_scripts.php'); ?>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['manager', 'admin'], true)) {
    header("location: authentication-login.php");
    exit;
}
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$pageTitle = 'Manager Dashboard';

$totalAgents = (int)$pdo->query("SELECT COUNT(*) FROM agents WHERE status IN ('approved','active')")->fetchColumn();
$activeTasks = (int)$pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status IN ('assigned','in_progress')")->fetchColumn();
$completedTasks = (int)$pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status = 'completed'")->fetchColumn();
$pendingBuyers = (int)$pdo->query("SELECT COUNT(*) FROM buyers WHERE status = 'pending'")->fetchColumn();
$pendingAgentApps = (int)$pdo->query("SELECT COUNT(*) FROM agent_applications WHERE status IN ('submitted','under_review')")->fetchColumn();
$pendingSellerApps = (int)$pdo->query("SELECT COUNT(*) FROM seller_applications WHERE status IN ('submitted','under_review')")->fetchColumn();

$agentWorkload = $pdo->query("
    SELECT
        CONCAT(a.first_name, ' ', a.surname) AS full_name,
        COUNT(t.id) AS total_tasks,
        SUM(CASE WHEN t.status IN ('assigned','in_progress') THEN 1 ELSE 0 END) AS active_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
    FROM agents a
    LEFT JOIN agent_task_allocations t ON t.agent_id = a.id
    WHERE a.status IN ('approved','active')
    GROUP BY a.id, a.first_name, a.surname
    ORDER BY active_tasks DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php include('_page_head.php'); ?>
</head>
<body>
<div id="main-wrapper">
<?php include("top-bar.php"); ?>
<?php include("left-sidebar.php"); ?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Manager Dashboard</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php renderAccessDeniedNotice(); ?>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $totalAgents ?></h2>
                    <p class="text-muted mb-0">Active Agents</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $activeTasks ?></h2>
                    <p class="text-muted mb-0">Open Tasks</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $completedTasks ?></h2>
                    <p class="text-muted mb-0">Completed Tasks</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $pendingBuyers ?></h2>
                    <p class="text-muted mb-0">Pending Buyers</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $pendingAgentApps ?></h2>
                    <p class="text-muted mb-0">Pending Agent Apps</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $pendingSellerApps ?></h2>
                    <p class="text-muted mb-0">Pending Seller Apps</p>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Agent Workload</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Agent</th><th>Total Tasks</th><th>Active</th><th>Completed</th></tr></thead>
                                <tbody>
                                    <?php foreach ($agentWorkload as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= $a['total_tasks'] ?></td>
                                            <td><?= $a['active_tasks'] ?></td>
                                            <td><?= $a['completed_tasks'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($agentWorkload)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No agents found.</td></tr>
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

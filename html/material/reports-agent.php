<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$pageTitle = 'Agent Performance Report';

$agents = $pdo->query("
    SELECT
        a.id, a.agent_id, CONCAT(a.first_name, ' ', a.surname) AS full_name, a.status,
        COUNT(t.id) AS total_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
    FROM agents a
    LEFT JOIN agent_task_allocations t ON t.agent_id = a.id
    GROUP BY a.id, a.agent_id, a.first_name, a.surname, a.status
    ORDER BY total_tasks DESC
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
                <li class="breadcrumb-item active">Agent Performance Report</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Agent Performance</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="agentReportTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr><th>Agent ID</th><th>Name</th><th>Status</th><th>Total Tasks</th><th>Completed</th><th>Completion Rate</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($agents as $a): $rate = $a['total_tasks'] > 0 ? round(($a['completed_tasks'] / $a['total_tasks']) * 100) : 0; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['agent_id']) ?></td>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= ucfirst($a['status']) ?></td>
                                            <td><?= $a['total_tasks'] ?></td>
                                            <td><?= $a['completed_tasks'] ?></td>
                                            <td><?= $rate ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($agents)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No agents found.</td></tr>
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

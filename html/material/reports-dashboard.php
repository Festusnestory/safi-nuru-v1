<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$pageTitle = 'Reports Dashboard';

$totalBuyers = (int)$pdo->query("SELECT COUNT(*) FROM buyers")->fetchColumn();
$totalAgents = (int)$pdo->query("SELECT COUNT(*) FROM agents WHERE status = 'active'")->fetchColumn();
$totalProperties = (int)$pdo->query("SELECT COUNT(*) FROM seller_properties")->fetchColumn();
$soldProperties = (int)$pdo->query("SELECT COUNT(*) FROM seller_properties WHERE property_status = 'sold'")->fetchColumn();
$pendingBuyers = (int)$pdo->query("SELECT COUNT(*) FROM buyers WHERE status = 'pending'")->fetchColumn();
$pendingAgentApps = (int)$pdo->query("SELECT COUNT(*) FROM agent_applications WHERE status IN ('submitted','under_review')")->fetchColumn();
$totalSalesValue = (float)$pdo->query("SELECT COALESCE(SUM(sold_price), 0) FROM seller_properties WHERE property_status = 'sold'")->fetchColumn();

$byRegion = $pdo->query("
    SELECT property_region AS region, COUNT(*) AS total
    FROM seller_properties
    GROUP BY property_region
    ORDER BY total DESC
    LIMIT 8
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
                <li class="breadcrumb-item active">Reports Dashboard</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $totalBuyers ?></h2>
                    <p class="text-muted mb-0">Total Buyers</p>
                    <small class="text-warning"><?= $pendingBuyers ?> pending review</small>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $totalAgents ?></h2>
                    <p class="text-muted mb-0">Active Agents</p>
                    <small class="text-warning"><?= $pendingAgentApps ?> applications pending</small>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $totalProperties ?></h2>
                    <p class="text-muted mb-0">Properties Listed</p>
                    <small class="text-success"><?= $soldProperties ?> sold</small>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0">N$<?= number_format($totalSalesValue, 0) ?></h2>
                    <p class="text-muted mb-0">Total Sales Value</p>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Properties by Region</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead><tr><th>Region</th><th>Properties</th></tr></thead>
                            <tbody>
                                <?php foreach ($byRegion as $row): ?>
                                    <tr><td><?= htmlspecialchars($row['region']) ?></td><td><?= $row['total'] ?></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($byRegion)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No data yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

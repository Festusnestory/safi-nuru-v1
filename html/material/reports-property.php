<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$pageTitle = 'Property Report';

$properties = $pdo->query("
    SELECT property_detail_type, property_region, property_town, selling_price, property_status, created_at
    FROM seller_properties
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$byStatus = $pdo->query("
    SELECT property_status, COUNT(*) AS total
    FROM seller_properties
    GROUP BY property_status
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
                <li class="breadcrumb-item active">Property Report</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row mb-3">
            <?php foreach ($byStatus as $s): ?>
                <div class="col-md-2 col-4">
                    <div class="card"><div class="card-body text-center">
                        <h3 class="mb-0"><?= $s['total'] ?></h3>
                        <small class="text-muted"><?= ucfirst($s['property_status']) ?></small>
                    </div></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">All Properties</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="propertyReportTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr><th>Type</th><th>Region</th><th>Town</th><th>Selling Price</th><th>Status</th><th>Listed</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['property_detail_type']) ?></td>
                                            <td><?= htmlspecialchars($p['property_region']) ?></td>
                                            <td><?= htmlspecialchars($p['property_town']) ?></td>
                                            <td>N$<?= number_format($p['selling_price'], 2) ?></td>
                                            <td><?= ucfirst($p['property_status']) ?></td>
                                            <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($properties)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No properties found.</td></tr>
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

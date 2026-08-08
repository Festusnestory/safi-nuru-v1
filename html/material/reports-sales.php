<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$pageTitle = 'Sales Report';

$sales = $pdo->query("
    SELECT sa.application_number, sp.property_detail_type, sp.property_region, sp.property_town, sp.sold_price, sp.buyer_name, sp.sale_date
    FROM seller_properties sp
    JOIN seller_applications sa ON sa.id = sp.application_id
    WHERE sp.property_status = 'sold'
    ORDER BY sp.sale_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalSales = count($sales);
$totalValue = array_sum(array_column($sales, 'sold_price'));
$avgValue = $totalSales > 0 ? $totalValue / $totalSales : 0;
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
                <li class="breadcrumb-item active">Sales Report</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center">
                    <h3 class="mb-0"><?= $totalSales ?></h3>
                    <small class="text-muted">Properties Sold</small>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center">
                    <h3 class="mb-0">N$<?= number_format($totalValue, 0) ?></h3>
                    <small class="text-muted">Total Sales Value</small>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center">
                    <h3 class="mb-0">N$<?= number_format($avgValue, 0) ?></h3>
                    <small class="text-muted">Average Sale Price</small>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Sold Properties</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="salesReportTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr><th>Application</th><th>Type</th><th>Region</th><th>Town</th><th>Sold Price</th><th>Buyer</th><th>Sale Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['application_number']) ?></td>
                                            <td><?= htmlspecialchars($s['property_detail_type']) ?></td>
                                            <td><?= htmlspecialchars($s['property_region']) ?></td>
                                            <td><?= htmlspecialchars($s['property_town']) ?></td>
                                            <td>N$<?= number_format($s['sold_price'], 2) ?></td>
                                            <td><?= htmlspecialchars($s['buyer_name']) ?></td>
                                            <td><?= $s['sale_date'] ? date('d M Y', strtotime($s['sale_date'])) : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($sales)): ?>
                                        <tr><td colspan="7" class="text-center text-muted">No sales recorded yet.</td></tr>
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

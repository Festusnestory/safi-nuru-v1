<?php
/** @var int $totalBuyers */
/** @var int $totalAgents */
/** @var int $totalProperties */
/** @var int $soldProperties */
/** @var int $pendingBuyers */
/** @var int $pendingAgentApps */
/** @var float $totalSalesValue */
/** @var array $byRegion */
$pageTitle = 'Reports Dashboard';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>

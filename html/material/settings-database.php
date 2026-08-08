<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin']);
include("./config/pdo.php");
$pageTitle = 'Database Settings';

$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
$tables = $pdo->prepare("
    SELECT table_name, table_type, table_rows, ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.tables
    WHERE table_schema = ?
    ORDER BY (data_length + index_length) DESC
");
$tables->execute([$dbName]);
$tables = $tables->fetchAll(PDO::FETCH_ASSOC);
$totalSizeMb = array_sum(array_column($tables, 'size_mb'));
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
                <li class="breadcrumb-item active">Database Settings</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center">
                    <h5 class="mb-0"><?= htmlspecialchars($dbName) ?></h5>
                    <small class="text-muted">Database Name</small>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center">
                    <h5 class="mb-0"><?= count($tables) ?></h5>
                    <small class="text-muted">Tables &amp; Views</small>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center">
                    <h5 class="mb-0"><?= number_format($totalSizeMb, 2) ?> MB</h5>
                    <small class="text-muted">Total Size</small>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Database Objects</h4>
                        <a href="backup-restore.php" class="btn btn-outline-primary btn-sm">Backup Database</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Name</th><th>Type</th><th>Row Count (approx.)</th><th>Size (MB)</th></tr></thead>
                                <tbody>
                                    <?php foreach ($tables as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['table_name']) ?></td>
                                            <td><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $t['table_type'])))) ?></td>
                                            <td><?= number_format((int)$t['table_rows']) ?></td>
                                            <td><?= number_format((float)$t['size_mb'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
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

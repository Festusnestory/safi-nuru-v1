<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$pageTitle = 'Custom Report';

$entity = $_GET['entity'] ?? 'buyers';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$dateError = '';

foreach (['from' => $dateFrom, 'to' => $dateTo] as $label => $dateValue) {
    if ($dateValue !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue) || DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue)?->format('Y-m-d') !== $dateValue)) {
        $dateError = 'Enter valid report dates.';
    }
}
if ($dateError === '' && $dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $dateError = 'The start date cannot be after the end date.';
}

$entityConfig = [
    'buyers'  => ['table' => 'buyers', 'dateCol' => 'created_at', 'cols' => ['application_number', 'full_name', 'email', 'phone', 'status', 'created_at']],
    'agents'  => ['table' => 'agents', 'dateCol' => 'created_at', 'cols' => ['agent_id', 'first_name', 'surname', 'email', 'status', 'created_at']],
    'properties' => ['table' => 'seller_properties', 'dateCol' => 'created_at', 'cols' => ['application_id', 'property_detail_type', 'property_region', 'selling_price', 'property_status', 'created_at']],
];
if (!isset($entityConfig[$entity])) {
    $entity = 'buyers';
}
$config = $entityConfig[$entity];

$where = [];
$params = [];
if ($dateError === '' && $dateFrom !== '') {
    $where[] = "{$config['dateCol']} >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateError === '' && $dateTo !== '') {
    $where[] = "{$config['dateCol']} <= ?";
    $params[] = $dateTo . ' 23:59:59';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$colsSql = implode(', ', $config['cols']);
$stmt = $pdo->prepare("SELECT {$colsSql} FROM {$config['table']} {$whereSql} ORDER BY {$config['dateCol']} DESC LIMIT 500");
$rows = [];
if ($dateError === '') {
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
<?php include("left-sidebar.php"); ?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Custom Report</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($dateError): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($dateError) ?></div><?php endif; ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="get" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label" for="report-entity">Entity</label>
                                <select id="report-entity" name="entity" class="form-select">
                                    <option value="buyers" <?= $entity === 'buyers' ? 'selected' : '' ?>>Buyers</option>
                                    <option value="agents" <?= $entity === 'agents' ? 'selected' : '' ?>>Agents</option>
                                    <option value="properties" <?= $entity === 'properties' ? 'selected' : '' ?>>Properties</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date-from">From</label>
                                <input id="date-from" type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date-to">To</label>
                                <input id="date-to" type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Run Report</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Results (<?= count($rows) ?>)</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr><?php foreach ($config['cols'] as $c): ?><th><?= ucwords(str_replace('_', ' ', $c)) ?></th><?php endforeach; ?></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr><?php foreach ($config['cols'] as $c): ?><td><?= htmlspecialchars((string)$row[$c]) ?></td><?php endforeach; ?></tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($rows)): ?>
                                        <tr><td colspan="<?= count($config['cols']) ?>" class="text-center text-muted">No results for this filter.</td></tr>
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

<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
include("./config/pdo.php");
$csrfToken = csrfToken('data_export');

$exportConfig = [
    'buyers'     => ['table' => 'buyers', 'cols' => ['id', 'application_number', 'full_name', 'email', 'phone', 'region', 'town', 'status', 'created_at']],
    'agents'     => ['table' => 'agents', 'cols' => ['id', 'agent_id', 'first_name', 'surname', 'email', 'mobile_number', 'company_name', 'status', 'created_at']],
    'properties' => ['table' => 'seller_properties', 'cols' => ['id', 'application_id', 'property_detail_type', 'property_region', 'property_town', 'selling_price', 'property_status', 'created_at']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download']) && isset($exportConfig[$_POST['download']])) {
    if (!validCsrfToken($_POST['csrf_token'] ?? null, 'data_export')) {
        http_response_code(419);
        exit('Your session has expired. Reload the page and try again.');
    }

    $entity = $_POST['download'];
    $config = $exportConfig[$entity];
    $colsSql = implode(', ', $config['cols']);
    $rows = $pdo->query("SELECT {$colsSql} FROM {$config['table']} ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $entity . '_' . date('Ymd') . '.csv"');
    header('Cache-Control: no-store, private, max-age=0');
    header('X-Content-Type-Options: nosniff');

    $out = fopen('php://output', 'w');
    fputcsv($out, $config['cols']);
    foreach ($rows as $row) {
        // Spreadsheet programs execute formula-like cell values. Prefixing
        // untrusted values protects exported names, emails, and other data
        // from CSV formula injection when opened by staff.
        $safeRow = array_map(static function ($value) {
            $value = (string)$value;
            return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
        }, $row);
        fputcsv($out, $safeRow);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Exports';
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
                <li class="breadcrumb-item active">Exports</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Download Data as CSV</h4></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-body text-center">
                                        <i class="mdi mdi-account-group fs-1 text-primary mb-2"></i>
                                        <h6>Buyers</h6>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="download" value="buyers" class="btn btn-outline-primary btn-sm w-100">Download CSV</button></form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-body text-center">
                                        <i class="mdi mdi-account-tie fs-1 text-primary mb-2"></i>
                                        <h6>Agents</h6>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="download" value="agents" class="btn btn-outline-primary btn-sm w-100">Download CSV</button></form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-body text-center">
                                        <i class="mdi mdi-home-city fs-1 text-primary mb-2"></i>
                                        <h6>Properties</h6>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="download" value="properties" class="btn btn-outline-primary btn-sm w-100">Download CSV</button></form>
                                    </div>
                                </div>
                            </div>
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

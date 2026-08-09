<?php
/** @var string $monthLabels JSON-encoded array */
/** @var string $monthValues JSON-encoded array */
/** @var string $agentLabels JSON-encoded array */
/** @var string $agentValues JSON-encoded array */
$pageTitle = 'Analytics';
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
                <li class="breadcrumb-item active">Analytics</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Buyer Applications Over Time</h4></div>
                    <div class="card-body"><div id="buyerTrendChart"></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Agents by Status</h4></div>
                    <div class="card-body"><div id="agentStatusChart"></div></div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">All Rights Reserved by Nuru.</footer>
</div>
</div>
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
<script>
new ApexCharts(document.querySelector("#buyerTrendChart"), {
    chart: { type: 'line', height: 320 },
    series: [{ name: 'Buyer Applications', data: <?= $monthValues ?> }],
    xaxis: { categories: <?= $monthLabels ?> }
}).render();

new ApexCharts(document.querySelector("#agentStatusChart"), {
    chart: { type: 'donut', height: 320 },
    series: <?= $agentValues ?>,
    labels: <?= $agentLabels ?>
}).render();
</script>
</body>
</html>

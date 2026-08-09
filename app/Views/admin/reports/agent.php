<?php
/** @var array $agents */
$pageTitle = 'Agent Performance Report';
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>

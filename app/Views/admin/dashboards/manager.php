<?php
/** @var int $totalAgents */
/** @var int $activeTasks */
/** @var int $completedTasks */
/** @var int $pendingBuyers */
/** @var int $pendingAgentApps */
/** @var int $pendingSellerApps */
/** @var array $agentWorkload */
$pageTitle = 'Manager Dashboard';
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
                <li class="breadcrumb-item active">Manager Dashboard</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php renderAccessDeniedNotice(); ?>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $totalAgents ?></h2>
                    <p class="text-muted mb-0">Active Agents</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $activeTasks ?></h2>
                    <p class="text-muted mb-0">Open Tasks</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $completedTasks ?></h2>
                    <p class="text-muted mb-0">Completed Tasks</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $pendingBuyers ?></h2>
                    <p class="text-muted mb-0">Pending Buyers</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $pendingAgentApps ?></h2>
                    <p class="text-muted mb-0">Pending Agent Apps</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $pendingSellerApps ?></h2>
                    <p class="text-muted mb-0">Pending Seller Apps</p>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Agent Workload</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Agent</th><th>Total Tasks</th><th>Active</th><th>Completed</th></tr></thead>
                                <tbody>
                                    <?php foreach ($agentWorkload as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= $a['total_tasks'] ?></td>
                                            <td><?= $a['active_tasks'] ?></td>
                                            <td><?= $a['completed_tasks'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($agentWorkload)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No agents found.</td></tr>
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>

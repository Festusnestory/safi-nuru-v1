<?php
/** @var int $openTasks */
/** @var int $overdueTasks */
/** @var int $completedTasks */
/** @var int $myBuyers */
/** @var int $mySellers */
/** @var array $upcomingTasks */
/** @var array $myExpiring */
$pageTitle = 'Agent Dashboard';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php require NURU_MATERIAL . '/agent_nemu.php'; ?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Agent Dashboard</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php renderAccessDeniedNotice(); ?>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $openTasks ?></h2>
                    <p class="text-muted mb-0">Open Tasks</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0 <?= $overdueTasks > 0 ? 'text-danger' : '' ?>"><?= $overdueTasks ?></h2>
                    <p class="text-muted mb-0">Overdue</p>
                </div></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $completedTasks ?></h2>
                    <p class="text-muted mb-0">Completed This Month</p>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $myBuyers ?></h2>
                    <p class="text-muted mb-0">My Buyers</p>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $mySellers ?></h2>
                    <p class="text-muted mb-0">My Sellers</p>
                </div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Upcoming Due Dates</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Application No</th><th>Region</th><th>Town</th><th>Due Date</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($upcomingTasks as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['application_id']) ?></td>
                                            <td><?= htmlspecialchars($t['property_region']) ?></td>
                                            <td><?= htmlspecialchars($t['property_town']) ?></td>
                                            <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—' ?></td>
                                            <td><?= htmlspecialchars($t['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($upcomingTasks)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No open tasks.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">My Deals — Countdown</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Application No</th><th>Region</th><th>Town</th><th>Status</th><th>Countdown</th></tr></thead>
                                <tbody>
                                    <?php foreach ($myExpiring as $d): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d['application_id']) ?></td>
                                            <td><?= htmlspecialchars($d['property_region']) ?></td>
                                            <td><?= htmlspecialchars($d['property_town']) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $d['property_status'])) ?></td>
                                            <td><?= renderCountdownBadge($d['property_status'], $d['status_deadline']) ?: '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($myExpiring)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No deals currently under offer or awaiting transfer.</td></tr>
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

<?php
/** @var int $totalConsultations */
/** @var int $consultationsThisMonth */
/** @var array $recentConsultations */
$pageTitle = 'Consultant Dashboard';
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
                <li class="breadcrumb-item active">Consultant Dashboard</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php renderAccessDeniedNotice(); ?>
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $totalConsultations ?></h2>
                    <p class="text-muted mb-0">Total Consultations Logged</p>
                </div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body text-center">
                    <h2 class="mb-0"><?= $consultationsThisMonth ?></h2>
                    <p class="text-muted mb-0">Logged This Month</p>
                </div></div>
            </div>
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <a href="consulting_agent_form.php" class="btn btn-lg btn-info">
                            <i data-feather="user-plus" class="feather-sm me-1"></i>
                            Log a Consultation
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">My Recent Consultations</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Application No</th><th>Name</th><th>Phone</th><th>Region</th><th>Town</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recentConsultations as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['application_number']) ?></td>
                                            <td><?= htmlspecialchars($c['full_name']) ?></td>
                                            <td><?= htmlspecialchars($c['phone']) ?></td>
                                            <td><?= htmlspecialchars($c['region']) ?></td>
                                            <td><?= htmlspecialchars($c['town']) ?></td>
                                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentConsultations)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No consultations logged yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="consultant_list.php" class="btn btn-sm btn-outline-info">View All My Consultations</a>
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

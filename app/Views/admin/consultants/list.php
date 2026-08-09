<?php
/** @var array $buyers */
/** @var string $baseUrl */
$pageTitle = 'Consultant List';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php
if (\App\Core\Auth::isFullAccess()) {
    require NURU_MATERIAL . '/left-sidebar.php';
} else {
    require NURU_MATERIAL . '/agent_nemu.php';
}
?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Consultant List</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Walk-in Consultations</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr><th>#</th><th>Application No</th><th>Name</th><th>Email</th><th>Phone</th><th>Region</th><th>Town</th><th>Date</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buyers as $index => $b): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($b['application_number']) ?></td>
                                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                                            <td><?= htmlspecialchars($b['email']) ?></td>
                                            <td><?= htmlspecialchars($b['phone']) ?></td>
                                            <td><?= htmlspecialchars($b['region']) ?></td>
                                            <td><?= htmlspecialchars($b['town']) ?></td>
                                            <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                                            <td><a href="<?= $baseUrl ?>/html/material/buyers_profile.php?id=<?= portalEncodeId((int)$b['id']) ?>" class="btn btn-sm btn-info">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($buyers)): ?>
                                        <tr><td colspan="9" class="text-center text-muted">No walk-in consultations recorded yet.</td></tr>
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

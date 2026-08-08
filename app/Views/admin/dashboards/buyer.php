<?php
/** @var array|null $buyer */
/** @var array $areas */
/** @var array $documents */
/** @var array|null $assignedAgent */
$pageTitle = 'My Application';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard_1.php">Nuru Real Estate</a>
        <a class="btn btn-outline-light btn-sm" href="config/logout.php">Logout</a>
    </div>
</nav>
<div class="container my-4">
    <?php renderAccessDeniedNotice(); ?>
    <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Buyer') ?></h3>

    <?php if (!$buyer): ?>
        <div class="alert alert-info">
            No buyer application is linked to your account yet.
            <a href="buyer/index.php">Start a new application</a>.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">Application Status</h5></div>
                    <div class="card-body">
                        <p><strong>Application Number:</strong> <?= htmlspecialchars($buyer['application_number']) ?></p>
                        <p><strong>Status:</strong>
                            <span class="badge bg-<?= $buyer['status'] === 'approved' ? 'success' : ($buyer['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($buyer['status']) ?>
                            </span>
                        </p>
                        <p><strong>Property Budget:</strong> N$<?= number_format($buyer['loan_amount'] + $buyer['down_payment'], 2) ?></p>
                        <p class="mb-0"><strong>Submitted:</strong> <?= date('d M Y', strtotime($buyer['created_at'])) ?></p>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">Preferred Areas</h5></div>
                    <div class="card-body">
                        <?php if ($areas): ?>
                            <ul class="mb-0">
                                <?php foreach ($areas as $a): ?>
                                    <li><?= htmlspecialchars(trim("{$a['location']}, {$a['suburb']}, {$a['town']}, {$a['region']}", ', ')) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">No preferred areas on file.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">My Documents</h5></div>
                    <div class="card-body">
                        <?php if ($documents): ?>
                            <ul class="mb-0">
                                <?php foreach ($documents as $d): ?>
                                    <li><?= htmlspecialchars($d['doc_type']) ?> - uploaded <?= date('d M Y', strtotime($d['uploaded_at'])) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">No documents uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">Your Agent</h5></div>
                    <div class="card-body">
                        <?php if ($assignedAgent): ?>
                            <p class="mb-1"><strong><?= htmlspecialchars($assignedAgent['full_name']) ?></strong></p>
                            <p class="mb-1"><?= htmlspecialchars($assignedAgent['email']) ?></p>
                            <p class="mb-0"><?= htmlspecialchars($assignedAgent['mobile_number']) ?></p>
                        <?php else: ?>
                            <p class="text-muted mb-0">No agent assigned yet. We'll notify you once one is.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

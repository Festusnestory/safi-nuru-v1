<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("location: authentication-login.php");
    exit;
}
include("./config/pdo.php");
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['seller']);

$stmt = $pdo->prepare("
    SELECT sa.*, spd.first_name, spd.surname
    FROM seller_applications sa
    LEFT JOIN seller_personal_details spd ON spd.application_id = sa.id
    WHERE sa.user_id = ?
    ORDER BY sa.created_at DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

$properties = [];
$documents = [];
$assignedAgent = null;

if ($application) {
    $stmt = $pdo->prepare("SELECT * FROM seller_properties WHERE application_id = ? ORDER BY id");
    $stmt->execute([$application['id']]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT document_type, original_filename, upload_date FROM seller_documents WHERE application_id = ?");
    $stmt->execute([$application['id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($application['assigned_agent_id']) {
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', surname) AS full_name, email, mobile_number FROM agents WHERE id = ?");
        $stmt->execute([$application['assigned_agent_id']]);
        $assignedAgent = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php $pageTitle = 'My Application'; include('_page_head.php'); ?>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard_5.php">Nuru Real Estate</a>
        <a class="btn btn-outline-light btn-sm" href="config/logout.php">Logout</a>
    </div>
</nav>
<div class="container my-4">
    <?php renderAccessDeniedNotice(); ?>
    <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Seller') ?></h3>

    <?php if (!$application): ?>
        <div class="alert alert-info">
            No seller application is linked to your account yet.
            <a href="seller/index.php">Start a new application</a>.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">Application Status</h5></div>
                    <div class="card-body">
                        <p><strong>Application Number:</strong> <?= htmlspecialchars($application['application_number']) ?></p>
                        <p><strong>Status:</strong>
                            <span class="badge bg-<?= $application['status'] === 'approved' ? 'success' : ($application['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($application['status']) ?>
                            </span>
                        </p>
                        <p class="mb-0"><strong>Submitted:</strong> <?= $application['submission_date'] ? date('d M Y', strtotime($application['submission_date'])) : date('d M Y', strtotime($application['created_at'])) ?></p>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">My Properties</h5></div>
                    <div class="card-body">
                        <?php if ($properties): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Type</th>
                                            <th>Selling Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($properties as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['property_street_name']) ?></td>
                                            <td><?= htmlspecialchars($p['property_detail_type']) ?></td>
                                            <td>N$ <?= number_format((float)$p['selling_price'], 2) ?></td>
                                            <td>
                                                <?= ucfirst(str_replace('_', ' ', $p['property_status'])) ?>
                                                <?= renderCountdownBadge($p['property_status'], $p['status_deadline']) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No properties on file yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">My Documents</h5></div>
                    <div class="card-body">
                        <?php if ($documents): ?>
                            <ul class="mb-0">
                                <?php foreach ($documents as $d): ?>
                                    <li><?= htmlspecialchars(str_replace('_', ' ', $d['document_type'])) ?> - uploaded <?= date('d M Y', strtotime($d['upload_date'])) ?></li>
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

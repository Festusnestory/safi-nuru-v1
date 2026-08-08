<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("location: authentication-login.php");
    exit;
}
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['buyer']);
include("./config/pdo.php");

$stmt = $pdo->prepare("SELECT * FROM buyers WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$buyer = $stmt->fetch(PDO::FETCH_ASSOC);

$areas = [];
$documents = [];
$assignedAgent = null;

if ($buyer) {
    $stmt = $pdo->prepare("SELECT region, town, location, suburb FROM buyer_preferred_areas WHERE buyer_id = ?");
    $stmt->execute([$buyer['id']]);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT doc_type, file_path, uploaded_at FROM buyer_documents WHERE buyer_id = ?");
    $stmt->execute([$buyer['id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($buyer['assigned_agent_id']) {
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', surname) AS full_name, email, mobile_number FROM agents WHERE id = ?");
        $stmt->execute([$buyer['assigned_agent_id']]);
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

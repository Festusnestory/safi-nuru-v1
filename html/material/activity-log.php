<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin']);
include("./config/pdo.php");
$pageTitle = 'Activity Log';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM activity_log")->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT al.id, al.level, al.category, al.action, al.description, al.ip_address, al.created_at, au.full_name
    FROM activity_log al
    LEFT JOIN admin_users au ON au.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <li class="breadcrumb-item active">Activity Log</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">System Activity (<?= $total ?> events)</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr><th>When</th><th>User</th><th>Level</th><th>Category</th><th>Action</th><th>Description</th><th>IP</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                                            <td><span class="badge bg-<?= $log['level'] === 'error' ? 'danger' : ($log['level'] === 'warning' ? 'warning' : 'info') ?>"><?= ucfirst($log['level']) ?></span></td>
                                            <td><?= htmlspecialchars($log['category']) ?></td>
                                            <td><?= htmlspecialchars($log['action']) ?></td>
                                            <td><?= htmlspecialchars($log['description']) ?></td>
                                            <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($logs)): ?>
                                        <tr><td colspan="7" class="text-center text-muted">No activity recorded yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
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

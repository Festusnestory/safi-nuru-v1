<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin']);
include("./config/pdo.php");
require_once __DIR__ . '/includes/functions.php';
$csrfToken = csrfToken('database_backup');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'download') {
    if (!validCsrfToken($_POST['csrf_token'] ?? null, 'database_backup')) {
        http_response_code(419);
        exit('Your session has expired. Reload the page and try again.');
    }

    $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
    $objectStatement = $pdo->prepare(
        "SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = ? ORDER BY table_type, table_name"
    );
    $objectStatement->execute([$dbName]);
    $objects = $objectStatement->fetchAll(PDO::FETCH_ASSOC);
    $tables = array_column(array_filter($objects, static fn(array $object): bool => $object['table_type'] === 'BASE TABLE'), 'table_name');
    $views = array_column(array_filter($objects, static fn(array $object): bool => $object['table_type'] === 'VIEW'), 'table_name');

    logActivity((int)$_SESSION['user_id'], 'DATABASE_BACKUP_DOWNLOADED', 'Downloaded a full database backup', 'backup', 'warning');

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="nuru_backup_' . date('Ymd_His') . '.sql"');
    header('Cache-Control: no-store, private, max-age=0');
    header('X-Content-Type-Options: nosniff');

    echo "-- Nuru database backup generated " . date('Y-m-d H:i:s') . "\n";
    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $safeTable = str_replace('`', '``', $table);
        $createStmt = $pdo->query("SHOW CREATE TABLE `$safeTable`")->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo ($createStmt[1] ?? '') . ";\n\n";

        $rowStmt = $pdo->query("SELECT * FROM `$safeTable`");
        while ($row = $rowStmt->fetch(PDO::FETCH_ASSOC)) {
            $values = array_map(function ($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, $row);
            echo "INSERT INTO `$table` (`" . implode('`, `', array_keys($row)) . "`) VALUES (" . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }

    // Views must be created after their source tables. Remove database-bound
    // DEFINER clauses so the backup can be restored by a different account.
    foreach ($views as $view) {
        $safeView = str_replace('`', '``', $view);
        $createView = $pdo->query("SHOW CREATE VIEW `$safeView`")->fetch(PDO::FETCH_NUM);
        $definition = (string)($createView[1] ?? '');
        $definition = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/i', '', $definition);
        echo "DROP VIEW IF EXISTS `$safeView`;\n";
        echo $definition . ";\n\n";
    }

    $triggers = $pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC);
    if ($triggers) {
        echo "DELIMITER ;;\n";
        foreach ($triggers as $trigger) {
            $triggerName = str_replace('`', '``', (string)$trigger['Trigger']);
            $createTrigger = $pdo->query("SHOW CREATE TRIGGER `$triggerName`")->fetch(PDO::FETCH_NUM);
            $definition = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/i', '', (string)($createTrigger[2] ?? $createTrigger[1] ?? ''));
            echo "DROP TRIGGER IF EXISTS `$triggerName`;;\n";
            echo $definition . ";;\n";
        }
        echo "DELIMITER ;\n\n";
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}

$pageTitle = 'Database Backup';
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
                <li class="breadcrumb-item active">Database Backup</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Download Backup</h4></div>
                    <div class="card-body">
                        <p class="text-muted">Download a full SQL export of the current database. Store it somewhere safe before making major changes.</p>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="download">
                            <button type="submit" class="btn btn-primary"><i class="mdi mdi-download me-1"></i> Download Backup (.sql)</button>
                        </form>
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

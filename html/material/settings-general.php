<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin']);
include("./config/pdo.php");
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'General Settings';
$csrfToken = csrfToken('general_settings');

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validCsrfToken($_POST['csrf_token'] ?? null, 'general_settings')) {
    http_response_code(419);
    $success = '';
    $error = 'Your session has expired. Reload the page and try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = trim((string)($_POST['app_name'] ?? ''));
    $timezone = trim((string)($_POST['app_timezone'] ?? ''));
    if (mb_strlen($appName) < 2 || mb_strlen($appName) > 100) {
        $error = 'Application name must be between 2 and 100 characters.';
    } elseif (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
        $error = 'Select a valid timezone.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_type) VALUES (?, ?, 'string') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['app_name', $appName]);
        $stmt->execute(['app_timezone', $timezone]);
        $pdo->commit();
        $nuruSettings['app_name'] = $appName;
        $nuruSettings['app_timezone'] = $timezone;
        date_default_timezone_set($timezone);
        $success = 'Settings saved.';
        logActivity((int)$_SESSION['user_id'], 'GENERAL_SETTINGS_UPDATED', 'Updated application name and timezone', 'settings', 'warning');
    }
}

$settings = [];
$rows = $pdo->query("SELECT setting_name, setting_value FROM app_settings")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $settings[$row['setting_name']] = $row['setting_value'];
}
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
                <li class="breadcrumb-item active">General Settings</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">General</h4></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="mb-3">
                                <label class="form-label" for="app-name">Application Name</label>
                                <input id="app-name" type="text" name="app_name" class="form-control" minlength="2" maxlength="100" value="<?= htmlspecialchars($settings['app_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="app-timezone">Timezone</label>
                                <select id="app-timezone" name="app_timezone" class="form-select" required>
                                    <?php foreach (DateTimeZone::listIdentifiers() as $timezone): ?>
                                        <option value="<?= htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8') ?>" <?= ($settings['app_timezone'] ?? 'Africa/Windhoek') === $timezone ? 'selected' : '' ?>><?= htmlspecialchars($timezone) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Settings</button>
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

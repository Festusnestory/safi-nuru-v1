<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin']);
include("./config/pdo.php");
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/mailer.php';
$pageTitle = 'Email Settings';
$csrfToken = csrfToken('email_settings');

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validCsrfToken($_POST['csrf_token'] ?? null, 'email_settings')) {
    http_response_code(419);
    $success = '';
    $error = 'Your session has expired. Reload the page and try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'save') === 'test') {
    $testEmail = strtolower(trim((string)($_POST['test_email'] ?? '')));
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL) || strlen($testEmail) > 255) {
        $error = 'Enter a valid test recipient address.';
    } else {
        $sent = nuruSendMail(
            $testEmail,
            'Nuru email delivery test',
            "This is a Nuru Real Estate delivery test generated at " . date(DATE_ATOM) . ".",
            false,
            'admin_test',
            (int)$_SESSION['user_id']
        );
        if ($sent) {
            $success = 'Test email accepted by the configured mail transport.';
            logActivity((int)$_SESSION['user_id'], 'EMAIL_TEST_SENT', 'Mail transport accepted a test message', 'settings', 'info');
        } else {
            $error = 'The configured mail transport rejected the test message. Review the server mail configuration before production use.';
            logActivity((int)$_SESSION['user_id'], 'EMAIL_TEST_FAILED', 'Mail transport rejected a test message', 'settings', 'warning');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromEmail = strtolower(trim((string)($_POST['smtp_from_email'] ?? '')));
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || strlen($fromEmail) > 255) {
        $error = 'Enter a valid sender email address.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_type) VALUES ('smtp_from_email', ?, 'string') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$fromEmail]);
        $nuruSettings['smtp_from_email'] = $fromEmail;
        $success = 'Email sender saved.';
        logActivity((int)$_SESSION['user_id'], 'EMAIL_SETTINGS_UPDATED', 'Updated the outbound sender address', 'settings', 'warning');
    }
}

$settings = [];
$rows = $pdo->query("SELECT setting_name, setting_value FROM app_settings")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

$testAddressStatement = $pdo->prepare('SELECT email FROM admin_users WHERE id = ? LIMIT 1');
$testAddressStatement->execute([(int)$_SESSION['user_id']]);
$testAddress = (string)($testAddressStatement->fetchColumn() ?: '');
$deliveryRows = $pdo->query(
    'SELECT purpose, delivery_status, failure_code, created_at
     FROM email_delivery_log
     ORDER BY id DESC
     LIMIT 10'
)->fetchAll(PDO::FETCH_ASSOC);
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
                <li class="breadcrumb-item active">Email Settings</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Email Delivery</h4></div>
                    <div class="card-body">
                        <div class="alert alert-info">Mail transport credentials are configured securely on the server. This page controls the sender address used by account and password-reset messages.</div>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="mb-3">
                                <label class="form-label" for="from-email">From Email Address</label>
                                <input id="from-email" type="email" name="smtp_from_email" class="form-control" maxlength="255" autocomplete="email" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Sender</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Test Delivery</h4></div>
                    <div class="card-body">
                        <p>Send a content-safe test and record whether the server transport accepted it.</p>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="test">
                            <div class="mb-3">
                                <label class="form-label" for="test-email">Test Recipient</label>
                                <input id="test-email" type="email" name="test_email" class="form-control" maxlength="255" autocomplete="email" value="<?= htmlspecialchars($testAddress, ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Send Test Email</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Recent Delivery Results</h4></div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Time</th><th>Purpose</th><th>Status</th><th>Failure</th></tr></thead>
                    <tbody>
                    <?php if (!$deliveryRows): ?>
                        <tr><td colspan="4" class="text-muted">No delivery attempts have been recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($deliveryRows as $delivery): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$delivery['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst((string)$delivery['purpose'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-<?= $delivery['delivery_status'] === 'sent' ? 'success' : 'danger' ?>"><?= htmlspecialchars((string)$delivery['delivery_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string)($delivery['failure_code'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <footer class="footer">All Rights Reserved by Nuru.</footer>
</div>
</div>
<?php include('_page_scripts.php'); ?>
</body>
</html>

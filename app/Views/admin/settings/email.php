<?php
/** @var string $csrfToken */
/** @var string $success */
/** @var string $error */
/** @var array $settings */
/** @var string $testAddress */
/** @var array $deliveryRows */
$pageTitle = 'Email Settings';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>

<?php
/** @var string $csrfToken */
/** @var string $success */
/** @var string $error */
/** @var array $settings */
$pageTitle = 'General Settings';
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>

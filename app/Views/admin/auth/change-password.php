<?php
/** @var bool $mustChangePassword */
/** @var array $errors */
/** @var string $csrfToken */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $mustChangePassword ? 'Set' : 'Change' ?> your Nuru password</title>
    <link href="../../assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 540px;">
    <section class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-3"><?= $mustChangePassword ? 'Set a new password' : 'Change your password' ?></h1>
            <p class="text-muted">
                <?= $mustChangePassword
                    ? 'For your security, choose a new password before continuing.'
                    : 'Confirm your current password, then choose a new one.' ?>
            </p>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!$mustChangePassword): ?>
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current password</label>
                    <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <input id="password" name="password" type="password" class="form-control" minlength="12" autocomplete="new-password" required>
                    <div class="form-text">At least 12 characters, including letters and numbers.</div>
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="12" autocomplete="new-password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Save password</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>

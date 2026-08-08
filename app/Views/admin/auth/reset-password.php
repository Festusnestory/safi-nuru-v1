<?php
/** @var bool $complete */
/** @var array|null $tokenRow */
/** @var array $errors */
/** @var string $rawToken */
/** @var string $csrf */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset your Nuru password</title>
    <link href="../../assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 540px;">
    <section class="card shadow-sm">
        <div class="card-body p-4">
            <?php if ($complete): ?>
                <h1 class="h3 mb-3">Password updated</h1>
                <div class="alert alert-success">Your password has been reset. You can now sign in.</div>
                <a class="btn btn-primary w-100" href="authentication-login.php">Return to sign in</a>
            <?php elseif (!$tokenRow): ?>
                <h1 class="h3 mb-3">Reset link unavailable</h1>
                <div class="alert alert-danger">This reset link is invalid, expired, or has already been used.</div>
                <a class="btn btn-primary w-100" href="authentication-login.php">Request a new link</a>
            <?php else: ?>
                <h1 class="h3 mb-3">Reset your password</h1>
                <p class="text-muted">Choose a secure new password for your Nuru account.</p>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password" class="form-control" minlength="12" autocomplete="new-password" required autofocus>
                        <div class="form-text">At least 12 characters, including letters and numbers.</div>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="12" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Reset password</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>

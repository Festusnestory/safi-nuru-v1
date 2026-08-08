<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$rawToken = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$validTokenFormat = preg_match('/^[a-f0-9]{64}$/', $rawToken) === 1;
$tokenRow = null;
$errors = [];
$complete = false;

if ($validTokenFormat) {
    $tokenStatement = $pdo->prepare(
        'SELECT prt.id, prt.user_id, au.password_hash
         FROM password_reset_tokens prt
         INNER JOIN admin_users au ON au.id = prt.user_id
         WHERE prt.token_hash = ? AND prt.used_at IS NULL
           AND prt.expires_at > NOW() AND au.is_active = 1
         LIMIT 1'
    );
    $tokenStatement->execute([hash('sha256', $rawToken)]);
    $tokenRow = $tokenStatement->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow) {
    $password = (string)($_POST['password'] ?? '');
    $confirmation = (string)($_POST['password_confirmation'] ?? '');

    if (!validCsrfToken($_POST['csrf_token'] ?? null, 'password_reset')) {
        $errors[] = 'Your session has expired. Reload the reset link and try again.';
    } elseif (strlen($password) < 12 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Use at least 12 characters, including letters and numbers.';
    } elseif (!hash_equals($password, $confirmation)) {
        $errors[] = 'The password confirmation does not match.';
    } elseif (password_verify($password, (string)$tokenRow['password_hash'])) {
        $errors[] = 'Choose a password that is different from your current password.';
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare(
                'SELECT id FROM password_reset_tokens
                 WHERE id = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE'
            );
            $lock->execute([(int)$tokenRow['id']]);
            if (!$lock->fetchColumn()) {
                throw new RuntimeException('Reset token is no longer active.');
            }

            $pdo->prepare(
                "UPDATE admin_users
                 SET password_hash = ?, must_change_password = 0,
                     failed_login_attempts = 0, locked_until = NULL,
                     remember_token = '', remember_token_expires = '',
                     session_version = session_version + 1
                 WHERE id = ?"
            )->execute([password_hash($password, PASSWORD_DEFAULT), (int)$tokenRow['user_id']]);
            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')
                ->execute([(int)$tokenRow['id']]);
            $pdo->commit();

            unset($_SESSION['_csrf_tokens']['password_reset']);
            session_regenerate_id(true);
            $complete = true;
            $tokenRow = null;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Password reset failed: ' . $error->getMessage());
            $errors[] = 'This reset link is no longer valid. Request a new one.';
            $tokenRow = null;
        }
    }
}

$csrf = $tokenRow ? csrfToken('password_reset') : '';
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

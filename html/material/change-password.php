<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: authentication-login.php');
    exit;
}

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$current = $pdo->prepare('SELECT role, password_hash, is_active, must_change_password, session_version FROM admin_users WHERE id = ? LIMIT 1');
$current->execute([(int)$_SESSION['user_id']]);
$currentUser = $current->fetch(PDO::FETCH_ASSOC);
$sessionIsStale = isset($_SESSION['session_version'])
    && (int)$_SESSION['session_version'] !== (int)($currentUser['session_version'] ?? 0);
if (!$currentUser || !(int)$currentUser['is_active'] || $sessionIsStale) {
    $_SESSION = [];
    session_destroy();
    header('Location: authentication-login.php?account=inactive');
    exit;
}
$_SESSION['role'] = (string)$currentUser['role'];
$_SESSION['must_change_password'] = (int)$currentUser['must_change_password'];
$_SESSION['session_version'] = (int)$currentUser['session_version'];
$currentHash = (string)$currentUser['password_hash'];

$errors = [];
$mustChangePassword = !empty($_SESSION['must_change_password']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmation = (string)($_POST['password_confirmation'] ?? '');

    if (!validCsrfToken($_POST['csrf_token'] ?? null, 'password_change')) {
        $errors[] = 'Your session has expired. Reload the page and try again.';
    } elseif (strlen($password) < 12 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Use at least 12 characters, including letters and numbers.';
    } elseif (!hash_equals($password, $confirmation)) {
        $errors[] = 'The password confirmation does not match.';
    } else {
        if (!$mustChangePassword && !password_verify($currentPassword, $currentHash)) {
            $errors[] = 'The current password is incorrect.';
        } elseif (password_verify($password, $currentHash)) {
            $errors[] = 'Choose a password that is different from your current password.';
        } else {
            $statement = $pdo->prepare(
                "UPDATE admin_users
                 SET password_hash = ?, must_change_password = 0,
                     remember_token = '', remember_token_expires = '',
                     session_version = session_version + 1
                 WHERE id = ?"
            );
            $statement->execute([password_hash($password, PASSWORD_DEFAULT), (int)$_SESSION['user_id']]);
            $_SESSION['must_change_password'] = false;
            $_SESSION['session_version'] = (int)$currentUser['session_version'] + 1;
            session_regenerate_id(true);

            $redirect = match ($_SESSION['role'] ?? '') {
                'buyer' => 'dashboard_1.php',
                'manager' => 'dashboard_2.php',
                'agent_coordinator' => 'dashboard_3.php',
                'agent_consultant' => 'dashboard_4.php',
                'seller' => 'dashboard_5.php',
                'admin' => 'admin.php',
                default => 'authentication-login.php',
            };
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$csrfToken = csrfToken('password_change');
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

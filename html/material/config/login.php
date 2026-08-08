<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/turnstile.php';

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;
const LOGIN_RATE_WINDOW_MINUTES = 15;
const MAX_EMAIL_FAILURES = 10;
const MAX_IP_FAILURES = 20;
const DUMMY_PASSWORD_HASH = '$2y$10$oDo94JFgoDJcn37bzZQJXOWGA2qcYlo9s4EbNCnoX88yKEAUNGUVu';

function loginResponse(int $httpStatus, string $status, string $message, ?string $redirect = null): never
{
    http_response_code($httpStatus);
    $payload = ['status' => $status, 'message' => $message];
    if ($redirect !== null) {
        $payload['redirect'] = $redirect;
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    loginResponse(405, 'error', 'Method not allowed.');
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $password === '' || strlen($password) > 4096) {
    loginResponse(422, 'error', 'Enter a valid email address and password.');
}

if (!verifyTurnstile($_POST['cf-turnstile-response'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null)) {
    loginResponse(403, 'error', 'CAPTCHA verification failed. Please try again.');
}

$emailHash = hash('sha256', $email);
$ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    $pdo->beginTransaction();

    $rateStatement = $pdo->prepare(
        'SELECT
            SUM(CASE WHEN ip_hash = ? THEN 1 ELSE 0 END) AS ip_failures,
            SUM(CASE WHEN email_hash = ? THEN 1 ELSE 0 END) AS email_failures
         FROM login_attempts
         WHERE succeeded = 0
           AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . LOGIN_RATE_WINDOW_MINUTES . ' MINUTE)'
    );
    $rateStatement->execute([$ipHash, $emailHash]);
    $rate = $rateStatement->fetch(PDO::FETCH_ASSOC) ?: [];
    if ((int)($rate['ip_failures'] ?? 0) >= MAX_IP_FAILURES || (int)($rate['email_failures'] ?? 0) >= MAX_EMAIL_FAILURES) {
        $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, succeeded) VALUES (?, ?, 0)')->execute([$emailHash, $ipHash]);
        $pdo->commit();
        // Keep the response independent of whether the email exists.
        password_verify($password, DUMMY_PASSWORD_HASH);
        loginResponse(429, 'error', 'Sign-in is temporarily unavailable. Try again later.');
    }

    $statement = $pdo->prepare(
        'SELECT id, email, password_hash, full_name, role, is_active, must_change_password,
                failed_login_attempts, locked_until, session_version
         FROM admin_users WHERE email = ? LIMIT 1 FOR UPDATE'
    );
    $statement->execute([$email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC) ?: null;

    $hashToVerify = $user ? (string)$user['password_hash'] : DUMMY_PASSWORD_HASH;
    $passwordMatches = password_verify($password, $hashToVerify);
    $isLocked = $user && !empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time();
    $isActive = $user && (int)$user['is_active'] === 1;

    if (!$user || !$passwordMatches || !$isActive || $isLocked) {
        if ($user && $isActive && !$isLocked && !$passwordMatches) {
            $attempts = (int)$user['failed_login_attempts'] + 1;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $pdo->prepare('UPDATE admin_users SET failed_login_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL ' . LOCKOUT_MINUTES . ' MINUTE) WHERE id = ?')
                    ->execute([(int)$user['id']]);
            } else {
                $pdo->prepare('UPDATE admin_users SET failed_login_attempts = ? WHERE id = ?')
                    ->execute([$attempts, (int)$user['id']]);
            }
        }
        $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, succeeded) VALUES (?, ?, 0)')->execute([$emailHash, $ipHash]);
        $pdo->commit();
        loginResponse($isLocked ? 429 : 401, 'error', $isLocked ? 'Sign-in is temporarily unavailable. Try again later.' : 'Invalid email or password.');
    }

    $newHash = password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)
        ? password_hash($password, PASSWORD_DEFAULT)
        : (string)$user['password_hash'];
    $pdo->prepare('UPDATE admin_users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?')
        ->execute([$newHash, (int)$user['id']]);
    $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, succeeded) VALUES (?, ?, 1)')->execute([$emailHash, $ipHash]);
    if (random_int(1, 100) === 1) {
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    }
    $pdo->commit();

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['email'] = (string)$user['email'];
    $_SESSION['full_name'] = (string)$user['full_name'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['must_change_password'] = (bool)$user['must_change_password'];
    $_SESSION['session_version'] = (int)$user['session_version'];

    $redirect = $_SESSION['must_change_password'] ? 'change-password.php' : match ($user['role']) {
        'buyer' => 'dashboard_1.php',
        'manager' => 'dashboard_2.php',
        'agent_coordinator' => 'dashboard_3.php',
        'agent_consultant' => 'dashboard_4.php',
        'seller' => 'dashboard_5.php',
        'admin' => 'admin.php',
        default => 'authentication-login.php',
    };

    loginResponse(200, 'success', 'Login successful.', $redirect);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Login failed: ' . $error->getMessage());
    loginResponse(500, 'error', 'Server error. Please try again later.');
}

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
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/mailer.php';

const RESET_WINDOW_MINUTES = 15;
const RESET_MAX_REQUESTS = 5;
const RESET_TOKEN_MINUTES = 30;

function resetResponse(): never
{
    // The response is deliberately identical for existing, unknown, inactive,
    // and throttled accounts to prevent email-address enumeration.
    echo json_encode([
        'status' => 'success',
        'message' => 'If an active account matches that email, reset instructions will be sent shortly.',
    ]);
    exit;
}

function passwordResetBaseUrl(): ?string
{
    $configured = trim((string)(getenv('NURU_APP_URL') ?: ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $hostWithoutPort = preg_replace('/:\d+$/', '', $host);
    if (!in_array($hostWithoutPort, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
        || !preg_match('/^[a-z0-9.\-:\[\]]+$/', $host)) {
        error_log('NURU_APP_URL must be configured before password reset emails can be sent.');
        return null;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $materialPosition = strpos($script, '/html/material');
    $portal = $materialPosition === false ? '/html/material' : substr($script, 0, $materialPosition + 14);

    return $scheme . '://' . $host . $portal;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

if (!validCsrfToken($_POST['csrf_token'] ?? null, 'password_reset_request')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Your session has expired. Reload the page and try again.']);
    exit;
}
unset($_SESSION['_csrf_tokens']['password_reset_request']);

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    resetResponse();
}

$emailHash = hash('sha256', $email);
$ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    $rate = $pdo->prepare(
        'SELECT
            SUM(ip_hash = :ip_hash) AS ip_requests,
            SUM(email_hash = :email_hash) AS email_requests
         FROM password_reset_requests
         WHERE requested_at >= DATE_SUB(NOW(), INTERVAL ' . RESET_WINDOW_MINUTES . ' MINUTE)'
    );
    $rate->execute(['ip_hash' => $ipHash, 'email_hash' => $emailHash]);
    $counts = $rate->fetch() ?: [];

    $pdo->prepare('INSERT INTO password_reset_requests (email_hash, ip_hash) VALUES (?, ?)')
        ->execute([$emailHash, $ipHash]);

    // Opportunistic bounded cleanup avoids unbounded audit-table growth.
    if (random_int(1, 100) === 1) {
        $pdo->exec('DELETE FROM password_reset_requests WHERE requested_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $pdo->exec('DELETE FROM password_reset_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
    }

    if ((int)($counts['ip_requests'] ?? 0) >= RESET_MAX_REQUESTS
        || (int)($counts['email_requests'] ?? 0) >= RESET_MAX_REQUESTS) {
        resetResponse();
    }

    $userStatement = $pdo->prepare('SELECT id, email, full_name FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1');
    $userStatement->execute([$email]);
    $user = $userStatement->fetch();
    if (!$user) {
        resetResponse();
    }

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $pdo->beginTransaction();
    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
        ->execute([(int)$user['id']]);
    $pdo->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . RESET_TOKEN_MINUTES . ' MINUTE))'
    )->execute([(int)$user['id'], $tokenHash]);
    $pdo->commit();

    $baseUrl = passwordResetBaseUrl();
    if ($baseUrl !== null) {
        $resetUrl = $baseUrl . '/reset-password.php?token=' . rawurlencode($rawToken);
        $subject = 'Reset your Nuru password';
        $body = "Hello " . (string)$user['full_name'] . ",\n\n"
            . "Use the link below within " . RESET_TOKEN_MINUTES . " minutes to reset your Nuru password:\n"
            . $resetUrl . "\n\nIf you did not request this, you can ignore this message.";
        nuruSendMail(
            (string)$user['email'],
            $subject,
            $body,
            false,
            'password_reset',
            (int)$user['id']
        );
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Password reset request failed: ' . $error->getMessage());
}

resetResponse();

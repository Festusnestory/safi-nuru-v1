<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\View;

/**
 * Login/logout/password-reset/password-change. Ported near-verbatim from
 * authentication-login.php + config/login.php + config/logout.php +
 * reset-password.php + change-password.php + config/request-password-reset.php
 * - these are security-sensitive (rate limiting, lockouts, CSRF, session
 * regeneration) so behavior is preserved exactly rather than re-modeled.
 * Not extending App\Core\Controller since several entry points here run
 * before any session/role exists (unlike the rest of the admin app).
 */
final class AuthController
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const LOGIN_RATE_WINDOW_MINUTES = 15;
    private const MAX_EMAIL_FAILURES = 10;
    private const MAX_IP_FAILURES = 20;
    private const DUMMY_PASSWORD_HASH = '$2y$10$oDo94JFgoDJcn37bzZQJXOWGA2qcYlo9s4EbNCnoX88yKEAUNGUVu';
    private const RESET_WINDOW_MINUTES = 15;
    private const RESET_MAX_REQUESTS = 5;
    private const RESET_TOKEN_MINUTES = 30;

    private \PDO $pdo;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        global $pdo;
        require \NURU_MATERIAL . '/config/pdo.php';
        $this->pdo = $pdo;
        require_once \NURU_MATERIAL . '/config/role_helpers.php';
    }

    /** Self-submitting entry point for the clean /login route: GET renders, POST authenticates. */
    public function loginPage(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->login();
            return;
        }
        $this->showLogin();
    }

    public function showLogin(): void
    {
        require_once \NURU_MATERIAL . '/config/turnstile.php';
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        View::render('admin.auth.login', [
            'passwordResetCsrf' => \csrfToken('password_reset_request'),
            'baseUrl' => \App\Core\Router::basePath(),
        ]);
    }

    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        require_once \NURU_MATERIAL . '/config/turnstile.php';

        $pdo = $this->pdo;

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->loginResponse(405, 'error', 'Method not allowed.');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $password === '' || strlen($password) > 4096) {
            $this->loginResponse(422, 'error', 'Enter a valid email address and password.');
        }

        if (!\verifyTurnstile($_POST['cf-turnstile-response'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null)) {
            $this->loginResponse(403, 'error', 'CAPTCHA verification failed. Please try again.');
        }

        $emailHash = hash('sha256', $email);
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        try {
            $pdo->beginTransaction();

            $rateStatement = $pdo->prepare(
                'SELECT
                    SUM(CASE WHEN ip_hash = ? THEN 1 ELSE 0 END) AS ip_failures,
                    SUM(CASE WHEN email_hash = ? THEN 1 ELSE 0 END) AS email_failures
                 FROM login_attempts
                 WHERE succeeded = 0
                   AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . self::LOGIN_RATE_WINDOW_MINUTES . ' MINUTE)'
            );
            $rateStatement->execute([$ipHash, $emailHash]);
            $rate = $rateStatement->fetch(\PDO::FETCH_ASSOC) ?: [];
            if ((int) ($rate['ip_failures'] ?? 0) >= self::MAX_IP_FAILURES || (int) ($rate['email_failures'] ?? 0) >= self::MAX_EMAIL_FAILURES) {
                $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, succeeded) VALUES (?, ?, 0)')->execute([$emailHash, $ipHash]);
                $pdo->commit();
                password_verify($password, self::DUMMY_PASSWORD_HASH);
                $this->loginResponse(429, 'error', 'Sign-in is temporarily unavailable. Try again later.');
            }

            $statement = $pdo->prepare(
                'SELECT id, email, password_hash, full_name, role, is_active, must_change_password,
                        failed_login_attempts, locked_until, session_version
                 FROM admin_users WHERE email = ? LIMIT 1 FOR UPDATE'
            );
            $statement->execute([$email]);
            $user = $statement->fetch(\PDO::FETCH_ASSOC) ?: null;

            $hashToVerify = $user ? (string) $user['password_hash'] : self::DUMMY_PASSWORD_HASH;
            $passwordMatches = password_verify($password, $hashToVerify);
            $isLocked = $user && !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time();
            $isActive = $user && (int) $user['is_active'] === 1;

            if (!$user || !$passwordMatches || !$isActive || $isLocked) {
                if ($user && $isActive && !$isLocked && !$passwordMatches) {
                    $attempts = (int) $user['failed_login_attempts'] + 1;
                    if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                        $pdo->prepare('UPDATE admin_users SET failed_login_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL ' . self::LOCKOUT_MINUTES . ' MINUTE) WHERE id = ?')
                            ->execute([(int) $user['id']]);
                    } else {
                        $pdo->prepare('UPDATE admin_users SET failed_login_attempts = ? WHERE id = ?')
                            ->execute([$attempts, (int) $user['id']]);
                    }
                }
                $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, succeeded) VALUES (?, ?, 0)')->execute([$emailHash, $ipHash]);
                $pdo->commit();
                $this->loginResponse($isLocked ? 429 : 401, 'error', $isLocked ? 'Sign-in is temporarily unavailable. Try again later.' : 'Invalid email or password.');
            }

            $newHash = password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)
                ? password_hash($password, PASSWORD_DEFAULT)
                : (string) $user['password_hash'];
            $pdo->prepare('UPDATE admin_users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?')
                ->execute([$newHash, (int) $user['id']]);
            $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, succeeded) VALUES (?, ?, 1)')->execute([$emailHash, $ipHash]);
            if (random_int(1, 100) === 1) {
                $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
            }
            $pdo->commit();

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['email'] = (string) $user['email'];
            $_SESSION['full_name'] = (string) $user['full_name'];
            $_SESSION['role'] = (string) $user['role'];
            $_SESSION['must_change_password'] = (bool) $user['must_change_password'];
            $_SESSION['session_version'] = (int) $user['session_version'];

            $target = $_SESSION['must_change_password'] ? 'change-password.php' : match ($user['role']) {
                'buyer' => 'dashboard_1.php',
                'manager' => 'dashboard_2.php',
                'agent_coordinator' => 'dashboard_3.php',
                'agent_consultant' => 'dashboard_4.php',
                'seller' => 'dashboard_5.php',
                'admin' => 'admin.php',
                default => 'authentication-login.php',
            };
            // Absolute so the redirect works whether this request landed via
            // the legacy html/material/authentication-login.php URL or the
            // clean /login route (public/index.php) - a relative target
            // resolves differently depending on which one served the page.
            // legacyUrl() sends admin.php to its migrated /admin/dashboard
            // route; the rest (still-unmigrated dashboard_N.php,
            // change-password.php) fall back to their absolute legacy path.
            $redirect = \App\Core\Router::legacyUrl($target);

            $this->loginResponse(200, 'success', 'Login successful.', $redirect);
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Login failed: ' . $error->getMessage());
            $this->loginResponse(500, 'error', 'Server error. Please try again later.');
        }
    }

    private function loginResponse(int $httpStatus, string $status, string $message, ?string $redirect = null): never
    {
        http_response_code($httpStatus);
        $payload = ['status' => $status, 'message' => $message];
        if ($redirect !== null) {
            $payload['redirect'] = $redirect;
        }
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();

        if (isset($_COOKIE['nuru_auth'])) {
            setcookie(
                'nuru_auth',
                '',
                time() - 3600,
                '/',
                '',
                !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                true
            );
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: ' . \App\Core\Router::basePath() . '/login');
        exit;
    }

    public function showResetPassword(): void
    {
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');

        $pdo = $this->pdo;
        $rawToken = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
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
            $password = (string) ($_POST['password'] ?? '');
            $confirmation = (string) ($_POST['password_confirmation'] ?? '');

            if (!\validCsrfToken($_POST['csrf_token'] ?? null, 'password_reset')) {
                $errors[] = 'Your session has expired. Reload the reset link and try again.';
            } elseif (strlen($password) < 12 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errors[] = 'Use at least 12 characters, including letters and numbers.';
            } elseif (!hash_equals($password, $confirmation)) {
                $errors[] = 'The password confirmation does not match.';
            } elseif (password_verify($password, (string) $tokenRow['password_hash'])) {
                $errors[] = 'Choose a password that is different from your current password.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $lock = $pdo->prepare(
                        'SELECT id FROM password_reset_tokens
                         WHERE id = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE'
                    );
                    $lock->execute([(int) $tokenRow['id']]);
                    if (!$lock->fetchColumn()) {
                        throw new \RuntimeException('Reset token is no longer active.');
                    }

                    $pdo->prepare(
                        "UPDATE admin_users
                         SET password_hash = ?, must_change_password = 0,
                             failed_login_attempts = 0, locked_until = NULL,
                             remember_token = '', remember_token_expires = '',
                             session_version = session_version + 1
                         WHERE id = ?"
                    )->execute([password_hash($password, PASSWORD_DEFAULT), (int) $tokenRow['user_id']]);
                    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')
                        ->execute([(int) $tokenRow['id']]);
                    $pdo->commit();

                    unset($_SESSION['_csrf_tokens']['password_reset']);
                    session_regenerate_id(true);
                    $complete = true;
                    $tokenRow = null;
                } catch (\Throwable $error) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Password reset failed: ' . $error->getMessage());
                    $errors[] = 'This reset link is no longer valid. Request a new one.';
                    $tokenRow = null;
                }
            }
        }

        $csrf = $tokenRow ? \csrfToken('password_reset') : '';
        View::render('admin.auth.reset-password', [
            'complete' => $complete,
            'tokenRow' => $tokenRow,
            'errors' => $errors,
            'rawToken' => $rawToken,
            'csrf' => $csrf,
        ]);
    }

    public function requestPasswordReset(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        require_once \NURU_MATERIAL . '/config/mailer.php';

        $pdo = $this->pdo;

        $resetResponse = static function (): never {
            echo json_encode([
                'status' => 'success',
                'message' => 'If an active account matches that email, reset instructions will be sent shortly.',
            ]);
            exit;
        };

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            exit;
        }

        if (!\validCsrfToken($_POST['csrf_token'] ?? null, 'password_reset_request')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Your session has expired. Reload the page and try again.']);
            exit;
        }
        unset($_SESSION['_csrf_tokens']['password_reset_request']);

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $resetResponse();
        }

        $emailHash = hash('sha256', $email);
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        try {
            $rate = $pdo->prepare(
                'SELECT
                    SUM(ip_hash = :ip_hash) AS ip_requests,
                    SUM(email_hash = :email_hash) AS email_requests
                 FROM password_reset_requests
                 WHERE requested_at >= DATE_SUB(NOW(), INTERVAL ' . self::RESET_WINDOW_MINUTES . ' MINUTE)'
            );
            $rate->execute(['ip_hash' => $ipHash, 'email_hash' => $emailHash]);
            $counts = $rate->fetch() ?: [];

            $pdo->prepare('INSERT INTO password_reset_requests (email_hash, ip_hash) VALUES (?, ?)')
                ->execute([$emailHash, $ipHash]);

            if (random_int(1, 100) === 1) {
                $pdo->exec('DELETE FROM password_reset_requests WHERE requested_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
                $pdo->exec('DELETE FROM password_reset_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
            }

            if ((int) ($counts['ip_requests'] ?? 0) >= self::RESET_MAX_REQUESTS
                || (int) ($counts['email_requests'] ?? 0) >= self::RESET_MAX_REQUESTS) {
                $resetResponse();
            }

            $userStatement = $pdo->prepare('SELECT id, email, full_name FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1');
            $userStatement->execute([$email]);
            $user = $userStatement->fetch();
            if (!$user) {
                $resetResponse();
            }

            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
                ->execute([(int) $user['id']]);
            $pdo->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . self::RESET_TOKEN_MINUTES . ' MINUTE))'
            )->execute([(int) $user['id'], $tokenHash]);
            $pdo->commit();

            $baseUrl = $this->passwordResetBaseUrl();
            if ($baseUrl !== null) {
                $resetUrl = $baseUrl . '/reset-password.php?token=' . rawurlencode($rawToken);
                $subject = 'Reset your Nuru password';
                $body = "Hello " . (string) $user['full_name'] . ",\n\n"
                    . "Use the link below within " . self::RESET_TOKEN_MINUTES . " minutes to reset your Nuru password:\n"
                    . $resetUrl . "\n\nIf you did not request this, you can ignore this message.";
                \nuruSendMail(
                    (string) $user['email'],
                    $subject,
                    $body,
                    false,
                    'password_reset',
                    (int) $user['id']
                );
            }
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Password reset request failed: ' . $error->getMessage());
        }

        $resetResponse();
    }

    private function passwordResetBaseUrl(): ?string
    {
        $configured = trim((string) (getenv('NURU_APP_URL') ?: ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host);
        if (!in_array($hostWithoutPort, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            || !preg_match('/^[a-z0-9.\-:\[\]]+$/', $host)) {
            error_log('NURU_APP_URL must be configured before password reset emails can be sent.');
            return null;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $materialPosition = strpos($script, '/html/material');
        $portal = $materialPosition === false ? '/html/material' : substr($script, 0, $materialPosition + 14);

        return $scheme . '://' . $host . $portal;
    }

    public function changePassword(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: authentication-login.php');
            exit;
        }

        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');

        $pdo = $this->pdo;
        $current = $pdo->prepare('SELECT role, password_hash, is_active, must_change_password, session_version FROM admin_users WHERE id = ? LIMIT 1');
        $current->execute([(int) $_SESSION['user_id']]);
        $currentUser = $current->fetch(\PDO::FETCH_ASSOC);
        $sessionIsStale = isset($_SESSION['session_version'])
            && (int) $_SESSION['session_version'] !== (int) ($currentUser['session_version'] ?? 0);
        if (!$currentUser || !(int) $currentUser['is_active'] || $sessionIsStale) {
            $_SESSION = [];
            session_destroy();
            header('Location: authentication-login.php?account=inactive');
            exit;
        }
        $_SESSION['role'] = (string) $currentUser['role'];
        $_SESSION['must_change_password'] = (int) $currentUser['must_change_password'];
        $_SESSION['session_version'] = (int) $currentUser['session_version'];
        $currentHash = (string) $currentUser['password_hash'];

        $errors = [];
        $mustChangePassword = !empty($_SESSION['must_change_password']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $confirmation = (string) ($_POST['password_confirmation'] ?? '');

            if (!\validCsrfToken($_POST['csrf_token'] ?? null, 'password_change')) {
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
                    $statement->execute([password_hash($password, PASSWORD_DEFAULT), (int) $_SESSION['user_id']]);
                    $_SESSION['must_change_password'] = false;
                    $_SESSION['session_version'] = (int) $currentUser['session_version'] + 1;
                    session_regenerate_id(true);

                    $target = match ($_SESSION['role'] ?? '') {
                        'buyer' => 'dashboard_1.php',
                        'manager' => 'dashboard_2.php',
                        'agent_coordinator' => 'dashboard_3.php',
                        'agent_consultant' => 'dashboard_4.php',
                        'seller' => 'dashboard_5.php',
                        'admin' => 'admin.php',
                        default => 'authentication-login.php',
                    };
                    header('Location: ' . \App\Core\Router::legacyUrl($target));
                    exit;
                }
            }
        }

        View::render('admin.auth.change-password', [
            'mustChangePassword' => $mustChangePassword,
            'errors' => $errors,
            'csrfToken' => \csrfToken('password_change'),
        ]);
    }
}

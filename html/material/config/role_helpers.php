<?php
/**
 * Shared role/access-control helpers. Session-based, matches the existing
 * app convention (no separate auth framework). Include from any page at
 * html/material/ root after session_start().
 */

require_once __DIR__ . '/../../../app/autoload.php';

function currentRole(): string
{
    return $_SESSION['role'] ?? '';
}

/** Build an absolute-in-app redirect that also works from nested folders. */
function portalPath(string $path): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/html/material/');
    $materialMarker = '/html/material';
    $materialPosition = strpos($scriptName, $materialMarker);
    if ($materialPosition !== false) {
        $basePath = substr($scriptName, 0, $materialPosition + strlen($materialMarker));
        return $basePath . '/' . ltrim($path, '/');
    }

    // A request dispatched through the app/Core/Router front controller
    // (public/index.php) has no /html/material in SCRIPT_NAME even though
    // the target page still physically lives there - route to it explicitly.
    $routerMarker = '/public/index.php';
    $routerPosition = strpos($scriptName, $routerMarker);
    if ($routerPosition !== false) {
        $appBase = substr($scriptName, 0, $routerPosition);
        return $appBase . '/html/material/' . ltrim($path, '/');
    }

    return '/' . ltrim($path, '/');
}

/** Admin or manager: full operational visibility (manager excludes System pages only). */
function isFullAccess(): bool
{
    return in_array(currentRole(), ['admin', 'manager'], true);
}

/** Human-readable role label for shared breadcrumbs and navigation. */
function roleDisplayName(): string
{
    return match (currentRole()) {
        'admin' => 'System Administrator',
        'manager' => 'Manager',
        'agent_coordinator' => 'Agent Coordinator',
        'agent_consultant' => 'Consultant',
        'buyer' => 'Buyer',
        'seller' => 'Seller',
        default => 'Nuru User',
    };
}

/**
 * Default landing page for the authoritative session role, resolved to a
 * full URL - the migrated admin dashboard's clean /admin/dashboard route
 * where one exists (via Router::legacyUrl()), the absolute legacy path
 * otherwise. Callers should use this directly; it is not a bare filename.
 */
function roleHomeRoute(): string
{
    $target = match (currentRole()) {
        'admin' => 'admin.php',
        'manager' => 'dashboard_2.php',
        'agent_coordinator' => 'dashboard_3.php',
        'agent_consultant' => 'dashboard_4.php',
        'buyer' => 'dashboard_1.php',
        'seller' => 'dashboard_5.php',
        default => 'authentication-login.php',
    };
    return \App\Core\Router::legacyUrl($target);
}

/** Explain an authorization redirect on the destination dashboard. */
function renderAccessDeniedNotice(): void
{
    if (($_GET['access'] ?? '') !== 'denied') {
        return;
    }

    echo '<div class="alert alert-warning" role="alert">'
        . '<strong>Access denied.</strong> Your account does not have permission to open that page.'
        . '</div>';
}

/** Redirect away (matching the app's existing login-redirect pattern) unless role is allowed. */
function requireRole(array $allowedRoles): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . portalPath('authentication-login.php'));
        exit;
    }

    // Treat the database as the source of truth on every protected request.
    // Without this check, a deactivated or demoted account can keep using an
    // already-open session until it happens to log out.
    global $pdo;
    if (!($pdo instanceof PDO)) {
        require __DIR__ . '/pdo.php';
    }

    $authUser = $pdo->prepare('SELECT role, is_active, must_change_password, session_version FROM admin_users WHERE id = ? LIMIT 1');
    $authUser->execute([(int)$_SESSION['user_id']]);
    $authoritativeUser = $authUser->fetch(PDO::FETCH_ASSOC);

    $sessionIsStale = isset($_SESSION['session_version'])
        && (int)$_SESSION['session_version'] !== (int)($authoritativeUser['session_version'] ?? 0);
    if (!$authoritativeUser || !(int)$authoritativeUser['is_active'] || $sessionIsStale) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        header('Location: ' . portalPath('authentication-login.php') . '?account=inactive');
        exit;
    }

    $_SESSION['role'] = (string)$authoritativeUser['role'];
    $_SESSION['must_change_password'] = (int)$authoritativeUser['must_change_password'];
    $_SESSION['session_version'] = (int)$authoritativeUser['session_version'];

    if (!in_array(currentRole(), $allowedRoles, true)) {
        header('Location: ' . roleHomeRoute() . '?access=denied', true, 303);
        exit;
    }

    if (!empty($_SESSION['must_change_password'])) {
        header('Location: ' . portalPath('change-password.php'));
        exit;
    }

    // Protected pages in this application routinely show financial and
    // identity data. Prevent that data from being retained in shared browser
    // or proxy caches, and make the standard browser hardening headers
    // consistent for every page that uses the access-control helper.
    header('Cache-Control: no-store, private, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

/** Create/retrieve a session-bound CSRF token for one named operation. */
function csrfToken(string $scope): string
{
    if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
        $_SESSION['_csrf_tokens'] = [];
    }

    if (empty($_SESSION['_csrf_tokens'][$scope])) {
        $_SESSION['_csrf_tokens'][$scope] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_tokens'][$scope];
}

/** Verify a token without accepting a token minted for a different operation. */
function validCsrfToken(?string $token, string $scope): bool
{
    $expected = $_SESSION['_csrf_tokens'][$scope] ?? null;
    return is_string($token) && is_string($expected) && hash_equals($expected, $token);
}

/**
 * Authoritative role check for JSON/download endpoints that need to return
 * their own 403 response instead of following requireRole()'s page redirect.
 */
function sessionHasAuthoritativeRole(array $allowedRoles): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    global $pdo;
    if (!($pdo instanceof PDO)) {
        require __DIR__ . '/pdo.php';
    }
    $statement = $pdo->prepare('SELECT role, is_active, must_change_password, session_version FROM admin_users WHERE id = ? LIMIT 1');
    $statement->execute([(int)$_SESSION['user_id']]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$user || !(int)$user['is_active']) {
        return false;
    }
    if (isset($_SESSION['session_version']) && (int)$_SESSION['session_version'] !== (int)$user['session_version']) {
        return false;
    }
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['must_change_password'] = (int)$user['must_change_password'];
    $_SESSION['session_version'] = (int)$user['session_version'];
    return !(int)$user['must_change_password'] && in_array((string)$user['role'], $allowedRoles, true);
}

/**
 * Resolve the logged-in agent_coordinator's own agents.id from their
 * admin_users.id (session user_id). agent_task_allocations.agent_id stores
 * agents.id, NOT admin_users.id - these can differ (they only coincide for
 * the first seeded agent). Returns null if the session user has no agent record.
 */
function resolveAgentId(PDO $pdo, int $userId): ?int
{
    static $cache = [];
    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    $stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $agentId = $stmt->fetchColumn();

    return $cache[$userId] = ($agentId !== false ? (int)$agentId : null);
}

/**
 * Render a small badge showing days remaining on a status countdown.
 * Green when comfortably within the period, amber within 5 days, red if
 * somehow still rendered at/past zero (should be rare - the lazy sweep in
 * config/property_lifecycle.php normally flips these to 'expired' first).
 * Returns '' for statuses without an active deadline.
 */
function renderCountdownBadge(string $status, ?string $deadline): string
{
    if (!$deadline || !in_array($status, ['under_offer', 'sold'], true)) {
        return '';
    }

    $daysLeft = (int)ceil((strtotime($deadline) - time()) / 86400);

    if ($daysLeft <= 0) {
        return '<span class="badge bg-danger">Expiring today</span>';
    }

    $tone = $daysLeft <= 5 ? 'bg-warning text-dark' : 'bg-success';
    $label = $status === 'under_offer' ? 'Offer expires' : 'Sale expires';

    return '<span class="badge ' . $tone . '">' . htmlspecialchars($label) . ' in ' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . '</span>';
}

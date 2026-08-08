<?php
/**
 * PortalAuth: thin wrapper over the same session the rest of the app uses
 * (config/login.php sets $_SESSION['user_id'], ['email'], ['full_name'], ['role']).
 * No separate login system - staff and buyer/manager accounts share one session.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class PortalAuth
{
    /** Permission name => roles allowed to hold it. */
    private static array $permissionMap = [
        'manage_agents'  => ['admin', 'agent_coordinator'],
        'view_agents'    => ['admin', 'agent_coordinator', 'manager'],
        'approve_agents' => ['admin'],
        'manage_buyers'  => ['admin', 'agent_coordinator', 'manager'],
        'manage_sellers' => ['admin', 'agent_coordinator', 'manager'],
    ];

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: ' . self::portalPath('authentication-login.php'));
            exit;
        }

        if (!empty($_SESSION['must_change_password'])) {
            header('Location: ' . self::portalPath('change-password.php'));
            exit;
        }
    }

    private static function portalPath(string $path): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/html/material/');
        $marker = '/html/material';
        $position = strpos($scriptName, $marker);
        $basePath = $position === false ? '' : substr($scriptName, 0, $position + strlen($marker));

        return $basePath . '/' . ltrim($path, '/');
    }

    public static function getCurrentUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id'        => $_SESSION['user_id'],
            'email'     => $_SESSION['email'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role'      => $_SESSION['role'] ?? '',
        ];
    }

    public static function hasPermission(string $permission): bool
    {
        $role = $_SESSION['role'] ?? '';
        $allowedRoles = self::$permissionMap[$permission] ?? [];

        return in_array($role, $allowedRoles, true);
    }

    public static function isSystemHealthy(): bool
    {
        return self::isAuthenticated();
    }
}

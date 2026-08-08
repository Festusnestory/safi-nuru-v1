<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Wraps the existing html/material/config/role_helpers.php function set
 * (requireRole(), currentRole(), csrfToken(), etc.) instead of
 * re-implementing access control. Those functions live in the global
 * namespace and are called with a leading backslash from here.
 */
final class Auth
{
    private static bool $loaded = false;

    public static function boot(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        require_once NURU_MATERIAL . '/config/role_helpers.php';
    }

    public static function requireRole(array $allowedRoles): void
    {
        self::boot();
        \requireRole($allowedRoles);
    }

    public static function currentRole(): string
    {
        self::boot();
        return \currentRole();
    }

    public static function isFullAccess(): bool
    {
        self::boot();
        return \isFullAccess();
    }

    public static function csrfToken(string $scope): string
    {
        self::boot();
        return \csrfToken($scope);
    }

    public static function validCsrfToken(?string $token, string $scope): bool
    {
        self::boot();
        return \validCsrfToken($token, $scope);
    }
}

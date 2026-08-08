<?php
declare(strict_types=1);

namespace App\Core;

final class Bootstrap
{
    private static bool $booted = false;

    /**
     * Matches the preamble copy-pasted at the top of ~50 legacy admin pages:
     * session_start() first, then bail to the login page if there is no
     * session at all (requireRole() below handles the finer-grained
     * role/active/session-version checks once a session exists).
     */
    public static function init(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function requireSession(): void
    {
        self::init();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . self::portalPath('authentication-login.php'));
            exit;
        }
    }

    public static function portalPath(string $path): string
    {
        Auth::boot();
        return \portalPath($path);
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around the existing html/material/config/pdo.php connection
 * script. That script is idempotent (it checks `isset($pdo) && $pdo
 * instanceof PDO` and returns early), which is the same guard every legacy
 * page already relies on via `include("./config/pdo.php")` - reusing it here
 * instead of writing a parallel connection routine keeps credential
 * resolution (env vars -> out-of-webroot config -> local XAMPP defaults)
 * and $nuruSettings/timezone loading byte-for-byte identical.
 */
final class Database
{
    public static function connection(): \PDO
    {
        global $pdo;
        require NURU_MATERIAL . '/config/pdo.php';
        return $pdo;
    }

    public static function settings(): array
    {
        global $nuruSettings;
        self::connection();
        return $nuruSettings ?? [];
    }
}

<?php
/**
 * Shared portal utility functions.
 */

require_once __DIR__ . '/../config/pdo.php';

if (!function_exists('logActivity')) {
    function logActivity(?int $userId, string $action, string $description = '', string $category = 'general', string $level = 'info'): void
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO activity_log (user_id, level, category, action, description, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $level,
                $category,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (PDOException $e) {
            error_log('logActivity failed: ' . $e->getMessage());
        }
    }
}

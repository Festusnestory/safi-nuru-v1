<?php
/**
 * SecurityIntegration: CSRF, input sanitization, file upload validation,
 * lightweight rate limiting, and audit logging for the self-service
 * application portal (agent/buyer/seller). Session-based, no separate
 * infrastructure beyond the app's existing $pdo connection.
 */

require_once __DIR__ . '/../config/pdo.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SecurityIntegration
{
    private const MAX_REQUESTS_PER_WINDOW = 60;
    private const RATE_WINDOW_SECONDS = 60;
    private const ALLOWED_UPLOAD_TYPES = [
        'application/pdf'  => 'pdf',
        'image/jpeg'       => 'jpg',
        'image/png'        => 'png',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10MB

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Simple per-session request-rate cap; returns false (and emits 429) if exceeded. */
    public static function processRequest(?string $action, string $method): bool
    {
        $now = time();
        $bucket = $_SESSION['_rate_bucket'] ?? ['window_start' => $now, 'count' => 0];

        if ($now - $bucket['window_start'] >= self::RATE_WINDOW_SECONDS) {
            $bucket = ['window_start' => $now, 'count' => 0];
        }

        $bucket['count']++;
        $_SESSION['_rate_bucket'] = $bucket;

        if ($bucket['count'] > self::MAX_REQUESTS_PER_WINDOW) {
            self::logEvent('warning', 'security', 'RATE_LIMIT_EXCEEDED', "Rate limit exceeded for action: $action");
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Too many requests, please slow down']);
            return false;
        }

        return true;
    }

    public static function logEvent(string $level, string $category, string $action, string $description = '', array $context = []): void
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO activity_log (user_id, level, category, action, description, context, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $level,
                $category,
                $action,
                $description,
                $context ? json_encode($context) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (PDOException $e) {
            error_log('SecurityIntegration::logEvent failed: ' . $e->getMessage());
        }
    }

    public static function generateCSRFToken(string $actionName): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_tokens'][$actionName] = $token;
        return $token;
    }

    public static function validateCSRFToken(?string $token, string $actionName): bool
    {
        $expected = $_SESSION['_csrf_tokens'][$actionName] ?? null;
        return $token !== null && $expected !== null && hash_equals($expected, $token);
    }

    public static function sanitizeInput($data, string $context = 'general')
    {
        if (is_array($data)) {
            return array_map(fn($v) => self::sanitizeInput($v, $context), $data);
        }

        $data = trim((string)$data);

        switch ($context) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'phone':
                return preg_replace('/[^0-9+\-\s()]/', '', $data);
            case 'alphanumeric':
                return preg_replace('/[^A-Za-z0-9]/', '', $data);
            default:
                return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
    }

    public static function validateFileUpload(array $file): array
    {
        $errors = [];
        $warnings = [];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'errors' => ['Upload failed'], 'warnings' => [], 'quarantined' => false];
        }

        if ($file['size'] > self::MAX_UPLOAD_BYTES) {
            $errors[] = 'File exceeds the 10MB size limit';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_UPLOAD_TYPES[$mimeType])) {
            $errors[] = 'File type not allowed (only PDF, JPG, PNG accepted)';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings, 'quarantined' => false];
        }

        $extension = self::ALLOWED_UPLOAD_TYPES[$mimeType];
        $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;

        return [
            'success'       => true,
            'errors'        => [],
            'warnings'      => $warnings,
            'quarantined'   => false,
            'safe_filename' => $safeFilename,
        ];
    }

    public static function getMetrics(string $period = '24h'): array
    {
        global $pdo;

        $intervals = ['1h' => '1 HOUR', '24h' => '1 DAY', '7d' => '7 DAY', '30d' => '30 DAY'];
        $interval = $intervals[$period] ?? '1 DAY';

        $stmt = $pdo->prepare(
            "SELECT category, level, COUNT(*) as count
             FROM activity_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)
             GROUP BY category, level"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function healthCheck(): array
    {
        global $pdo;

        $dbHealthy = false;
        try {
            $pdo->query('SELECT 1');
            $dbHealthy = true;
        } catch (PDOException $e) {
            $dbHealthy = false;
        }

        return [
            'status' => $dbHealthy ? 'healthy' : 'unhealthy',
            'checks' => ['database' => $dbHealthy ? 'healthy' : 'unhealthy'],
        ];
    }
}

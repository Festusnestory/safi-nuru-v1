<?php
declare(strict_types=1);

/**
 * Tamper-evident identifiers for links to sensitive records. IDs are not
 * confidential; authorization still runs on every destination page. The
 * HMAC prevents callers from changing an ID without knowing the server key.
 */
function portalIdKey(): string
{
    $configured = (string)(getenv('NURU_ID_TOKEN_KEY') ?: getenv('NURU_ID_SECRET_KEY') ?: '');
    if ($configured === '') {
        // Shared hosts do not always expose environment variables to PHP-FPM.
        // Accept a production-only secret file alongside (never inside) the
        // public document root, mirroring the database-runtime configuration.
        $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
        $keyFile = trim((string)(getenv('NURU_ID_TOKEN_FILE') ?: ''));
        if ($keyFile === '' && $documentRoot !== '') {
            $keyFile = dirname($documentRoot) . DIRECTORY_SEPARATOR . '.nuru-id-token.php';
        }
        if ($keyFile !== '' && is_file($keyFile)) {
            $loadedKey = require $keyFile;
            if (is_string($loadedKey)) {
                $configured = trim($loadedKey);
            } elseif (is_array($loadedKey)) {
                $configured = trim((string)($loadedKey['key'] ?? ''));
            }
        }
    }
    if ($configured !== '') {
        if (strlen($configured) < 32) {
            throw new RuntimeException('NURU_ID_TOKEN_KEY must contain at least 32 characters.');
        }
        return $configured;
    }
    $host = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
    $isLocal = PHP_SAPI === 'cli' || in_array($host, ['', 'localhost', '127.0.0.1', '::1'], true);
    if (!$isLocal) {
        error_log('NURU_ID_TOKEN_KEY is required in production.');
        throw new RuntimeException('Secure record links are not configured.');
    }
    return hash('sha256', 'nuru-local-development-id-token-key', true);
}

function portalEncodeId(int $id): string
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID must be positive.');
    }
    $payload = (string)$id;
    $mac = hash_hmac('sha256', $payload, portalIdKey());
    return rtrim(strtr(base64_encode($payload . '.' . $mac), '+/', '-_'), '=');
}

function portalDecodeId(?string $token): ?int
{
    if (!is_string($token) || $token === '' || strlen($token) > 160) {
        return null;
    }
    $padding = (4 - strlen($token) % 4) % 4;
    $decoded = base64_decode(strtr($token . str_repeat('=', $padding), '-_', '+/'), true);
    if (!is_string($decoded) || !preg_match('/^([1-9][0-9]*)\.([a-f0-9]{64})$/', $decoded, $matches)) {
        return null;
    }
    if (!hash_equals(hash_hmac('sha256', $matches[1], portalIdKey()), $matches[2])) {
        return null;
    }
    $id = filter_var($matches[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : (int)$id;
}

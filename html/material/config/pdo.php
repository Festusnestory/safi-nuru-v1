<?php
// Several legacy pages still include this file after the access-control
// bootstrap has already opened a connection. Reuse it instead of creating a
// second MySQL connection for the same request.
if (isset($pdo) && $pdo instanceof PDO) {
    return;
}

$envHost = getenv('NURU_DB_HOST');
$envDb = getenv('NURU_DB_NAME');
$envUser = getenv('NURU_DB_USER');
$envPass = getenv('NURU_DB_PASSWORD');
$requestHost = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
$remoteAddress = strtolower((string)($_SERVER['REMOTE_ADDR'] ?? ''));
$isLocal = PHP_SAPI === 'cli'
    || (
        in_array($requestHost, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
        && in_array($remoteAddress, ['127.0.0.1', '::1'], true)
    );

// Shared hosts do not always expose process environment variables to PHP-FPM.
// Support a PHP array stored one directory above the public document root so
// production credentials remain outside both the repository and web root.
$runtimeDbConfig = [];
$documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
$runtimeConfigFile = trim((string)(getenv('NURU_CONFIG_FILE') ?: ''));
if ($runtimeConfigFile === '' && $documentRoot !== '') {
    $runtimeConfigFile = dirname($documentRoot) . DIRECTORY_SEPARATOR . '.nuru-db.php';
}
if (!$isLocal && $runtimeConfigFile !== '' && is_file($runtimeConfigFile)) {
    $loadedRuntimeConfig = require $runtimeConfigFile;
    if (is_array($loadedRuntimeConfig)) {
        $runtimeDbConfig = $loadedRuntimeConfig;
    }
}

$configuredHost = $envHost !== false ? $envHost : ($runtimeDbConfig['host'] ?? false);
$configuredDb = $envDb !== false ? $envDb : ($runtimeDbConfig['database'] ?? false);
$configuredUser = $envUser !== false ? $envUser : ($runtimeDbConfig['username'] ?? false);
$configuredPass = $envPass !== false ? $envPass : ($runtimeDbConfig['password'] ?? false);

// Local XAMPP defaults remain convenient for development. A deployed site
// must explicitly provide every database credential; it must never fall back
// to a passwordless root database account.
if (
    !$isLocal
    && (
        $configuredHost === false || trim((string)$configuredHost) === ''
        || $configuredDb === false || trim((string)$configuredDb) === ''
        || $configuredUser === false || trim((string)$configuredUser) === ''
        || $configuredPass === false || (string)$configuredPass === ''
    )
) {
    error_log('Database configuration is incomplete. Set NURU_DB_HOST, NURU_DB_NAME, NURU_DB_USER, and NURU_DB_PASSWORD.');
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
    exit;
}

$host = $configuredHost !== false ? (string)$configuredHost : 'localhost';
$db   = $configuredDb !== false ? (string)$configuredDb : 'nurure';
$user = $configuredUser !== false ? (string)$configuredUser : 'root';
$pass = $configuredPass !== false ? (string)$configuredPass : '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Enable buffered queries
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Never disclose connection details, credentials, hostnames, or SQL
    // driver messages to a browser. The server log retains the diagnostic.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please try again later.']);
    exit;
}

// Application-wide presentation settings. Keep a safe timezone even while
// installing or recovering a database where app_settings may not yet exist.
$nuruSettings = [
    'app_name' => 'Nuru Real Estate',
    'app_timezone' => getenv('NURU_TIMEZONE') ?: 'Africa/Windhoek',
    'smtp_from_email' => getenv('NURU_MAIL_FROM') ?: 'no-reply@nuru.com',
];
try {
    $settingsRows = $pdo->query("SELECT setting_name, setting_value FROM app_settings WHERE setting_name IN ('app_name', 'app_timezone', 'smtp_from_email')")->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($settingsRows as $settingName => $settingValue) {
        if (trim((string)$settingValue) !== '') {
            $nuruSettings[$settingName] = trim((string)$settingValue);
        }
    }
} catch (Throwable $settingsError) {
    error_log('Application settings could not be loaded: ' . $settingsError->getMessage());
}
if (!in_array($nuruSettings['app_timezone'], DateTimeZone::listIdentifiers(), true)) {
    $nuruSettings['app_timezone'] = 'Africa/Windhoek';
}
date_default_timezone_set($nuruSettings['app_timezone']);






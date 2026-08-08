<?php
/**
 * Cloudflare Turnstile CAPTCHA config + server-side verification helper.
 *
 * Keys are injected through TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY.
 * Cloudflare's public test pair is allowed only on localhost, so a missing
 * production configuration fails closed instead of silently making CAPTCHA
 * protection ineffective.
 *
 * TURNSTILE_ENABLED: master on/off switch, set via the TURNSTILE_ENABLED
 * env var (any of "0"/"false"/"off" disables it). Verification is enabled
 * by default everywhere. Localhost uses Cloudflare's public test pair so the
 * complete client/server verification path remains testable; every non-local
 * host fails closed unless production keys are injected privately.
 */

$requestHost = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
$remoteAddress = strtolower((string)($_SERVER['REMOTE_ADDR'] ?? ''));
$isLocalhost = PHP_SAPI === 'cli'
    || (
        in_array($requestHost, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
        && in_array($remoteAddress, ['127.0.0.1', '::1'], true)
    );

// As with database credentials, shared PHP-FPM hosts may not pass process
// environment variables through to web requests. Permit a private PHP array
// one directory above the public document root so live Turnstile keys never
// need to be committed or placed in a web-accessible directory.
$runtimeTurnstileConfig = [];
$documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
$runtimeConfigFile = trim((string)(getenv('NURU_TURNSTILE_CONFIG_FILE') ?: ''));
if ($runtimeConfigFile === '' && $documentRoot !== '') {
    $runtimeConfigFile = dirname($documentRoot) . DIRECTORY_SEPARATOR . '.nuru-turnstile.php';
}
if (!$isLocalhost && $runtimeConfigFile !== '' && is_file($runtimeConfigFile)) {
    $loadedRuntimeConfig = require $runtimeConfigFile;
    if (is_array($loadedRuntimeConfig)) {
        $runtimeTurnstileConfig = $loadedRuntimeConfig;
    }
}

$enabledValue = getenv('TURNSTILE_ENABLED');
if ($enabledValue === false && array_key_exists('enabled', $runtimeTurnstileConfig)) {
    $enabledValue = $runtimeTurnstileConfig['enabled'];
}
$enabledEnv = $enabledValue === false ? '' : strtolower(trim((string)$enabledValue));
$turnstileEnabled = $enabledEnv === ''
    ? true
    : !in_array($enabledEnv, ['0', 'false', 'off'], true);
define('TURNSTILE_ENABLED', $turnstileEnabled);

$siteKeyValue = getenv('TURNSTILE_SITE_KEY');
$secretKeyValue = getenv('TURNSTILE_SECRET_KEY');
$siteKey = trim((string)($siteKeyValue !== false ? $siteKeyValue : ($runtimeTurnstileConfig['site_key'] ?? '')));
$secretKey = trim((string)($secretKeyValue !== false ? $secretKeyValue : ($runtimeTurnstileConfig['secret_key'] ?? '')));

if ($siteKey === '' || $secretKey === '') {
    if ($isLocalhost) {
        $siteKey = '1x00000000000000000000AA';
        $secretKey = '1x0000000000000000000000000000000AA';
    }
}

define('TURNSTILE_SITE_KEY', $siteKey);
define('TURNSTILE_SECRET_KEY', $secretKey);
define('TURNSTILE_CONFIGURED', $siteKey !== '' && $secretKey !== '');
define('TURNSTILE_READY', TURNSTILE_ENABLED && TURNSTILE_CONFIGURED);

/**
 * Verify a Turnstile response token with Cloudflare's siteverify API.
 * Returns true only if Cloudflare confirms the token is valid and unused.
 */
function verifyTurnstile(?string $token, ?string $remoteIp = null): bool
{
    if (!TURNSTILE_ENABLED) {
        return true;
    }

    if (!TURNSTILE_CONFIGURED || empty($token)) {
        if (!TURNSTILE_CONFIGURED) {
            error_log('Turnstile is not configured; refusing CAPTCHA verification.');
        }
        return false;
    }

    $payload = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
    ];
    if ($remoteIp) {
        $payload['remoteip'] = $remoteIp;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Turnstile verification request failed: ' . $curlError);
        return false;
    }

    $result = json_decode($response, true);
    return !empty($result['success']);
}

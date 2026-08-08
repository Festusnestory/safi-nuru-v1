<?php
/**
 * Account-provisioning helpers shared by staff and self-service workflows.
 * A unique high-entropy temporary password replaces the former shared
 * "Welcome" credentials. The account is marked for a mandatory password
 * change before it can reach any application page.
 */

require_once __DIR__ . '/mailer.php';

function generateTemporaryPassword(): string
{
    return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
}

function sendTemporaryCredentialEmail(string $email, string $fullName, string $temporaryPassword): bool
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/html/material/');
    $marker = '/html/material';
    $position = strpos($scriptName, $marker);
    $appPath = $position === false ? '' : substr($scriptName, 0, $position + strlen($marker));
    $baseUrl = rtrim(getenv('NURU_APP_URL') ?: $scheme . '://' . $host . $appPath, '/');
    $loginUrl = $baseUrl . '/authentication-login.php';

    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
    $subject = 'Your Nuru account is ready';
    $message = <<<HTML
<html><body style="font-family:Arial,sans-serif">
<h2>Welcome to Nuru Real Estate</h2>
<p>Dear <strong>{$safeName}</strong>,</p>
<p>Your account has been created. Use the following one-time temporary password to sign in:</p>
<p><strong>Email:</strong> {$safeEmail}<br><strong>Temporary password:</strong> {$safePassword}</p>
<p><a href="{$loginUrl}">Sign in to Nuru</a></p>
<p>You will be required to choose a new password immediately after signing in. Do not share this message.</p>
</body></html>
HTML;

    return nuruSendMail($email, $subject, $message, true, 'temporary_credentials');
}

/**
 * Queue one credential message in process memory until the surrounding
 * database transaction has committed. Temporary credentials are deliberately
 * never persisted to the database, filesystem, session, or application log.
 */
function queueTemporaryCredentialEmail(string $email, string $fullName, string $temporaryPassword): void
{
    if (!isset($GLOBALS['nuruDeferredCredentialEmails']) || !is_array($GLOBALS['nuruDeferredCredentialEmails'])) {
        $GLOBALS['nuruDeferredCredentialEmails'] = [];
    }

    $GLOBALS['nuruDeferredCredentialEmails'][] = [
        'email' => $email,
        'full_name' => $fullName,
        'temporary_password' => $temporaryPassword,
    ];
}

/**
 * Flush the already-rendered receipt to the browser, then perform credential
 * delivery outside the user-visible response path. Hostinger may expose
 * either the standard FastCGI or LiteSpeed request-finishing primitive; the
 * guarded fallback preserves delivery on other SAPIs without changing the
 * transaction result.
 */
function finishResponseAndDeliverQueuedCredentialEmails(string $responseBody): void
{
    $messages = $GLOBALS['nuruDeferredCredentialEmails'] ?? [];
    $GLOBALS['nuruDeferredCredentialEmails'] = [];

    ignore_user_abort(true);
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        // Shared LiteSpeed hosts may not expose litespeed_finish_request(). A
        // complete, explicitly-sized response lets the browser finish as soon
        // as the receipt is emitted even while the mail transport continues.
        @ini_set('zlib.output_compression', '0');
        header('Content-Length: ' . strlen($responseBody));
        header('Connection: close');
        header('X-Accel-Buffering: no');
    }
    echo $responseBody;

    if (!is_array($messages) || $messages === []) {
        return;
    }

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } elseif (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
    } elseif (PHP_SAPI !== 'cli') {
        // Best-effort fallback for SAPIs without an explicit finish primitive.
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();
    }

    foreach ($messages as $message) {
        try {
            $sent = sendTemporaryCredentialEmail(
                (string)($message['email'] ?? ''),
                (string)($message['full_name'] ?? ''),
                (string)($message['temporary_password'] ?? '')
            );
            if (!$sent) {
                error_log('Queued temporary credential delivery was rejected by the mail transport.');
            }
        } catch (Throwable $mailError) {
            // The application is already committed and the receipt already
            // returned. A mail transport failure must never invite a duplicate
            // application retry.
            error_log('Queued temporary credential delivery failed after the response was completed.');
        }
    }
}

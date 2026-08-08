<?php
declare(strict_types=1);

/**
 * Send an application email and retain a content-free delivery audit record.
 * Message bodies, addresses, tokens, and credentials are never persisted.
 */
function nuruSendMail(
    string $recipient,
    string $subject,
    string $body,
    bool $isHtml = false,
    string $purpose = 'general',
    ?int $userId = null
): bool {
    $recipient = strtolower(trim($recipient));
    $purpose = preg_replace('/[^a-z0-9_-]/', '', strtolower($purpose)) ?: 'general';
    $recipientHash = hash('sha256', $recipient);
    $failureCode = null;

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || strlen($recipient) > 255) {
        $sent = false;
        $failureCode = 'invalid_recipient';
    } elseif ($subject === '' || preg_match('/[\r\n]/', $subject)) {
        $sent = false;
        $failureCode = 'invalid_subject';
    } else {
        $configuredFrom = trim((string)(getenv('NURU_MAIL_FROM')
            ?: ($GLOBALS['nuruSettings']['smtp_from_email'] ?? 'no-reply@nuru.com')));
        $from = filter_var($configuredFrom, FILTER_VALIDATE_EMAIL)
            ? $configuredFrom
            : 'no-reply@nuru.com';
        $appName = trim((string)($GLOBALS['nuruSettings']['app_name'] ?? 'Nuru Real Estate'));
        $safeFromName = preg_replace('/[\r\n<>]+/', '', $appName) ?: 'Nuru Real Estate';

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'From: ' . $safeFromName . ' <' . $from . '>',
            'X-Auto-Response-Suppress: All',
        ];

        $sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));
        if (!$sent) {
            $failureCode = 'transport_rejected';
        }
    }

    try {
        if (($GLOBALS['pdo'] ?? null) instanceof PDO) {
            $statement = $GLOBALS['pdo']->prepare(
                'INSERT INTO email_delivery_log
                    (user_id, purpose, recipient_hash, delivery_status, failure_code)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $userId,
                substr($purpose, 0, 50),
                $recipientHash,
                $sent ? 'sent' : 'failed',
                $failureCode,
            ]);
        }
    } catch (Throwable $loggingError) {
        error_log('Email delivery audit could not be recorded.');
    }

    if (!$sent) {
        error_log('Email delivery failed for purpose ' . substr($purpose, 0, 50) . '.');
    }

    return $sent;
}

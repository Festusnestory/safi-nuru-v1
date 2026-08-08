<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\View;
use App\Models\Inquiry;

/**
 * Contact page (GET) + the shared inquiry handler (POST) used by both the
 * contact form and each property detail page's enquiry form. Logic ported
 * as-is from contact.php/inquiry-handler.php - only the DB insert moved
 * into App\Models\Inquiry.
 */
final class ContactController
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        require \NURU_ROOT . '/public/bootstrap.php';
        $this->pdo = $pdo;
    }

    public function show(): void
    {
        $sent = ($_GET['sent'] ?? '') === '1';
        $stateKey = (string) ($_GET['state'] ?? '');
        $contactState = preg_match('/^[a-f0-9]{24}$/', $stateKey)
            && is_array($_SESSION['nuru_contact_form_states'][$stateKey] ?? null)
                ? $_SESSION['nuru_contact_form_states'][$stateKey]
                : [];
        if ($stateKey !== '') {
            unset($_SESSION['nuru_contact_form_states'][$stateKey]);
        }
        $errors = is_array($contactState['errors'] ?? null) ? $contactState['errors'] : [];
        $old = is_array($contactState['old'] ?? null) ? $contactState['old'] : [];
        $globalError = (string) ($contactState['global'] ?? '');

        $now = time();
        foreach ((array) ($_SESSION['_contact_idempotency'] ?? []) as $token => $attempt) {
            if (!is_array($attempt) || (int) ($attempt['time'] ?? 0) < $now - 7200) {
                unset($_SESSION['_contact_idempotency'][$token]);
            }
        }
        foreach ((array) ($_SESSION['nuru_contact_form_states'] ?? []) as $token => $state) {
            if (!is_array($state) || (int) ($state['issued_at'] ?? 0) < $now - 7200) {
                unset($_SESSION['nuru_contact_form_states'][$token]);
            }
        }
        foreach ((array) ($_SESSION['_contact_form_tokens'] ?? []) as $token => $issuedAt) {
            if (!is_int($issuedAt) || $issuedAt < $now - 7200) {
                unset($_SESSION['_contact_form_tokens'][$token]);
            }
        }
        $submissionToken = bin2hex(random_bytes(24));
        $_SESSION['_contact_form_tokens'][$submissionToken] = $now;
        if (count($_SESSION['_contact_form_tokens']) > 20) {
            asort($_SESSION['_contact_form_tokens']);
            $_SESSION['_contact_form_tokens'] = array_slice($_SESSION['_contact_form_tokens'], -20, null, true);
        }

        \renderHeader('Contact', 'Speak with Nuru Real Estate about buying, selling or investing in property in Namibia.', 'contact');
        View::render('public.contact', [
            'sent' => $sent,
            'errors' => $errors,
            'old' => $old,
            'globalError' => $globalError,
            'submissionToken' => $submissionToken,
        ]);
        \renderFooter();
    }

    public function submit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method not allowed');
        }

        $redirectWithState = static function (array $errors, array $old, string $global = ''): never {
            $stateKey = bin2hex(random_bytes(12));
            $_SESSION['nuru_contact_form_states'][$stateKey] = [
                'errors' => $errors,
                'old' => $old,
                'global' => $global,
                'issued_at' => time(),
            ];
            header('Location: contact.php?error=1&state=' . $stateKey, true, 303);
            exit;
        };
        $sameOrigin = static function (): bool {
            $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
            if ($origin === '') {
                return true;
            }
            $requestHost = (string) parse_url('https://' . (string) ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
            return $requestHost !== ''
                && strcasecmp((string) parse_url($origin, PHP_URL_HOST), $requestHost) === 0;
        };

        $old = [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'interest' => (string) ($_POST['interest'] ?? 'buy'),
            'message' => trim((string) ($_POST['message'] ?? '')),
            'consent' => !empty($_POST['consent']),
        ];
        $submissionToken = trim((string) ($_POST['submission_token'] ?? ''));
        if (!preg_match('/^[a-f0-9]{48}$/', $submissionToken)) {
            $redirectWithState([], $old, 'This form session is invalid. Reload the page and try again.');
        }
        $priorSubmission = $_SESSION['_contact_idempotency'][$submissionToken] ?? null;
        if (is_array($priorSubmission) && ($priorSubmission['state'] ?? '') === 'succeeded') {
            header('Location: contact.php?sent=1', true, 303);
            exit;
        }
        if (is_array($priorSubmission) && ($priorSubmission['state'] ?? '') === 'processing'
            && time() - (int) ($priorSubmission['time'] ?? 0) < 120) {
            $redirectWithState([], $old, 'This enquiry is already being processed. Please wait before trying again.');
        }

        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!isset($_SESSION['_contact_form_tokens'][$submissionToken])
            || !$sameOrigin()
            || !$token
            || !hash_equals((string) ($_SESSION['nuru_public_csrf'] ?? ''), $token)) {
            $redirectWithState([], $old, 'Your session expired. Please review the form and submit it again.');
        }
        if (!empty($_POST['website'])) {
            header('Location: contact.php?sent=1', true, 303);
            exit;
        }

        $errors = [];
        $email = filter_var($old['email'], FILTER_VALIDATE_EMAIL);
        $phoneDigits = preg_replace('/\D+/', '', $old['phone']);
        $phoneShapeValid = preg_match('/^\+?[0-9][0-9\s().-]{5,24}$/', $old['phone']) === 1;
        if (mb_strlen($old['full_name']) < 2) {
            $errors['full_name'] = 'Enter your full name.';
        }
        if (!$email) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (!$phoneShapeValid || strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15) {
            $errors['phone'] = 'Enter a valid phone number using 7–15 digits.';
        }
        if (mb_strlen($old['message']) < 5) {
            $errors['message'] = 'Tell us briefly how we can help.';
        }
        if (!$old['consent']) {
            $errors['consent'] = 'Consent is required before we can respond.';
        }
        if ($errors) {
            $redirectWithState($errors, $old);
        }

        $_SESSION['_contact_idempotency'][$submissionToken] = ['state' => 'processing', 'time' => time()];

        $interest = in_array($old['interest'], ['buy', 'sell', 'invest', 'general'], true) ? $old['interest'] : 'general';
        $propertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $appKey = (string) (getenv('NURU_APP_KEY') ?: '');
        $appEnvironment = strtolower((string) (getenv('NURU_APP_ENV') ?: 'development'));
        $requestHost = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
        $requestAddress = strtolower((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $isLocalRequest = $appEnvironment !== 'production'
            && in_array($requestHost, ['localhost', '127.0.0.1', '::1'], true)
            && in_array($requestAddress, ['127.0.0.1', '::1'], true);
        if ($appKey === '' && $isLocalRequest) {
            $appKey = 'nuru-local-development-only-signing-key';
        }
        if (strlen($appKey) < 32) {
            unset($_SESSION['_contact_idempotency'][$submissionToken]);
            error_log('Public inquiry signing key is not configured.');
            $redirectWithState([], $old, 'The enquiry service is temporarily unavailable. Please try again shortly.');
        }

        $ipHash = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), $appKey);
        $model = new Inquiry($this->pdo);
        try {
            if ($model->recentCountForIp($ipHash) >= 5) {
                unset($_SESSION['_contact_idempotency'][$submissionToken]);
                $redirectWithState([], $old, 'Too many enquiries were sent recently. Please try again later.');
            }
            $model->insert([
                'property_id' => $propertyId,
                'full_name' => $old['full_name'],
                'email' => $email,
                'phone' => $old['phone'],
                'interest' => $interest,
                'message' => $old['message'],
                'ip_hash' => $ipHash,
                'source_page' => (string) ($_SERVER['HTTP_REFERER'] ?? ''),
                'submission_token' => $submissionToken,
            ]);
            $_SESSION['_contact_idempotency'][$submissionToken] = ['state' => 'succeeded', 'time' => time()];
            unset($_SESSION['_contact_form_tokens'][$submissionToken]);
            header('Location: contact.php?sent=1', true, 303);
            exit;
        } catch (\Throwable $error) {
            if ($error instanceof \PDOException
                && (($error->errorInfo[0] ?? '') === '23000')
                && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                $_SESSION['_contact_idempotency'][$submissionToken] = ['state' => 'succeeded', 'time' => time()];
                unset($_SESSION['_contact_form_tokens'][$submissionToken]);
                header('Location: contact.php?sent=1', true, 303);
                exit;
            }
            unset($_SESSION['_contact_idempotency'][$submissionToken]);
            $reference = strtoupper(bin2hex(random_bytes(5)));
            error_log("Public inquiry failed [{$reference}]: " . $error->getMessage());
            $redirectWithState(
                [],
                $old,
                "We could not save your enquiry right now. Your details are still here; please try again shortly. Reference: {$reference}."
            );
        }
    }
}

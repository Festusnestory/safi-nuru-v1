<?php
declare(strict_types=1);
require __DIR__ . '/public/bootstrap.php';

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
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin === '') {
        return true;
    }
    $requestHost = (string)parse_url('https://' . (string)($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    return $requestHost !== ''
        && strcasecmp((string)parse_url($origin, PHP_URL_HOST), $requestHost) === 0;
};

$old = [
    'full_name' => trim((string)($_POST['full_name'] ?? '')),
    'email' => trim((string)($_POST['email'] ?? '')),
    'phone' => trim((string)($_POST['phone'] ?? '')),
    'interest' => (string)($_POST['interest'] ?? 'buy'),
    'message' => trim((string)($_POST['message'] ?? '')),
    'consent' => !empty($_POST['consent']),
];
$submissionToken = trim((string)($_POST['submission_token'] ?? ''));
if (!preg_match('/^[a-f0-9]{48}$/', $submissionToken)) {
    $redirectWithState([], $old, 'This form session is invalid. Reload the page and try again.');
}
$priorSubmission = $_SESSION['_contact_idempotency'][$submissionToken] ?? null;
if (is_array($priorSubmission) && ($priorSubmission['state'] ?? '') === 'succeeded') {
    header('Location: contact.php?sent=1', true, 303);
    exit;
}
if (is_array($priorSubmission) && ($priorSubmission['state'] ?? '') === 'processing'
    && time() - (int)($priorSubmission['time'] ?? 0) < 120) {
    $redirectWithState([], $old, 'This enquiry is already being processed. Please wait before trying again.');
}

$token = (string)($_POST['csrf_token'] ?? '');
if (!isset($_SESSION['_contact_form_tokens'][$submissionToken])
    || !$sameOrigin()
    || !$token
    || !hash_equals((string)($_SESSION['nuru_public_csrf'] ?? ''), $token)) {
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
$appKey = (string)(getenv('NURU_APP_KEY') ?: '');
$appEnvironment = strtolower((string)(getenv('NURU_APP_ENV') ?: 'development'));
$requestHost = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
$requestAddress = strtolower((string)($_SERVER['REMOTE_ADDR'] ?? ''));
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

$ipHash = hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''), $appKey);
try {
    $rate = $pdo->prepare('SELECT COUNT(*) FROM public_inquiries WHERE ip_hash = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $rate->execute([':ip' => $ipHash]);
    if ((int)$rate->fetchColumn() >= 5) {
        unset($_SESSION['_contact_idempotency'][$submissionToken]);
        $redirectWithState([], $old, 'Too many enquiries were sent recently. Please try again later.');
    }
    $stmt = $pdo->prepare('INSERT INTO public_inquiries (property_id, full_name, email, phone, interest, message, consent_at, ip_hash, source_page, submission_token) VALUES (:property_id,:name,:email,:phone,:interest,:message,NOW(),:ip,:source,:submission_token)');
    $stmt->execute([
        ':property_id' => $propertyId,
        ':name' => mb_substr($old['full_name'], 0, 120),
        ':email' => $email,
        ':phone' => mb_substr($old['phone'], 0, 40),
        ':interest' => $interest,
        ':message' => mb_substr($old['message'], 0, 1500),
        ':ip' => $ipHash,
        ':source' => mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 255),
        ':submission_token' => $submissionToken,
    ]);
    $_SESSION['_contact_idempotency'][$submissionToken] = ['state' => 'succeeded', 'time' => time()];
    unset($_SESSION['_contact_form_tokens'][$submissionToken]);
    header('Location: contact.php?sent=1', true, 303);
    exit;
} catch (Throwable $error) {
    if ($error instanceof PDOException
        && (($error->errorInfo[0] ?? '') === '23000')
        && (int)($error->errorInfo[1] ?? 0) === 1062) {
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

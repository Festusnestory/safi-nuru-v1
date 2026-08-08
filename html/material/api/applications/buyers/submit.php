<?php
/**
 * File: api/applications/buyers/submit.php
 * Purpose: Save buyer multi-step form with proper error handling and all 7 table inserts
 * Author: MiniMax Agent
 */

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once '../../../config/pdo.php';
require_once '../../security_integration.php';
require_once '../../../config/turnstile.php';
require_once '../../../config/account_provisioning.php';

if (!SecurityIntegration::processRequest('buyer_application_submit', 'POST')) {
    exit;
}

// Log errors for debugging, but never echo them into the response - this
// endpoint returns JSON, and a PHP warning/notice printed mid-response
// breaks the JSON output while also leaking file paths and line numbers.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * Custom logging function for debugging
 */
function debugLog($message, $level = 'INFO') {
    if (getenv('NURU_DEBUG_LOG') !== '1'
        || strtolower((string)(getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
        return;
    }
    $timestamp = date('Y-m-d H:i:s');
    $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', (string)$message), 0, 1000);
    $logMessage = "[$timestamp] [$level] $safeMessage\n";
    error_log($logMessage, 3, '../../../logs/debug.log');
}

// Never trust the client-supplied filename for the saved path - it's fully
// attacker-controlled and was previously concatenated in directly, allowing
// both path traversal and arbitrary extensions (e.g. a ".php" upload saved
// into the web root). The original name is still stored separately for
// display; only the on-disk name is regenerated.
function safeUploadFilename(string $originalName, array $allowedExtensions): ?string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return null;
    }
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
// doc_type also flows into the upload directory path (uploads/buyers/$docType/)
// and reaches this endpoint as raw JSON, not just from the trusted client UI -
// it must be restricted to a known-safe allowlist too, not just the filename.
const ALLOWED_BUYER_DOC_TYPES = ['id_passport', 'proof_of_income', 'bank_statements', 'marriage_certificate', 'employment_letter', 'additional_documents', 'signature'];
const REQUIRED_BUYER_DECLARATIONS = ['information_accurate', 'false_information_warning', 'verification_consent', 'terms_accepted'];
const MAX_BUYER_DOCUMENT_BYTES = 10 * 1024 * 1024;
$createdBuyerUploadPaths = [];
$buyerIdempotencyKey = '';
$buyerCommitted = false;

function normaliseBuyerText(mixed $value): string {
    $text = trim((string)$value);
    if (class_exists('Normalizer')) {
        $normalised = Normalizer::normalize($text, Normalizer::FORM_C);
        if (is_string($normalised)) {
            $text = $normalised;
        }
    }
    return $text;
}

function validateBuyerText(mixed $value, int $maxLength, string $label): string {
    $text = normaliseBuyerText($value);
    if (mb_strlen($text, 'UTF-8') > $maxLength) {
        throw new InvalidArgumentException("{$label} must not exceed {$maxLength} characters.");
    }
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\p{Cf}/u', $text)) {
        throw new InvalidArgumentException("{$label} contains unsupported control characters.");
    }
    return $text;
}

/** Validate all business-critical fields independently of the browser. */
function validateBuyerApplication(PDO $pdo, array &$data): void {
    $required = [
        'full_name' => 'full name',
        'dateOfBirth' => 'date of birth',
        'id_type' => 'ID type',
        'idNumber' => 'ID/passport number',
        'marital_status' => 'marital status',
        'phone' => 'mobile number',
        'email' => 'email address',
        'region' => 'region',
        'town' => 'town',
        'employment_type' => 'employment type',
        'nationality' => 'nationality',
        'gender' => 'gender',
        'property_type' => 'property type',
        'price_type' => 'price type',
        'signature_location' => 'signature location',
        'signature_date' => 'signature date',
        'signature_type' => 'signature method',
    ];

    foreach ($required as $field => $label) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            throw new InvalidArgumentException("Please provide the {$label}.");
        }
    }

    $textLimits = [
        'firstName' => [100, 'First name'],
        'lastName' => [100, 'Last name'],
        'full_name' => [150, 'Full name'],
        'idNumber' => [13, 'ID/passport number'],
        'email' => [150, 'Email address'],
        'phone' => [20, 'Mobile number'],
        'address' => [200, 'Residential address'],
        'region' => [100, 'Region'],
        'town' => [100, 'Town'],
        'nationality' => [100, 'Nationality'],
        'employer_name' => [150, 'Employer name'],
        'position' => [100, 'Position'],
        'additional_requirements' => [2000, 'Additional requirements'],
        'signature_location' => [100, 'Signature location'],
    ];
    foreach ($textLimits as $field => [$limit, $label]) {
        if (array_key_exists($field, $data) && !is_array($data[$field])) {
            $data[$field] = validateBuyerText($data[$field], $limit, $label);
        }
    }
    foreach ([
        'buyer_spouse' => ['full_name' => 150, 'id_passport' => 20, 'phone' => 20, 'email' => 150],
        'buyer_next_of_kin' => ['full_name' => 150, 'relationship' => 50, 'phone' => 20, 'region' => 100, 'town' => 100, 'address' => 200, 'email' => 150],
    ] as $section => $limits) {
        if (!is_array($data[$section] ?? null)) continue;
        foreach ($limits as $field => $limit) {
            if (array_key_exists($field, $data[$section])) {
                $data[$section][$field] = validateBuyerText($data[$section][$field], $limit, ucwords(str_replace('_', ' ', $field)));
            }
        }
    }

    $data['full_name'] = trim((string)$data['full_name']);
    $data['email'] = strtolower(trim((string)$data['email']));
    $data['idNumber'] = strtoupper(trim((string)$data['idNumber']));
    if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
        throw new InvalidArgumentException('Please provide a valid full name.');
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $data['email'])) {
        throw new InvalidArgumentException('Please provide a valid email address.');
    }

    $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$data['dateOfBirth']);
    $birthErrors = DateTimeImmutable::getLastErrors();
    if (!$birthDate || ($birthErrors !== false && ($birthErrors['warning_count'] || $birthErrors['error_count']))) {
        throw new InvalidArgumentException('Please provide a valid date of birth.');
    }
    $age = $birthDate->diff(new DateTimeImmutable('today'))->y;
    if ($age < 18 || $age > 100) {
        throw new InvalidArgumentException('Applicants must be between 18 and 100 years old.');
    }

    $allowedIdTypes = ['national_id', 'passport', 'drivers_license'];
    if (!in_array($data['id_type'], $allowedIdTypes, true)) {
        throw new InvalidArgumentException('Please select a valid ID type.');
    }
    $validId = $data['id_type'] === 'passport'
        ? preg_match('/^[A-Z0-9]{6,9}$/', $data['idNumber'])
        : preg_match('/^[0-9]{10,13}$/', $data['idNumber']);
    if (!$validId) {
        throw new InvalidArgumentException('Please provide a valid ID or passport number.');
    }

    $phone = preg_replace('/\s+/', '', (string)$data['phone']);
    if (str_starts_with($phone, '+264')) {
        $phone = '0' . substr($phone, 4);
    }
    if (!preg_match('/^0\d{9}$/', $phone)) {
        throw new InvalidArgumentException('Please provide a valid Namibian mobile number.');
    }
    $data['phone'] = $phone;

    $allowedValues = [
        'marital_status' => ['single', 'married', 'divorced', 'widowed'],
        'gender' => ['male', 'female', 'other'],
        'employment_type' => ['full_time_permanent', 'part_time_permanent', 'contract_employee', 'self_employed', 'business_owner', 'pensioner', 'unemployed', 'student'],
        'property_type' => ['house', 'apartment', 'townhouse', 'flat', 'commercial', 'land', 'farm', 'industrial'],
        'price_type' => ['fixed', 'negotiable', 'auction'],
    ];
    foreach ($allowedValues as $field => $allowed) {
        if (!in_array($data[$field], $allowed, true)) {
            throw new InvalidArgumentException('One or more selected values are invalid.');
        }
    }

    $propertyValue = filter_var($data['property_value'] ?? null, FILTER_VALIDATE_FLOAT);
    $downPayment = filter_var($data['down_payment'] ?? null, FILTER_VALIDATE_FLOAT);
    $monthlyIncome = filter_var($data['monthly_income'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($propertyValue === false || $propertyValue <= 0 || $downPayment === false || $downPayment < 0 || $downPayment > $propertyValue) {
        throw new InvalidArgumentException('Please provide a valid property value and down payment.');
    }
    if ($monthlyIncome === false || $monthlyIncome < 0 || ($data['employment_type'] !== 'unemployed' && $monthlyIncome <= 0)) {
        throw new InvalidArgumentException('Please provide a valid monthly income.');
    }
    $data['property_value'] = round((float)$propertyValue, 2);
    $data['down_payment'] = round((float)$downPayment, 2);
    $data['loan_amount'] = round((float)$propertyValue - (float)$downPayment, 2);
    $data['monthly_income'] = round((float)$monthlyIncome, 2);

    if ($data['signature_type'] !== 'upload') {
        throw new InvalidArgumentException('Please upload a signature to complete the declaration.');
    }
    $signatureDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$data['signature_date']);
    if (!$signatureDate || $signatureDate->format('Y-m-d') !== $data['signature_date'] || $signatureDate > new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Please provide a valid signature date.');
    }

    if ($data['marital_status'] === 'married') {
        $spouse = $data['buyer_spouse'] ?? [];
        foreach (['full_name', 'id_passport', 'date_of_birth'] as $field) {
            if (empty($spouse[$field])) {
                throw new InvalidArgumentException('Please complete all required spouse details.');
            }
        }
    }

    $nextOfKin = $data['buyer_next_of_kin'] ?? [];
    foreach (['full_name', 'relationship', 'phone'] as $field) {
        if (empty($nextOfKin[$field])) {
            throw new InvalidArgumentException('Please complete the required next-of-kin details.');
        }
    }
    if (!empty($nextOfKin['email']) && !filter_var($nextOfKin['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please provide a valid next-of-kin email address.');
    }

    $declarations = array_values(array_unique(array_map('strval', is_array($data['declarations'] ?? null) ? $data['declarations'] : [])));
    if (array_diff(REQUIRED_BUYER_DECLARATIONS, $declarations)) {
        throw new InvalidArgumentException('Please accept all declarations to proceed.');
    }
    $data['declarations'] = REQUIRED_BUYER_DECLARATIONS;

    $documents = is_array($data['buyer_documents'] ?? null) ? $data['buyer_documents'] : [];
    $documentTypes = [];
    foreach ($documents as $document) {
        if (is_array($document) && !empty($document['doc_type']) && !empty($document['content'])) {
            $documentTypes[] = (string)$document['doc_type'];
        }
    }
    $requiredDocuments = ['id_passport', 'proof_of_income', 'bank_statements', 'signature'];
    if ($data['marital_status'] === 'married') {
        $requiredDocuments[] = 'marriage_certificate';
    }
    if (array_diff($requiredDocuments, array_unique($documentTypes))) {
        throw new InvalidArgumentException('Please upload every required document and your signature.');
    }

    $duplicate = $pdo->prepare("SELECT id FROM buyers WHERE (LOWER(email) = LOWER(?) OR id_number = ?) AND status IN ('pending','approved') LIMIT 1");
    $duplicate->execute([$data['email'], $data['idNumber']]);
    if ($duplicate->fetchColumn()) {
        throw new InvalidArgumentException('An active buyer application already exists for these details.');
    }

    $existingAccount = $pdo->prepare("SELECT role FROM admin_users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $existingAccount->execute([$data['email']]);
    $existingRole = $existingAccount->fetchColumn();
    if ($existingRole !== false && $existingRole !== 'buyer') {
        throw new InvalidArgumentException('This email address is already associated with another portal role.');
    }
}

/**
 * Send login email to newly created user
 */
function sendLoginEmail($toEmail, $fullName, $username, $temporaryPassword) {
    return sendTemporaryCredentialEmail($toEmail, $fullName, $temporaryPassword);

    $loginUrl = "../../../authentication-login.php";
    $subject  = "Your Nuru Real Estate Login Details";

    $message = "
    <html>
    <head>
        <title>Nuru Real Estate Login</title>
    </head>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Welcome to Nuru Real Estate</h2>
        <p>Dear <strong>{$fullName}</strong>,</p>
        <p>Your account has been successfully created on the Nuru Real Estate system.</p>
        <p><strong>Login Details:</strong></p>
        <ul>
            <li><strong>Username / Email:</strong> {$toEmail}</li>
            <li><strong>Temporary Password:</strong> {$temporaryPassword}</li>
        </ul>
        <p>Please log in using the link below and change your password immediately:</p>
        <p>
            <a href='{$loginUrl}' style='background:#1e88e5;color:#fff;padding:10px 15px;text-decoration:none;border-radius:4px;'>
               Login to Nuru Real Estate
            </a>
        </p>
        <p>If you did not request this account, please contact support immediately.</p>
        <p>Kind regards,<br><strong>Nuru Real Estate Team</strong></p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Nuru Real Estate <no-reply@nuru.com>\r\n";

    return @mail($toEmail, $subject, $message, $headers);
}

/**
 * Generate unique application number
 */
function generateApplicationNumber(PDO $pdo) {
    $exists = $pdo->prepare('SELECT 1 FROM buyers WHERE application_number = ? LIMIT 1');
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $applicationNumber = sprintf('BUY-%s-%06d', date('Ymd'), random_int(0, 999999));
        $exists->execute([$applicationNumber]);
        if (!$exists->fetchColumn()) {
            return $applicationNumber;
        }
    }

    throw new RuntimeException('Unable to allocate an application number.');
}

/**
 * Create admin user for buyer if not exists
 */
function createAdminUserIfNotExists(PDO $pdo, array $data, int $buyerId) {
    if (empty($data['email'])) {
        debugLog("No email provided, skipping user creation", 'WARNING');
        return null;
    }

    // Check if user already exists
    $check = $pdo->prepare("SELECT id, email, role FROM admin_users WHERE LOWER(email) = LOWER(?)");
    $check->execute([$data['email']]);
    $existingUser = $check->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        if ($existingUser['role'] !== 'buyer') {
            throw new InvalidArgumentException('This email address is already associated with another portal role.');
        }
        debugLog("User already exists with email: {$data['email']}", 'WARNING');
        // Link existing user to buyer if not already linked
        return (int)$existingUser['id'];
    }

    // Generate username from name fields
    $firstName = $data['firstName'] ?? $data['full_name'] ?? 'Buyer';
    $surname = $data['lastName'] ?? '';
    $usernameBase = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '.', $firstName . '.' . $surname), '.'));
    if ($usernameBase === '') {
        $usernameBase = 'buyer';
    }
    $username = $usernameBase;
    $usernameExists = $pdo->prepare('SELECT 1 FROM admin_users WHERE username = ? LIMIT 1');
    $usernameAllocated = false;
    for ($suffix = 1; $suffix <= 100; $suffix++) {
        $usernameExists->execute([$username]);
        if (!$usernameExists->fetchColumn()) {
            $usernameAllocated = true;
            break;
        }
        $username = $usernameBase . '.' . $suffix;
    }
    if (!$usernameAllocated) {
        throw new RuntimeException('Unable to allocate a portal username.');
    }

    $fullName = trim(($firstName ?? '') . ' ' . ($surname ?? ''));
    if (empty(trim($fullName))) {
        $fullName = $data['email'];
    }

    $temporaryPassword = generateTemporaryPassword();
    $defaultPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    $role = 'buyer';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_users
            (username, email, password_hash, full_name, role, is_active, agent_id, remember_token, remember_token_expires)
            VALUES (?, ?, ?, ?, ?, 1, '', '', '')
        ");

        $stmt->execute([
            $username,
            $data['email'],
            $defaultPassword,
            $fullName,
            $role
        ]);

        $adminUserId = (int)$pdo->lastInsertId();
        debugLog("Created admin user ID: $adminUserId for buyer ID: $buyerId", 'SUCCESS');

        // Deliver only after the transaction commits and the receipt has been
        // flushed. Holding this mail transport call inside the transaction
        // previously made buyer submissions appear frozen for ~30 seconds.
        queueTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword);
        debugLog("Login email queued for post-response delivery", 'INFO');

        return $adminUserId;

    } catch (PDOException $e) {
        debugLog("Failed to create admin user: " . $e->getMessage(), 'ERROR');
        throw new RuntimeException('Unable to provision the buyer portal account.', 0, $e);
    }
}

/**
 * Insert main buyer record and return the buyer ID
 */
function insertBuyerRecord(PDO $pdo, array $data, string $applicationNumber): int {
    debugLog("Preparing to insert buyer record", 'INFO');

    $loadedBy = 'Self Loaded';
    if (!empty($_SESSION['user_id'])) {
        $loadedBy = (string)$_SESSION['user_id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO buyers (
            application_number, full_name, date_of_birth, id_type, id_number,
            marital_status, phone, email, region, town, address,
            employment_type, employer_name, position, monthly_income,
            property_value, down_payment, loan_amount, loaded_by,
            nationality, gender, property_type, price_type, additional_requirements,
            status, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'full_application')
    ");

    $executeParams = [
        $applicationNumber,
        $data['full_name'] ?? ($data['firstName'] . ' ' . $data['lastName']) ?? null,
        $data['dateOfBirth'] ?? null,
        $data['id_type'] ?? $data['idType'] ?? null,
        $data['idNumber'] ?? null,
        $data['marital_status'] ?? $data['maritalStatus'] ?? null,
        $data['phone'] ?? $data['mobileNumber'] ?? null,
        $data['email'] ?? null,
        $data['region'] ?? null,
        $data['town'] ?? null,
        $data['address'] ?? null,
        $data['employment_type'] ?? $data['employmentType'] ?? null,
        $data['employer_name'] ?? $data['employerName'] ?? null,
        $data['position'] ?? $data['jobTitle'] ?? null,
        isset($data['monthly_income']) ? (float)$data['monthly_income'] :
            (isset($data['monthlyIncome']) ? (float)$data['monthlyIncome'] : 0),
        isset($data['property_value']) ? (float)$data['property_value'] :
            (isset($data['propertyValue']) ? (float)$data['propertyValue'] : 0),
        isset($data['down_payment']) ? (float)$data['down_payment'] :
            (isset($data['downPayment']) ? (float)$data['downPayment'] : 0),
        isset($data['loan_amount']) ? (float)$data['loan_amount'] :
            (isset($data['loanAmount']) ? (float)$data['loanAmount'] : 0),
        $loadedBy,
        $data['nationality'] ?? '',
        $data['gender'] ?? '',
        $data['property_type'] ?? $data['propertyType'] ?? '',
        $data['price_type'] ?? $data['priceType'] ?? '',
        $data['additional_requirements'] ?? $data['additionalRequirements'] ?? '',
    ];

    debugLog('Buyer insert parameters prepared', 'DEBUG');

    $result = $stmt->execute($executeParams);

    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        debugLog("Insert failed: " . implode(' - ', $errorInfo), 'ERROR');
        throw new Exception("Failed to insert buyer: " . implode(' - ', $errorInfo));
    }

    // Try to get ID using lastInsertId first
    $buyerId = (int)$pdo->lastInsertId();

    // If lastInsertId returns 0, query the record to get the ID
    if ($buyerId === 0) {
        debugLog("lastInsertId() returned 0, querying for record", 'WARNING');
        $escapedAppNum = $pdo->quote($applicationNumber);
        $record = $pdo->query("SELECT id FROM buyers WHERE application_number = {$escapedAppNum}")->fetch(PDO::FETCH_ASSOC);

        if ($record && isset($record['id'])) {
            $buyerId = (int)$record['id'];
            debugLog("Retrieved buyer ID from query: $buyerId", 'SUCCESS');
        } else {
            throw new Exception("Failed to retrieve buyer ID after insert");
        }
    }

    debugLog("Buyer inserted successfully with ID: $buyerId", 'SUCCESS');
    return $buyerId;
}

/**
 * Insert spouse information if buyer is married
 */
function insertSpouseInfo(PDO $pdo, int $buyerId, array $data): bool {
    $maritalStatus = strtolower($data['marital_status'] ?? $data['maritalStatus'] ?? '');

    if ($maritalStatus !== 'married') {
        debugLog("Buyer is not married, skipping spouse insert", 'INFO');
        return true;
    }

    if (empty($data['buyer_spouse']) || !is_array($data['buyer_spouse'])) {
        debugLog("Married but no spouse data provided", 'WARNING');
        return true; // Don't fail, just skip
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO buyer_spouse (buyer_id, full_name, id_passport, date_of_birth, phone, email)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $buyerId,
            $data['buyer_spouse']['full_name'] ?? null,
            $data['buyer_spouse']['id_passport'] ?? null,
            $data['buyer_spouse']['date_of_birth'] ?? null,
            $data['buyer_spouse']['phone'] ?? '',
            $data['buyer_spouse']['email'] ?? ''
        ]);

        $spouseId = (int)$pdo->lastInsertId();
        debugLog("Spouse inserted successfully with ID: $spouseId", 'SUCCESS');
        return true;

    } catch (PDOException $e) {
        debugLog("Failed to insert spouse: " . $e->getMessage(), 'ERROR');
        throw new Exception("Failed to insert spouse information: " . $e->getMessage());
    }
}

/**
 * Insert next of kin information
 */
function insertNextOfKin(PDO $pdo, int $buyerId, array $data): bool {
    if (empty($data['buyer_next_of_kin']) || !is_array($data['buyer_next_of_kin'])) {
        debugLog("No next of kin data provided", 'WARNING');
        return true; // Don't fail, next of kin might be optional
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO buyer_next_of_kin (buyer_id, full_name, relationship, phone, region, town, address, email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $buyerId,
            $data['buyer_next_of_kin']['full_name'] ?? null,
            $data['buyer_next_of_kin']['relationship'] ?? null,
            $data['buyer_next_of_kin']['phone'] ?? null,
            $data['buyer_next_of_kin']['region'] ?? null,
            $data['buyer_next_of_kin']['town'] ?? null,
            $data['buyer_next_of_kin']['address'] ?? null,
            $data['buyer_next_of_kin']['email'] ?? ''
        ]);

        $nokId = (int)$pdo->lastInsertId();
        debugLog("Next of kin inserted successfully with ID: $nokId", 'SUCCESS');
        return true;

    } catch (PDOException $e) {
        debugLog("Failed to insert next of kin: " . $e->getMessage(), 'ERROR');
        throw new Exception("Failed to insert next of kin: " . $e->getMessage());
    }
}

/**
 * Insert preferred areas (can be multiple)
 */
function insertPreferredAreas(PDO $pdo, int $buyerId, array $data): bool {
    $preferredAreas = [];

    if (!empty($data['preferredAreas']) && is_array($data['preferredAreas'])) {
        $preferredAreas = $data['preferredAreas'];
    }

    if (empty($preferredAreas)) {
        debugLog("No preferred areas data provided", 'INFO');
        return true;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO buyer_preferred_areas (buyer_id, region, town, location, suburb)
            VALUES (?, ?, ?, ?, ?)
        ");

        $insertedCount = 0;
        foreach ($preferredAreas as $area) {
            if (!is_array($area)) continue;

            $stmt->execute([
                $buyerId,
                $area['region'] ?? null,
                $area['town'] ?? null,
                $area['location'] ?? null,
                $area['suburb'] ?? null
            ]);
            $insertedCount++;
        }

        debugLog("Inserted $insertedCount preferred area(s)", 'SUCCESS');
        return true;

    } catch (PDOException $e) {
        debugLog("Failed to insert preferred areas: " . $e->getMessage(), 'ERROR');
        throw new Exception("Failed to insert preferred areas: " . $e->getMessage());
    }
}

/**
 * Insert declarations
 */
function insertDeclarations(PDO $pdo, int $buyerId, array $data): bool {
    $declarations = [];

    if (!empty($data['declarations']) && is_array($data['declarations'])) {
        $declarations = $data['declarations'];
    }

    if (empty($declarations)) {
        debugLog("No declarations data provided", 'INFO');
        return true;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO buyer_declarations (buyer_id, declaration, accepted)
            VALUES (?, ?, 1)
        ");

        $insertedCount = 0;
        foreach ($declarations as $declaration) {
            if (empty($declaration)) continue;

            $stmt->execute([$buyerId, $declaration]);
            $insertedCount++;
        }

        debugLog("Inserted $insertedCount declaration(s)", 'SUCCESS');
        return true;

    } catch (PDOException $e) {
        debugLog("Failed to insert declarations: " . $e->getMessage(), 'ERROR');
        throw new Exception("Failed to insert declarations: " . $e->getMessage());
    }
}

/**
 * Insert buyer documents from buyer_documents array
 */
function insertBuyerDocuments(PDO $pdo, int $buyerId, array $data): bool {
    global $createdBuyerUploadPaths;
    $documents = [];

    if (!empty($data['buyer_documents']) && is_array($data['buyer_documents'])) {
        $documents = $data['buyer_documents'];
    }

    if (empty($documents)) {
        debugLog("No buyer documents data provided", 'INFO');
        return true;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO buyer_documents (buyer_id, doc_type, file_path, uploaded_at)
            VALUES (?, ?, ?, NOW())
        ");

        $insertedCount = 0;
        foreach ($documents as $doc) {
            if (!is_array($doc) || empty($doc['file_name']) || empty($doc['content'])) {
                throw new InvalidArgumentException('A submitted document is incomplete.');
            }

            $docType = (string)($doc['doc_type'] ?? '');
            if (!in_array($docType, ALLOWED_BUYER_DOC_TYPES, true)) {
                throw new InvalidArgumentException('A submitted document type is not allowed.');
            }

            $decodedContent = base64_decode((string)$doc['content'], true);
            if ($decodedContent === false || $decodedContent === '') {
                throw new InvalidArgumentException('A submitted document is invalid.');
            }
            $contentLength = strlen($decodedContent);
            if ($contentLength > MAX_BUYER_DOCUMENT_BYTES) {
                throw new InvalidArgumentException('Each document must be 10MB or smaller.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($decodedContent) ?: '';
            $mimeExtensions = [
                'application/pdf' => ['pdf'],
                'image/jpeg' => ['jpg', 'jpeg'],
                'image/png' => ['png'],
                'application/msword' => ['doc'],
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
                'application/zip' => ['docx'],
            ];
            $originalExtension = strtolower(pathinfo((string)$doc['file_name'], PATHINFO_EXTENSION));
            if (!isset($mimeExtensions[$mimeType]) || !in_array($originalExtension, $mimeExtensions[$mimeType], true)) {
                throw new InvalidArgumentException('Only genuine PDF, Word, JPG, and PNG documents are accepted.');
            }

            $savedExtension = $originalExtension === 'jpeg' ? 'jpg' : $originalExtension;
            $safeName = bin2hex(random_bytes(16)) . '.' . $savedExtension;

            $uploadDir = dirname(__DIR__, 3) . "/uploads/buyers/$docType/";
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
                    throw new RuntimeException('Unable to create the protected upload directory.');
                }
            }

            $fullPath = $uploadDir . $safeName;
            $savedPath = "uploads/buyers/$docType/" . $safeName;

            if (file_put_contents($fullPath, $decodedContent, LOCK_EX) === false) {
                throw new RuntimeException('Unable to store a submitted document.');
            }
            $createdBuyerUploadPaths[] = $fullPath;

            $stmt->execute([
                $buyerId,
                $docType,
                $savedPath
            ]);
            $insertedCount++;
        }

        debugLog("Inserted $insertedCount document(s)", 'SUCCESS');
        return true;

    } catch (Throwable $e) {
        debugLog("Failed to insert documents: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

// Main execution starts here
debugLog("=== API CALL STARTED ===");
debugLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
debugLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not Set'));

// Capture raw input
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 60 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'The application documents exceed the total upload limit.']);
    exit;
}
$rawInput = file_get_contents('php://input');
debugLog("Raw input received: " . strlen($rawInput) . " characters");

if ($rawInput) {
    debugLog('Raw input received without recording applicant data');
}

// Check if PDO connection is valid
if (!$pdo) {
    debugLog("ERROR: PDO connection is null", 'ERROR');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

debugLog("Database connection successful", 'SUCCESS');

// Test database connection
try {
    $testResult = $pdo->query("SELECT 1")->fetch();
    debugLog("Database test query result: " . ($testResult ? 'SUCCESS' : 'FAILED'));
} catch (PDOException $e) {
    debugLog("Database test query failed: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection test failed']);
    exit;
}

try {
    // Parse input data
    debugLog("Parsing input data...", 'INFO');

    if (!empty($_POST)) {
        debugLog("Using POST data");
        $data = $_POST;
    } else {
        debugLog("Using JSON input");
        $rawData = $rawInput;
        $data = json_decode($rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugLog("JSON decode error: " . json_last_error_msg(), 'ERROR');
            throw new Exception("Invalid JSON input: " . json_last_error_msg());
        }
    }

    debugLog("Data parsed successfully. Type: " . gettype($data));
    debugLog("Data keys: " . implode(', ', array_keys($data)));
    debugLog("Data size: " . count($data) . " items");

    if (empty($data) || !is_array($data)) {
        debugLog("ERROR: Data is empty or not an array", 'ERROR');
        throw new Exception("No data received");
    }

    $buyerIdempotencyKey = trim((string)($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ($data['idempotency_key'] ?? '')));
    if (!preg_match('/^[A-Za-z0-9-]{16,80}$/', $buyerIdempotencyKey)) {
        throw new InvalidArgumentException('The submission identifier is invalid. Reload the page and try again.');
    }
    $previousAttempt = $_SESSION['_buyer_idempotency'][$buyerIdempotencyKey] ?? null;
    if (is_array($previousAttempt) && ($previousAttempt['state'] ?? '') === 'succeeded') {
        echo json_encode($previousAttempt['response']);
        exit;
    }
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
    if (!SecurityIntegration::validateCSRFToken($submittedToken, 'buyer_application_submit')) {
        debugLog("CSRF token validation failed", 'ERROR');
        throw new InvalidArgumentException("Invalid or expired form token. Please reload the page.");
    }
    if (is_array($previousAttempt) && ($previousAttempt['state'] ?? '') === 'processing'
        && time() - (int)($previousAttempt['time'] ?? 0) < 900) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'This application is already being processed. Please wait.',
            'duplicate' => true,
        ]);
        exit;
    }
    $_SESSION['_buyer_idempotency'][$buyerIdempotencyKey] = [
        'state' => 'processing',
        'time' => time(),
    ];
    session_write_close();

    if (!verifyTurnstile($data['cf-turnstile-response'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null)) {
        debugLog("Turnstile CAPTCHA verification failed", 'ERROR');
        throw new InvalidArgumentException("CAPTCHA verification failed. Please try again.");
    }

    validateBuyerApplication($pdo, $data);

    // Server-side email validation - there was previously none at all, only
    // the client-side <input type="email">, which is trivially bypassed by
    // posting directly to this endpoint. An unvalidated email also becomes
    // an email-header-injection vector (CRLF in the value) the moment a
    // working SMTP relay is configured, so this matters even though mail()
    // isn't currently delivering anything in this environment.
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        debugLog("Rejected invalid email: {$data['email']}", 'ERROR');
        throw new Exception("Please provide a valid email address");
    }

    // Start transaction
    debugLog("Starting database transaction...", 'INFO');
    $pdo->beginTransaction();
    debugLog("Transaction started successfully", 'SUCCESS');

    // Step 1: Generate application number
    $applicationNumber = generateApplicationNumber($pdo);
    debugLog("Generated application number: $applicationNumber", 'INFO');

    // Step 2: Insert main buyer record
    $buyerId = insertBuyerRecord($pdo, $data, $applicationNumber);

    // Step 3: Insert spouse information (if applicable)
    insertSpouseInfo($pdo, $buyerId, $data);

    // Step 4: Insert next of kin information
    insertNextOfKin($pdo, $buyerId, $data);

    // Step 5: Insert preferred areas
    insertPreferredAreas($pdo, $buyerId, $data);

    // Step 6: Insert declarations
    insertDeclarations($pdo, $buyerId, $data);

    // Step 7: Insert buyer documents
    insertBuyerDocuments($pdo, $buyerId, $data);

    // Step 8: Create admin user account and link it back to the buyer record
    $buyerUserId = createAdminUserIfNotExists($pdo, $data, $buyerId);
    if (!$buyerUserId) {
        throw new RuntimeException('Unable to provision the buyer portal account.');
    }
    $pdo->prepare("UPDATE buyers SET user_id = ? WHERE id = ?")->execute([$buyerUserId, $buyerId]);

    // Commit transaction
    debugLog("Committing transaction...", 'INFO');
    $commitResult = $pdo->commit();

    if (!$commitResult) {
        $errorInfo = $pdo->errorInfo();
        debugLog("Commit failed: " . implode(' - ', $errorInfo), 'ERROR');
        throw new Exception("Failed to commit transaction: " . implode(' - ', $errorInfo));
    }

    debugLog("Transaction committed successfully", 'SUCCESS');
    $buyerCommitted = true;

    // Success response
    $response = [
        'success' => true,
        'application_number' => $applicationNumber,
        'buyer_id' => $buyerId,
        'status' => 'Pending review',
        'submitted_at' => date(DATE_ATOM),
        'message' => 'Buyer application submitted successfully.'
    ];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_buyer_idempotency'][$buyerIdempotencyKey] = [
        'state' => 'succeeded',
        'time' => time(),
        'response' => $response,
    ];
    unset($_SESSION['_csrf_tokens']['buyer_application_submit']);
    session_write_close();

    debugLog("Sending success response: " . json_encode($response), 'SUCCESS');
    finishResponseAndDeliverQueuedCredentialEmails((string)json_encode($response));
    exit;

} catch (Throwable $e) {
    debugLog("EXCEPTION CAUGHT: " . $e->getMessage(), 'ERROR');
    debugLog("Exception trace: " . $e->getTraceAsString(), 'ERROR');

    // Once the database commit succeeds, the application is durable. Never
    // report a failure that encourages a duplicate retry merely because
    // post-commit session bookkeeping encountered a secondary problem.
    if ($buyerCommitted && isset($response) && is_array($response)) {
        http_response_code(200);
        finishResponseAndDeliverQueuedCredentialEmails((string)json_encode($response));
        exit;
    }

    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        debugLog("Rolling back transaction...", 'WARNING');
        $pdo->rollBack();
        debugLog("Transaction rolled back", 'SUCCESS');
    }

    if (!$buyerCommitted) {
        foreach ($createdBuyerUploadPaths as $createdPath) {
            if (is_file($createdPath)) {
                @unlink($createdPath);
            }
        }
    }
    if ($buyerIdempotencyKey !== '') {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['_buyer_idempotency'][$buyerIdempotencyKey]);
        session_write_close();
    }

    // Send error response
    $isValidationError = $e instanceof InvalidArgumentException;
    $errorResponse = [
        'success' => false,
        'error' => $isValidationError
            ? $e->getMessage()
            : 'Unable to submit the application. Please review the form and try again.'
    ];

    debugLog("Sending error response: " . json_encode($errorResponse), 'ERROR');
    http_response_code($isValidationError ? 422 : 400);
    echo json_encode($errorResponse);
}

debugLog("=== API CALL ENDED ===");

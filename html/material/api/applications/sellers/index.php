<?php
// ===============================================================
// File: api/applications/sellers/index.php
// Purpose: Save seller multi-step form (personal, marital, address, kin, property, docs)
// ===============================================================
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once '../../../config/pdo.php';
require_once '../../security_integration.php';
require_once '../../../config/turnstile.php';
require_once '../../../config/account_provisioning.php';

if (!SecurityIntegration::processRequest('seller_application_submit', $_SERVER['REQUEST_METHOD'])) {
    exit;
}

$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 80 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'The application upload is too large. Reduce the file sizes and try again.']);
    exit;
}

// Enable error reporting for debugging (but prevent HTML output)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to prevent HTML output
ini_set('log_errors', 1);     // But still log them

// Custom logging function
function debugLog($message) {
    if (getenv('NURU_DEBUG_LOG') !== '1'
        || strtolower((string)(getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
        return;
    }
    $timestamp = date('Y-m-d H:i:s');
    $safeMessage = substr(preg_replace('/[\r\n]+/', ' ', (string)$message), 0, 1000);
    $logMessage = "[$timestamp] $safeMessage\n";
    error_log($logMessage, 3, '../../../logs/debug.log');
}

debugLog("=== SELLER API CALL STARTED ===");
debugLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
debugLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not Set'));

// Allowed upload extensions, matching what the client-side forms already
// advertise via <input accept>. Server-side enforcement is the only one that
// actually matters - the client-side accept attribute is trivially bypassed
// by posting directly to this endpoint. Never trust the client-supplied
// filename for the saved path (path traversal risk); always regenerate one.
function safeUploadFilename(string $originalName, array $allowedExtensions): ?string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return null;
    }
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'webm'];

class SellerValidationException extends RuntimeException {}

function validateEncodedSellerFile(array &$file, string $category, int $maxBytes): void {
    $allowed = match ($category) {
        'document' => [
            'application/pdf' => ['pdf'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        ],
        'image' => [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
        ],
        'video' => [
            'video/mp4' => ['mp4'],
            'video/quicktime' => ['mov'],
            'video/x-msvideo' => ['avi'],
            'video/webm' => ['webm'],
            'application/octet-stream' => ['avi'],
        ],
        default => [],
    };

    $name = basename((string)($file['name'] ?? ''));
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $encoded = (string)($file['content'] ?? '');
    $decoded = $encoded === '' ? false : base64_decode($encoded, true);
    if ($name === '' || $decoded === false || $decoded === '') {
        throw new SellerValidationException('One or more uploaded files is empty or invalid.');
    }
    $size = strlen($decoded);
    if ($size > $maxBytes) {
        throw new SellerValidationException('One or more uploaded files exceeds the permitted size.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->buffer($decoded);
    if (!isset($allowed[$mime]) || !in_array($extension, $allowed[$mime], true)) {
        throw new SellerValidationException('One or more uploaded files has a type that is not allowed.');
    }

    $file['name'] = $name;
    $file['size'] = $size;
    $file['type'] = $mime;
}

function sellerDate(string $value): ?DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function sellerNormaliseText(mixed $value): string {
    $text = trim((string)$value);
    if (class_exists('Normalizer')) {
        $normalised = Normalizer::normalize($text, Normalizer::FORM_C);
        if (is_string($normalised)) {
            $text = $normalised;
        }
    }
    return $text;
}

function sellerValidateText(string $field, mixed $value, int $maxLength, string $label): string {
    $text = sellerNormaliseText($value);
    if (mb_strlen($text, 'UTF-8') > $maxLength) {
        throw new SellerValidationException("{$label} must not exceed {$maxLength} characters.");
    }
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\p{Cf}/u', $text)) {
        throw new SellerValidationException("{$label} contains unsupported control characters.");
    }
    return $text;
}

/**
 * Parses one Sale Type breakdown amount (as submitted from the form -
 * possibly comma-formatted, possibly empty). Empty/non-numeric/negative
 * values are treated as 0 rather than rejected outright, matching the
 * client-side calculator's behavior - the >0 check on the resulting total
 * is what actually enforces a real amount was provided.
 */
function sellerMoneyAmount(mixed $value): float {
    $numeric = str_replace(',', '', (string)$value);
    if (!is_numeric($numeric)) {
        return 0.0;
    }
    $amount = (float)$numeric;
    return $amount > 0 ? $amount : 0.0;
}

/**
 * Server-side source of truth for Total Selling Price - never trust a
 * client-submitted total, always recompute it from the individual
 * breakdown amounts for the given Sale Type ('plot_and_plan' or
 * 'existing_house'). Shared by both the Individual Seller and Property
 * Developer (per house type) paths, since the formulas only differ by
 * whether 'other_fees' applies.
 *
 *   plot_and_plan:   plot_selling_price + construction_amount + agent_commission_fees [+ other_fees]
 *   existing_house:  property_selling_price + agent_commission_fees [+ other_fees]
 */
function sellerCalculateTotalSellingPrice(string $salePricingType, array $amounts): float {
    $commission = sellerMoneyAmount($amounts['agent_commission_fees'] ?? null);
    $otherFees = sellerMoneyAmount($amounts['other_fees'] ?? null);

    return match ($salePricingType) {
        'plot_and_plan' => sellerMoneyAmount($amounts['plot_selling_price'] ?? null)
            + sellerMoneyAmount($amounts['construction_amount'] ?? null)
            + $commission + $otherFees,
        'existing_house' => sellerMoneyAmount($amounts['property_selling_price'] ?? null)
            + $commission + $otherFees,
        default => 0.0,
    };
}

function validateSellerApplication(PDO $pdo, array &$data): void {
    $requiredStrings = [
        'surname', 'firstName', 'dateOfBirth', 'idType', 'idNumber', 'nationality', 'gender',
        'maritalStatus', 'streetName', 'region', 'town', 'email', 'mobileNumber',
        'nokSurname', 'nokFirstName', 'nokContactNumber', 'nokEmail', 'nokStreetName',
        'nokRegion', 'nokTown', 'saleType', 'signatureLocation', 'signatureDate', 'signatureType',
    ];
    $fieldLimits = [
        'surname' => 100, 'firstName' => 100, 'dateOfBirth' => 10, 'idType' => 20,
        'idNumber' => 13, 'nationality' => 100, 'gender' => 20, 'maritalStatus' => 20,
        'streetName' => 200, 'region' => 100, 'town' => 100, 'email' => 190,
        'mobileNumber' => 20, 'nokSurname' => 100, 'nokFirstName' => 100,
        'nokContactNumber' => 20, 'nokEmail' => 190, 'nokStreetName' => 200,
        'nokRegion' => 100, 'nokTown' => 100, 'saleType' => 40,
        'signatureLocation' => 100, 'signatureDate' => 10, 'signatureType' => 20,
    ];
    foreach ($requiredStrings as $field) {
        if (!isset($data[$field]) || sellerNormaliseText($data[$field]) === '') {
            throw new SellerValidationException('Please complete all required fields.');
        }
        $data[$field] = sellerValidateText($field, $data[$field], $fieldLimits[$field] ?? 255, 'This field');
    }

    $optionalLimits = [
        'middleName' => 100, 'maidenName' => 100, 'spouseSurname' => 100,
        'spouseFirstName' => 100, 'spouseIdNumber' => 13, 'spouseNationality' => 100,
        'nokSuburb' => 100, 'nokLocation' => 150, 'propertyStreetName' => 200,
        'propertyTown' => 100,
    ];
    foreach ($optionalLimits as $field => $limit) {
        if (array_key_exists($field, $data) && !is_array($data[$field])) {
            $data[$field] = sellerValidateText($field, $data[$field], $limit, 'This field');
        }
    }

    $personNamePattern = "/^[\\p{L}\\p{M}\\p{Zs}'’ʼ\\-‐‑]{2,100}$/u";
    foreach (['surname', 'firstName', 'nokSurname', 'nokFirstName'] as $nameField) {
        if (!preg_match($personNamePattern, $data[$nameField])) {
            throw new SellerValidationException('Names may contain letters, spaces, apostrophes, and hyphens only.');
        }
    }
    foreach (['middleName', 'maidenName'] as $nameField) {
        if (($data[$nameField] ?? '') !== ''
            && !preg_match("/^[\\p{L}\\p{M}\\p{Zs}'’ʼ\\-‐‑]{1,100}$/u", (string)$data[$nameField])) {
            throw new SellerValidationException('Names may contain letters, spaces, apostrophes, and hyphens only.');
        }
    }
    $birthDate = sellerDate($data['dateOfBirth']);
    $age = $birthDate ? $birthDate->diff(new DateTimeImmutable('today'))->y : -1;
    if (!$birthDate || $birthDate > new DateTimeImmutable('today') || $age < 18 || $age > 120) {
        throw new SellerValidationException('The applicant must be between 18 and 120 years old.');
    }
    if (!in_array($data['idType'], ['National ID', 'Passport'], true)) {
        throw new SellerValidationException('Please select a valid ID type.');
    }
    $validId = $data['idType'] === 'National ID'
        ? preg_match('/^\d{11}$/', $data['idNumber'])
        : preg_match('/^[A-Z0-9]{6,9}$/i', $data['idNumber']);
    if (!$validId) {
        throw new SellerValidationException('Please provide a valid ID or passport number.');
    }
    if (!in_array($data['gender'], ['Male', 'Female'], true)
        || !in_array($data['maritalStatus'], ['Single', 'Married', 'Separated', 'Divorced', 'Widower'], true)) {
        throw new SellerValidationException('Please select valid personal details.');
    }
    foreach (['email', 'nokEmail'] as $emailField) {
        $data[$emailField] = strtolower($data[$emailField]);
        if (!filter_var($data[$emailField], FILTER_VALIDATE_EMAIL)) {
            throw new SellerValidationException('Please provide valid email addresses.');
        }
    }
    foreach (['mobileNumber', 'nokContactNumber'] as $phoneField) {
        $normalised = preg_replace('/[\s()-]+/', '', $data[$phoneField]);
        if (!preg_match('/^(?:\+264\d{9}|0\d{9})$/', $normalised)) {
            throw new SellerValidationException('Please provide valid Namibian contact numbers.');
        }
        $data[$phoneField] = $normalised;
    }

    $regions = ['Zambezi', 'Erongo', 'Hardap', 'Karas', 'Kavango East', 'Kavango West', 'Khomas', 'Kunene', 'Ohangwena', 'Omaheke', 'Omusati', 'Oshana', 'Oshikoto', 'Otjozondjupa'];
    foreach (['region', 'nokRegion'] as $regionField) {
        if (!in_array($data[$regionField], $regions, true)) {
            throw new SellerValidationException('Please select valid regions.');
        }
    }

    if ($data['maritalStatus'] === 'Married') {
        foreach (['spouseSurname', 'spouseFirstName', 'spouseDateOfBirth', 'spouseIdType', 'spouseIdNumber', 'spouseNationality', 'spouseGender'] as $field) {
            if (trim((string)($data[$field] ?? '')) === '') {
                throw new SellerValidationException('Please complete the spouse details.');
            }
        }
        foreach (['spouseSurname', 'spouseFirstName'] as $nameField) {
            if (!preg_match($personNamePattern, (string)$data[$nameField])) {
                throw new SellerValidationException('Names may contain letters, spaces, apostrophes, and hyphens only.');
            }
        }
        $spouseBirthDate = sellerDate((string)$data['spouseDateOfBirth']);
        if (!$spouseBirthDate || $spouseBirthDate->diff(new DateTimeImmutable('today'))->y < 18) {
            throw new SellerValidationException('Please provide a valid spouse date of birth.');
        }
        if (!in_array($data['spouseGender'], ['Male', 'Female'], true)
            || !in_array($data['spouseIdType'], ['National ID', 'Passport'], true)) {
            throw new SellerValidationException('Please select valid spouse details.');
        }
    }

    if (!in_array($data['saleType'], ['Individual', 'Property Development'], true)) {
        throw new SellerValidationException('Please select a valid sale type.');
    }
    if ($data['saleType'] === 'Individual') {
        foreach (['landSize', 'propertyStreetName', 'propertyRegion', 'propertyTown'] as $field) {
            if (trim((string)($data[$field] ?? '')) === '') {
                throw new SellerValidationException('Please complete the property details.');
            }
        }
        if (!in_array($data['propertyRegion'], $regions, true)) {
            throw new SellerValidationException('Please select valid property details.');
        }
        $data['landSize'] = str_replace(',', '', (string)$data['landSize']);
        if (!is_numeric($data['landSize']) || (float)$data['landSize'] <= 0) {
            throw new SellerValidationException('Land size must be greater than zero.');
        }
        if (!in_array($data['salePricingType'] ?? '', ['vacant_land', 'plot_and_plan', 'existing_house'], true)) {
            throw new SellerValidationException('Please select a Property Type (Vacant Land, Plot & Plan, or Existing House).');
        }

        // The old standalone Property Type / Land Type selects were
        // removed from the form - Property Type (salePricingType) now
        // covers the same "what kind of land/property is this" concept, so
        // derive the values the rest of this script (and the DB schema)
        // still expect from it instead. property_detail_type has no
        // equivalent input left in the simplified form; default it rather
        // than leaving a NOT NULL column unset.
        $data['propertyDetailType'] = 'Single Residential';
        $data['landType'] = match ($data['salePricingType']) {
            'vacant_land' => 'Vacant Land',
            'plot_and_plan' => 'Plot and Plan',
            'existing_house' => 'Existing Property',
        };

        if ($data['salePricingType'] === 'vacant_land') {
            // No breakdown to compute from - Vacant Land is priced
            // directly, same as every field on this form was before the
            // Sale Type pricing breakdown existed.
            $data['sellingPrice'] = str_replace(',', '', (string)($data['sellingPrice'] ?? ''));
            if (!is_numeric($data['sellingPrice']) || (float)$data['sellingPrice'] <= 0) {
                throw new SellerValidationException('Selling price must be greater than zero.');
            }
        } else {
            // Total Selling Price is never trusted from the client for the
            // priced Sale Types - it is always recomputed here from the
            // breakdown fields so the stored total can't drift from (or be
            // spoofed independently of) its components.
            $data['sellingPrice'] = (string)sellerCalculateTotalSellingPrice($data['salePricingType'], [
                'plot_selling_price' => $data['plotSellingPrice'] ?? '',
                'construction_amount' => $data['constructionAmount'] ?? '',
                'property_selling_price' => $data['propertySellingPrice'] ?? '',
                'agent_commission_fees' => $data['agentCommissionFees'] ?? '',
            ]);
            if ((float)$data['sellingPrice'] <= 0) {
                throw new SellerValidationException('Selling price must be greater than zero.');
            }
        }
    } else {
        $developments = $data['developments'] ?? null;
        if (!is_array($developments) || count($developments) < 1 || count($developments) > 20) {
            throw new SellerValidationException('Provide between 1 and 20 property developments.');
        }
        // By-reference throughout: the recomputed selling_price below must
        // write back into $data['developments'] itself (a plain foreach
        // copies each element by value, silently discarding the write) so
        // the INSERT statements later see the server-computed total, not
        // whatever the client originally submitted.
        foreach ($data['developments'] as &$development) {
            $developmentName = sellerValidateText('development_name', $development['development_name'] ?? '', 150, 'Development name');
            $developmentTown = sellerValidateText('town', $development['town'] ?? '', 100, 'Development town');
            if ($developmentName === ''
                || !in_array((string)($development['region'] ?? ''), $regions, true)
                || $developmentTown === '') {
                throw new SellerValidationException('Complete every property development.');
            }
            $houseTypes = $development['house_types'] ?? null;
            if (!is_array($houseTypes) || count($houseTypes) < 1 || count($houseTypes) > 25) {
                throw new SellerValidationException('Each development needs between 1 and 25 house types.');
            }
            foreach ($development['house_types'] as &$houseType) {
                foreach (['property_type', 'number_of_units', 'land_size'] as $field) {
                    if (trim((string)($houseType[$field] ?? '')) === '') {
                        throw new SellerValidationException('Complete every required house-type field.');
                    }
                }
                if (!ctype_digit((string)$houseType['number_of_units']) || (int)$houseType['number_of_units'] < 1
                    || !is_numeric(str_replace(',', '', (string)$houseType['land_size']))) {
                    throw new SellerValidationException('Provide valid development quantities and prices.');
                }
                if (!in_array($houseType['sale_pricing_type'] ?? '', ['vacant_land', 'plot_and_plan', 'existing_house'], true)) {
                    throw new SellerValidationException('Select a House Type (Vacant Land, Plot & Plan, or Existing House) for every house type.');
                }

                // The old per-house-type Property Type (land_type) select was
                // removed - House Type (sale_pricing_type) now covers the
                // same concept, so derive the value the DB schema still
                // expects from it instead.
                $houseType['land_type'] = match ($houseType['sale_pricing_type']) {
                    'vacant_land' => 'Vacant Land',
                    'plot_and_plan' => 'Plot and Plan',
                    'existing_house' => 'Existing Property',
                };

                if ($houseType['sale_pricing_type'] === 'vacant_land') {
                    // No breakdown to compute from - priced directly.
                    $houseType['selling_price'] = str_replace(',', '', (string)($houseType['selling_price'] ?? ''));
                    if (!is_numeric($houseType['selling_price']) || (float)$houseType['selling_price'] <= 0) {
                        throw new SellerValidationException('Each house type\'s selling price must be greater than zero.');
                    }
                } else {
                    // Total Selling Price is recomputed server-side from this
                    // house type's Sale Type breakdown, same as the individual
                    // seller path - never trusted from the client.
                    $houseType['selling_price'] = (string)sellerCalculateTotalSellingPrice($houseType['sale_pricing_type'], [
                        'plot_selling_price' => $houseType['plot_selling_price'] ?? '',
                        'construction_amount' => $houseType['construction_amount'] ?? '',
                        'property_selling_price' => $houseType['property_selling_price'] ?? '',
                        'agent_commission_fees' => $houseType['agent_commission_fees'] ?? '',
                        'other_fees' => $houseType['other_fees'] ?? '',
                    ]);
                    if ((float)$houseType['selling_price'] <= 0) {
                        throw new SellerValidationException('Each house type\'s selling price must be greater than zero.');
                    }
                }
            }
            unset($houseType);
        }
        unset($development);
    }

    $requiredDeclarations = ['certification', 'authorization', 'indemnification', 'commission', 'property_rights'];
    $declarations = array_values(array_unique(array_map('strval', is_array($data['declarations'] ?? null) ? $data['declarations'] : [])));
    if (array_diff($requiredDeclarations, $declarations)) {
        throw new SellerValidationException('All declarations must be accepted.');
    }
    $data['declarations'] = $requiredDeclarations;
    if ($data['signatureType'] !== 'upload' || sellerDate($data['signatureDate'])?->format('Y-m-d') !== date('Y-m-d')) {
        throw new SellerValidationException('Use an uploaded signature dated today.');
    }

    $requiredFiles = ['id_document', 'proof_of_residence', 'title_deed'];
    if ($data['maritalStatus'] === 'Married') {
        $requiredFiles[] = 'marriage_certificate';
    }
    foreach ($requiredFiles as $field) {
        if (count($data[$field]['files'] ?? []) !== 1) {
            throw new SellerValidationException('Upload every required document.');
        }
        validateEncodedSellerFile($data[$field]['files'][0], 'document', 10 * 1024 * 1024);
    }
    if (count($data['signatureFile']['files'] ?? []) !== 1) {
        throw new SellerValidationException('Upload your signature image.');
    }
    validateEncodedSellerFile($data['signatureFile']['files'][0], 'image', 10 * 1024 * 1024);

    $images = &$data['propertyImages']['files'];
    if (!is_array($images) || count($images) < 1 || count($images) > 20) {
        throw new SellerValidationException('Upload between 1 and 20 property images.');
    }
    foreach ($images as &$image) {
        validateEncodedSellerFile($image, 'image', 10 * 1024 * 1024);
    }
    unset($image);
    $videos = &$data['propertyVideos']['files'];
    if (is_array($videos)) {
        if (count($videos) > 3) {
            throw new SellerValidationException('Upload no more than 3 property videos.');
        }
        foreach ($videos as &$video) {
            validateEncodedSellerFile($video, 'video', 25 * 1024 * 1024);
        }
        unset($video);
    }
    foreach (($data['additionalDocFile']['files'] ?? []) as &$additionalFile) {
        validateEncodedSellerFile($additionalFile, 'document', 10 * 1024 * 1024);
    }
    unset($additionalFile);

    $duplicate = $pdo->prepare(
        'SELECT 1 FROM seller_residential_address WHERE LOWER(email) = ?
         UNION SELECT 1 FROM seller_personal_details WHERE id_number = ? LIMIT 1'
    );
    $duplicate->execute([$data['email'], $data['idNumber']]);
    if ($duplicate->fetchColumn()) {
        throw new SellerValidationException('An application already exists for this email address or identity number.');
    }
    $existingAccount = $pdo->prepare('SELECT 1 FROM admin_users WHERE LOWER(email) = ? LIMIT 1');
    $existingAccount->execute([$data['email']]);
    if ($existingAccount->fetchColumn()) {
        throw new SellerValidationException('An account already exists for this email address. Sign in instead.');
    }
}

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
            <a href='{$loginUrl}' style='background:#1e88e5;color:#fff;padding:10px 15px;
               text-decoration:none;border-radius:4px;'>
               Login to Nuru Real Estate
            </a>
        </p>

        <p>If you did not request this account, please contact support immediately.</p>

        <p>Kind regards,<br>
        <strong>Nuru Real Estate Team</strong></p>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Nuru Real Estate <no-reply@nuru.com>\r\n";

    return @mail($toEmail, $subject, $message, $headers);
}

function createAdminUserIfNotExists(PDO $pdo, array $data) {

    if (empty($data['email'])) {
        return null;
    }

    // Check if user already exists
    $check = $pdo->prepare("SELECT id, role FROM admin_users WHERE email = ?");
    $check->execute([$data['email']]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (($existing['role'] ?? '') !== 'seller') {
            throw new SellerValidationException('This email address belongs to a different portal account.');
        }
        return (int)$existing['id']; // Do NOT resend email
    }

    // Previously hardcoded role='manager' here, silently granting full
    // internal admin access to anyone who submitted a public seller
    // application. Fixed to use the dedicated, properly-scoped 'seller'
    // role (dashboard_5.php - own application only, no sidebar).
    $temporaryPassword = generateTemporaryPassword();
    $defaultPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    $role = 'seller';

    $usernameBase = strtolower(
        preg_replace('/\s+/', '.', $data['firstName'] . '.' . $data['surname'])
    );
    $usernameBase = preg_replace('/[^a-z0-9.]+/', '', $usernameBase) ?: 'seller';
    $username = $usernameBase;
    $suffix = 1;
    $usernameCheck = $pdo->prepare('SELECT 1 FROM admin_users WHERE username = ? LIMIT 1');
    while (true) {
        $usernameCheck->execute([$username]);
        if (!$usernameCheck->fetchColumn()) {
            break;
        }
        $username = $usernameBase . '.' . ++$suffix;
    }

    $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['surname'] ?? ''));

    $stmt = $pdo->prepare("
        INSERT INTO admin_users
        (username, email, password_hash, full_name, role, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    $stmt->execute([
        $username,
        $data['email'],
        $defaultPassword,
        $fullName,
        $role
    ]);

    $adminUserId = $pdo->lastInsertId();

    // Deliver only after commit and after the durable receipt is flushed.
    // Synchronous mail delivery here previously kept public seller requests
    // open for roughly a minute while the database transaction was held.
    queueTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword);
    debugLog("Login email queued for post-response delivery");

    return $adminUserId;
}

// Check if data is being received
$rawInput = file_get_contents('php://input');
debugLog("Raw input received: " . strlen($rawInput) . " characters");
if ($rawInput) {
    debugLog('Raw input received without recording applicant data');
}

// ===============================================================
// Generate Unique Application Number for Sellers
// FIXED: removed nested transaction + LOCK/UNLOCK TABLES (these break outer transactions)
// ===============================================================
function generateSellerApplicationNumber(PDO $pdo) {
    $check = $pdo->prepare('SELECT 1 FROM seller_applications WHERE application_number = ? LIMIT 1');
    do {
        $applicationNumber = sprintf('SELL-%s-%06d', date('Ymd'), random_int(0, 999999));
        $check->execute([$applicationNumber]);
    } while ($check->fetchColumn());
    return $applicationNumber;
}

debugLog("Parsing input data...");

// ===============================================================
// IDEMPOTENT SUBMISSION STATE
// ===============================================================
$sellerIdempotencyKey = trim((string)($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ''));
if ($sellerIdempotencyKey === '') {
    $sellerIdempotencyKey = 'legacy-' . substr(hash('sha256', $rawInput . session_id()), 0, 40);
}
if (!preg_match('/^[A-Za-z0-9-]{16,80}$/', $sellerIdempotencyKey)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'The submission identifier is invalid. Reload the page and try again.']);
    exit;
}
$lastSubmission = $_SESSION['_seller_idempotency'][$sellerIdempotencyKey] ?? null;
if (is_array($lastSubmission) && ($lastSubmission['state'] ?? '') === 'succeeded') {
    echo json_encode($lastSubmission['response']);
    exit;
}
if (is_array($lastSubmission) && ($lastSubmission['state'] ?? '') === 'processing'
    && time() - (int)($lastSubmission['time'] ?? 0) < 900) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'error' => 'This application is already being processed. Please wait.',
        'duplicate' => true,
    ]);
    exit;
}
$_SESSION['_seller_idempotency'][$sellerIdempotencyKey] = [
    'state' => 'processing',
    'time' => time(),
];
session_write_close();

debugLog("Duplicate submission check passed");
$createdUploadFiles = [];
$sellerCommitted = false;

try {
    debugLog("Testing database connection...");
    $result = $pdo->query("SELECT 1");
    $result->fetch();
    debugLog("Database connection successful");
    debugLog("Database test query result: SUCCESS");

    // Start transaction
    debugLog("Starting seller transaction...");
    $pdo->beginTransaction();
    debugLog("Transaction started successfully");

    debugLog("Checking for data...");

    // Parse JSON data
    $data = null;
    if ($rawInput) {
        debugLog("Using JSON input");
        $data = json_decode($rawInput, true);
        if ($data === null) {
            throw new Exception('Invalid JSON input');
        }
        debugLog("JSON decoded successfully: YES");
        debugLog("Data variable type: " . gettype($data));
        debugLog("Data keys: " . implode(', ', array_keys($data)));
        debugLog("Data array size: " . count($data));
    } else {
        debugLog("No input data received");
        throw new Exception('No data received');
    }

    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null);
    if (!SecurityIntegration::validateCSRFToken($submittedToken, 'seller_application_submit')) {
        debugLog("CSRF token validation failed");
        throw new Exception("Invalid or expired form token, please reload the page");
    }

    if (!verifyTurnstile($data['cf-turnstile-response'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null)) {
        debugLog("Turnstile CAPTCHA verification failed");
        throw new Exception("CAPTCHA verification failed. Please try again.");
    }

    if (($data['signatureType'] ?? '') === 'otp') {
        throw new Exception('SMS verification is not available. Please use a drawn or uploaded signature.');
    }

    validateSellerApplication($pdo, $data);

    // Generate application number
    debugLog("Generating seller application number...");
    $applicationNumber = generateSellerApplicationNumber($pdo);
    debugLog("Generated application number: " . $applicationNumber);

    // ===============================================================
    // 1. INSERT MAIN SELLER APPLICATION
    // ===============================================================
    debugLog("Preparing main seller application insert statement...");

    $stmt = $pdo->prepare("
        INSERT INTO seller_applications (application_number, status, submission_date, created_at)
        VALUES (?, 'submitted', NOW(), NOW())
    ");

    debugLog("Execute parameters: [\"$applicationNumber\"]");
    $result = $stmt->execute([$applicationNumber]);
    debugLog("Main application insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

    if (!$result) {
        throw new Exception('Failed to insert main seller application');
    }

    $application_id = $pdo->lastInsertId();
    debugLog("Inserted application ID: " . $application_id);

    // ===============================================================
    // 2. INSERT PERSONAL DETAILS
    // ===============================================================
    if (!empty($data['firstName']) || !empty($data['surname'])) {
        debugLog("Processing personal details...");

        $age = null;
        if (!empty($data['dateOfBirth'])) {
            $birthDate = new DateTime($data['dateOfBirth']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        $loaded_by = 'Self Loaded';
        if (!empty($_SESSION['user_id'])) {
            $loaded_by = $_SESSION['user_id'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO seller_personal_details
            (application_id, surname, first_name, middle_name, maiden_name, date_of_birth, age, id_type, id_number, nationality, gender, created_at, loaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        debugLog("Personal details parameters: " . json_encode([
            $data['surname'] ?? null,
            $data['firstName'] ?? null,
            $data['middleName'] ?? null,
            $data['maidenName'] ?? null,
            $data['dateOfBirth'] ?? null,
            $age,
            $data['idType'] ?? 'National ID',
            $data['idNumber'] ?? null,
            $data['nationality'] ?? null,
            $data['gender'] ?? null
        ]));

        $result = $stmt->execute([
            $application_id,
            $data['surname'] ?? null,
            $data['firstName'] ?? null,
            $data['middleName'] ?? null,
            $data['maidenName'] ?? null,
            $data['dateOfBirth'] ?? null,
            $age,
            $data['idType'] ?? 'National ID',
            $data['idNumber'] ?? null,
            $data['nationality'] ?? null,
            $data['gender'] ?? null,
            $loaded_by
        ]);

        $adminUserId = createAdminUserIfNotExists($pdo, $data);
        if ($adminUserId) {
            $pdo->prepare("UPDATE seller_applications SET user_id = ? WHERE id = ?")
                ->execute([$adminUserId, $application_id]);
        }
        debugLog("Personal details insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

        if (!$result) {
            throw new Exception('Failed to insert personal details');
        }
    }

    // ===============================================================
    // 3. INSERT MARITAL STATUS
    // ===============================================================
    if (!empty($data['maritalStatus'])) {
        debugLog("Processing marital status...");

        $stmt = $pdo->prepare("
            INSERT INTO seller_marital_status
            (application_id, marital_status, spouse_surname, spouse_first_name, spouse_date_of_birth, spouse_id_type, spouse_id_number, spouse_nationality, spouse_gender, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        debugLog("Marital status parameters: " . json_encode([
            $data['maritalStatus'],
            $data['spouseSurname'] ?? null,
            $data['spouseFirstName'] ?? null,
            $data['spouseDateOfBirth'] ?? null,
            $data['spouseIdType'] ?? null,
            $data['spouseIdNumber'] ?? null,
            $data['spouseNationality'] ?? null,
            $data['spouseGender'] ?? null
        ]));

        $result = $stmt->execute([
            $application_id,
            $data['maritalStatus'],
            $data['spouseSurname'] ?? null,
            $data['spouseFirstName'] ?? null,
            $data['spouseDateOfBirth'] ?? null,
            $data['spouseIdType'] ?? null,
            $data['spouseIdNumber'] ?? null,
            $data['spouseNationality'] ?? null,
            $data['spouseGender'] ?? null
        ]);

        debugLog("Marital status insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

        if (!$result) {
            throw new Exception('Failed to insert marital status');
        }
    }

    // ===============================================================
    // 4. INSERT RESIDENTIAL ADDRESS
    // ===============================================================
    if (!empty($data['streetName']) || !empty($data['region'])) {
        debugLog("Processing residential address...");

        $stmt = $pdo->prepare("
            INSERT INTO seller_residential_address
            (application_id, erf_no, street_name, suburb, location, region, town, email, mobile_number, po_box, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        debugLog("Residential address parameters: " . json_encode([
            $data['erfNo'] ?? null,
            $data['streetName'] ?? null,
            $data['suburb'] ?? null,
            $data['location'] ?? null,
            $data['region'] ?? null,
            $data['town'] ?? null,
            $data['email'] ?? null,
            $data['mobileNumber'] ?? null,
            $data['poBox'] ?? null
        ]));

        $result = $stmt->execute([
            $application_id,
            $data['erfNo'] ?? null,
            $data['streetName'] ?? null,
            $data['suburb'] ?? null,
            $data['location'] ?? null,
            $data['region'] ?? null,
            $data['town'] ?? null,
            $data['email'] ?? null,
            $data['mobileNumber'] ?? null,
            $data['poBox'] ?? null
        ]);

        debugLog("Residential address insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

        if (!$result) {
            throw new Exception('Failed to insert residential address');
        }
    }

    // ===============================================================
    // 5. INSERT NEXT OF KIN
    // ===============================================================
    if (!empty($data['nokFirstName']) || !empty($data['nokSurname'])) {
        debugLog("Processing next of kin...");

        $stmt = $pdo->prepare("
            INSERT INTO seller_next_of_kin
            (application_id, nok_surname, nok_first_name, nok_contact_number, nok_email, nok_erf_no, nok_street_name, nok_suburb, nok_location, nok_region, nok_town, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        debugLog("Next of kin parameters: " . json_encode([
            $data['nokSurname'] ?? null,
            $data['nokFirstName'] ?? null,
            $data['nokContactNumber'] ?? null,
            $data['nokEmail'] ?? null,
            $data['nokErfNo'] ?? null,
            $data['nokStreetName'] ?? null,
            $data['nokSuburb'] ?? null,
            $data['nokLocation'] ?? null,
            $data['nokRegion'] ?? null,
            $data['nokTown'] ?? null
        ]));

        $result = $stmt->execute([
            $application_id,
            $data['nokSurname'] ?? null,
            $data['nokFirstName'] ?? null,
            $data['nokContactNumber'] ?? null,
            $data['nokEmail'] ?? null,
            $data['nokErfNo'] ?? null,
            $data['nokStreetName'] ?? null,
            $data['nokSuburb'] ?? null,
            $data['nokLocation'] ?? null,
            $data['nokRegion'] ?? null,
            $data['nokTown'] ?? null
        ]);

        debugLog("Next of kin insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

        if (!$result) {
            throw new Exception('Failed to insert next of kin');
        }
    }

    // ===============================================================
    // 6. INSERT PROPERTY DETAILS
    // ===============================================================
    $propertyId = null; // guards property images/videos inserts below if this step is ever skipped
    if (!empty($data['propertyDetailType']) || !empty($data['propertyStreetName'])) {
        debugLog("Processing property details...");

        $stmt = $pdo->prepare("
            INSERT INTO seller_properties
            (application_id, property_detail_type, land_type, sale_pricing_type, plot_selling_price, construction_amount, property_selling_price, agent_commission_fees, land_size, selling_price, house_size, number_of_rooms, number_of_bathrooms, additional_features, property_erf_no, property_street_name, property_suburb, property_location, property_region, property_town, listing_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        debugLog("Property details parameters: " . json_encode([
            $data['propertyDetailType'] ?? null,
            $data['landType'] ?? null,
            $data['landSize'] ?? null,
            (float)$data['sellingPrice'] ?? null,
            $data['houseSize'] ?? null,
            $data['rooms'] ?? null,
            $data['bathrooms'] ?? null,
            $data['additionalFeatures'] ?? null,
            $data['propertyErfNo'] ?? null,
            $data['propertyStreetName'] ?? null,
            $data['propertySuburb'] ?? null,
            $data['propertyLocation'] ?? null,
            $data['propertyRegion'] ?? null,
            $data['propertyTown'] ?? null
        ]));

		$rawSellingPrice = $data['sellingPrice'] ?? null;

		$cleanSellingPrice = null;
		if ($rawSellingPrice !== null && $rawSellingPrice !== '') {
			// remove commas, spaces, and currency symbols (N$, $, etc.)
			$clean = preg_replace('/[^\d.\-]/', '', (string)$rawSellingPrice);
			$cleanSellingPrice = ($clean === '' ? null : (float)$clean);
		}



        $result = $stmt->execute([
            $application_id,
            $data['propertyDetailType'] ?? null,
            $data['landType'] ?? null,
            $data['salePricingType'] ?: null,
            sellerMoneyAmount($data['plotSellingPrice'] ?? null) ?: null,
            sellerMoneyAmount($data['constructionAmount'] ?? null) ?: null,
            sellerMoneyAmount($data['propertySellingPrice'] ?? null) ?: null,
            sellerMoneyAmount($data['agentCommissionFees'] ?? null) ?: null,
            $data['landSize'] ?? null,
            $cleanSellingPrice ?? null,
            $data['houseSize'] ?? null,
            $data['rooms'] ?? null,
            $data['bathrooms'] ?? null,
            $data['additionalFeatures'] ?? null,
            $data['propertyErfNo'] ?? null,
            $data['propertyStreetName'] ?? null,
            $data['propertySuburb'] ?? null,
            $data['propertyLocation'] ?? null,
            $data['propertyRegion'] ?? null,
            $data['propertyTown'] ?? null
        ]);

        debugLog("Property details insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

        if (!$result) {
            throw new Exception('Failed to insert property details');
        }
        $propertyId = $pdo->lastInsertId();
    }

    // ===============================================================
    // 7. INSERT SALE TYPE
    // ===============================================================
    if (!empty($data['saleType'])) {
        debugLog("Processing sale type...");

        $stmt = $pdo->prepare("
            INSERT INTO seller_sale_type
            (application_id, sale_type, developer_name, property_type)
            VALUES (?, ?, ?, ?)
        ");

        debugLog("Sale type parameters: " . json_encode([
            $data['saleType'],
            $data['developerName'] ?? null,
            $data['propertyType'] ?? null
        ]));

        $result = $stmt->execute([
            $application_id,
            $data['saleType'],
            $data['developerName'] ?? null,
            $data['propertyType'] ?? null
        ]);

        debugLog("Sale type insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

        if (!$result) {
            throw new Exception('Failed to insert sale type');
        }
    }

    // ===============================================================
    // 7b. INSERT PROPERTY DEVELOPMENTS + GENERATED UNIT PROPERTIES
    // Each house type's unit count generates that many real, individually
    // matchable seller_properties rows (not a single row with a mutable
    // counter), so units work with the existing matching/task/checklist/
    // countdown pipeline unchanged. development_house_type_id links each
    // generated row back to its house type for live remaining-stock counts.
    // ===============================================================
    if (($data['saleType'] ?? null) === 'Property Development' && !empty($data['developments']) && is_array($data['developments'])) {
        debugLog("Processing property developments...");

        $propertyTypeMap = [
            'Free Standing House Unit' => 'Single Residential',
            'General Residential House Unit' => 'General Residential',
            'Business/Commercial Property' => 'Commercial/Business',
            'Farm Property' => 'Farm',
            'Institutional Property' => 'Institutional',
        ];

        $devStmt = $pdo->prepare("
            INSERT INTO seller_developments
            (application_id, development_index, development_name, region, town, location, suburb, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $houseTypeStmt = $pdo->prepare("
            INSERT INTO seller_development_house_types
            (development_id, house_type_index, property_type, number_of_units, house_size, land_type, sale_pricing_type, plot_selling_price, construction_amount, property_selling_price, agent_commission_fees, other_fees, land_size, selling_price, number_of_rooms, number_of_bathrooms, additional_features, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $unitStmt = $pdo->prepare("
            INSERT INTO seller_properties
            (application_id, property_detail_type, land_type, sale_pricing_type, plot_selling_price, construction_amount, property_selling_price, agent_commission_fees, other_fees, land_size, house_size, selling_price, number_of_rooms, number_of_bathrooms, additional_features, property_street_name, property_suburb, property_location, property_region, property_town, development_house_type_id, listing_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($data['developments'] as $devIndex => $development) {
            if (empty($development['development_name'])) {
                continue;
            }

            $devStmt->execute([
                $application_id,
                $devIndex + 1,
                $development['development_name'],
                $development['region'] ?? null,
                $development['town'] ?? null,
                $development['location'] ?? null,
                $development['suburb'] ?? null,
            ]);
            $developmentId = $pdo->lastInsertId();

            $houseTypes = $development['house_types'] ?? [];
            foreach ($houseTypes as $htIndex => $houseType) {
                if (empty($houseType['property_type'])) {
                    continue;
                }

                // Comma-formatted monetary/size input ("456,960.78") is stripped
                // down to plain digits/decimal here regardless of formatting.
                $rawLandSize = $houseType['land_size'] ?? null;
                $landSize = $rawLandSize !== null ? preg_replace('/[^\d.\-]/', '', (string)$rawLandSize) : null;

                $rawHouseSize = $houseType['house_size'] ?? null;
                $houseSize = $rawHouseSize !== null ? preg_replace('/[^\d.\-]/', '', (string)$rawHouseSize) : null;

                $rawPrice = $houseType['selling_price'] ?? null;
                $sellingPrice = $rawPrice !== null ? preg_replace('/[^\d.\-]/', '', (string)$rawPrice) : null;

                $salePricingType = $houseType['sale_pricing_type'] ?? null;
                $plotSellingPrice = sellerMoneyAmount($houseType['plot_selling_price'] ?? null) ?: null;
                $constructionAmount = sellerMoneyAmount($houseType['construction_amount'] ?? null) ?: null;
                $propertySellingPrice = sellerMoneyAmount($houseType['property_selling_price'] ?? null) ?: null;
                $agentCommissionFees = sellerMoneyAmount($houseType['agent_commission_fees'] ?? null) ?: null;
                $otherFees = sellerMoneyAmount($houseType['other_fees'] ?? null) ?: null;

                $houseTypeStmt->execute([
                    $developmentId,
                    $htIndex + 1,
                    $houseType['property_type'],
                    (int)($houseType['number_of_units'] ?? 1),
                    $houseSize !== '' ? $houseSize : null,
                    $houseType['land_type'] ?? null,
                    $salePricingType,
                    $plotSellingPrice,
                    $constructionAmount,
                    $propertySellingPrice,
                    $agentCommissionFees,
                    $otherFees,
                    $landSize !== '' ? $landSize : null,
                    $sellingPrice !== '' ? $sellingPrice : null,
                    $houseType['rooms'] ?? null,
                    $houseType['bathrooms'] ?? null,
                    $houseType['additional_features'] ?? null,
                ]);
                $houseTypeId = $pdo->lastInsertId();

                $unitCount = max(1, (int)($houseType['number_of_units'] ?? 1));
                $mappedPropertyType = $propertyTypeMap[$houseType['property_type']] ?? 'General Residential';

                for ($i = 1; $i <= $unitCount; $i++) {
                    $streetLabel = sprintf('%s - %s #%d', $development['development_name'], $houseType['property_type'], $i);

                    $unitStmt->execute([
                        $application_id,
                        $mappedPropertyType,
                        $houseType['land_type'] ?? null,
                        $salePricingType,
                        $plotSellingPrice,
                        $constructionAmount,
                        $propertySellingPrice,
                        $agentCommissionFees,
                        $otherFees,
                        $landSize !== '' ? $landSize : null,
                        $houseSize !== '' ? $houseSize : null,
                        $sellingPrice !== '' ? $sellingPrice : null,
                        $houseType['rooms'] ?? null,
                        $houseType['bathrooms'] ?? null,
                        $houseType['additional_features'] ?? null,
                        $streetLabel,
                        $development['suburb'] ?? null,
                        $development['location'] ?? null,
                        $development['region'] ?? null,
                        $development['town'] ?? null,
                        $houseTypeId,
                    ]);

                    if ($propertyId === null) {
                        $propertyId = $pdo->lastInsertId();
                    }
                }
            }
        }

        debugLog("Property developments processed successfully");
    }

    // ===============================================================
    // 8. INSERT DOCUMENTS
    // FIXED: define $decodedContent from $fileContent (previously undefined)
    // ===============================================================
    $documentTypes = ['id_document', 'proof_of_residence', 'title_deed', 'marriage_certificate'];

    foreach ($documentTypes as $docType) {
        $fileField = $docType;

        // getFileInputData() in seller-form.js always returns {files:[],hasFiles:false}
        // for an untouched input, never null/undefined - checking !empty($data[$fileField])
        // was always true (non-empty wrapper object) and created a phantom "unknown"/0-byte
        // document row for every unselected optional file (e.g. marriage_certificate when
        // not married). Must check the files array itself is non-empty.
        if (!empty($data[$fileField]['files']) && is_array($data[$fileField]['files'])) {
            debugLog("Processing $docType document...");

            $fileData = $data[$fileField];
            $fileName = $fileData['files'][0]['name'] ?? 'unknown';
            $fileSize = $fileData['files'][0]['size'] ?? 0;
            $fileType = $fileData['files'][0]['type'] ?? 'application/octet-stream';
            $fileContent = $fileData['files'][0]['content'] ?? null;

            // Never trust the client-supplied filename for the saved path -
            // it's fully attacker-controlled and was previously concatenated
            // in directly, allowing both path traversal and arbitrary
            // extensions (e.g. a ".php" upload saved into the web root).
            // The original name is still stored as original_filename for
            // display; only the on-disk name is regenerated.
            $safeName = safeUploadFilename($fileName, ALLOWED_DOCUMENT_EXTENSIONS);
            if ($safeName === null) {
                debugLog("ERROR: Rejected $docType upload with disallowed extension: $fileName");
                throw new Exception("File type not allowed for $docType");
            }

            // Create upload directory if it doesn't exist
            $uploadDir = dirname(__DIR__, 3) . "/uploads/seller/$docType/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Save file to disk
            $filePath = "uploads/seller/$docType/" . $safeName;
            $fullPath = dirname(__DIR__, 3) . "/" . $filePath;

            $decodedContent = base64_decode((string)$fileContent, true);

            if ($decodedContent !== false) {
                $saveResult = file_put_contents($fullPath, $decodedContent, LOCK_EX);
                if ($saveResult !== false) {
                    $createdUploadFiles[] = $fullPath;
                    debugLog("File saved to: $fullPath ($saveResult bytes)");
                } else {
                    debugLog("ERROR: Failed to save file to: $fullPath - " . (error_get_last()['message'] ?? 'unknown'));
                    throw new Exception("Failed to save file: $fullPath");
                }
            } else {
                debugLog("ERROR: Invalid base64 content for $docType");
                throw new Exception("Invalid base64 content for $docType");
            }

            $fileHash = hash_file('sha256', $fullPath);

            $stmt = $pdo->prepare("
                INSERT INTO seller_documents
                (application_id, document_type, document_name, original_filename, file_path, file_size, mime_type, file_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            debugLog("Document parameters for $docType: " . json_encode([
                $docType,
                $fileName,
                $filePath,
                $fileSize,
                $fileType
            ]));

            $result = $stmt->execute([
                $application_id,
                $docType,
                $docType,
                $fileName,
                $filePath,
                $fileSize,
                $fileType,
                $fileHash
            ]);

            debugLog("$docType document insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                debugLog("Warning: Failed to insert $docType document - " . implode(' - ', $errorInfo));
            }
        } else {
            debugLog("No $docType document found in data (field: $fileField)");
        }
    }

    // ===============================================================
    // 8.1. INSERT ADDITIONAL DOCUMENTS
    // ===============================================================
    if (!empty($data['additionalDocName']) && !empty($data['additionalDocFile'])) {
        debugLog("Processing additional documents...");

        $additionalDocNames = is_array($data['additionalDocName']) ? $data['additionalDocName'] : [$data['additionalDocName']];
        $additionalDocFiles = $data['additionalDocFile'];

        if (is_array($additionalDocNames) && is_array($additionalDocFiles['files'])) {
            foreach ($additionalDocFiles['files'] as $index => $file) {
                if (!empty($file['name']) && !empty($additionalDocNames[$index])) {
                    $docName = $additionalDocNames[$index];
                    $fileName = $file['name'];
                    $safeName = safeUploadFilename($fileName, ALLOWED_DOCUMENT_EXTENSIONS);
                    if ($safeName === null) {
                        throw new SellerValidationException('An additional document has a disallowed file type.');
                    }
                    $filePath = "uploads/seller/additional_documents/$safeName";
                    $fullFilePath = dirname(__DIR__, 3) . "/" . $filePath;
                    $fileSize = $file['size'] ?? 0;
                    $fileType = $file['type'] ?? 'application/octet-stream';

                    $addDocDir = dirname(__DIR__, 3) . "/uploads/seller/additional_documents/";
                    if (!is_dir($addDocDir)) {
                        mkdir($addDocDir, 0755, true);
                    }

                    if (!empty($file['content'])) {
                        $addDocDecoded = base64_decode($file['content'], true);
                        if ($addDocDecoded !== false) {
                            $addDocSaveResult = file_put_contents($fullFilePath, $addDocDecoded, LOCK_EX);
                            if ($addDocSaveResult !== false) {
                                $createdUploadFiles[] = $fullFilePath;
                                debugLog("Additional document saved to: $fullFilePath ($addDocSaveResult bytes)");
                            } else {
                                throw new RuntimeException('Failed to save an additional document.');
                            }
                        } else {
                            throw new SellerValidationException('An additional document is invalid.');
                        }
                    } else {
                        throw new SellerValidationException('An additional document is empty.');
                    }
                    $fileHash = hash_file('sha256', $fullFilePath);

                    $stmt = $pdo->prepare("
                        INSERT INTO seller_additional_documents
                        (application_id, document_name, original_filename, file_path, file_size, mime_type, file_hash)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $application_id,
                        $docName,
                        $fileName,
                        $filePath,
                        $fileSize,
                        $fileType,
                        $fileHash
                    ]);

                    debugLog("Additional document insert result for $docName ($fileName): " . ($result ? 'SUCCESS' : 'FAILURE'));
                }
            }
        }
    }

    // ===============================================================
    // 9. INSERT PROPERTY IMAGES
    // ===============================================================
    if (!empty($data['propertyImages'])) {
        debugLog("Processing property images...");

        $images = $data['propertyImages'];
        if (is_array($images['files'])) {
            foreach ($images['files'] as $index => $file) {
                if (!empty($file['name'])) {
                    $safeImageName = safeUploadFilename((string)$file['name'], ALLOWED_IMAGE_EXTENSIONS);
                    if ($safeImageName === null) {
                        throw new SellerValidationException('A property image has a disallowed file type.');
                    }
                    $imageName = pathinfo($safeImageName, PATHINFO_FILENAME);
                    $imagePath = "uploads/seller/property_images/$safeImageName";
                    $fullImagePath = dirname(__DIR__, 3) . "/" . $imagePath;
                    $originalFilename = $file['name'];
                    $fileSize = $file['size'] ?? 0;
                    $mimeType = $file['type'] ?? 'image/jpeg';
                    $isPrimary = $index === 0 ? 1 : 0;

                    $imgDir = dirname(__DIR__, 3) . "/uploads/seller/property_images/";
                    if (!is_dir($imgDir)) {
                        mkdir($imgDir, 0755, true);
                    }

                    if (!empty($file['content'])) {
                        $imgDecoded = base64_decode($file['content'], true);
                        if ($imgDecoded !== false) {
                            $imgSaveResult = file_put_contents($fullImagePath, $imgDecoded, LOCK_EX);
                            if ($imgSaveResult !== false) {
                                $createdUploadFiles[] = $fullImagePath;
                                debugLog("Property image saved to: $fullImagePath ($imgSaveResult bytes)");
                            } else {
                                throw new RuntimeException('Failed to save a property image.');
                            }
                        } else {
                            throw new SellerValidationException('A property image is invalid.');
                        }
                    } else {
                        throw new SellerValidationException('A property image is empty.');
                    }
                    $fileHash = hash_file('sha256', $fullImagePath);

                    $stmt = $pdo->prepare("
                        INSERT INTO seller_property_images
                        (application_id, image_name, original_filename, file_path, file_size, mime_type, file_hash, image_order, is_primary, propertyId)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $application_id,
                        $imageName,
                        $originalFilename,
                        $imagePath,
                        $fileSize,
                        $mimeType,
                        $fileHash,
                        $index,
                        $isPrimary,
                        $propertyId
                    ]);

                    debugLog("Property image insert result for $originalFilename: " . ($result ? 'SUCCESS' : 'FAILURE'));
                }
            }
        }
    }

    // ===============================================================
    // 10. INSERT PROPERTY VIDEOS
    // ===============================================================
    if (!empty($data['propertyVideos'])) {
        debugLog("Processing property videos...");

        $videos = $data['propertyVideos'];
        if (is_array($videos['files'])) {
            foreach ($videos['files'] as $index => $file) {
                if (!empty($file['name'])) {
                    $safeVideoName = safeUploadFilename((string)$file['name'], ALLOWED_VIDEO_EXTENSIONS);
                    if ($safeVideoName === null) {
                        throw new SellerValidationException('A property video has a disallowed file type.');
                    }
                    $videoName = pathinfo($safeVideoName, PATHINFO_FILENAME);
                    $videoPath = "uploads/seller/property_videos/$safeVideoName";
                    $fullVideoPath = dirname(__DIR__, 3) . "/" . $videoPath;
                    $originalFilename = $file['name'];
                    $fileSize = $file['size'] ?? 0;
                    $mimeType = $file['type'] ?? 'video/mp4';

                    $vidDir = dirname(__DIR__, 3) . "/uploads/seller/property_videos/";
                    if (!is_dir($vidDir)) {
                        mkdir($vidDir, 0755, true);
                    }

                    if (!empty($file['content'])) {
                        $vidDecoded = base64_decode($file['content'], true);
                        if ($vidDecoded !== false) {
                            $vidSaveResult = file_put_contents($fullVideoPath, $vidDecoded, LOCK_EX);
                            if ($vidSaveResult !== false) {
                                $createdUploadFiles[] = $fullVideoPath;
                                debugLog("Property video saved to: $fullVideoPath ($vidSaveResult bytes)");
                            } else {
                                throw new RuntimeException('Failed to save a property video.');
                            }
                        } else {
                            throw new SellerValidationException('A property video is invalid.');
                        }
                    } else {
                        throw new SellerValidationException('A property video is empty.');
                    }
                    $fileHash = hash_file('sha256', $fullVideoPath);

                    $stmt = $pdo->prepare("
                        INSERT INTO seller_property_videos
                        (application_id, video_name, original_filename, file_path, file_size, mime_type, file_hash, video_order, propertyId)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $application_id,
                        $videoName,
                        $originalFilename,
                        $videoPath,
                        $fileSize,
                        $mimeType,
                        $fileHash,
                        $index,
                        $propertyId
                    ]);

                    debugLog("Property video insert result for $originalFilename: " . ($result ? 'SUCCESS' : 'FAILURE'));
                }
            }
        }
    }

    // ===============================================================
    // 11. INSERT DECLARATIONS
    // ===============================================================
    debugLog("Raw declarations data: " . json_encode($data['declarations'] ?? 'NOT FOUND'));

    $certification = 0;
    $authorization = 0;
    $indemnification = 0;
    $commission = 0;
    $propertyRights = 0;

    if (isset($data['declarations'])) {
        debugLog("Processing declarations...");
        $declarations = $data['declarations'];
        debugLog("Declarations raw value: " . json_encode($declarations));

        if (is_array($declarations) && !empty($declarations) && array_keys($declarations) !== range(0, count($declarations) - 1)) {
            debugLog("Processing as associative array");
            $certification = !empty($declarations['certification']) ? 1 : 0;
            $authorization = !empty($declarations['authorization']) ? 1 : 0;
            $indemnification = !empty($declarations['indemnification']) ? 1 : 0;
            $commission = !empty($declarations['commission']) ? 1 : 0;
            $propertyRights = !empty($declarations['property_rights']) ? 1 : 0;
        } elseif (is_array($declarations) && !empty($declarations)) {
            debugLog("Processing as indexed array with " . count($declarations) . " items");
            $certification = (!empty($declarations[0]) && $declarations[0] === 'on') ? 1 : 0;
            $authorization = (!empty($declarations[1]) && $declarations[1] === 'on') ? 1 : 0;
            $indemnification = (!empty($declarations[2]) && $declarations[2] === 'on') ? 1 : 0;
            $commission = (!empty($declarations[3]) && $declarations[3] === 'on') ? 1 : 0;
            $propertyRights = (!empty($declarations[4]) && $declarations[4] === 'on') ? 1 : 0;

            if ($certification === 0) $certification = in_array('certification', $declarations, true) ? 1 : 0;
            if ($authorization === 0) $authorization = in_array('authorization', $declarations, true) ? 1 : 0;
            if ($indemnification === 0) $indemnification = in_array('indemnification', $declarations, true) ? 1 : 0;
            if ($commission === 0) $commission = in_array('commission', $declarations, true) ? 1 : 0;
            if ($propertyRights === 0) $propertyRights = in_array('property_rights', $declarations, true) ? 1 : 0;
        } else {
            debugLog("Checking individual declaration fields");
            $certification = !empty($data['certification']) ? 1 : 0;
            $authorization = !empty($data['authorization']) ? 1 : 0;
            $indemnification = !empty($data['indemnification']) ? 1 : 0;
            $commission = !empty($data['commission']) ? 1 : 0;
            $propertyRights = !empty($data['property_rights']) ? 1 : 0;
        }

        debugLog("Final declarations - certification: $certification, authorization: $authorization, indemnification: $indemnification, commission: $commission, propertyRights: $propertyRights");

        $stmt = $pdo->prepare("
            INSERT INTO seller_declarations
            (application_id, certification_declaration, authorization_declaration, indemnification_declaration, commission_fees_declaration, property_rights_declaration, signature_location, signature_date, signature_type, signature_file_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        debugLog("Declaration parameters: " . json_encode([
            $certification, $authorization, $indemnification, $commission, $propertyRights,
            $data['signatureLocation'] ?? null,
            $data['signatureDate'] ?? null,
            $data['signatureType'] ?? 'upload'
        ]));

        $signatureFilePath = null;
        if (!empty($data['signatureFile']['files'][0]['name'])) {
            $signatureFileName = $data['signatureFile']['files'][0]['name'];
            $signatureSafeName = safeUploadFilename($signatureFileName, ALLOWED_IMAGE_EXTENSIONS);
            if ($signatureSafeName === null) {
                debugLog("ERROR: Rejected signature upload with disallowed extension: $signatureFileName");
                throw new Exception("File type not allowed for signature");
            }
            $signatureFilePath = "uploads/seller/signature/$signatureSafeName";
            $fullSignaturePath = dirname(__DIR__, 3) . "/" . $signatureFilePath;
            $signatureDir = dirname(__DIR__, 3) . "/uploads/seller/signature/";

            if (!is_dir($signatureDir)) {
                mkdir($signatureDir, 0755, true);
            }

            $sigFileData = $data['signatureFile']['files'][0];
            if (!empty($sigFileData['content'])) {
                $sigDecoded = base64_decode($sigFileData['content'], true);
                if ($sigDecoded !== false) {
                    $sigSaveResult = file_put_contents($fullSignaturePath, $sigDecoded, LOCK_EX);
                    if ($sigSaveResult !== false) {
                        $createdUploadFiles[] = $fullSignaturePath;
                        debugLog("Signature file saved to: $fullSignaturePath ($sigSaveResult bytes)");
                    } else {
                        throw new RuntimeException('Failed to save the signature image.');
                    }
                } else {
                    throw new SellerValidationException('The signature image is invalid.');
                }
            } else {
                throw new SellerValidationException('The signature image is empty.');
            }
        }

        $result = $stmt->execute([
            $application_id,
            $certification,
            $authorization,
            $indemnification,
            $commission,
            $propertyRights,
            $data['signatureLocation'] ?? null,
            $data['signatureDate'] ?? null,
            $data['signatureType'] ?? 'upload',
            $signatureFilePath
        ]);

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            debugLog("Declaration insert error: " . implode(' - ', $errorInfo));
            throw new Exception('Failed to insert declarations: ' . implode(' - ', $errorInfo));
        }

        debugLog("Declarations insert result: SUCCESS - certification=$certification, authorization=$authorization, indemnification=$indemnification, commission=$commission, propertyRights=$propertyRights");
    } else {
        debugLog("No declarations data found in request");
    }

    // ===============================================================
    // Commit Transaction
    // ===============================================================
    debugLog("Committing seller transaction...");

    if (!$pdo->inTransaction()) {
        $errorInfo = $pdo->errorInfo();
        $pdoError = $errorInfo[2] ?? 'Unknown database error';
        debugLog("ERROR: No active transaction - transaction was likely auto-rolled back. PDO Error: $pdoError");
        throw new Exception('Transaction was rolled back due to an error. Please check the logs. Database error: ' . $pdoError);
    }

    $commitResult = $pdo->commit();
    debugLog("Commit result: " . ($commitResult ? 'SUCCESS' : 'FAILURE'));

    if (!$commitResult) {
        throw new Exception('Failed to commit transaction');
    }
    $sellerCommitted = true;
    $response = [
        'success' => true,
        'message' => 'Seller application submitted successfully!',
        'data' => [
            'application_number' => $applicationNumber,
            'application_id' => $application_id,
            'status' => 'submitted',
            'submission_date' => date('Y-m-d H:i:s')
        ]
    ];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_seller_idempotency'][$sellerIdempotencyKey] = [
        'state' => 'succeeded',
        'time' => time(),
        'response' => $response,
    ];
    unset($_SESSION['_csrf_tokens']['seller_application_submit']);
    session_write_close();

    debugLog("Sending success response");
    finishResponseAndDeliverQueuedCredentialEmails((string)json_encode($response, JSON_PRETTY_PRINT));
    exit;

} catch (Throwable $e) {
    debugLog("=== EXCEPTION CAUGHT ===");
    debugLog("Exception Message: " . $e->getMessage());
    debugLog("Exception File: " . $e->getFile());
    debugLog("Exception Line: " . $e->getLine());
    debugLog("Exception Code: " . $e->getCode());

    $trace = $e->getTrace();
    debugLog("Exception Trace Details:");
    foreach ($trace as $i => $traceItem) {
        debugLog("  [$i] " . ($traceItem['file'] ?? 'N/A') . ":" . ($traceItem['line'] ?? 'N/A') . " - " . ($traceItem['function'] ?? 'N/A'));
    }

    $lastErr = error_get_last();
    debugLog("Last PHP Error: " . ($lastErr['message'] ?? 'none'));
    debugLog("Current PHP Version: " . phpversion());
    debugLog('Server request context captured without sensitive headers');

    // A committed application is already durable. Return its receipt rather
    // than encouraging a retry that could create a second seller record if a
    // post-commit session-cache operation fails.
    if ($sellerCommitted && isset($response) && is_array($response)) {
        http_response_code(200);
        finishResponseAndDeliverQueuedCredentialEmails((string)json_encode($response, JSON_PRETTY_PRINT));
        exit;
    }

    if (isset($pdo) && $pdo->inTransaction()) {
        debugLog("Rolling back transaction...");
        try {
            $pdo->rollBack();
            debugLog("Transaction rolled back successfully");
        } catch (Exception $rollbackException) {
            debugLog("Rollback failed: " . $rollbackException->getMessage());
        }
    }

    if (!$sellerCommitted) {
        foreach ($createdUploadFiles as $createdUploadFile) {
            if (is_string($createdUploadFile) && is_file($createdUploadFile)) {
                @unlink($createdUploadFile);
            }
        }
    }
    if (!empty($sellerIdempotencyKey)) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['_seller_idempotency'][$sellerIdempotencyKey]);
        session_write_close();
    }

    debugLog("Sending error response to client");

    // File paths, line numbers, stack traces, and PHP version were previously
    // sent to the CLIENT on every error - a reconnaissance gift to an attacker
    // (server layout, exact source lines, version for CVE targeting). All of
    // this is already captured above via debugLog() for real debugging; the
    // client only needs the user-facing message.
    $isValidationError = $e instanceof SellerValidationException;
    http_response_code($isValidationError ? 422 : 500);
    $errorResponse = [
        'success' => false,
        'error' => $isValidationError
            ? $e->getMessage()
            : 'Unable to submit the application. Please try again later.',
        'application_number' => $applicationNumber ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    debugLog("=== EXCEPTION HANDLING COMPLETED ===");
}
?>

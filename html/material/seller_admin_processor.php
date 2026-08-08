<?php
// ===============================================================
// File: seller_admin_processor.php
// Purpose: Save seller multi-step form (personal, marital, address, kin, property, docs)
// ===============================================================
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/config/account_provisioning.php';

if (!sessionHasAuthoritativeRole(['admin', 'manager', 'agent_coordinator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'staff_seller_form')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Your session has expired. Please reload and try again.']);
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
    $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', (string)$message), 0, 1000);
    $logMessage = "[$timestamp] $safeMessage\n";
    error_log($logMessage, 3, __DIR__ . '/logs/debug.log');
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

const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'gif', 'tiff', 'bmp'];
const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'webm'];

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
    $check = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
    $check->execute([$data['email']]);
    $existingUserId = $check->fetchColumn();

    if ($existingUserId) {
        return $existingUserId; // Do NOT resend email
    }

    // Previously hardcoded role='manager' here, silently granting full
    // internal admin access to anyone who submitted a seller application.
    // Fixed to use the dedicated, properly-scoped 'seller' role
    // (dashboard_5.php - own application only, no sidebar).
    $temporaryPassword = generateTemporaryPassword();
    $defaultPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    $role = 'seller';

    $username = strtolower(
        preg_replace('/\s+/', '.', $data['firstName'] . '.' . $data['surname'])
    );

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

    // Send email
    $mailSent = sendLoginEmail($data['email'], $fullName, $username, $temporaryPassword);
    debugLog("Login email sent to {$data['email']}: " . ($mailSent ? 'YES' : 'NO'));

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
    $prefix = 'SELL';
    $date = date('Ymd');

    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(application_number, 15) AS UNSIGNED)), 0) AS max_seq
        FROM seller_applications
        WHERE application_number LIKE ?
    ");
    $pattern = "$prefix-$date-%";
    $stmt->execute([$pattern]);

    $maxSeq = (int)($stmt->fetchColumn() ?: 0);
    $nextSeq = $maxSeq + 1;

    $applicationNumber = sprintf('%s-%s-%04d', $prefix, $date, $nextSeq);
    debugLog("Generated application number: $applicationNumber");

    return $applicationNumber;
}

debugLog("Parsing input data...");

// ===============================================================
// CHECK FOR DUPLICATE SUBMISSION
// ===============================================================
$requestSignature = md5($rawInput . ($_SESSION['user_id'] ?? session_id()));
$lastSignatureFile = __DIR__ . '/logs/last_submission_' . session_id() . '.txt';

// Check for recent duplicate submission (within last 10 seconds)
if (file_exists($lastSignatureFile)) {
    $lastSubmission = json_decode(file_get_contents($lastSignatureFile), true);
    if ($lastSubmission && isset($lastSubmission['signature']) && $lastSubmission['signature'] === $requestSignature) {
        $timeDiff = time() - ($lastSubmission['time'] ?? 0);
        if ($timeDiff < 10) {
            debugLog("DUPLICATE SUBMISSION DETECTED - Rejecting request (submitted $timeDiff seconds ago)");
            echo json_encode([
                'success' => false,
                'error' => 'Duplicate submission detected. Please wait before submitting again.',
                'duplicate' => true
            ]);
            exit;
        }
    }
}

file_put_contents($lastSignatureFile, json_encode([
    'signature' => $requestSignature,
    'time' => time(),
    'application' => $applicationNumber ?? null
]), LOCK_EX);

debugLog("Duplicate submission check passed");

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

    debugLog("Data validation passed");

    if (($data['signatureType'] ?? '') === 'otp') {
        throw new Exception('SMS verification is not available. Please use a drawn or uploaded signature.');
    }

    // Server-side email validation - there was previously none at all, only
    // the client-side <input type="email">, which is trivially bypassed by
    // posting directly to this endpoint. An unvalidated email also becomes
    // an email-header-injection vector (CRLF in the value) the moment a
    // working SMTP relay is configured, so this matters even though mail()
    // isn't currently delivering anything in this environment.
    foreach (['email' => 'email', 'nokEmail' => 'next of kin email'] as $field => $label) {
        if (!empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            debugLog("Rejected invalid $label: {$data[$field]}");
            throw new Exception("Please provide a valid $label address");
        }
    }

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
            (application_id, property_detail_type, land_type, land_size, selling_price, house_size, number_of_rooms, number_of_bathrooms, additional_features, property_erf_no, property_street_name, property_suburb, property_location, property_region, property_town, listing_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
            (development_id, house_type_index, property_type, number_of_units, house_size, land_type, land_size, selling_price, number_of_rooms, number_of_bathrooms, additional_features, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $unitStmt = $pdo->prepare("
            INSERT INTO seller_properties
            (application_id, property_detail_type, land_type, land_size, house_size, selling_price, number_of_rooms, number_of_bathrooms, additional_features, property_street_name, property_suburb, property_location, property_region, property_town, development_house_type_id, listing_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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

                $houseTypeStmt->execute([
                    $developmentId,
                    $htIndex + 1,
                    $houseType['property_type'],
                    (int)($houseType['number_of_units'] ?? 1),
                    $houseSize !== '' ? $houseSize : null,
                    $houseType['land_type'] ?? null,
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

        // getFileInputData() always returns {files:[],hasFiles:false} for an untouched
        // input, never null/undefined - checking !empty($data[$fileField]) was always
        // true (non-empty wrapper object) and created a phantom "unknown"/0-byte document
        // row for every unselected optional file (e.g. marriage_certificate when not
        // married). Must check the files array itself is non-empty.
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
            $uploadDir = __DIR__ . "/uploads/seller/$docType/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Save file to disk
            $filePath = "uploads/seller/$docType/" . $safeName;
            $fullPath = __DIR__ . "/" . $filePath;

            $decodedContent = base64_decode((string)$fileContent, true);

            if ($decodedContent !== false) {
                $saveResult = file_put_contents($fullPath, $decodedContent);
                if ($saveResult !== false) {
                    debugLog("File saved to: $fullPath ($saveResult bytes)");
                } else {
                    debugLog("ERROR: Failed to save file to: $fullPath - " . (error_get_last()['message'] ?? 'unknown'));
                    throw new Exception("Failed to save file: $fullPath");
                }
            } else {
                debugLog("ERROR: Invalid base64 content for $docType");
                throw new Exception("Invalid base64 content for $docType");
            }

            $fileHash = md5($filePath . $fileSize);

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
                        debugLog("ERROR: Rejected additional document upload with disallowed extension: $fileName");
                        continue;
                    }
                    $filePath = "uploads/seller/additional_documents/$safeName";
                    $fullFilePath = __DIR__ . "/" . $filePath;
                    $fileSize = $file['size'] ?? 0;
                    $fileType = $file['type'] ?? 'application/octet-stream';
                    $fileHash = md5($filePath . $fileSize);

                    $addDocDir = __DIR__ . "/uploads/seller/additional_documents/";
                    if (!is_dir($addDocDir)) {
                        mkdir($addDocDir, 0755, true);
                    }

                    if (!empty($file['content'])) {
                        $addDocDecoded = base64_decode($file['content'], true);
                        if ($addDocDecoded !== false) {
                            $addDocSaveResult = file_put_contents($fullFilePath, $addDocDecoded);
                            if ($addDocSaveResult !== false) {
                                debugLog("Additional document saved to: $fullFilePath ($addDocSaveResult bytes)");
                            } else {
                                debugLog("ERROR: Failed to save additional document: " . (error_get_last()['message'] ?? 'unknown'));
                            }
                        } else {
                            debugLog("ERROR: Invalid base64 content for additional document $fileName");
                        }
                    }

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
                    $imageName = 'property_image_' . $index . '_' . time();
                    $imagePath = "uploads/seller/property_images/$imageName";
                    $fullImagePath = __DIR__ . "/" . $imagePath;
                    $originalFilename = $file['name'];
                    $fileSize = $file['size'] ?? 0;
                    $mimeType = $file['type'] ?? 'image/jpeg';
                    $fileHash = md5($imagePath . $fileSize);
                    $isPrimary = $index === 0 ? 1 : 0;

                    $imgDir = __DIR__ . "/uploads/seller/property_images/";
                    if (!is_dir($imgDir)) {
                        mkdir($imgDir, 0755, true);
                    }

                    if (!empty($file['content'])) {
                        $imgDecoded = base64_decode($file['content'], true);
                        if ($imgDecoded !== false) {
                            $imgSaveResult = file_put_contents($fullImagePath, $imgDecoded);
                            if ($imgSaveResult !== false) {
                                debugLog("Property image saved to: $fullImagePath ($imgSaveResult bytes)");
                            } else {
                                debugLog("ERROR: Failed to save property image: " . (error_get_last()['message'] ?? 'unknown'));
                            }
                        } else {
                            debugLog("ERROR: Invalid base64 content for property image $originalFilename");
                        }
                    }

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
                    $videoName = 'property_video_' . $index . '_' . time();
                    $videoPath = "uploads/seller/property_videos/$videoName";
                    $fullVideoPath = __DIR__ . "/" . $videoPath;
                    $originalFilename = $file['name'];
                    $fileSize = $file['size'] ?? 0;
                    $mimeType = $file['type'] ?? 'video/mp4';
                    $fileHash = md5($videoPath . $fileSize);

                    $vidDir = __DIR__ . "/uploads/seller/property_videos/";
                    if (!is_dir($vidDir)) {
                        mkdir($vidDir, 0755, true);
                    }

                    if (!empty($file['content'])) {
                        $vidDecoded = base64_decode($file['content'], true);
                        if ($vidDecoded !== false) {
                            $vidSaveResult = file_put_contents($fullVideoPath, $vidDecoded);
                            if ($vidSaveResult !== false) {
                                debugLog("Property video saved to: $fullVideoPath ($vidSaveResult bytes)");
                            } else {
                                debugLog("ERROR: Failed to save property video: " . (error_get_last()['message'] ?? 'unknown'));
                            }
                        } else {
                            debugLog("ERROR: Invalid base64 content for property video $originalFilename");
                        }
                    }

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
            $fullSignaturePath = __DIR__ . "/" . $signatureFilePath;
            $signatureDir = __DIR__ . "/uploads/seller/signature/";

            if (!is_dir($signatureDir)) {
                mkdir($signatureDir, 0755, true);
            }

            $sigFileData = $data['signatureFile']['files'][0];
            if (!empty($sigFileData['content'])) {
                $sigDecoded = base64_decode($sigFileData['content'], true);
                if ($sigDecoded !== false) {
                    $sigSaveResult = file_put_contents($fullSignaturePath, $sigDecoded);
                    if ($sigSaveResult !== false) {
                        debugLog("Signature file saved to: $fullSignaturePath ($sigSaveResult bytes)");
                    } else {
                        debugLog("ERROR: Failed to save signature file: " . (error_get_last()['message'] ?? 'unknown'));
                    }
                } else {
                    debugLog("ERROR: Invalid base64 content for signature file");
                }
            } else {
                debugLog("No signature file content found - checking for direct upload");
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

    $stmt = $pdo->prepare("
        UPDATE seller_applications
        SET status = 'submitted', submission_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$application_id]);

    debugLog("Application status updated to submitted");

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

    debugLog("Sending success response");
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
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

    if (isset($pdo) && $pdo->inTransaction()) {
        debugLog("Rolling back transaction...");
        try {
            $pdo->rollBack();
            debugLog("Transaction rolled back successfully");
        } catch (Exception $rollbackException) {
            debugLog("Rollback failed: " . $rollbackException->getMessage());
        }
    }

    debugLog("Sending error response to client");

    // File paths, line numbers, stack traces, and PHP version were previously
    // sent to the CLIENT on every error - a reconnaissance gift to an attacker
    // (server layout, exact source lines, version for CVE targeting). All of
    // this is already captured above via debugLog() for real debugging; the
    // client only needs the user-facing message.
    $errorResponse = [
        'success' => false,
        'error' => 'Unable to submit the application. Please review the form and try again.',
        'application_number' => $applicationNumber ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    debugLog("=== EXCEPTION HANDLING COMPLETED ===");
}
?>

<?php
/**
 * File: admin_buyer_processor.php
 * Purpose: Handle buyer application form submission
 */

session_start();
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/config/account_provisioning.php';
require_once __DIR__ . '/api/security_integration.php';

// Log errors for debugging, but never echo them into the response - this
// endpoint returns JSON, and a PHP warning/notice printed mid-response
// breaks the JSON output while also leaking file paths and line numbers.
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Utility: Debug log
function debugLog($msg, $level = 'INFO') {
    if (getenv('NURU_DEBUG_LOG') !== '1'
        || strtolower((string)(getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
        return;
    }
    $time = date('Y-m-d H:i:s');
    $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', (string)$msg), 0, 1000);
    @error_log("[$time] [$level] $safeMessage\n", 3, __DIR__ . '/logs/debug.log');
}

// Utility: Generate application number
function generateApplicationNumber(PDO $pdo) {
    $prefix = 'BUY';
    $date = date('Ymd');
    $stmt = $pdo->query("SELECT COUNT(*) FROM buyers");
    $count = $stmt->fetchColumn() + 1;
    return sprintf('%s-%s-%04d', $prefix, $date, $count);
}

// Utility: Create buyer user if not exists
function createBuyerUserIfNotExists(PDO $pdo, $data, $buyerId) {
    if (empty($data['email'])) return null;

    $check = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
    $check->execute([$data['email']]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if ($existing) return (int)$existing['id'];

    $username = strtolower(preg_replace('/\s+/', '.', ($data['firstName'] ?? 'Buyer') . '.' . ($data['lastName'] ?? '')));
    if (empty($username)) $username = 'buyer.' . uniqid();

    $temporaryPassword = generateTemporaryPassword();
    $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
    
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$username, $data['email'], $passwordHash, $fullName, 'buyer']);
    if (!sendTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword)) {
        error_log('Buyer account provisioned but credential email could not be sent for user ' . $username);
    }
    
    return (int)$pdo->lastInsertId();
}

// basename() already strips path-traversal segments from the uploaded
// filename below, but nothing was checking the extension - a file named
// "shell.php" would be saved as-is into the web-servable uploads/ tree.
const ALLOWED_UPLOAD_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'gif', 'tiff', 'bmp'];
// Helper: Handle uploaded files
function saveUploadedFiles($buyerId) {
    $saved = [];
    
    // Define allowed document types and their field names
    $docFields = [
        'id_passport' => 'id_passport',
        'proof_of_income' => 'proof_of_income',
        'bank_statements' => 'bank_statements',
        'employment_letter' => 'employment_letter',
        'marriage_certificate' => 'marriage_certificate'
    ];
    
    $uploadDir = __DIR__ . '/uploads/buyers/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }
    
    foreach ($docFields as $docType => $fieldName) {
        if (!empty($_FILES[$fieldName]['name']) && $_FILES[$fieldName]['error'] === 0) {
            $file = $_FILES[$fieldName];

            $validation = SecurityIntegration::validateFileUpload($file);
            if (!$validation['success']) {
                debugLog("Rejected $docType upload: " . implode(', ', $validation['errors']), 'ERROR');
                continue;
            }

            $filename = $buyerId . '_' . $docType . '_' . $validation['safe_filename'];
            $target = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $saved[] = [
                    'file_path' => 'uploads/buyers/' . $filename,
                    'doc_type' => $docType
                ];
            }
        }
    }
    
    // Handle signature file upload
    if (!empty($_FILES['signatureFile']['name']) && $_FILES['signatureFile']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['signatureFile'];
        $validation = SecurityIntegration::validateFileUpload($file);
        if ($validation['success'] && $file['size'] <= 5 * 1024 * 1024) {
            $filename = $buyerId . '_signature_' . $validation['safe_filename'];
            $target = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $saved[] = [
                    'file_path' => 'uploads/buyers/' . $filename,
                    'doc_type' => 'signature'
                ];
            }
        }
    }
    
    return $saved;
}

// Process form submission
try {
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    if (!sessionHasAuthoritativeRole(['admin', 'manager'])) {
        http_response_code(403);
        throw new Exception('You are not authorized to submit this form');
    }

    $data = $_POST;

    if (empty($data)) {
        throw new Exception("No form data submitted");
    }

    if (!validCsrfToken($data['csrf_token'] ?? null, 'staff_buyer_form')) {
        http_response_code(419);
        throw new Exception('Your session has expired. Please reload and try again.');
    }

    // Server-side email validation - there was previously none at all, only
    // the client-side <input type="email">, which is trivially bypassed by
    // posting directly to this endpoint. An unvalidated email also becomes
    // an email-header-injection vector (CRLF in the value) the moment a
    // working SMTP relay is configured, so this matters even though mail()
    // isn't currently delivering anything in this environment.
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        debugLog("Rejected invalid email: {$data['email']}");
        throw new Exception("Please provide a valid email address");
    }

    debugLog('Buyer form submitted');
    
    $pdo->beginTransaction();
    
    $applicationNumber = generateApplicationNumber($pdo);
    
    // Build full name from first and last name
    $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
    
    // Prepare address
    $address = '';
    if (!empty($data['addressLine1'])) {
        $address = $data['addressLine1'];
        if (!empty($data['addressLine2'])) {
            $address .= ', ' . $data['addressLine2'];
        }
    }
    
    // 1. Insert buyer main record
    $stmt = $pdo->prepare("
        INSERT INTO buyers (
            application_number, full_name, nationality, gender, property_type, price_type, date_of_birth, id_type, id_number,
            marital_status, phone, email, region, town, address,
            employment_type, employer_name, position, monthly_income,
            property_value, down_payment, loan_amount, loaded_by, additional_requirements,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $applicationNumber,
        $fullName,
        $data['nationality'] ?? null,
        $data['gender'] ?? null,
        $data['propertyType'] ?? null,
        $data['priceType'] ?? null,
        $data['dateOfBirth'] ?? null,
        $data['idType'] ?? null,
        $data['idNumber'] ?? null,
        $data['maritalStatus'] ?? null,
        $data['contactNumber'] ?? null,
        $data['email'] ?? null,
        $data['region'] ?? null,
        $data['town'] ?? null,
        $address ?: null,
        $data['employmentType'] ?? null,
        $data['employerName'] ?? null,
        $data['jobTitle'] ?? null,
        isset($data['monthlyIncome']) ? (float)$data['monthlyIncome'] : 0,
        isset($data['propertyValue']) ? (float)$data['propertyValue'] : 0,
        isset($data['downPayment']) ? (float)$data['downPayment'] : 0,
        isset($data['loanAmount']) ? (float)$data['loanAmount'] : 0,
        $_SESSION['user_id'] ?? 'Self Loaded',
        $data['additionalRequirements'] ?? ''
    ]);
    
    $buyerId = (int)$pdo->lastInsertId();
    
    debugLog("Buyer created with ID: " . $buyerId);
    
    // 2. Spouse info (if married)
    if (($data['maritalStatus'] ?? '') === 'married' && !empty($data['spouseFullName'])) {
        $stmt = $pdo->prepare("
            INSERT INTO buyer_spouse (buyer_id, full_name, id_passport, date_of_birth, phone, email) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $buyerId,
            $data['spouseFullName'] ?? null,
            $data['spouseIdPassport'] ?? null,
            $data['spouseDateOfBirth'] ?? null,
            $data['spouseContactNumber'] ?? null,
            $data['spouseEmail'] ?? null
        ]);
    }
    
    // 3. Next of kin
    if (!empty($data['nokFirstName']) || !empty($data['nokLastName'])) {
        $nokFullName = trim(($data['nokFirstName'] ?? '') . ' ' . ($data['nokLastName'] ?? ''));
        $nokAddress = '';
        if (!empty($data['nokAddress'])) {
            $nokAddress = $data['nokAddress'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO buyer_next_of_kin (buyer_id, full_name, relationship, phone, email, region, town, address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $buyerId,
            $nokFullName,
            $data['nokRelationship'] ?? null,
            $data['nokContactNumber'] ?? null,
            $data['nokEmail'] ?? null,
            $data['nokRegion'] ?? null,
            $data['nokTown'] ?? null,
            $nokAddress ?: null
        ]);
    }
    
    // 4. Preferred areas
    if (!empty($data['preferredRegion']) && is_array($data['preferredRegion'])) {
        $stmt = $pdo->prepare("INSERT INTO buyer_preferred_areas (buyer_id, region, town, location, suburb) VALUES (?, ?, ?, ?, ?)");
        
        $regions = $data['preferredRegion'];
        $towns = $data['preferredTown'] ?? [];
        $locations = $data['preferredLocation'] ?? [];
        $suburbs = $data['preferredSuburb'] ?? [];
        
        $count = count($regions);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($regions[$i])) {
                $stmt->execute([
                    $buyerId,
                    $regions[$i] ?? null,
                    $towns[$i] ?? null,
                    $locations[$i] ?? null,
                    $suburbs[$i] ?? null
                ]);
            }
        }
    }
    
    // 5. Additional requirements
    if (!empty($data['additionalRequirements'])) {
        $stmt = $pdo->prepare("UPDATE buyers SET additional_requirements = ? WHERE id = ?");
        $stmt->execute([$data['additionalRequirements'], $buyerId]);
    }
    
    // 6. Declarations
    if (!empty($data['declarations']) && is_array($data['declarations'])) {
        $stmt = $pdo->prepare("INSERT INTO buyer_declarations (buyer_id, declaration, accepted, created_at) VALUES (?, ?, 1, NOW())");
        foreach ($data['declarations'] as $declaration) {
            $stmt->execute([$buyerId, $declaration]);
        }
    }
    
    // 7. Buyer documents
    $savedDocs = saveUploadedFiles($buyerId);
    if (!empty($savedDocs)) {
        $stmt = $pdo->prepare("INSERT INTO buyer_documents (buyer_id, doc_type, file_path, uploaded_at) VALUES (?, ?, ?, NOW())");
        foreach ($savedDocs as $doc) {
            $stmt->execute([$buyerId, $doc['doc_type'], $doc['file_path']]);
        }
    }
    
    // 8. Create buyer user account
    createBuyerUserIfNotExists($pdo, $data, $buyerId);
    
    $pdo->commit();
    
    debugLog("Buyer application submitted successfully: " . $applicationNumber);
    
    // Redirect to success page or return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // AJAX request - return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'buyer_id' => $buyerId,
            'application_number' => $applicationNumber,
            'message' => 'Buyer application submitted successfully'
        ]);
    } else {
        // Regular form submission - redirect to success page
        $_SESSION['application_success'] = true;
        $_SESSION['application_number'] = $applicationNumber;
        header('Location: buyer-success.php?app=' . $applicationNumber);
        exit;
    }
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    debugLog("ERROR: " . $e->getMessage(), 'ERROR');
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Unable to submit the application. Please review the form and try again.'
        ]);
    } else {
        $_SESSION['form_error'] = 'Unable to submit the application. Please review the form and try again.';
        header('Location: buyer_admin_form.php?error=submit_failed');
        exit;
    }
}

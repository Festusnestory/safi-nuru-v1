<?php
/**
 * File: admin_consulting_agent_processor.php
 * Purpose: Handle consulting agent form submission for pre-approved bank buyers
 * This processor saves to the buyers table and buyer_preferred_areas table
 */

session_start();
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/api/security_integration.php';
requireRole(['admin','manager','agent_coordinator','agent_consultant']);
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

class ConsultationValidationException extends RuntimeException {}

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
    $check = $pdo->prepare('SELECT 1 FROM buyers WHERE application_number = ? LIMIT 1');
    do {
        $number = sprintf('BUY-%s-%06d', date('Ymd'), random_int(0, 999999));
        $check->execute([$number]);
    } while ($check->fetchColumn());
    return $number;
}

// Helper: Save preferred areas to buyer_preferred_areas table
function savePreferredAreas(PDO $pdo, $buyerId, $data) {
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
        
        debugLog("Saved " . $count . " preferred areas for buyer ID: " . $buyerId);
    }
}

function validateConsultation(PDO $pdo, array &$data): void {
    foreach (['firstName', 'lastName', 'idType', 'idNumber', 'dateOfBirth', 'nationality', 'gender', 'contactNumber', 'email', 'propertyType', 'priceType', 'propertyValue'] as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            throw new ConsultationValidationException('Please complete all required fields.');
        }
        $data[$field] = trim((string)$data[$field]);
        if (strlen($data[$field]) > 255) {
            throw new ConsultationValidationException('One or more fields is too long.');
        }
    }
    if (!preg_match("/^[\\p{L} .'-]{2,100}$/u", $data['firstName'])
        || !preg_match("/^[\\p{L} .'-]{2,100}$/u", $data['lastName'])) {
        throw new ConsultationValidationException('Please provide a valid first and last name.');
    }
    if (!in_array($data['idType'], ['national_id', 'passport', 'drivers_license'], true)) {
        throw new ConsultationValidationException('Please select a valid identity type.');
    }
    $validId = $data['idType'] === 'national_id'
        ? preg_match('/^\d{11}$/', $data['idNumber'])
        : preg_match('/^[A-Z0-9-]{6,20}$/i', $data['idNumber']);
    if (!$validId) {
        throw new ConsultationValidationException('Please provide a valid identity number.');
    }
    $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['dateOfBirth']);
    $age = $birthDate ? $birthDate->diff(new DateTimeImmutable('today'))->y : -1;
    if (!$birthDate || $birthDate->format('Y-m-d') !== $data['dateOfBirth']
        || $birthDate > new DateTimeImmutable('today') || $age < 18 || $age > 120) {
        throw new ConsultationValidationException('The client must be between 18 and 120 years old.');
    }
    $genderMap = ['male' => 'Male', 'female' => 'Female', 'Male' => 'Male', 'Female' => 'Female'];
    if (!isset($genderMap[$data['gender']])) {
        throw new ConsultationValidationException('Please select a valid gender.');
    }
    $data['gender'] = $genderMap[$data['gender']];
    $data['email'] = strtolower($data['email']);
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new ConsultationValidationException('Please provide a valid email address.');
    }
    $data['contactNumber'] = preg_replace('/[\s()-]+/', '', $data['contactNumber']);
    if (!preg_match('/^(?:\+264\d{9}|0\d{9})$/', $data['contactNumber'])) {
        throw new ConsultationValidationException('Please provide a valid Namibian contact number.');
    }
    if (!in_array($data['propertyType'], ['house', 'apartment', 'townhouse', 'plot', 'commercial'], true)
        || !in_array($data['priceType'], ['fixed', 'negotiable', 'auction'], true)) {
        throw new ConsultationValidationException('Please select valid property requirements.');
    }
    if (!is_numeric($data['propertyValue']) || (float)$data['propertyValue'] <= 0) {
        throw new ConsultationValidationException('Property value must be greater than zero.');
    }
    $data['propertyValue'] = round((float)$data['propertyValue'], 2);
    $downPayment = trim((string)($data['downPayment'] ?? '0'));
    if ($downPayment === '') {
        $downPayment = '0';
    }
    if (!is_numeric($downPayment) || (float)$downPayment < 0 || (float)$downPayment > $data['propertyValue']) {
        throw new ConsultationValidationException('Down payment must be between zero and the property value.');
    }
    $data['downPayment'] = round((float)$downPayment, 2);
    $data['loanAmount'] = round($data['propertyValue'] - $data['downPayment'], 2);

    $regions = ['Zambezi', 'Erongo', 'Hardap', 'Karas', 'Kavango East', 'Kavango West', 'Khomas', 'Kunene', 'Ohangwena', 'Omaheke', 'Omusati', 'Oshana', 'Oshikoto', 'Otjozondjupa'];
    $preferredRegions = $data['preferredRegion'] ?? [];
    if (!is_array($preferredRegions) || count($preferredRegions) > 5) {
        throw new ConsultationValidationException('Provide no more than five preferred areas.');
    }
    foreach ($preferredRegions as $region) {
        if ($region !== '' && !in_array($region, $regions, true)) {
            throw new ConsultationValidationException('Please select valid preferred regions.');
        }
    }
    if (strlen((string)($data['additionalRequirements'] ?? '')) > 2000) {
        throw new ConsultationValidationException('Additional requirements must be 2,000 characters or fewer.');
    }
    $duplicate = $pdo->prepare('SELECT 1 FROM buyers WHERE id_number = ? OR LOWER(email) = ? LIMIT 1');
    $duplicate->execute([$data['idNumber'], $data['email']]);
    if ($duplicate->fetchColumn()) {
        throw new ConsultationValidationException('A buyer record already exists for this email address or identity number.');
    }
}

// Process form submission
try {
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new ConsultationValidationException('Invalid request method.');
    }
    if (!SecurityIntegration::processRequest('consulting_agent_form', 'POST')) {
        exit;
    }
    
    $data = $_POST;
    
    if (empty($data)) {
        throw new Exception("No form data submitted");
    }

    if (!validCsrfToken($data['csrf_token'] ?? null, 'consulting_agent_form')) {
        http_response_code(419);
        throw new Exception('Your session has expired. Please reload and try again.');
    }

    validateConsultation($pdo, $data);
    
    debugLog('Consulting agent form submitted');
    
    $pdo->beginTransaction();
    
    $applicationNumber = generateApplicationNumber($pdo);
    
    // Build full name from first and last name
    $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
    $primaryRegion = null;
    $primaryTown = null;
    foreach (($data['preferredRegion'] ?? []) as $index => $preferredRegion) {
        if ($preferredRegion !== '') {
            $primaryRegion = $preferredRegion;
            $primaryTown = $data['preferredTown'][$index] ?? null;
            break;
        }
    }
    
    // Map form fields from simplified form to buyers table
    // Step 1 fields: firstName, lastName, idType, idNumber, dateOfBirth, nationality, gender, contactNumber, email
    // Step 2 (originally step 6) fields: propertyType, priceType, propertyValue, downPayment, loanAmount, additionalRequirements
    
    // 1. Insert buyer main record with fields from step 1 and step 6
    $stmt = $pdo->prepare("
        INSERT INTO buyers (
            application_number, full_name,
            date_of_birth, id_type, id_number, nationality, gender,
            phone, email, region, town,
            property_type, price_type, property_value, down_payment, loan_amount,
            additional_requirements,
            loaded_by, source, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'quick_consult', NOW())
    ");
    
    $stmt->execute([
        $applicationNumber,
        $fullName,
        $data['dateOfBirth'] ?? null,
        $data['idType'] ?? null,
        $data['idNumber'] ?? null,
        $data['nationality'] ?? null,
        $data['gender'] ?? null,
        $data['contactNumber'] ?? null,
        $data['email'] ?? null,
        $primaryRegion,
        $primaryTown,
        $data['propertyType'] ?? null,
        $data['priceType'] ?? null,
        isset($data['propertyValue']) ? (float)$data['propertyValue'] : 0,
        isset($data['downPayment']) ? (float)$data['downPayment'] : 0,
        isset($data['loanAmount']) ? (float)$data['loanAmount'] : 0,
        $data['additionalRequirements'] ?? null,
        $_SESSION['user_id'] ?? 'Consultant Loaded'
    ]);
    
    $buyerId = (int)$pdo->lastInsertId();
    
    debugLog("Buyer created from Consulting Agent form with ID: " . $buyerId);
    
    // 2. Save preferred areas to buyer_preferred_areas table
    savePreferredAreas($pdo, $buyerId, $data);
    
    $pdo->commit();
    unset($_SESSION['_csrf_tokens']['consulting_agent_form']);
    
    debugLog("Consulting Agent buyer application submitted successfully: " . $applicationNumber);
    
    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // AJAX request - return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'buyer_id' => $buyerId,
            'application_number' => $applicationNumber,
            'message' => 'Pre-approved buyer application submitted successfully'
        ]);
    } else {
        // Regular form submission - redirect to success page
        $_SESSION['application_success'] = true;
        $_SESSION['application_number'] = $applicationNumber;
        header('Location: buyer-success.php?app=' . $applicationNumber);
        exit;
    }
    
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    debugLog("ERROR: " . $e->getMessage(), 'ERROR');
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        $isValidation = $e instanceof ConsultationValidationException;
        http_response_code($isValidation ? 422 : 500);
        echo json_encode([
            'success' => false,
            'error' => $isValidation
                ? $e->getMessage()
                : 'Unable to submit the application. Please try again later.'
        ]);
    } else {
        // Regular form submission - show error
        $_SESSION['form_error'] = 'Unable to submit the application. Please review the form and try again.';
        header('Location: consulting_agent_form.php?error=submit_failed');
        exit;
    }
}

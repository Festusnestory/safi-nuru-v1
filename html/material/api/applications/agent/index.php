<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/pdo.php';
require_once __DIR__ . '/../../security_integration.php';
require_once __DIR__ . '/../../../config/turnstile.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

class AgentApplicationValidationException extends RuntimeException {}

function agentResponse(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function validAgentDate(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function normaliseAgentMoney(string $value): float
{
    $normalised = str_replace([',', ' '], '', trim($value));
    if ($normalised === '' || !is_numeric($normalised)) {
        throw new AgentApplicationValidationException('Income and deduction amounts must be valid numbers.');
    }
    $amount = round((float)$normalised, 2);
    if ($amount < 0 || $amount > 9999999999.99) {
        throw new AgentApplicationValidationException('Income and deduction amounts are outside the permitted range.');
    }
    return $amount;
}

function validateAgentApplication(PDO $pdo, array &$data): void
{
    $required = [
        'surname', 'first_name', 'date_of_birth', 'id_type', 'id_number', 'nationality', 'gender',
        'town', 'region', 'email', 'mobile_number', 'kin_surname', 'kin_first_name',
        'kin_contact_number', 'company_name', 'job_title', 'gross_income', 'total_deductions',
    ];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            throw new AgentApplicationValidationException('Please complete all required fields.');
        }
        $data[$field] = trim((string)$data[$field]);
        if (strlen($data[$field]) > 255) {
            throw new AgentApplicationValidationException('One or more fields is too long.');
        }
    }

    foreach (['surname', 'first_name', 'kin_surname', 'kin_first_name'] as $nameField) {
        if (!preg_match("/^[\\p{L} .'-]{2,100}$/u", $data[$nameField])) {
            throw new AgentApplicationValidationException('Please provide valid names.');
        }
    }
    $birthDate = validAgentDate($data['date_of_birth']);
    $age = $birthDate ? $birthDate->diff(new DateTimeImmutable('today'))->y : -1;
    if (!$birthDate || $birthDate > new DateTimeImmutable('today') || $age < 18 || $age > 120) {
        throw new AgentApplicationValidationException('The applicant must be between 18 and 120 years old.');
    }
    if (!in_array($data['id_type'], ['National ID', 'Passport'], true)) {
        throw new AgentApplicationValidationException('Please select a valid ID type.');
    }
    $validIdentity = $data['id_type'] === 'National ID'
        ? preg_match('/^\d{11}$/', $data['id_number'])
        : preg_match('/^[A-Z0-9]{6,9}$/i', $data['id_number']);
    if (!$validIdentity) {
        throw new AgentApplicationValidationException('Please provide a valid ID or passport number.');
    }
    if (!in_array($data['gender'], ['Male', 'Female'], true)) {
        throw new AgentApplicationValidationException('Please select a valid gender.');
    }

    $data['email'] = strtolower($data['email']);
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new AgentApplicationValidationException('Please provide a valid email address.');
    }
    if (!empty($data['kin_email']) && !filter_var($data['kin_email'], FILTER_VALIDATE_EMAIL)) {
        throw new AgentApplicationValidationException('Please provide a valid next-of-kin email address.');
    }
    if (!empty($data['emp_email']) && !filter_var($data['emp_email'], FILTER_VALIDATE_EMAIL)) {
        throw new AgentApplicationValidationException('Please provide a valid employment email address.');
    }
    foreach (['mobile_number', 'kin_contact_number'] as $phoneField) {
        $normalised = preg_replace('/[\s()-]+/', '', $data[$phoneField]);
        if (!preg_match('/^(?:\+264\d{9}|0\d{9})$/', $normalised)) {
            throw new AgentApplicationValidationException('Please provide valid Namibian contact numbers.');
        }
        $data[$phoneField] = $normalised;
    }

    $townRegions = [
        'Windhoek' => 'Khomas', 'Swakopmund' => 'Erongo', 'Walvis Bay' => 'Erongo',
        'Rundu' => 'Kavango East', 'Oshakati' => 'Oshana', 'Katima Mulilo' => 'Zambezi',
        'Otjiwarongo' => 'Otjozondjupa', 'Gobabis' => 'Omaheke', 'Keetmanshoop' => 'Karas',
        'Tsumeb' => 'Oshikoto', 'Grootfontein' => 'Otjozondjupa', 'Rehoboth' => 'Hardap',
        'Mariental' => 'Hardap', 'Okahandja' => 'Otjozondjupa', 'Ondangwa' => 'Oshana',
    ];
    if (!isset($townRegions[$data['town']]) || $townRegions[$data['town']] !== $data['region']) {
        throw new AgentApplicationValidationException('Please select a valid residential town.');
    }
    foreach ([['kin_town', 'kin_region'], ['emp_town', 'emp_region']] as [$townField, $regionField]) {
        $town = trim((string)($data[$townField] ?? ''));
        $region = trim((string)($data[$regionField] ?? ''));
        if (($town !== '' || $region !== '') && (!isset($townRegions[$town]) || $townRegions[$town] !== $region)) {
            throw new AgentApplicationValidationException('Please select valid address towns.');
        }
    }

    $data['gross_income'] = normaliseAgentMoney($data['gross_income']);
    $data['total_deductions'] = normaliseAgentMoney($data['total_deductions']);
    if ($data['total_deductions'] > $data['gross_income']) {
        throw new AgentApplicationValidationException('Total deductions cannot exceed gross income.');
    }
    $data['net_pay'] = round($data['gross_income'] - $data['total_deductions'], 2);

    $duplicate = $pdo->prepare(
        "SELECT 1 FROM agents WHERE LOWER(email) = ? OR id_number = ?
         UNION SELECT 1 FROM agent_applications
         WHERE (LOWER(email) = ? OR id_number = ?) AND status IN ('submitted','under_review','approved')
         LIMIT 1"
    );
    $duplicate->execute([$data['email'], $data['id_number'], $data['email'], $data['id_number']]);
    if ($duplicate->fetchColumn()) {
        throw new AgentApplicationValidationException('An agent or pending application already exists for this email address or identity number.');
    }
}

function storeAgentDocument(array $file, string $type, array &$createdFiles): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new AgentApplicationValidationException('Upload every required document.');
    }
    $validation = SecurityIntegration::validateFileUpload($file);
    if (!$validation['success']) {
        throw new AgentApplicationValidationException('One or more documents has an invalid type or exceeds 10MB.');
    }

    $uploadDirectory = dirname(__DIR__, 3) . '/uploads/agents/';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Unable to create the document directory.');
    }
    $storedFilename = (string)$validation['safe_filename'];
    $absolutePath = $uploadDirectory . $storedFilename;
    if (!move_uploaded_file((string)$file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Unable to store an uploaded document.');
    }
    $createdFiles[] = $absolutePath;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return [
        'document_type' => $type,
        'original_filename' => basename((string)$file['name']),
        'stored_filename' => $storedFilename,
        'file_path' => 'uploads/agents/' . $storedFilename,
        'file_size' => filesize($absolutePath),
        'mime_type' => (string)$finfo->file($absolutePath),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    agentResponse(405, ['success' => false, 'error' => 'Method not allowed.']);
}
if (!SecurityIntegration::processRequest('agent_application_submit', 'POST')) {
    exit;
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 55 * 1024 * 1024) {
    agentResponse(413, ['success' => false, 'error' => 'The combined document upload is too large.']);
}
if (!SecurityIntegration::validateCSRFToken($_POST['csrf_token'] ?? null, 'agent_application_submit')) {
    agentResponse(403, ['success' => false, 'error' => 'Invalid or expired form token. Reload the page and try again.']);
}
if (!verifyTurnstile($_POST['cf-turnstile-response'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null)) {
    agentResponse(403, ['success' => false, 'error' => 'CAPTCHA verification failed. Please try again.']);
}

$createdFiles = [];
try {
    $data = $_POST;
    validateAgentApplication($pdo, $data);

    $requiredFiles = ['id_document', 'proof_residence', 'agency_ffc', 'agent_neab', 'agent_ffc'];
    foreach ($requiredFiles as $fileField) {
        if (empty($_FILES[$fileField])) {
            throw new AgentApplicationValidationException('Upload every required document.');
        }
    }

    $checkNumber = $pdo->prepare('SELECT 1 FROM agent_applications WHERE application_number = ? LIMIT 1');
    do {
        $applicationNumber = sprintf('AGT-%s-%06d', date('Ymd'), random_int(0, 999999));
        $checkNumber->execute([$applicationNumber]);
    } while ($checkNumber->fetchColumn());

    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        "INSERT INTO agent_applications (
            application_number, status, submitted_by, submission_date,
            surname, first_name, middle_name, maiden_name, date_of_birth, id_type, id_number, nationality, gender,
            email, mobile_number, po_box, residential_erf_no, residential_street_name, residential_suburb,
            residential_location, residential_town, residential_region,
            kin_surname, kin_first_name, kin_contact_number, kin_email, kin_erf_no, kin_street_name,
            kin_suburb, kin_location, kin_town, kin_region,
            company_name, job_title, employment_number, gross_income, total_deductions, net_pay,
            employment_email, employment_erf_no, employment_street_name, employment_suburb,
            employment_location, employment_town, employment_region
        ) VALUES (
            ?, 'submitted', NULL, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )"
    );
    $statement->execute([
        $applicationNumber,
        $data['surname'], $data['first_name'], $data['middle_name'] ?? null, $data['maiden_name'] ?? null,
        $data['date_of_birth'], $data['id_type'], $data['id_number'], $data['nationality'], $data['gender'],
        $data['email'], $data['mobile_number'], $data['po_box'] ?? null,
        $data['erf_no'] ?? null, $data['street_name'] ?? null, $data['suburb'] ?? null, $data['location'] ?? null,
        $data['town'], $data['region'],
        $data['kin_surname'], $data['kin_first_name'], $data['kin_contact_number'], $data['kin_email'] ?? null,
        $data['kin_erf_no'] ?? null, $data['kin_street_name'] ?? null, $data['kin_suburb'] ?? null,
        $data['kin_location'] ?? null, $data['kin_town'] ?? null, $data['kin_region'] ?? null,
        $data['company_name'], $data['job_title'], $data['employment_number'] ?? null,
        $data['gross_income'], $data['total_deductions'], $data['net_pay'],
        $data['emp_email'] ?? null, $data['emp_erf_no'] ?? null, $data['emp_street_name'] ?? null,
        $data['emp_suburb'] ?? null, $data['emp_location'] ?? null, $data['emp_town'] ?? null, $data['emp_region'] ?? null,
    ]);
    $applicationId = (int)$pdo->lastInsertId();

    $documentStatement = $pdo->prepare(
        'INSERT INTO agent_documents
         (application_id, document_type, document_name, original_filename, stored_filename, file_path, file_size, mime_type)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($requiredFiles as $fileField) {
        $document = storeAgentDocument($_FILES[$fileField], $fileField, $createdFiles);
        $documentStatement->execute([
            $applicationId, $document['document_type'], str_replace('_', ' ', $fileField),
            $document['original_filename'], $document['stored_filename'], $document['file_path'],
            $document['file_size'], $document['mime_type'],
        ]);
    }

    $pdo->commit();
    unset($_SESSION['_csrf_tokens']['agent_application_submit']);
    SecurityIntegration::logEvent('INFO', 'agent_form', 'AGENT_APPLICATION_SUBMITTED', 'Public agent application submitted', [
        'application_id' => $applicationId,
        'application_number' => $applicationNumber,
    ]);
    agentResponse(201, [
        'success' => true,
        'application_id' => $applicationId,
        'application_number' => $applicationNumber,
        'status' => 'submitted',
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($createdFiles as $createdFile) {
        if (is_file($createdFile)) {
            @unlink($createdFile);
        }
    }
    $isValidation = $error instanceof AgentApplicationValidationException;
    if (!$isValidation) {
        error_log('Agent application submission failed: ' . $error->getMessage());
    }
    agentResponse($isValidation ? 422 : 500, [
        'success' => false,
        'error' => $isValidation ? $error->getMessage() : 'Unable to submit the application. Please try again later.',
    ]);
}

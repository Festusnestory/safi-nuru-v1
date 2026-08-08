<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private, max-age=0');

require_once '../../../config/pdo.php';
require_once '../../../config/role_helpers.php';
require_once '../../security_integration.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// This endpoint is for staff attaching a document to an existing buyer. It
// must never let an anonymous request create an arbitrary file in web root.
if (!sessionHasAuthoritativeRole(['admin', 'manager', 'agent_coordinator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if (!validCsrfToken($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null), 'buyer_document_upload')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Your session has expired. Please reload and try again.']);
    exit;
}

$buyerId = filter_input(INPUT_POST, 'buyer_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$documentType = $_POST['doc_type'] ?? '';
$allowedDocumentTypes = [
    'id_passport', 'proof_of_income', 'bank_statements', 'marriage_certificate',
    'employment_letter', 'additional_documents', 'signature',
];

if (!$buyerId || !in_array($documentType, $allowedDocumentTypes, true) || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid document upload request']);
    exit;
}

$buyerCheck = $pdo->prepare('SELECT id FROM buyers WHERE id = ?');
$buyerCheck->execute([$buyerId]);
if (!$buyerCheck->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Buyer not found']);
    exit;
}

if (currentRole() === 'agent_coordinator') {
    $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
    $scopeCheck = $pdo->prepare(
        "SELECT 1
         FROM buyers scoped_buyer
         WHERE scoped_buyer.id = :buyer_id
           AND (
               scoped_buyer.loaded_by = :user_id
               OR scoped_buyer.assigned_agent_id = :agent_id
               OR EXISTS (
                   SELECT 1
                   FROM agent_task_allocations scoped_allocation
                   WHERE scoped_allocation.allocation_type = 'buyer'
                     AND scoped_allocation.agent_id = :task_agent_id
                     AND scoped_allocation.entity_id = scoped_buyer.id
               )
           )
         LIMIT 1"
    );
    $scopeCheck->execute([
        ':buyer_id' => (int)$buyerId,
        ':user_id' => (int)$_SESSION['user_id'],
        ':agent_id' => $agentId,
        ':task_agent_id' => $agentId,
    ]);
    if (!$scopeCheck->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'This buyer is not assigned to you']);
        exit;
    }
}

$validation = SecurityIntegration::validateFileUpload($_FILES['file']);
if (!$validation['success']) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(', ', $validation['errors'])]);
    exit;
}

$uploadDir = realpath(__DIR__ . '/../../../uploads');
if ($uploadDir === false) {
    $uploadDir = __DIR__ . '/../../../uploads';
}
$buyerDir = $uploadDir . DIRECTORY_SEPARATOR . 'buyers';
if (!is_dir($buyerDir) && !mkdir($buyerDir, 0750, true) && !is_dir($buyerDir)) {
    error_log('Unable to create buyer upload directory');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to store the document']);
    exit;
}

$filename = bin2hex(random_bytes(16)) . '.' . pathinfo($validation['safe_filename'], PATHINFO_EXTENSION);
$destination = $buyerDir . DIRECTORY_SEPARATOR . $filename;

try {
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        throw new RuntimeException('move_uploaded_file failed');
    }

    $path = 'uploads/buyers/' . $filename;
    $stmt = $pdo->prepare('INSERT INTO buyer_documents (buyer_id, doc_type, file_path, uploaded_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$buyerId, $documentType, $path]);
    SecurityIntegration::logEvent('info', 'buyer_document', 'DOCUMENT_UPLOADED', "Document added to buyer #{$buyerId}");

    echo json_encode(['success' => true, 'path' => $path]);
} catch (Throwable $e) {
    if (is_file($destination ?? '')) {
        unlink($destination);
    }
    error_log('Buyer document upload failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to store the document']);
}

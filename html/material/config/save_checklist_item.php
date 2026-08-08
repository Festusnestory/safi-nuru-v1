<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager']);

function itemResponse(int $code, string $status, string $message): never
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    itemResponse(405, 'error', 'Method not allowed.');
}
if (!validCsrfToken($_POST['csrf_token'] ?? null, 'checklist_configuration')) {
    itemResponse(403, 'error', 'Your session token has expired. Refresh the page and try again.');
}

$stageId = filter_var($_POST['stage_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$itemName = trim((string)($_POST['item_name'] ?? ''));
$itemOrder = filter_var($_POST['item_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
$isRequired = isset($_POST['is_required']) ? 1 : 0;
if ($stageId === false || $itemName === '' || mb_strlen($itemName) > 255 || $itemOrder === false) {
    itemResponse(422, 'error', 'Enter a valid stage, checklist item, and order.');
}

try {
    $stage = $pdo->prepare('SELECT id FROM checklist_stages WHERE id = ? AND is_active = 1');
    $stage->execute([(int)$stageId]);
    if (!$stage->fetchColumn()) {
        itemResponse(422, 'error', 'Select an active checklist stage.');
    }
    $duplicate = $pdo->prepare('SELECT id FROM checklist_items WHERE stage_id = ? AND (item_order = ? OR LOWER(item_name) = LOWER(?)) LIMIT 1');
    $duplicate->execute([(int)$stageId, (int)$itemOrder, $itemName]);
    if ($duplicate->fetchColumn()) {
        itemResponse(409, 'error', 'That stage already has an item with the same name or order.');
    }
    $statement = $pdo->prepare('INSERT INTO checklist_items (stage_id, item_name, item_order, is_required) VALUES (?, ?, ?, ?)');
    $statement->execute([(int)$stageId, $itemName, (int)$itemOrder, $isRequired]);
    itemResponse(201, 'success', 'Checklist item added successfully.');
} catch (Throwable $error) {
    error_log('Checklist item creation failed: ' . $error->getMessage());
    itemResponse(500, 'error', 'The checklist item could not be saved.');
}

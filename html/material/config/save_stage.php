<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager']);

function stageResponse(int $code, string $status, string $message): never
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    stageResponse(405, 'error', 'Method not allowed.');
}
if (!validCsrfToken($_POST['csrf_token'] ?? null, 'checklist_configuration')) {
    stageResponse(403, 'error', 'Your session token has expired. Refresh the page and try again.');
}

$stageName = trim((string)($_POST['stage_name'] ?? ''));
$stageOrder = filter_var($_POST['stage_order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
$description = trim((string)($_POST['description'] ?? ''));
$isActive = isset($_POST['is_active']) ? 1 : 0;
if ($stageName === '' || mb_strlen($stageName) > 150 || $stageOrder === false || mb_strlen($description) > 2000) {
    stageResponse(422, 'error', 'Enter a valid stage name, order, and description.');
}

try {
    $duplicate = $pdo->prepare('SELECT id FROM checklist_stages WHERE stage_order = ? OR LOWER(stage_name) = LOWER(?) LIMIT 1');
    $duplicate->execute([(int)$stageOrder, $stageName]);
    if ($duplicate->fetchColumn()) {
        stageResponse(409, 'error', 'A stage with that name or order already exists.');
    }
    $statement = $pdo->prepare('INSERT INTO checklist_stages (stage_name, stage_order, description, is_active) VALUES (?, ?, ?, ?)');
    $statement->execute([$stageName, (int)$stageOrder, $description !== '' ? $description : null, $isActive]);
    stageResponse(201, 'success', 'Stage saved successfully.');
} catch (Throwable $error) {
    error_log('Checklist stage creation failed: ' . $error->getMessage());
    stageResponse(500, 'error', 'The stage could not be saved.');
}

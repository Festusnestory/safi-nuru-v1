<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin', 'manager']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}
$statement = $pdo->query('SELECT id, stage_name FROM checklist_stages WHERE is_active = 1 ORDER BY stage_order, id');
echo json_encode($statement->fetchAll(PDO::FETCH_ASSOC));

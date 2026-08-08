<?php
session_start();
require 'pdo.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!sessionHasAuthoritativeRole(['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'agent_management')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload and try again.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$agentId = isset($data['agent_id']) ? (int)$data['agent_id'] : 0;

if ($agentId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid agent ID']);
    exit;
}

$sql = "UPDATE agents a LEFT JOIN admin_users u ON u.id = a.user_id
        SET a.status = 'rejected', u.is_active = CASE WHEN u.role = 'agent_coordinator' THEN 0 ELSE u.is_active END
        WHERE a.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $agentId]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Agent not found or already removed']);
    exit;
}

logActivity((int)$_SESSION['user_id'], 'AGENT_REMOVED', "Agent #{$agentId} removed", 'agent_form', 'warning');
echo json_encode(['success' => true]);

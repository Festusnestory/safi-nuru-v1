<?php
// ===============================================================
// File: api/csrf-token.php
// Purpose: Generate and return CSRF token
// ===============================================================

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Only names used by the application's public/self-service forms may be
// requested here. A token is session-bound; it is never useful cross-origin.
$scope = $_GET['scope'] ?? 'buyer_application_submit';
$allowedScopes = ['buyer_application_submit', 'seller_application_submit', 'agent_form_application_submit'];
if (!in_array($scope, $allowedScopes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid token scope']);
    exit;
}

$token = csrfToken($scope);

echo json_encode([
    'token' => $token,
    'timestamp' => time(),
    'scope' => $scope,
]);

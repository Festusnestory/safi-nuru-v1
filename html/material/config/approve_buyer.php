<?php
session_start();
require 'pdo.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/role_helpers.php';

header('Content-Type: application/json');

if (!sessionHasAuthoritativeRole(['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'buyer_management')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload and try again.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$buyerId = isset($data['buyer_id']) ? (int)$data['buyer_id'] : 0;

if (!$buyerId) {
    echo json_encode(['success' => false, 'message' => 'Invalid buyer ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE buyers SET status = 'approved' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$buyerId]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Buyer not found or already reviewed']);
        exit;
    }

    $pdo->prepare("UPDATE admin_users u JOIN buyers b ON b.user_id = u.id SET u.is_active = 1 WHERE b.id = ? AND u.role = 'buyer'")->execute([$buyerId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Buyer approval failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to approve the buyer.']);
    exit;
}

logActivity((int)$_SESSION['user_id'], 'BUYER_APPROVED', "Buyer #$buyerId approved", 'buyer_form');

echo json_encode(['success' => true]);

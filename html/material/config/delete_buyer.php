<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');

if (!sessionHasAuthoritativeRole(['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
if (!validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'buyer_management')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session token has expired. Refresh the page and try again.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$buyerId = filter_var($data['buyer_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($buyerId === false) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid buyer ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE buyers SET status = 'rejected' WHERE id = :id AND status = 'pending'");
    $stmt->execute([':id' => (int)$buyerId]);
    if ($stmt->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Buyer not found or already reviewed']);
        exit;
    }
    $pdo->prepare("UPDATE admin_users u JOIN buyers b ON b.user_id = u.id SET u.is_active = 0 WHERE b.id = ? AND u.role = 'buyer'")->execute([(int)$buyerId]);
    $pdo->commit();
    logActivity((int)$_SESSION['user_id'], 'BUYER_REJECTED', "Buyer #{$buyerId} rejected", 'buyer_form', 'warning');
    echo json_encode(['success' => true, 'message' => 'Buyer application rejected']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Buyer rejection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to reject the buyer.']);
}

<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');

require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'manager']);

function sellerReviewResponse(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    sellerReviewResponse(405, false, 'Method not allowed.');
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'seller_management')) {
    sellerReviewResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}
$applicationId = filter_var($data['application_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$decision = (string)($data['decision'] ?? '');
$reason = trim((string)($data['reason'] ?? ''));
if ($applicationId === false || !in_array($decision, ['approve', 'reject'], true) || mb_strlen($reason) > 1000) {
    sellerReviewResponse(422, false, 'Invalid seller review request.');
}
if ($decision === 'reject' && $reason === '') {
    sellerReviewResponse(422, false, 'A rejection reason is required.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, application_number, status, user_id FROM seller_applications WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => (int)$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        $pdo->rollBack();
        sellerReviewResponse(404, false, 'Seller application not found.');
    }
    if (!in_array($application['status'], ['submitted', 'under_review'], true)) {
        $pdo->rollBack();
        sellerReviewResponse(409, false, 'This seller application has already been reviewed.');
    }

    if ($decision === 'approve') {
        $pdo->prepare("UPDATE seller_applications SET status = 'approved', review_date = NOW(), approved_date = NOW(), rejection_reason = NULL WHERE id = :id")
            ->execute([':id' => (int)$applicationId]);
        $pdo->prepare("UPDATE seller_properties SET property_status = 'available' WHERE application_id = :id AND property_status = 'pending_review'")
            ->execute([':id' => (int)$applicationId]);
        $isActive = 1;
    } else {
        $pdo->prepare("UPDATE seller_applications SET status = 'rejected', review_date = NOW(), rejection_reason = :reason WHERE id = :id")
            ->execute([':reason' => $reason, ':id' => (int)$applicationId]);
        $pdo->prepare("UPDATE seller_properties SET property_status = 'withdrawn', status_deadline = NULL WHERE application_id = :id AND property_status IN ('pending_review', 'available')")
            ->execute([':id' => (int)$applicationId]);
        $isActive = 0;
    }
    if (!empty($application['user_id'])) {
        $pdo->prepare("UPDATE admin_users SET is_active = :active WHERE id = :id AND role = 'seller'")
            ->execute([':active' => $isActive, ':id' => (int)$application['user_id']]);
    }
    $pdo->commit();
    $activity = $decision === 'approve' ? 'SELLER_APPROVED' : 'SELLER_REJECTED';
    logActivity((int)$_SESSION['user_id'], $activity, "Seller application {$application['application_number']} {$decision}d", 'seller_form', $decision === 'reject' ? 'warning' : 'info');
    sellerReviewResponse(200, true, $decision === 'approve' ? 'Seller application approved.' : 'Seller application rejected.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Seller application review failed: ' . $e->getMessage());
    sellerReviewResponse(500, false, 'The seller application could not be reviewed.');
}

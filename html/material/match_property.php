<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/config/property_lifecycle.php';
require_once __DIR__ . '/includes/functions.php';
requireRole(['admin', 'manager', 'agent_coordinator']);

function matchResponse(int $code, bool $success, string $message): never
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    matchResponse(405, false, 'Method not allowed.');
}
if (!validCsrfToken($_POST['csrf_token'] ?? null, 'property_matching')) {
    matchResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
}
$sellerId = filter_var($_POST['seller_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$buyerId = filter_var($_POST['buyer_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($sellerId === false || $buyerId === false) {
    matchResponse(422, false, 'Invalid buyer or property selection.');
}

try {
    $pdo->beginTransaction();
    $buyerStatement = $pdo->prepare('SELECT id, full_name, status, loaded_by, assigned_agent_id FROM buyers WHERE id = ? FOR UPDATE');
    $buyerStatement->execute([(int)$buyerId]);
    $buyer = $buyerStatement->fetch(PDO::FETCH_ASSOC);
    $propertyStatement = $pdo->prepare("SELECT sp.id, sp.property_status, sp.application_id, sa.assigned_agent_id,
            spp.loaded_by AS seller_loaded_by
        FROM seller_properties sp
        JOIN seller_applications sa ON sa.id = sp.application_id
        LEFT JOIN seller_personal_details spp ON spp.application_id = sa.id
        WHERE sp.id = ? FOR UPDATE");
    $propertyStatement->execute([(int)$sellerId]);
    $property = $propertyStatement->fetch(PDO::FETCH_ASSOC);
    if (!$buyer || !$property) {
        throw new RuntimeException('The buyer or property is no longer available.', 404);
    }
    if ($buyer['status'] !== 'approved') {
        throw new RuntimeException('Only approved buyers can be matched.', 409);
    }
    if ($property['property_status'] !== 'available') {
        throw new RuntimeException('The property is no longer available for matching.', 409);
    }
    if (currentRole() === 'agent_coordinator') {
        $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
        $buyerTask = $pdo->prepare("SELECT 1 FROM agent_task_allocations WHERE allocation_type = 'buyer' AND entity_id = ? AND agent_id = ? LIMIT 1");
        $buyerTask->execute([(int)$buyerId, $agentId]);
        $propertyTask = $pdo->prepare("SELECT 1 FROM agent_task_allocations WHERE allocation_type = 'seller' AND entity_id = ? AND agent_id = ? LIMIT 1");
        $propertyTask->execute([(int)$sellerId, $agentId]);
        $buyerAllowed = (int)($buyer['assigned_agent_id'] ?? 0) === $agentId
            || (string)($buyer['loaded_by'] ?? '') === (string)$_SESSION['user_id']
            || (bool)$buyerTask->fetchColumn();
        $propertyAllowed = (int)($property['assigned_agent_id'] ?? 0) === $agentId
            || (string)($property['seller_loaded_by'] ?? '') === (string)$_SESSION['user_id']
            || (bool)$propertyTask->fetchColumn();
        if (!$buyerAllowed || !$propertyAllowed) {
            throw new RuntimeException('You are not assigned to both records.', 403);
        }
    }

    $deadline = computeStatusDeadline('under_offer');
    $pdo->prepare("UPDATE seller_properties SET buyer_id = ?, buyer_name = ?, property_status = 'under_offer', status_deadline = ?, updated_at = NOW() WHERE id = ?")
        ->execute([(int)$buyerId, (string)$buyer['full_name'], $deadline, (int)$sellerId]);
    $pdo->prepare("INSERT INTO property_match_audit (seller_property_id, buyer_id, buyer_name, action, performed_by, notes)
        VALUES (?, ?, ?, 'matched', ?, 'Initial buyer-seller match')")
        ->execute([(int)$sellerId, (int)$buyerId, (string)$buyer['full_name'], (int)$_SESSION['user_id']]);
    $pdo->commit();
    logActivity((int)$_SESSION['user_id'], 'PROPERTY_MATCHED', "Matched property #{$sellerId} with buyer #{$buyerId}", 'property_matching', 'warning');
    matchResponse(200, true, 'Property matched successfully.');
} catch (RuntimeException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $status = in_array($error->getCode(), [403, 404, 409], true) ? $error->getCode() : 422;
    matchResponse($status, false, $error->getMessage());
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Property matching failed: ' . $error->getMessage());
    matchResponse(500, false, 'The property could not be matched.');
}

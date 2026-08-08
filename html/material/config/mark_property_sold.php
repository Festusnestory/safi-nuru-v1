<?php
session_start();
require __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
require_once __DIR__ . '/property_lifecycle.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, private, max-age=0');

if (!sessionHasAuthoritativeRole(['admin', 'manager', 'agent_coordinator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
if (!validCsrfToken($_POST['csrf_token'] ?? null, 'mark_property_sold')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session token has expired. Refresh the page and try again.']);
    exit;
}

$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$soldPrice = isset($_POST['sold_price']) && $_POST['sold_price'] !== '' ? (float)$_POST['sold_price'] : null;
$saleNotes = $_POST['sale_notes'] ?? null;

if ($propertyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property']);
    exit;
}

// Authorization: admin/manager always allowed; coordinators may only update
// properties in their loaded, directly assigned, or task-assigned portfolio.
if (!isFullAccess()) {
    $myAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $owns = $pdo->prepare(
        "SELECT 1
         FROM seller_properties scoped_property
         INNER JOIN seller_applications scoped_seller
            ON scoped_seller.id = scoped_property.application_id
         LEFT JOIN seller_personal_details scoped_person
            ON scoped_person.application_id = scoped_seller.id
         WHERE scoped_property.id = :property_id
           AND (
               scoped_person.loaded_by = :user_id
               OR scoped_seller.assigned_agent_id = :agent_id
               OR EXISTS (
                   SELECT 1
                   FROM agent_task_allocations scoped_allocation
                   WHERE scoped_allocation.allocation_type = 'seller'
                     AND scoped_allocation.agent_id = :task_agent_id
                     AND (
                         scoped_allocation.entity_id = scoped_property.id
                         OR scoped_allocation.entity_reference = scoped_seller.application_number
                     )
               )
           )
         LIMIT 1"
    );
    $owns->execute([
        ':property_id' => $propertyId,
        ':user_id' => (int)$_SESSION['user_id'],
        ':agent_id' => $myAgentId ?? 0,
        ':task_agent_id' => $myAgentId ?? 0,
    ]);
    if (!$owns->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This property is not assigned to you']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare("SELECT property_status, selling_price, buyer_id, buyer_name FROM seller_properties WHERE id = ? FOR UPDATE");
    $check->execute([$propertyId]);
    $property = $check->fetch();

    if (!$property) {
        throw new Exception('Property not found');
    }
    if ($property['property_status'] !== 'under_offer') {
        throw new Exception('Only properties currently under offer can be marked as sold');
    }

    $finalPrice = $soldPrice ?? $property['selling_price'];
    $deadline = computeStatusDeadline('sold');

    $update = $pdo->prepare("
        UPDATE seller_properties
        SET property_status = 'sold',
            sale_date = NOW(),
            sold_price = ?,
            status_deadline = ?,
            sale_notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $update->execute([$finalPrice, $deadline, $saleNotes, $propertyId]);

    $audit = $pdo->prepare("
        INSERT INTO property_match_audit
        (seller_property_id, buyer_id, buyer_name, action, performed_by, notes)
        VALUES (?, ?, ?, 'sold', ?, 'Marked as sold')
    ");
    $audit->execute([$propertyId, $property['buyer_id'], $property['buyer_name'], $_SESSION['user_id']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Property marked as sold']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

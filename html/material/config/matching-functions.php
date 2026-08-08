<?php
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
}
require_once __DIR__ . '/pdo.php';
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin','manager','agent_coordinator']);

function normalizeStr($s) {
    return mb_strtolower(trim((string)$s));
}

function fetchBuyers() {
    global $pdo;
    $sql = "SELECT b.id, b.full_name, b.down_payment, b.loan_amount, b.region, b.town FROM buyers b";
    $params = [];
    if (($_SESSION['role'] ?? '') === 'agent_coordinator') {
        $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
        $sql .= " WHERE (
            b.loaded_by = :buyer_loaded_by
            OR b.assigned_agent_id = :buyer_agent_id
            OR EXISTS (
                SELECT 1 FROM agent_task_allocations buyer_allocation
                WHERE buyer_allocation.allocation_type = 'buyer'
                  AND buyer_allocation.entity_id = b.id
                  AND buyer_allocation.agent_id = :buyer_task_agent_id
            )
        )";
        $params[':buyer_loaded_by'] = (int)$_SESSION['user_id'];
        $params[':buyer_agent_id'] = $agentId;
        $params[':buyer_task_agent_id'] = $agentId;
    }
    $sql .= " ORDER BY b.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchPreferredAreas() {
    global $pdo;
    return $pdo->query("SELECT buyer_id, region, town, location FROM buyer_preferred_areas ORDER BY id ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSellers() {
    global $pdo;
    $sql = "
        SELECT
            sp.id,
            sp.selling_price,
            sp.property_region,
            sp.property_town,
            sp.property_status
        FROM seller_properties sp
        INNER JOIN seller_applications sa ON sa.id = sp.application_id
        LEFT JOIN seller_personal_details spd ON spd.application_id = sp.application_id
        WHERE sp.property_status = 'available'
    ";
    $params = [];
    if (($_SESSION['role'] ?? '') === 'agent_coordinator') {
        $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
        $sql .= " AND (
            spd.loaded_by = :seller_loaded_by
            OR sa.assigned_agent_id = :seller_agent_id
            OR EXISTS (
                SELECT 1 FROM agent_task_allocations seller_allocation
                WHERE seller_allocation.allocation_type = 'seller'
                  AND seller_allocation.agent_id = :seller_task_agent_id
                  AND (
                      seller_allocation.entity_id = sp.id
                      OR seller_allocation.entity_reference = sa.application_number
                  )
            )
        )";
        $params[':seller_loaded_by'] = (int)$_SESSION['user_id'];
        $params[':seller_agent_id'] = $agentId;
        $params[':seller_task_agent_id'] = $agentId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$buyers  = fetchBuyers();
$pAreas  = fetchPreferredAreas();
$sellers = fetchSellers();

// group preferred areas by buyer
$pAreasByBuyer = [];
foreach ($pAreas as $pa) {
    $pAreasByBuyer[$pa['buyer_id']][] = $pa;
}

// matching container
$buyerSummary = [];

foreach ($buyers as $buyer) {
    $buyerID = $buyer['id'];
    $buyerName = $buyer['full_name'];
    $buyerBudget = (float)$buyer['down_payment'] + (float)$buyer['loan_amount'];

    $matches = [];
    $matchedAreas = []; // count region+town popularity

    // loop buyer preferred areas
    foreach ($pAreasByBuyer[$buyerID] ?? [] as $rank => $area) {
        $pref_region = normalizeStr($area['region']);
        $pref_town   = normalizeStr($area['town']);
        $pref_location   = normalizeStr($area['location']);
        $priority    = $rank + 1;

        foreach ($sellers as $seller) {
            $sellerPrice = (float)$seller['selling_price'];

            if ($sellerPrice <= $buyerBudget) { // TIER1 price check ✅
                if (normalizeStr($seller['property_region']) === $pref_region &&
                    normalizeStr($seller['property_town']) === $pref_town) {
                    
                    $matches[(int)$seller['id']] = true;
                    $key = $area['region'] . " - " . $area['town']." - ".$area['location'];
                    $matchedAreas[$key] = ($matchedAreas[$key] ?? 0) + 1;
                }
            }
        }
    }

    // find most matched preferred area
    arsort($matchedAreas);
    $topArea = array_key_first($matchedAreas);
    $topAreaCount = $matchedAreas[$topArea] ?? 0;

    $buyerSummary[] = [
        'buyer_id' => $buyerID,
        'buyer_name' => $buyerName,
        'matched_count' => count($matches),
        'seller_ids' => array_keys($matches),
        'top_area' => $topArea ?? 'None',
        'top_area_count' => $topAreaCount,
    ];
}
?>

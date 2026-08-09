<?php
// KNOWN DRIFT / CORRECTNESS BUG (flagged during the MVC matching migration,
// not fixed here): unlike the canonical config/matching-functions.php (see
// app/Models/Matching.php, which is ported from that file), fetchSellers()
// below scopes agent_coordinator visibility by seller_personal_details.loaded_by
// only - it does NOT check assigned_agent_id or agent_task_allocations, so an
// agent_coordinator who was only assigned a seller property via task
// allocation (not the original loader) will not see it here. It also omits
// the buyer's down_payment from the budget calculation and includes
// 'withdrawn' properties as matchable. As of this migration nothing in the
// codebase require()s this file directly (confirmed via grep - only
// matching-functions1.php's sibling matching-functions.php and
// app/Models/Matching.php are actually used), so it is inert dead code left
// as-is rather than repointed.
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
}
include("pdo.php");
require_once __DIR__ . '/role_helpers.php';
requireRole(['admin','manager','agent_coordinator']);

function normalizeStr($s) {
    return mb_strtolower(trim((string)$s));
}

function fetchBuyers() {
    global $pdo;
    return $pdo->query("SELECT id, full_name, down_payment, loan_amount, region, town FROM buyers ORDER BY id ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
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
	";
	$params = [];
	if (($_SESSION['role'] ?? '') === 'agent_coordinator') {
		$sql .= "
			INNER JOIN seller_personal_details spd
				ON sp.application_id = spd.application_id
			WHERE sp.property_status IN ('available', 'withdrawn')
				AND spd.loaded_by = :loaded_by
		";
		$params[':loaded_by'] = $_SESSION['user_id'];
	} else {
		$sql .= " WHERE sp.property_status IN ('available', 'withdrawn') ";
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
    //$buyerBudget = (float)($buyer['down_payment'] + $buyer['loan_amount']);
    $buyerBudget = (float)($buyer['loan_amount']);

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
                    
                    $matches[] = $seller['id'];
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
        'seller_ids' => $matches,
        'top_area' => $topArea ?? 'None',
        'top_area_count' => $topAreaCount,
    ];
}
?>
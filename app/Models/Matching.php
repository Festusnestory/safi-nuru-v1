<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Buyer/seller matching engine. Ported from the canonical
 * html/material/config/matching-functions.php (the version that correctly
 * scopes agent_coordinator visibility) - matching-functions1.php is a thin
 * alias of that file and matching-functions2.php is a drifted duplicate
 * missing the same scoping; neither is used as the basis for this model.
 */
final class Matching
{
    public function __construct(private \PDO $pdo)
    {
    }

    private static function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /**
     * @return array<int, array{id:int,full_name:string,down_payment:float,loan_amount:float,region:?string,town:?string}>
     */
    private function fetchBuyers(bool $scopeToAgent, int $userId, int $agentId): array
    {
        $sql = 'SELECT b.id, b.full_name, b.down_payment, b.loan_amount, b.region, b.town FROM buyers b';
        $params = [];
        if ($scopeToAgent) {
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
            $params[':buyer_loaded_by'] = $userId;
            $params[':buyer_agent_id'] = $agentId;
            $params[':buyer_task_agent_id'] = $agentId;
        }
        $sql .= ' ORDER BY b.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function fetchPreferredAreas(): array
    {
        return $this->pdo->query('SELECT buyer_id, region, town, location FROM buyer_preferred_areas ORDER BY id ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function fetchAvailableSellers(bool $scopeToAgent, int $userId, int $agentId): array
    {
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
        if ($scopeToAgent) {
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
            $params[':seller_loaded_by'] = $userId;
            $params[':seller_agent_id'] = $agentId;
            $params[':seller_task_agent_id'] = $agentId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Per-buyer match summary (matched property count, top preferred area,
     * seller ids) used by match-table.php / match-table1.php / match_list.php.
     * Mirrors config/matching-functions.php's algorithm exactly, including
     * the price-tier check (seller price <= buyer down payment + loan) and
     * the region+town preferred-area matching.
     */
    public function buyerSummary(bool $scopeToAgent, int $userId, int $agentId): array
    {
        $buyers = $this->fetchBuyers($scopeToAgent, $userId, $agentId);
        $preferredAreas = $this->fetchPreferredAreas();
        $sellers = $this->fetchAvailableSellers($scopeToAgent, $userId, $agentId);

        $areasByBuyer = [];
        foreach ($preferredAreas as $area) {
            $areasByBuyer[$area['buyer_id']][] = $area;
        }

        $summary = [];
        foreach ($buyers as $buyer) {
            $buyerId = $buyer['id'];
            $buyerBudget = (float) $buyer['down_payment'] + (float) $buyer['loan_amount'];

            $matches = [];
            $matchedAreas = [];

            foreach ($areasByBuyer[$buyerId] ?? [] as $area) {
                $prefRegion = self::normalize($area['region']);
                $prefTown = self::normalize($area['town']);

                foreach ($sellers as $seller) {
                    $sellerPrice = (float) $seller['selling_price'];
                    if ($sellerPrice <= $buyerBudget
                        && self::normalize($seller['property_region']) === $prefRegion
                        && self::normalize($seller['property_town']) === $prefTown
                    ) {
                        $matches[(int) $seller['id']] = true;
                        $key = $area['region'] . ' - ' . $area['town'] . ' - ' . $area['location'];
                        $matchedAreas[$key] = ($matchedAreas[$key] ?? 0) + 1;
                    }
                }
            }

            arsort($matchedAreas);
            $topArea = array_key_first($matchedAreas);
            $topAreaCount = $matchedAreas[$topArea] ?? 0;

            $summary[] = [
                'buyer_id' => $buyerId,
                'buyer_name' => $buyer['full_name'],
                'matched_count' => count($matches),
                'seller_ids' => array_keys($matches),
                'top_area' => $topArea ?? 'None',
                'top_area_count' => $topAreaCount,
            ];
        }

        return $summary;
    }

    /**
     * Properties currently under offer (i.e. matched to a buyer awaiting
     * sale). Mirrors the query in match-results.php exactly.
     */
    public function activeMatches(bool $scopeToAgent, int $userId, int $agentId): array
    {
        $sql = "
            SELECT
                sp.id,
                sp.application_id,
                sp.property_detail_type,
                sp.property_region,
                sp.property_town,
                sp.sold_price,
                sp.selling_price,
                sp.buyer_name,
                sp.sale_date,
                sp.status_deadline,
                (SELECT CONCAT(spn.first_name, ' ', spn.surname)
                 FROM seller_personal_details spn
                 WHERE spn.application_id = sp.application_id LIMIT 1) AS seller_name
        ";
        $params = [];
        if ($scopeToAgent) {
            $sql .= ", (ata.agent_id IS NOT NULL) AS owned_by_me
                FROM seller_properties sp
                LEFT JOIN seller_personal_details spd
                    ON sp.application_id = spd.application_id
                LEFT JOIN agent_task_allocations ata
                    ON ata.entity_id = sp.id AND ata.agent_id = :my_agent_id
                WHERE sp.property_status = 'under_offer' AND sp.buyer_name is not null
                    AND (spd.loaded_by = :loaded_by OR ata.agent_id IS NOT NULL)
            ";
            $params[':loaded_by'] = $userId;
            $params[':my_agent_id'] = $agentId;
        } else {
            $sql .= ", 1 AS owned_by_me
                FROM seller_properties sp
                WHERE sp.property_status = 'under_offer' AND sp.buyer_name is not null
            ";
        }
        $sql .= ' ORDER BY sp.sale_date DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Agents eligible for task assignment. Mirrors match-results.php's agent dropdown query. */
    public function availableAgents(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                surname,
                first_name,
                company_name
            FROM agents
            WHERE status IN ('approved','active')
            ORDER BY surname, first_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Seller portfolio listing used by agentsellers_matched.php /
     * selleragent_table.php. Mirrors that query exactly (note the "sp"
     * alias there is seller_personal_details, not seller_properties).
     */
    public function sellerPortfolio(bool $scopeToAgent, int $userId, int $agentId): array
    {
        $sql = "
            SELECT
                sp.id AS seller_id,
                sp.application_id AS application_number,
                CONCAT(sp.first_name, ' ', sp.surname) AS full_name,
                sra.email,
                sra.mobile_number AS phone,
                sra.region,
                sra.town,
                sp.created_at
            FROM seller_personal_details sp
            INNER JOIN seller_applications sa ON sa.id = sp.application_id
            LEFT JOIN seller_residential_address sra
                ON sra.application_id = sp.application_id
        ";
        $params = [];
        if ($scopeToAgent) {
            $sql .= " WHERE (
                sp.loaded_by = :loaded_by
                OR sa.assigned_agent_id = :agent_id
                OR EXISTS (
                    SELECT 1
                    FROM agent_task_allocations allocation
                    INNER JOIN seller_properties scoped_property
                        ON scoped_property.id = allocation.entity_id
                       AND scoped_property.application_id = sa.id
                    WHERE allocation.allocation_type = 'seller'
                      AND allocation.agent_id = :task_agent_id
                )
                OR EXISTS (
                    SELECT 1
                    FROM agent_task_allocations reference_allocation
                    WHERE reference_allocation.allocation_type = 'seller'
                      AND reference_allocation.entity_reference = sa.application_number
                      AND reference_allocation.agent_id = :reference_agent_id
                )
            )";
            $params[':loaded_by'] = $userId;
            $params[':agent_id'] = $agentId;
            $params[':task_agent_id'] = $agentId;
            $params[':reference_agent_id'] = $agentId;
        }
        $sql .= ' ORDER BY sp.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Buyer row locked for an update, used by confirmMatch(). Mirrors match_property.php. */
    public function lockBuyer(int $buyerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, full_name, status, loaded_by, assigned_agent_id FROM buyers WHERE id = ? FOR UPDATE');
        $stmt->execute([$buyerId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Seller property row locked for an update, used by confirmMatch(). Mirrors match_property.php. */
    public function lockProperty(int $propertyId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT sp.id, sp.property_status, sp.application_id, sa.assigned_agent_id,
                    spp.loaded_by AS seller_loaded_by
             FROM seller_properties sp
             JOIN seller_applications sa ON sa.id = sp.application_id
             LEFT JOIN seller_personal_details spp ON spp.application_id = sa.id
             WHERE sp.id = ? FOR UPDATE"
        );
        $stmt->execute([$propertyId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** True when an agent_coordinator has a buyer task allocation for the given buyer. */
    public function hasBuyerTask(int $buyerId, int $agentId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM agent_task_allocations WHERE allocation_type = 'buyer' AND entity_id = ? AND agent_id = ? LIMIT 1");
        $stmt->execute([$buyerId, $agentId]);
        return (bool) $stmt->fetchColumn();
    }

    /** True when an agent_coordinator has a seller task allocation for the given property. */
    public function hasPropertyTask(int $propertyId, int $agentId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM agent_task_allocations WHERE allocation_type = 'seller' AND entity_id = ? AND agent_id = ? LIMIT 1");
        $stmt->execute([$propertyId, $agentId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Matches a buyer to a property. Mirrors match_property.php's UPDATE exactly. */
    public function matchPropertyToBuyer(int $propertyId, int $buyerId, string $buyerName, ?string $deadline): void
    {
        $this->pdo->prepare("UPDATE seller_properties SET buyer_id = ?, buyer_name = ?, property_status = 'under_offer', status_deadline = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$buyerId, $buyerName, $deadline, $propertyId]);
    }
}

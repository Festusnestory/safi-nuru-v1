<?php
declare(strict_types=1);

namespace App\Models;

final class Property
{
    public function __construct(private \PDO $pdo)
    {
    }

    /** All listed properties, newest first. */
    public function all(): array
    {
        $stmt = $this->pdo->prepare($this->baseSql());
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Properties visible to an agent_coordinator: ones they loaded, ones
     * whose seller is assigned to them, or ones attached to one of their
     * seller task allocations. Mirrors the original inline query in
     * html/material/properties-list.php exactly.
     */
    public function forAgent(int $agentId, int $userId): array
    {
        $sql = $this->baseSql(
            'WHERE (
                EXISTS (
                    SELECT 1 FROM seller_personal_details seller_person
                    WHERE seller_person.application_id = sp.application_id
                      AND seller_person.loaded_by = :loaded_by
                )
                OR EXISTS (
                    SELECT 1 FROM seller_applications seller_application
                    WHERE seller_application.id = sp.application_id
                      AND seller_application.assigned_agent_id = :agent_id
                )
                OR EXISTS (
                    SELECT 1 FROM agent_task_allocations allocation
                    INNER JOIN seller_applications scoped_seller
                        ON scoped_seller.id = sp.application_id
                    WHERE allocation.allocation_type = \'seller\'
                      AND allocation.agent_id = :task_agent_id
                      AND (
                          allocation.entity_id = sp.id
                          OR allocation.entity_reference = scoped_seller.application_number
                      )
                )
            )'
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':loaded_by' => $userId,
            ':agent_id' => $agentId,
            ':task_agent_id' => $agentId,
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function baseSql(string $whereClause = ''): string
    {
        return "
            SELECT
                sp.id,
                sp.application_id,
                sp.property_detail_type,
                sp.property_region,
                sp.property_town,
                sp.selling_price,
                sp.property_status,
                sp.status_deadline,
                sp.created_at
            FROM seller_properties sp
            $whereClause
            ORDER BY sp.created_at DESC
        ";
    }

    /**
     * Available (property_status = 'available') listings. Mirrors
     * html/material/properties-available.php exactly, including the
     * agent_coordinator visibility scoping.
     */
    public function available(bool $scopeToAgent, int $userId, int $agentId): array
    {
        $sql = "
            SELECT
                sp.id,
                sp.application_id,
                sp.property_detail_type,
                sp.property_region,
                sp.property_town,
                sp.selling_price,
                sp.property_status,
                sp.created_at
            FROM seller_properties sp
            WHERE sp.property_status = 'available'
        ";
        $params = [];
        if ($scopeToAgent) {
            $sql .= "
                AND (
                    EXISTS (
                        SELECT 1 FROM seller_personal_details seller_person
                        WHERE seller_person.application_id = sp.application_id
                          AND seller_person.loaded_by = :loaded_by
                    )
                    OR EXISTS (
                        SELECT 1 FROM seller_applications seller_application
                        WHERE seller_application.id = sp.application_id
                          AND seller_application.assigned_agent_id = :agent_id
                    )
                    OR EXISTS (
                        SELECT 1 FROM agent_task_allocations allocation
                        INNER JOIN seller_applications scoped_seller
                            ON scoped_seller.id = sp.application_id
                        WHERE allocation.allocation_type = 'seller'
                          AND allocation.agent_id = :task_agent_id
                          AND (
                              allocation.entity_id = sp.id
                              OR allocation.entity_reference = scoped_seller.application_number
                          )
                    )
                )
            ";
            $params[':loaded_by'] = $userId;
            $params[':agent_id'] = $agentId;
            $params[':task_agent_id'] = $agentId;
        }
        $sql .= ' ORDER BY sp.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sold (property_status = 'sold') listings. Mirrors
     * html/material/properties-sold.php exactly, including the
     * agent_coordinator visibility scoping.
     */
    public function sold(bool $scopeToAgent, int $userId, int $agentId): array
    {
        $sql = "
            SELECT
                sp.id,
                sp.application_id,
                sp.property_detail_type,
                sp.property_region,
                sp.property_town,
                sp.selling_price,
                sp.sold_price,
                sp.buyer_name,
                sp.sale_date,
                sp.status_deadline,
                sp.created_at
            FROM seller_properties sp
            INNER JOIN seller_personal_details spd
                ON sp.application_id = spd.application_id
            WHERE sp.property_status = 'sold'
        ";
        $params = [];
        if ($scopeToAgent) {
            $sql .= " AND (
                spd.loaded_by = :loaded_by
                OR EXISTS (
                    SELECT 1 FROM seller_applications seller_application
                    WHERE seller_application.id = sp.application_id
                      AND seller_application.assigned_agent_id = :agent_id
                )
                OR EXISTS (
                    SELECT 1 FROM agent_task_allocations allocation
                    INNER JOIN seller_applications scoped_seller
                        ON scoped_seller.id = sp.application_id
                    WHERE allocation.allocation_type = 'seller'
                      AND allocation.agent_id = :task_agent_id
                      AND (
                          allocation.entity_id = sp.id
                          OR allocation.entity_reference = scoped_seller.application_number
                      )
                )
            )";
            $params[':loaded_by'] = $userId;
            $params[':agent_id'] = $agentId;
            $params[':task_agent_id'] = $agentId;
        }
        $sql .= ' ORDER BY sp.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Seller applications eligible to receive a new property via the staff
     * "Add Property" form. Mirrors the seller dropdown query in
     * html/material/property_admin_form.php exactly.
     */
    public function eligibleSellersForStaffForm(bool $scopeToAgent, int $userId, int $agentId): array
    {
        [$sellerScope, $params] = $this->staffFormSellerScope($scopeToAgent, $userId, $agentId);

        $sql = "
            SELECT
                sa.id,
                sa.application_number,
                sa.status,
                CONCAT_WS(' ', spd.first_name, spd.surname) AS seller_name,
                sra.email,
                COUNT(existing_property.id) AS property_count
            FROM seller_applications sa
            INNER JOIN seller_personal_details spd ON spd.application_id = sa.id
            LEFT JOIN seller_residential_address sra ON sra.application_id = sa.id
            LEFT JOIN seller_properties existing_property ON existing_property.application_id = sa.id
            WHERE sa.status IN ('submitted', 'under_review', 'approved', 'completed')
            {$sellerScope}
            GROUP BY sa.id, sa.application_number, sa.status, spd.first_name, spd.surname, sra.email
            ORDER BY spd.first_name, spd.surname, sa.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Confirms the given seller application is eligible and, when scoped to
     * an agent_coordinator, within that agent's authorised portfolio.
     * Mirrors the authorization query in property_admin_form.php exactly.
     */
    public function authorizeSellerForStaffForm(int $applicationId, bool $scopeToAgent, int $userId, int $agentId): ?array
    {
        [$sellerScope, $scopeParams] = $this->staffFormSellerScope($scopeToAgent, $userId, $agentId);

        $sql = "
            SELECT sa.id, sa.application_number, sa.status
            FROM seller_applications sa
            INNER JOIN seller_personal_details spd ON spd.application_id = sa.id
            WHERE sa.id = :application_id
              AND sa.status IN ('submitted', 'under_review', 'approved', 'completed')
              {$sellerScope}
            LIMIT 1
        ";
        $params = array_merge([':application_id' => $applicationId], $scopeParams);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return array{0: string, 1: array<string, int>} */
    private function staffFormSellerScope(bool $scopeToAgent, int $userId, int $agentId): array
    {
        if (!$scopeToAgent) {
            return ['', []];
        }
        $sellerScope = ' AND (
            spd.loaded_by = :scope_user_id
            OR sa.assigned_agent_id = :scope_agent_id
            OR EXISTS (
                SELECT 1
                FROM agent_task_allocations scoped_allocation
                WHERE scoped_allocation.allocation_type = \'seller\'
                  AND scoped_allocation.agent_id = :scope_task_agent_id
                  AND (
                      scoped_allocation.entity_reference = sa.application_number
                      OR scoped_allocation.entity_id IN (
                          SELECT scoped_property.id
                          FROM seller_properties scoped_property
                          WHERE scoped_property.application_id = sa.id
                      )
                  )
            )
        )';
        $params = [
            ':scope_user_id' => $userId,
            ':scope_agent_id' => $agentId,
            ':scope_task_agent_id' => $agentId,
        ];
        return [$sellerScope, $params];
    }

    /**
     * Inserts a new property row from the staff "Add Property" form.
     * Mirrors the INSERT in property_admin_form.php exactly, including
     * column order. Returns the new property id.
     */
    public function createFromStaffForm(array $fields): int
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO seller_properties (
                application_id,
                property_detail_type,
                land_type,
                sale_pricing_type,
                plot_selling_price,
                construction_amount,
                property_selling_price,
                agent_commission_fees,
                land_size,
                selling_price,
                house_size,
                number_of_rooms,
                number_of_bathrooms,
                additional_features,
                property_erf_no,
                property_street_name,
                property_suburb,
                property_location,
                property_region,
                property_town,
                property_status,
                listing_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $insert->execute([
            $fields['application_id'],
            $fields['property_detail_type'],
            $fields['land_type'],
            $fields['sale_pricing_type'],
            $fields['plot_selling_price'],
            $fields['construction_amount'],
            $fields['property_selling_price'],
            $fields['agent_commission_fees'],
            $fields['land_size'],
            $fields['selling_price'],
            $fields['house_size'],
            $fields['number_of_rooms'],
            $fields['number_of_bathrooms'],
            $fields['additional_features'],
            $fields['property_erf_no'],
            $fields['property_street_name'],
            $fields['property_suburb'],
            $fields['property_location'],
            $fields['property_region'],
            $fields['property_town'],
            $fields['property_status'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Authorization check used by config/mark_property_sold.php: true when
     * the property is in the given agent_coordinator's loaded, directly
     * assigned, or task-assigned portfolio. Mirrors that query exactly.
     */
    public function ownedByAgentPortfolio(int $propertyId, int $userId, int $agentId): bool
    {
        $stmt = $this->pdo->prepare(
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
        $stmt->execute([
            ':property_id' => $propertyId,
            ':user_id' => $userId,
            ':agent_id' => $agentId,
            ':task_agent_id' => $agentId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    /** Row lock + fetch used by config/mark_property_sold.php before updating. */
    public function lockForSale(int $propertyId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT property_status, selling_price, buyer_id, buyer_name FROM seller_properties WHERE id = ? FOR UPDATE');
        $stmt->execute([$propertyId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Marks a property sold. Mirrors config/mark_property_sold.php's UPDATE exactly. */
    public function markSold(int $propertyId, float $finalPrice, ?string $deadline, ?string $saleNotes): void
    {
        $update = $this->pdo->prepare("
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
    }

    /** Appends a property_match_audit row. Shared by markSold() and Matching::confirmMatch(). */
    public function recordMatchAudit(int $propertyId, ?int $buyerId, ?string $buyerName, string $action, int $performedBy, string $notes): void
    {
        $audit = $this->pdo->prepare("
            INSERT INTO property_match_audit
            (seller_property_id, buyer_id, buyer_name, action, performed_by, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $audit->execute([$propertyId, $buyerId, $buyerName, $action, $performedBy, $notes]);
    }
}

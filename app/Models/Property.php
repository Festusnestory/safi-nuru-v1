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
}

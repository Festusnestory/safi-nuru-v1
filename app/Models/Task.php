<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Read queries backing the agent-task-allocation pages (tasks-pending.php,
 * tasks-in-progress.php, tasks-completed.php, tasks-cancelled.php,
 * agent-tasks-pending.php, agent_tasks-in-progress.php, task_view.php) and
 * the property checklist wizard (checklist.php). Each method mirrors its
 * source file's inline SQL as closely as possible - only the literal status
 * string embedded in the original WHERE clauses was lifted into a bound
 * parameter, which does not change any query result.
 */
final class Task
{
    public function __construct(private \PDO $pdo)
    {
    }

    /**
     * tasks-pending.php / tasks-cancelled.php: one status, ordered by due
     * date. $agentId narrows to a single agent_coordinator's own tasks
     * (null for admin/manager, who see everything).
     */
    public function byStatus(string $status, ?int $agentId): array
    {
        $whereClause = 'WHERE ata.status = :status';
        $params = [':status' => $status];
        if ($agentId !== null) {
            $whereClause .= ' AND ata.agent_id = :agent_id';
            $params[':agent_id'] = $agentId;
        }

        $sql = "
            SELECT
                ata.id AS task_id,
                ata.agent_id,
                ata.entity_id AS property_id,
                ata.entity_reference,
                ata.due_date,
                ata.status,

                COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_id,
                COALESCE(sp.property_detail_type, 'Buyer support') AS property_detail_type,
                COALESCE(sp.property_region, b.region, '—') AS property_region,
                COALESCE(sp.property_town, b.town, '—') AS property_town,
                COALESCE(sp.sold_price, b.property_value, 0) AS sold_price,
                COALESCE(sp.buyer_name, b.full_name, '—') AS buyer_name,
                sp.sale_date,

                CONCAT(a.surname, ' ', a.first_name) AS agent_name,
                a.company_name,

                CASE
                    WHEN ata.due_date IS NOT NULL AND ata.due_date < CURDATE()
                    THEN 1 ELSE 0
                END AS is_overdue

            FROM agent_task_allocations ata
            LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
            LEFT JOIN seller_applications sa ON sp.application_id = sa.id
            LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
            JOIN agents a ON a.id = ata.agent_id

            $whereClause

            ORDER BY ata.due_date ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * tasks-in-progress.php: status = in_progress, completed_at IS NULL.
     * $agentId is applied via HAVING (matching the original, which builds
     * the filter after the GROUP BY).
     */
    public function inProgress(?int $agentId): array
    {
        $havingClause = '';
        $params = [];
        if ($agentId !== null) {
            $havingClause = 'HAVING ata.agent_id = :agent_id';
            $params[':agent_id'] = $agentId;
        }

        $sql = "
            SELECT
            ata.id AS task_id,
            ata.agent_id,
            ata.entity_reference,
            ata.status,
            ata.progress_percentage,
            ata.due_date,
            ata.started_at,
            ata.last_contact_date,
            ata.next_action_date,
            ata.next_action_description,
            ata.entity_id,

            COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_id,
            COALESCE(sp.property_region, b.region, '—') AS property_region,
            COALESCE(sp.property_town, b.town, '—') AS property_town,

            CONCAT(a.surname, ' ', a.first_name) AS agent_name,
            a.company_name,

            COUNT(ac.id) AS communications_count,

            CASE
                WHEN ata.due_date IS NOT NULL AND ata.due_date < CURDATE() THEN 'Overdue'
                WHEN ata.due_date IS NOT NULL AND ata.due_date = CURDATE() THEN 'Due Today'
                ELSE 'On Track'
            END AS sla_status

        FROM agent_task_allocations ata
        LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
        LEFT JOIN seller_applications sa ON sp.application_id = sa.id
        LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
        JOIN agents a ON a.id = ata.agent_id
        LEFT JOIN agent_communications ac ON ac.allocation_id = ata.id

        WHERE ata.status = 'in_progress'
          AND ata.completed_at IS NULL

        GROUP BY ata.id
        $havingClause
        ORDER BY
            FIELD(sla_status,'Overdue','Due Today','On Track'),
            ata.due_date ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** tasks-completed.php: status = completed, completed_at set, agent filter applied in WHERE. */
    public function completed(?int $agentId): array
    {
        $whereClause = "WHERE ata.status = 'completed' AND ata.completed_at IS NOT NULL";
        $params = [];
        if ($agentId !== null) {
            $whereClause .= ' AND ata.agent_id = :agent_id';
            $params[':agent_id'] = $agentId;
        }

        $sql = "
            SELECT
                ata.id AS task_id,
                ata.agent_id,
                ata.entity_reference,
                ata.status,
                ata.progress_percentage,
                ata.due_date,
                ata.started_at,
                ata.completed_at,
                ata.last_contact_date,
                ata.next_action_date,
                ata.next_action_description,

                COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_id,
                COALESCE(sp.property_region, b.region, '—') AS property_region,
                COALESCE(sp.property_town, b.town, '—') AS property_town,

                CONCAT(a.surname, ' ', a.first_name) AS agent_name,
                a.company_name,

                COUNT(ac.id) AS communications_count,

                CASE
                    WHEN ata.completed_at IS NOT NULL THEN 'Completed'
                    ELSE '—'
                END AS sla_status

            FROM agent_task_allocations ata
            LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
            LEFT JOIN seller_applications sa ON sp.application_id = sa.id
            LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
            JOIN agents a ON a.id = ata.agent_id
            LEFT JOIN agent_communications ac ON ac.allocation_id = ata.id

            $whereClause

            GROUP BY ata.id
            ORDER BY ata.completed_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** agent-tasks-pending.php: an agent's own assigned/on_hold tasks. */
    public function agentPending(int $agentId): array
    {
        $sql = "
            SELECT
                ata.id AS task_id,
                ata.agent_id,
                ata.status,
                ata.due_date,
                ata.priority,
                ata.created_at,

                sp.application_id,
                COALESCE(sp.property_detail_type, 'Buyer support') AS property_detail_type,
                COALESCE(sp.property_region, b.region, '—') AS property_region,
                COALESCE(sp.property_town, b.town, '—') AS property_town,
                COALESCE(sp.sold_price, b.property_value, 0) AS sold_price,
                COALESCE(sp.buyer_name, b.full_name, '—') AS buyer_name,
                sp.sale_date,
                sp.selling_price,

                CONCAT(u.first_name, ' ', u.surname) AS agent_name,
                 COALESCE(spd.surname, b.full_name, 'Record unavailable') AS seller_name,
                u.company_name,

                CASE
                    WHEN ata.due_date IS NOT NULL
                     AND ata.due_date < CURDATE()
                     AND ata.status = 'assigned'
                    THEN 1
                    ELSE 0
                END AS is_overdue

            FROM agent_task_allocations ata

            LEFT JOIN seller_properties sp
                ON ata.allocation_type = 'seller' AND ata.entity_id = sp.id

            LEFT JOIN seller_personal_details spd
                ON sp.application_id = spd.application_id

            LEFT JOIN buyers b
                ON ata.allocation_type = 'buyer' AND ata.entity_id = b.id

            INNER JOIN agents u
                ON ata.agent_id = u.id

            WHERE ata.agent_id = :agent_id
              AND ata.status IN ('assigned', 'on_hold')

            ORDER BY ata.due_date ASC, ata.priority DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':agent_id' => $agentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** agent_tasks-in-progress.php: an agent's own in_progress tasks. */
    public function agentInProgress(int $agentId): array
    {
        $sql = "
            SELECT
                ata.id AS task_id,
                ata.entity_id AS entity_id,
                ata.agent_id,
                ata.status,
                ata.due_date,
                ata.priority,
                ata.created_at,


                sp.application_id,
                COALESCE(sp.property_detail_type, 'Buyer support') AS property_detail_type,
                COALESCE(sp.property_region, b.region, '—') AS property_region,
                COALESCE(sp.property_town, b.town, '—') AS property_town,
                COALESCE(sp.sold_price, b.property_value, 0) AS sold_price,
                COALESCE(sp.buyer_name, b.full_name, '—') AS buyer_name,
                sp.sale_date,
                sp.selling_price,

                CONCAT(u.first_name, ' ', u.surname) AS agent_name,
                 COALESCE(spd.surname, b.full_name, 'Record unavailable') AS seller_name,
                u.company_name,

                CASE
                    WHEN ata.due_date IS NOT NULL
                     AND ata.due_date < CURDATE()
                     AND ata.status = 'assigned'
                    THEN 1
                    ELSE 0
                END AS is_overdue

            FROM agent_task_allocations ata

            LEFT JOIN seller_properties sp
                ON ata.allocation_type = 'seller' AND ata.entity_id = sp.id

            LEFT JOIN seller_personal_details spd
                ON sp.application_id = spd.application_id

            LEFT JOIN buyers b
                ON ata.allocation_type = 'buyer' AND ata.entity_id = b.id

            INNER JOIN agents u
                ON ata.agent_id = u.id

            WHERE ata.agent_id = :agent_id
              AND ata.status = 'in_progress'

            ORDER BY ata.due_date ASC, ata.priority DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':agent_id' => $agentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** task_view.php: single task, scoped to $agentId when supplied (agent_coordinator). */
    public function find(int $taskId, ?int $agentId): ?array
    {
        $sql = "SELECT ata.*,
                CONCAT(a.first_name, ' ', a.surname) AS agent_name,
                a.company_name,
                sp.property_region, sp.property_town, sp.property_detail_type,
                b.full_name AS buyer_name, b.region AS buyer_region, b.town AS buyer_town,
                COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_number
            FROM agent_task_allocations ata
            JOIN agents a ON a.id = ata.agent_id
            LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
            LEFT JOIN seller_applications sa ON sp.application_id = sa.id
            LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
            WHERE ata.id = :id";
        $params = [':id' => $taskId];
        if ($agentId !== null) {
            $sql .= ' AND ata.agent_id = :agent_id';
            $params[':agent_id'] = $agentId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $task = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $task ?: null;
    }

    /** task_view.php: activity history for a task, newest first. */
    public function history(int $taskId): array
    {
        $stmt = $this->pdo->prepare("SELECT h.*, COALESCE(u.full_name, u.email, 'System user') AS performer
            FROM agent_task_history h
            LEFT JOIN admin_users u ON u.id = h.performed_by
            WHERE h.allocation_id = :id ORDER BY h.created_at DESC, h.id DESC");
        $stmt->execute([':id' => $taskId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * checklist.php: locates the in_progress/completed seller allocation a
     * checklist belongs to. $requestedTaskId narrows to a specific task_id
     * when supplied; $agentId (non-null for agent_coordinator) restricts to
     * that agent's own allocation.
     */
    public function findChecklistTask(int $propertyId, int $requestedTaskId, ?int $agentId): ?array
    {
        $params = [':property_id' => $propertyId];
        $sql = "SELECT id, agent_id, status FROM agent_task_allocations
            WHERE allocation_type = 'seller' AND entity_id = :property_id AND status IN ('in_progress', 'completed')";
        if ($requestedTaskId > 0) {
            $sql .= ' AND id = :task_id';
            $params[':task_id'] = $requestedTaskId;
        }
        if ($agentId !== null) {
            $sql .= ' AND agent_id = :agent_id';
            $params[':agent_id'] = $agentId;
        }
        $sql .= ' ORDER BY id DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $task = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $task ?: null;
    }

    /** checklist.php: whether $agentId owns an in_progress/completed seller task for $propertyId. */
    public function agentOwnsProperty(int $agentId, int $propertyId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM agent_task_allocations WHERE agent_id = :agent_id AND allocation_type = 'seller' AND entity_id = :property_id AND status IN ('in_progress', 'completed') LIMIT 1");
        $stmt->execute([':agent_id' => $agentId, ':property_id' => $propertyId]);
        return (bool) $stmt->fetchColumn();
    }

    /** checklist.php: active stages/items with completion flags for one allocation. */
    public function checklistRows(int $allocationId): array
    {
        $sql = 'SELECT
            cs.id            AS stage_id,
            cs.stage_name,
            cs.stage_order,
            cs.description,

            ci.id            AS item_id,
            ci.item_name,
            ci.item_order,
            ci.is_required,

            IFNULL(pcs.is_completed, 0) AS is_completed
        FROM checklist_stages cs
        JOIN checklist_items ci
            ON ci.stage_id = cs.id
        LEFT JOIN property_checklist_status pcs
            ON pcs.checklist_item_id = ci.id
           AND pcs.allocation_id = :allocation_id
        WHERE cs.is_active = 1
        ORDER BY cs.stage_order, ci.item_order;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['allocation_id' => $allocationId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** config/get_stages.php: active stages for the "add checklist item" dropdown. */
    public function activeStages(): array
    {
        $stmt = $this->pdo->query('SELECT id, stage_name FROM checklist_stages WHERE is_active = 1 ORDER BY stage_order, id');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

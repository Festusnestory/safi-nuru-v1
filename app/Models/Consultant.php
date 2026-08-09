<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Read-side query for the internal "consultant list" page (consultant_list.php),
 * which lists the buyers table filtered to source = 'quick_consult' - the
 * walk-in consultations logged via the consulting agent form.
 */
final class Consultant
{
    public function __construct(private \PDO $pdo)
    {
    }

    /**
     * Walk-in consultations. When $loadedBy is provided (agent_consultant role),
     * results are scoped to that user - mirrors consultant_list.php's
     * currentRole() === 'agent_consultant' branch exactly.
     */
    public function list(?int $loadedBy): array
    {
        $sql = "
            SELECT id, application_number, full_name, email, phone, region, town, created_at
            FROM buyers
            WHERE source = 'quick_consult'
        ";
        $params = [];
        if ($loadedBy !== null) {
            $sql .= ' AND loaded_by = :loaded_by';
            $params[':loaded_by'] = $loadedBy;
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

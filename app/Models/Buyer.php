<?php
declare(strict_types=1);

namespace App\Models;

final class Buyer
{
    public function __construct(private \PDO $pdo)
    {
    }

    /** Full profile row for buyers_profile.php, sourced from the vw_buyers_profile view. */
    public function findProfile(int $buyerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vw_buyers_profile WHERE buyer_id = :buyer_id');
        $stmt->execute([':buyer_id' => $buyerId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** Uploaded documents for a buyer, newest first. */
    public function documents(int $buyerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT doc_type, file_path
            FROM buyer_documents
            WHERE buyer_id = :buyer_id
            ORDER BY uploaded_at DESC
        ');
        $stmt->execute([':buyer_id' => $buyerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * True when an agent_coordinator is allowed to view this buyer: they
     * loaded it, are directly assigned, or hold a task allocation for it.
     * Mirrors the ownership check formerly inline in buyers_profile.php.
     */
    public function isVisibleToAgent(int $buyerId, int $agentId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM buyers b WHERE b.id = ? AND (
            b.assigned_agent_id = ? OR b.loaded_by = ? OR EXISTS (
                SELECT 1 FROM agent_task_allocations ata
                WHERE ata.allocation_type = 'buyer' AND ata.entity_id = b.id AND ata.agent_id = ?
            )
        )");
        $stmt->execute([$buyerId, $agentId, $userId, $agentId]);
        return (bool) $stmt->fetchColumn();
    }

    /** True when an agent_consultant's own quick-consult buyer matches. */
    public function isVisibleToConsultant(int $buyerId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM buyers WHERE id = ? AND source = 'quick_consult' AND loaded_by = ?");
        $stmt->execute([$buyerId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Moves a pending buyer to approved and reactivates their portal login.
     * Returns false when the buyer no longer exists / was already reviewed
     * (mirrors config/approve_buyer.php's rowCount() === 0 guard). The
     * caller is responsible for catching/rolling back on a thrown error, to
     * match the original endpoint's try/catch boundary.
     */
    public function approve(int $buyerId): bool
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("UPDATE buyers SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$buyerId]);

        if ($stmt->rowCount() === 0) {
            $this->pdo->rollBack();
            return false;
        }

        $this->pdo->prepare("UPDATE admin_users u JOIN buyers b ON b.user_id = u.id SET u.is_active = 1 WHERE b.id = ? AND u.role = 'buyer'")
            ->execute([$buyerId]);
        $this->pdo->commit();

        return true;
    }

    /**
     * Moves a pending buyer to rejected and deactivates their portal login.
     * Mirrors config/delete_buyer.php - despite the filename, this is a
     * status change (a "Reject" action in the buyer list table), never a
     * row delete.
     */
    public function reject(int $buyerId): bool
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("UPDATE buyers SET status = 'rejected' WHERE id = :id AND status = 'pending'");
        $stmt->execute([':id' => $buyerId]);

        if ($stmt->rowCount() !== 1) {
            $this->pdo->rollBack();
            return false;
        }

        $this->pdo->prepare("UPDATE admin_users u JOIN buyers b ON b.user_id = u.id SET u.is_active = 0 WHERE b.id = ? AND u.role = 'buyer'")
            ->execute([$buyerId]);
        $this->pdo->commit();

        return true;
    }
}

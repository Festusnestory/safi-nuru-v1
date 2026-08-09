<?php
declare(strict_types=1);

namespace App\Models;

final class Inquiry
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function recentCountForIp(string $ipHash): int
    {
        $rate = $this->pdo->prepare('SELECT COUNT(*) FROM public_inquiries WHERE ip_hash = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $rate->execute([':ip' => $ipHash]);
        return (int) $rate->fetchColumn();
    }

    public function insert(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO public_inquiries (property_id, full_name, email, phone, interest, message, consent_at, ip_hash, source_page, submission_token) VALUES (:property_id,:name,:email,:phone,:interest,:message,NOW(),:ip,:source,:submission_token)');
        $stmt->execute([
            ':property_id' => $data['property_id'],
            ':name' => mb_substr($data['full_name'], 0, 120),
            ':email' => $data['email'],
            ':phone' => mb_substr($data['phone'], 0, 40),
            ':interest' => $data['interest'],
            ':message' => mb_substr($data['message'], 0, 1500),
            ':ip' => $data['ip_hash'],
            ':source' => mb_substr($data['source_page'], 0, 255),
            ':submission_token' => $data['submission_token'],
        ]);
    }

    /**
     * Row count per status, keyed by status. Backs the filter-pill badges on
     * html/material/public-inquiries.php (admin review list).
     */
    public function countsByStatus(array $allowedStatuses): array
    {
        $counts = array_fill_keys($allowedStatuses, 0);
        $rows = $this->pdo->query('SELECT status, COUNT(*) AS total FROM public_inquiries GROUP BY status')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['total'];
            }
        }
        return $counts;
    }

    /**
     * Inquiries (optionally filtered by status) with joined property and
     * assignee display data, newest first, for the admin review list.
     */
    public function listForAdmin(?string $status): array
    {
        $sql = 'SELECT
                    pi.id,
                    pi.property_id,
                    pi.full_name,
                    pi.email,
                    pi.phone,
                    pi.interest,
                    pi.message,
                    pi.status,
                    pi.source_page,
                    pi.created_at,
                    sp.property_detail_type,
                    sp.property_town,
                    au.full_name AS assigned_name
                FROM public_inquiries pi
                LEFT JOIN seller_properties sp ON sp.id = pi.property_id
                LEFT JOIN admin_users au ON au.id = pi.assigned_to';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE pi.status = ?';
            $params[] = $status;
        }
        $sql .= " ORDER BY FIELD(pi.status, 'new', 'contacted', 'qualified', 'closed', 'spam'),
                         pi.created_at DESC
                  LIMIT 500";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM public_inquiries WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Updates status and, unless the new status is 'new', backfills
     * assigned_to with the acting admin if it isn't already set. Returns the
     * affected row count (0 can mean "already had this status", not failure
     * - callers should confirm existence separately, matching the original
     * page's behaviour).
     */
    public function updateStatus(int $id, string $status, int $actingUserId): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE public_inquiries
             SET status = ?,
                 assigned_to = CASE
                     WHEN ? = \'new\' THEN assigned_to
                     ELSE COALESCE(assigned_to, ?)
                 END
             WHERE id = ?'
        );
        $stmt->execute([$status, $status, $actingUserId, $id]);
        return $stmt->rowCount();
    }
}

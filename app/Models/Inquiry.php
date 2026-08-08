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
}

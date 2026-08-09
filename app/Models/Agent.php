<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Read-side queries for internal staff agent-management pages (agent_list.php
 * + agenttable_list.php + agent_profile.php). Write/processor logic
 * (admin_agent_processor.php, config/approve_agent_application.php,
 * config/delete_agent.php, config/update_agent_status.php) stays in
 * App\Controllers\Admin\AgentController, ported near-verbatim, matching how
 * App\Controllers\Admin\AuthController keeps its security-sensitive
 * transactional logic inline rather than behind a model.
 */
final class Agent
{
    public function __construct(private \PDO $pdo)
    {
    }

    /** All registered agents, newest first. Mirrors agenttable_list.php's $sql. */
    public function all(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id,
                a.application_id,
                CONCAT(a.first_name, ' ', a.surname) AS full_name,
                a.email,
                a.mobile_number,
                a.company_name,
                a.job_title,
                a.status,
                a.created_at
            FROM agents a
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Agent applications awaiting a staff decision. Mirrors agenttable_list.php's $pendingApps. */
    public function pendingApplications(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, application_number, CONCAT(first_name, ' ', surname) AS full_name, email, mobile_number, company_name, submission_date
            FROM agent_applications
            WHERE status IN ('submitted','under_review')
            ORDER BY submission_date ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Single agent record by primary key. Mirrors agent_profile.php's $sql. */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM agents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $agent = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $agent === false ? null : $agent;
    }
}

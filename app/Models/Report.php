<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Read-only aggregate/listing queries backing the admin reports, analytics,
 * and CSV export pages (html/material/reports-*.php, analytics.php,
 * exports.php). Each method mirrors one of those pages' original inline SQL
 * byte-for-byte; only the entity-config-driven custom report and CSV export
 * take their table/column list as parameters instead of hardcoding it here,
 * since that config lives in the controller (input validation stays there).
 */
final class Report
{
    public function __construct(private \PDO $pdo)
    {
    }

    /** Headline counters for reports-dashboard.php. */
    public function summaryCounts(): array
    {
        return [
            'totalBuyers' => (int) $this->pdo->query('SELECT COUNT(*) FROM buyers')->fetchColumn(),
            'totalAgents' => (int) $this->pdo->query("SELECT COUNT(*) FROM agents WHERE status = 'active'")->fetchColumn(),
            'totalProperties' => (int) $this->pdo->query('SELECT COUNT(*) FROM seller_properties')->fetchColumn(),
            'soldProperties' => (int) $this->pdo->query("SELECT COUNT(*) FROM seller_properties WHERE property_status = 'sold'")->fetchColumn(),
            'pendingBuyers' => (int) $this->pdo->query("SELECT COUNT(*) FROM buyers WHERE status = 'pending'")->fetchColumn(),
            'pendingAgentApps' => (int) $this->pdo->query("SELECT COUNT(*) FROM agent_applications WHERE status IN ('submitted','under_review')")->fetchColumn(),
            'totalSalesValue' => (float) $this->pdo->query("SELECT COALESCE(SUM(sold_price), 0) FROM seller_properties WHERE property_status = 'sold'")->fetchColumn(),
        ];
    }

    /** Top 8 regions by property count, for reports-dashboard.php. */
    public function propertiesByRegion(): array
    {
        return $this->pdo->query("
            SELECT property_region AS region, COUNT(*) AS total
            FROM seller_properties
            GROUP BY property_region
            ORDER BY total DESC
            LIMIT 8
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Sold properties with sale details, for reports-sales.php. */
    public function soldProperties(): array
    {
        return $this->pdo->query("
            SELECT sa.application_number, sp.property_detail_type, sp.property_region, sp.property_town, sp.sold_price, sp.buyer_name, sp.sale_date
            FROM seller_properties sp
            JOIN seller_applications sa ON sa.id = sp.application_id
            WHERE sp.property_status = 'sold'
            ORDER BY sp.sale_date DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** All properties, for reports-property.php. */
    public function allProperties(): array
    {
        return $this->pdo->query('
            SELECT property_detail_type, property_region, property_town, selling_price, property_status, created_at
            FROM seller_properties
            ORDER BY created_at DESC
        ')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Property counts grouped by status, for reports-property.php. */
    public function propertyStatusCounts(): array
    {
        return $this->pdo->query('
            SELECT property_status, COUNT(*) AS total
            FROM seller_properties
            GROUP BY property_status
        ')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Per-agent task totals/completion, for reports-agent.php. */
    public function agentPerformance(): array
    {
        return $this->pdo->query("
            SELECT
                a.id, a.agent_id, CONCAT(a.first_name, ' ', a.surname) AS full_name, a.status,
                COUNT(t.id) AS total_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
            FROM agents a
            LEFT JOIN agent_task_allocations t ON t.agent_id = a.id
            GROUP BY a.id, a.agent_id, a.first_name, a.surname, a.status
            ORDER BY total_tasks DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Ad-hoc filtered listing for reports-custom.php. $cols/$table/$dateCol
     * come from the controller's fixed entity whitelist, not raw user input.
     */
    public function customReport(string $table, string $dateCol, array $cols, string $whereSql, array $params): array
    {
        $colsSql = implode(', ', $cols);
        $stmt = $this->pdo->prepare("SELECT {$colsSql} FROM {$table} {$whereSql} ORDER BY {$dateCol} DESC LIMIT 500");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Buyer applications per month (last 12), for analytics.php. */
    public function monthlyBuyerTrend(): array
    {
        return $this->pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
            FROM buyers
            GROUP BY month
            ORDER BY month ASC
            LIMIT 12
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Agent counts grouped by status, for analytics.php. */
    public function agentStatusCounts(): array
    {
        return $this->pdo->query('
            SELECT status, COUNT(*) AS total FROM agents GROUP BY status
        ')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Full-table row export, for exports.php. $table/$cols come from a fixed whitelist in the controller. */
    public function exportRows(string $table, array $cols): array
    {
        $colsSql = implode(', ', $cols);
        return $this->pdo->query("SELECT {$colsSql} FROM {$table} ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Report;

/**
 * Reports, analytics, and CSV export pages
 * (html/material/reports-dashboard.php, reports-sales.php,
 * reports-property.php, reports-agent.php, reports-custom.php,
 * analytics.php, exports.php). All admin/manager only.
 */
final class ReportController extends Controller
{
    private const EXPORT_CONFIG = [
        'buyers' => ['table' => 'buyers', 'cols' => ['id', 'application_number', 'full_name', 'email', 'phone', 'region', 'town', 'status', 'created_at']],
        'agents' => ['table' => 'agents', 'cols' => ['id', 'agent_id', 'first_name', 'surname', 'email', 'mobile_number', 'company_name', 'status', 'created_at']],
        'properties' => ['table' => 'seller_properties', 'cols' => ['id', 'application_id', 'property_detail_type', 'property_region', 'property_town', 'selling_price', 'property_status', 'created_at']],
    ];

    private const CUSTOM_REPORT_CONFIG = [
        'buyers' => ['table' => 'buyers', 'dateCol' => 'created_at', 'cols' => ['application_number', 'full_name', 'email', 'phone', 'status', 'created_at']],
        'agents' => ['table' => 'agents', 'dateCol' => 'created_at', 'cols' => ['agent_id', 'first_name', 'surname', 'email', 'status', 'created_at']],
        'properties' => ['table' => 'seller_properties', 'dateCol' => 'created_at', 'cols' => ['application_id', 'property_detail_type', 'property_region', 'selling_price', 'property_status', 'created_at']],
    ];

    public function dashboard(): void
    {
        $this->requireRole(['admin', 'manager']);
        $model = new Report($this->pdo);

        $this->render('admin.reports.dashboard', array_merge($model->summaryCounts(), [
            'byRegion' => $model->propertiesByRegion(),
        ]));
    }

    public function sales(): void
    {
        $this->requireRole(['admin', 'manager']);
        $model = new Report($this->pdo);

        $sales = $model->soldProperties();
        $totalSales = count($sales);
        $totalValue = array_sum(array_column($sales, 'sold_price'));
        $avgValue = $totalSales > 0 ? $totalValue / $totalSales : 0;

        $this->render('admin.reports.sales', [
            'sales' => $sales,
            'totalSales' => $totalSales,
            'totalValue' => $totalValue,
            'avgValue' => $avgValue,
        ]);
    }

    public function property(): void
    {
        $this->requireRole(['admin', 'manager']);
        $model = new Report($this->pdo);

        $this->render('admin.reports.property', [
            'properties' => $model->allProperties(),
            'byStatus' => $model->propertyStatusCounts(),
        ]);
    }

    public function agent(): void
    {
        $this->requireRole(['admin', 'manager']);
        $model = new Report($this->pdo);

        $this->render('admin.reports.agent', [
            'agents' => $model->agentPerformance(),
        ]);
    }

    public function custom(): void
    {
        $this->requireRole(['admin', 'manager']);

        $entity = $_GET['entity'] ?? 'buyers';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $dateError = '';

        foreach (['from' => $dateFrom, 'to' => $dateTo] as $label => $dateValue) {
            if ($dateValue !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue) || \DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue)?->format('Y-m-d') !== $dateValue)) {
                $dateError = 'Enter valid report dates.';
            }
        }
        if ($dateError === '' && $dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            $dateError = 'The start date cannot be after the end date.';
        }

        if (!isset(self::CUSTOM_REPORT_CONFIG[$entity])) {
            $entity = 'buyers';
        }
        $config = self::CUSTOM_REPORT_CONFIG[$entity];

        $where = [];
        $params = [];
        if ($dateError === '' && $dateFrom !== '') {
            $where[] = "{$config['dateCol']} >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        if ($dateError === '' && $dateTo !== '') {
            $where[] = "{$config['dateCol']} <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $rows = [];
        if ($dateError === '') {
            $model = new Report($this->pdo);
            $rows = $model->customReport($config['table'], $config['dateCol'], $config['cols'], $whereSql, $params);
        }

        $this->render('admin.reports.custom', [
            'entity' => $entity,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dateError' => $dateError,
            'config' => $config,
            'rows' => $rows,
        ]);
    }

    public function analytics(): void
    {
        $this->requireRole(['admin', 'manager']);
        $model = new Report($this->pdo);

        $monthly = $model->monthlyBuyerTrend();
        $agentStatusCounts = $model->agentStatusCounts();

        $this->render('admin.reports.analytics', [
            'monthLabels' => json_encode(array_column($monthly, 'month')),
            'monthValues' => json_encode(array_map('intval', array_column($monthly, 'total'))),
            'agentLabels' => json_encode(array_map('ucfirst', array_column($agentStatusCounts, 'status'))),
            'agentValues' => json_encode(array_map('intval', array_column($agentStatusCounts, 'total'))),
        ]);
    }

    public function exports(): void
    {
        $this->requireRole(['admin', 'manager']);
        $csrfToken = \App\Core\Auth::csrfToken('data_export');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download']) && isset(self::EXPORT_CONFIG[$_POST['download']])) {
            if (!\App\Core\Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'data_export')) {
                http_response_code(419);
                exit('Your session has expired. Reload the page and try again.');
            }

            $entity = $_POST['download'];
            $config = self::EXPORT_CONFIG[$entity];
            $model = new Report($this->pdo);
            $rows = $model->exportRows($config['table'], $config['cols']);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $entity . '_' . date('Ymd') . '.csv"');
            header('Cache-Control: no-store, private, max-age=0');
            header('X-Content-Type-Options: nosniff');

            $out = fopen('php://output', 'w');
            fputcsv($out, $config['cols']);
            foreach ($rows as $row) {
                // Spreadsheet programs execute formula-like cell values. Prefixing
                // untrusted values protects exported names, emails, and other data
                // from CSV formula injection when opened by staff.
                $safeRow = array_map(static function ($value) {
                    $value = (string) $value;
                    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
                }, $row);
                fputcsv($out, $safeRow);
            }
            fclose($out);
            exit;
        }

        $this->render('admin.reports.exports', [
            'csrfToken' => $csrfToken,
        ]);
    }
}

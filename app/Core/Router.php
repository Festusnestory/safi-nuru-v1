<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal front-controller router for the new clean-URL routes (starting
 * with /login, /logout). Existing pages keep working at their current
 * html/material/*.php and root *.php URLs via thin shims - this only
 * handles the small, growing set of routes registered against public/index.php.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function any(string $path, callable $handler): void
    {
        $this->get($path, $handler);
        $this->post($path, $handler);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requested = (string) ($_GET['__route'] ?? '');
        $path = '/' . trim($requested, '/');

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $handler();
    }

    /**
     * Absolute base path of the app (e.g. "/nuru-v1"), derived from
     * SCRIPT_NAME regardless of whether the current request landed here via
     * public/index.php or a legacy html/material/*.php file - both markers
     * are checked so shared views render correct links either way.
     */
    public static function basePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        foreach (['/public/index.php', '/html/material'] as $marker) {
            $pos = strpos($script, $marker);
            if ($pos !== false) {
                return rtrim(substr($script, 0, $pos), '/');
            }
        }
        return rtrim(dirname($script), '/');
    }

    /**
     * Maps legacy html/material/*.php paths (as used throughout the shared
     * nav partials) to their migrated clean /admin/* route, where one
     * exists. Every entry here has a matching $router->any(...) call in
     * public/index.php - keep the two in sync when either changes.
     */
    private const LEGACY_ROUTES = [
        'buyers-list.php' => '/admin/buyers-list',
        'buyer_admin_form.php' => '/admin/buyer-admin-form',
        'admin_buyer_processor.php' => '/admin/admin-buyer-processor',
        'buyers_profile.php' => '/admin/buyers-profile',
        'config/approve_buyer.php' => '/admin/approve-buyer',
        'config/delete_buyer.php' => '/admin/delete-buyer',
        'sellers-list.php' => '/admin/sellers-list',
        'sellers_profile.php' => '/admin/sellers-profile',
        'seller_admin_form.php' => '/admin/seller-admin-form',
        'seller_admin_processor.php' => '/admin/seller-admin-processor',
        'config/review_seller_application.php' => '/admin/review-seller-application',
        'agent_list.php' => '/admin/agent-list',
        'agent_admin_form.php' => '/admin/agent-admin-form',
        'admin_agent_processor.php' => '/admin/admin-agent-processor',
        'agent_profile.php' => '/admin/agent-profile',
        'config/approve_agent_application.php' => '/admin/approve-agent-application',
        'config/delete_agent.php' => '/admin/delete-agent',
        'config/update_agent_status.php' => '/admin/update-agent-status',
        'consulting_agent_form.php' => '/admin/consulting-agent-form',
        'admin_consulting_agent_processor.php' => '/admin/admin-consulting-agent-processor',
        'consultant_list.php' => '/admin/consultant-list',
        'properties-list.php' => '/admin/properties-list',
        'properties-available.php' => '/admin/properties-available',
        'properties-sold.php' => '/admin/properties-sold',
        'property_admin_form.php' => '/admin/property-admin-form',
        'match-results.php' => '/admin/match-results',
        'match-table.php' => '/admin/match-table',
        'match-table1.php' => '/admin/match-table1',
        'match_list.php' => '/admin/match-list',
        'match_property.php' => '/admin/match-property',
        'matched_sellers.php' => '/admin/matched-sellers',
        'agentsellers_matched.php' => '/admin/agentsellers-matched',
        'selleragent_table.php' => '/admin/selleragent-table',
        'agenttable_list.php' => '/admin/agenttable-list',
        'reports-dashboard.php' => '/admin/reports-dashboard',
        'reports-sales.php' => '/admin/reports-sales',
        'reports-property.php' => '/admin/reports-property',
        'reports-agent.php' => '/admin/reports-agent',
        'reports-custom.php' => '/admin/reports-custom',
        'analytics.php' => '/admin/analytics',
        'exports.php' => '/admin/exports',
        'settings-general.php' => '/admin/settings-general',
        'settings-email.php' => '/admin/settings-email',
        'settings-database.php' => '/admin/settings-database',
        'backup-restore.php' => '/admin/backup-restore',
        'activity-log.php' => '/admin/activity-log',
        'user-management.php' => '/admin/user-management',
        'view_document.php' => '/admin/view-document',
        'public-inquiries.php' => '/admin/public-inquiries',
        'tasks-pending.php' => '/admin/tasks-pending',
        'tasks-in-progress.php' => '/admin/tasks-in-progress',
        'tasks-completed.php' => '/admin/tasks-completed',
        'tasks-cancelled.php' => '/admin/tasks-cancelled',
        'agent-tasks-pending.php' => '/admin/agent-tasks-pending',
        'agent_tasks-in-progress.php' => '/admin/agent-tasks-in-progress',
        'task_view.php' => '/admin/task-view',
        'config/assign_agent_task.php' => '/admin/assign-agent-task',
        'config/reassign_task.php' => '/admin/reassign-task',
        'config/update_task_progress.php' => '/admin/update-task-progress',
        'config/agent_update_task_status.php' => '/admin/agent-update-task-status',
        'checklist.php' => '/admin/checklist',
        'items.php' => '/admin/items',
        'add_items.php' => '/admin/add-items',
        'stages.php' => '/admin/stages',
        'add_stages.php' => '/admin/add-stages',
        'config/save_stage.php' => '/admin/save-stage',
        'config/get_stages.php' => '/admin/get-stages',
        'config/save-checklist-item.php' => '/admin/save-checklist-item',
        'config/save_checklist_item.php' => '/admin/add-checklist-item',
        'config/logout.php' => '/logout',
        'buyer/index.php' => '/buyer',
        'seller/index.php' => '/seller',
        'agent/index.php' => '/agent',
        'authentication-login.php' => '/login',
    ];

    /**
     * Resolves a legacy html/material/*.php path to the shortest working
     * URL: the migrated clean /admin/* route if one is registered, else the
     * absolute legacy path (still served via its shim either way).
     */
    public static function legacyUrl(string $legacyPath): string
    {
        $legacyPath = ltrim($legacyPath, './');
        $clean = self::LEGACY_ROUTES[$legacyPath] ?? null;
        if ($clean !== null) {
            return self::basePath() . $clean;
        }
        return self::basePath() . '/html/material/' . $legacyPath;
    }
}

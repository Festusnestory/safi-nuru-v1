<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\Admin\AgentController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BuyerController;
use App\Controllers\Admin\ConsultantController;
use App\Controllers\Admin\DocumentController;
use App\Controllers\Admin\InquiryController;
use App\Controllers\Admin\MatchingController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\SellerController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\TaskController;
use App\Core\Router;

$router = new Router();

$router->any('/login', function (): void {
    (new AuthController())->loginPage();
});

$router->any('/logout', function (): void {
    (new AuthController())->logout();
});

$router->any('/buyer', function (): void {
    require \NURU_MATERIAL . '/buyer/index.php';
});

$router->any('/seller', function (): void {
    require \NURU_MATERIAL . '/seller/index.php';
});

$router->any('/agent', function (): void {
    require \NURU_MATERIAL . '/agent/index.php';
});

$router->any('/admin/buyers-list', function (): void {
    (new BuyerController())->list();
});

$router->any('/admin/buyer-admin-form', function (): void {
    (new BuyerController())->form();
});

$router->any('/admin/admin-buyer-processor', function (): void {
    (new BuyerController())->store();
});

$router->any('/admin/buyers-profile', function (): void {
    (new BuyerController())->profile();
});

$router->any('/admin/approve-buyer', function (): void {
    (new BuyerController())->approve();
});

$router->any('/admin/delete-buyer', function (): void {
    (new BuyerController())->reject();
});

$router->any('/admin/sellers-list', function (): void {
    (new SellerController())->list();
});

$router->any('/admin/sellers-profile', function (): void {
    (new SellerController())->profile();
});

$router->any('/admin/seller-admin-form', function (): void {
    (new SellerController())->formPage();
});

$router->any('/admin/seller-admin-processor', function (): void {
    (new SellerController())->store();
});

$router->any('/admin/review-seller-application', function (): void {
    (new SellerController())->review();
});

$router->any('/admin/agent-list', function (): void {
    (new AgentController())->list();
});

$router->any('/admin/agent-admin-form', function (): void {
    (new AgentController())->form();
});

$router->any('/admin/admin-agent-processor', function (): void {
    (new AgentController())->store();
});

$router->any('/admin/agent-profile', function (): void {
    (new AgentController())->profile();
});

$router->any('/admin/approve-agent-application', function (): void {
    (new AgentController())->approveApplication();
});

$router->any('/admin/delete-agent', function (): void {
    (new AgentController())->delete();
});

$router->any('/admin/update-agent-status', function (): void {
    (new AgentController())->updateStatus();
});

$router->any('/admin/consulting-agent-form', function (): void {
    (new ConsultantController())->form();
});

$router->any('/admin/admin-consulting-agent-processor', function (): void {
    (new ConsultantController())->store();
});

$router->any('/admin/consultant-list', function (): void {
    (new ConsultantController())->list();
});

$router->any('/admin/properties-available', function (): void {
    (new \App\Controllers\Admin\PropertyController())->available();
});

$router->any('/admin/properties-sold', function (): void {
    (new \App\Controllers\Admin\PropertyController())->sold();
});

$router->any('/admin/property-admin-form', function (): void {
    (new \App\Controllers\Admin\PropertyController())->form();
});

$router->any('/admin/match-results', function (): void {
    (new MatchingController())->results();
});

$router->any('/admin/match-table', function (): void {
    (new MatchingController())->table('agent');
});

$router->any('/admin/match-table1', function (): void {
    (new MatchingController())->table('auto');
});

$router->any('/admin/match-list', function (): void {
    (new MatchingController())->table('agent');
});

$router->any('/admin/match-property', function (): void {
    (new MatchingController())->confirmMatch();
});

$router->any('/admin/matched-sellers', function (): void {
    (new MatchingController())->checkallDemo();
});

$router->any('/admin/agentsellers-matched', function (): void {
    (new MatchingController())->sellerPortfolio();
});

$router->any('/admin/selleragent-table', function (): void {
    http_response_code(404);
    exit('Not found.');
});

$router->any('/admin/agenttable-list', function (): void {
    http_response_code(404);
    exit('Not found.');
});

$router->any('/admin/reports-dashboard', function (): void {
    (new ReportController())->dashboard();
});

$router->any('/admin/reports-sales', function (): void {
    (new ReportController())->sales();
});

$router->any('/admin/reports-property', function (): void {
    (new ReportController())->property();
});

$router->any('/admin/reports-agent', function (): void {
    (new ReportController())->agent();
});

$router->any('/admin/reports-custom', function (): void {
    (new ReportController())->custom();
});

$router->any('/admin/analytics', function (): void {
    (new ReportController())->analytics();
});

$router->any('/admin/exports', function (): void {
    (new ReportController())->exports();
});

$router->any('/admin/settings-general', function (): void {
    (new SettingsController())->general();
});

$router->any('/admin/settings-email', function (): void {
    (new SettingsController())->email();
});

$router->any('/admin/settings-database', function (): void {
    (new SettingsController())->database();
});

$router->any('/admin/backup-restore', function (): void {
    (new SettingsController())->backup();
});

$router->any('/admin/activity-log', function (): void {
    (new SettingsController())->activityLog();
});

$router->any('/admin/user-management', function (): void {
    (new SettingsController())->users();
});

$router->any('/admin/view-document', function (): void {
    (new DocumentController())->view();
});

$router->any('/admin/public-inquiries', function (): void {
    (new InquiryController())->list();
});

$router->any('/admin/tasks-pending', function (): void {
    (new TaskController())->pending();
});

$router->any('/admin/tasks-in-progress', function (): void {
    (new TaskController())->inProgress();
});

$router->any('/admin/tasks-completed', function (): void {
    (new TaskController())->completed();
});

$router->any('/admin/tasks-cancelled', function (): void {
    (new TaskController())->cancelled();
});

$router->any('/admin/agent-tasks-pending', function (): void {
    (new TaskController())->agentPending();
});

$router->any('/admin/agent-tasks-in-progress', function (): void {
    (new TaskController())->agentInProgress();
});

$router->any('/admin/task-view', function (): void {
    (new TaskController())->view();
});

$router->any('/admin/assign-agent-task', function (): void {
    (new TaskController())->assign();
});

$router->any('/admin/reassign-task', function (): void {
    (new TaskController())->reassign();
});

$router->any('/admin/update-task-progress', function (): void {
    (new TaskController())->updateProgress();
});

$router->any('/admin/agent-update-task-status', function (): void {
    (new TaskController())->updateStatus();
});

$router->any('/admin/checklist', function (): void {
    (new TaskController())->checklist();
});

$router->any('/admin/items', function (): void {
    (new TaskController())->items();
});

$router->any('/admin/add-items', function (): void {
    (new TaskController())->addItemsFragment();
});

$router->any('/admin/stages', function (): void {
    (new TaskController())->stages();
});

$router->any('/admin/add-stages', function (): void {
    (new TaskController())->addStagesFragment();
});

$router->any('/admin/save-stage', function (): void {
    (new TaskController())->saveStage();
});

$router->any('/admin/get-stages', function (): void {
    (new TaskController())->getStages();
});

// config/save-checklist-item.php (agent toggling one checklist item's
// completion from checklist.php) vs config/save_checklist_item.php (admin
// defining a new checklist item template from items.php) are two distinct
// legacy endpoints that collide under the strip-.php/underscores-to-hyphens
// naming rule - "add-checklist-item" (creates a new item definition) keeps
// them apart instead of both mapping to "/admin/save-checklist-item".
$router->any('/admin/save-checklist-item', function (): void {
    (new TaskController())->saveChecklistItemProgress();
});

$router->any('/admin/add-checklist-item', function (): void {
    (new TaskController())->createChecklistItem();
});

$router->dispatch();

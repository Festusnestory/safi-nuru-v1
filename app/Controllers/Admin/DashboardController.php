<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;

/**
 * Role-specific dashboard landing pages (dashboard_1.php..dashboard_5.php).
 * Each method name matches the role it serves rather than the original
 * numeric filename - role_helpers.php::roleHomeRoute() and config/login.php
 * still redirect to the same dashboard_N.php URLs, which stay as thin shims.
 */
final class DashboardController extends Controller
{
    // The main admin/manager dashboard (formerly admin.php). Its stats and
    // region-breakdown data are computed by top-bar.php itself (required by
    // the View below), not here - matching-functions.php is included only
    // for parity with the legacy page, which loaded it but never called any
    // of its functions.
    public function admin(): void
    {
        $this->requireRole(['admin', 'manager']);
        require_once \NURU_MATERIAL . '/config/matching-functions.php';

        $this->render('admin.dashboards.admin', [
            'baseUrl' => \App\Core\Router::basePath(),
        ]);
    }

    public function buyer(): void
    {
        $this->requireRole(['buyer']);

        $stmt = $this->pdo->prepare('SELECT * FROM buyers WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $buyer = $stmt->fetch(\PDO::FETCH_ASSOC);

        $areas = [];
        $documents = [];
        $assignedAgent = null;

        if ($buyer) {
            $stmt = $this->pdo->prepare('SELECT region, town, location, suburb FROM buyer_preferred_areas WHERE buyer_id = ?');
            $stmt->execute([$buyer['id']]);
            $areas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare('SELECT doc_type, file_path, uploaded_at FROM buyer_documents WHERE buyer_id = ?');
            $stmt->execute([$buyer['id']]);
            $documents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($buyer['assigned_agent_id']) {
                $stmt = $this->pdo->prepare("SELECT CONCAT(first_name, ' ', surname) AS full_name, email, mobile_number FROM agents WHERE id = ?");
                $stmt->execute([$buyer['assigned_agent_id']]);
                $assignedAgent = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
        }

        $this->render('admin.dashboards.buyer', [
            'buyer' => $buyer,
            'areas' => $areas,
            'documents' => $documents,
            'assignedAgent' => $assignedAgent,
        ]);
    }

    public function seller(): void
    {
        $this->requireRole(['seller']);

        $stmt = $this->pdo->prepare("
            SELECT sa.*, spd.first_name, spd.surname
            FROM seller_applications sa
            LEFT JOIN seller_personal_details spd ON spd.application_id = sa.id
            WHERE sa.user_id = ?
            ORDER BY sa.created_at DESC LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $application = $stmt->fetch(\PDO::FETCH_ASSOC);

        $properties = [];
        $documents = [];
        $assignedAgent = null;

        if ($application) {
            $stmt = $this->pdo->prepare('SELECT * FROM seller_properties WHERE application_id = ? ORDER BY id');
            $stmt->execute([$application['id']]);
            $properties = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare('SELECT document_type, original_filename, upload_date FROM seller_documents WHERE application_id = ?');
            $stmt->execute([$application['id']]);
            $documents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($application['assigned_agent_id']) {
                $stmt = $this->pdo->prepare("SELECT CONCAT(first_name, ' ', surname) AS full_name, email, mobile_number FROM agents WHERE id = ?");
                $stmt->execute([$application['assigned_agent_id']]);
                $assignedAgent = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
        }

        $this->render('admin.dashboards.seller', [
            'application' => $application,
            'properties' => $properties,
            'documents' => $documents,
            'assignedAgent' => $assignedAgent,
        ]);
    }

    public function manager(): void
    {
        $this->requireRole(['admin', 'manager']);

        $totalAgents = (int) $this->pdo->query("SELECT COUNT(*) FROM agents WHERE status IN ('approved','active')")->fetchColumn();
        $activeTasks = (int) $this->pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status IN ('assigned','in_progress')")->fetchColumn();
        $completedTasks = (int) $this->pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status = 'completed'")->fetchColumn();
        $pendingBuyers = (int) $this->pdo->query("SELECT COUNT(*) FROM buyers WHERE status = 'pending'")->fetchColumn();
        $pendingAgentApps = (int) $this->pdo->query("SELECT COUNT(*) FROM agent_applications WHERE status IN ('submitted','under_review')")->fetchColumn();
        $pendingSellerApps = (int) $this->pdo->query("SELECT COUNT(*) FROM seller_applications WHERE status IN ('submitted','under_review')")->fetchColumn();

        $agentWorkload = $this->pdo->query("
            SELECT
                CONCAT(a.first_name, ' ', a.surname) AS full_name,
                COUNT(t.id) AS total_tasks,
                SUM(CASE WHEN t.status IN ('assigned','in_progress') THEN 1 ELSE 0 END) AS active_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
            FROM agents a
            LEFT JOIN agent_task_allocations t ON t.agent_id = a.id
            WHERE a.status IN ('approved','active')
            GROUP BY a.id, a.first_name, a.surname
            ORDER BY active_tasks DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin.dashboards.manager', [
            'totalAgents' => $totalAgents,
            'activeTasks' => $activeTasks,
            'completedTasks' => $completedTasks,
            'pendingBuyers' => $pendingBuyers,
            'pendingAgentApps' => $pendingAgentApps,
            'pendingSellerApps' => $pendingSellerApps,
            'agentWorkload' => $agentWorkload,
        ]);
    }

    public function agentCoordinator(): void
    {
        $this->requireRole(['agent_coordinator']);

        $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);

        $openTasks = 0;
        $overdueTasks = 0;
        $completedTasks = 0;
        $myBuyers = 0;
        $mySellers = 0;
        $upcomingTasks = [];

        if ($myAgentId) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM agent_task_allocations WHERE agent_id = ? AND status IN ('assigned','in_progress')");
            $stmt->execute([$myAgentId]);
            $openTasks = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM agent_task_allocations WHERE agent_id = ? AND status IN ('assigned','in_progress') AND due_date IS NOT NULL AND due_date < CURDATE()");
            $stmt->execute([$myAgentId]);
            $overdueTasks = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM agent_task_allocations WHERE agent_id = ? AND status = 'completed' AND completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
            $stmt->execute([$myAgentId]);
            $completedTasks = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("
                SELECT ata.id AS task_id, ata.due_date, ata.status, ata.entity_reference,
                       COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_id,
                       COALESCE(sp.property_region, b.region) AS property_region,
                       COALESCE(sp.property_town, b.town) AS property_town
                FROM agent_task_allocations ata
                LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
                LEFT JOIN seller_applications sa ON sa.id = sp.application_id
                LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
                WHERE ata.agent_id = ? AND ata.status IN ('assigned','in_progress')
                ORDER BY ata.due_date ASC
                LIMIT 5
            ");
            $stmt->execute([$myAgentId]);
            $upcomingTasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM buyers b WHERE b.loaded_by = :user_id OR EXISTS (
            SELECT 1 FROM agent_task_allocations ata WHERE ata.agent_id = :agent_id AND ata.allocation_type = 'buyer' AND ata.entity_id = b.id
        )");
        $stmt->execute([':user_id' => (int) $_SESSION['user_id'], ':agent_id' => $myAgentId ?? 0]);
        $myBuyers = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT sa.id) FROM seller_applications sa
            LEFT JOIN seller_personal_details spd ON spd.application_id = sa.id
            WHERE spd.loaded_by = :user_id OR EXISTS (
                SELECT 1 FROM seller_properties sp
                JOIN agent_task_allocations ata ON ata.allocation_type = 'seller' AND ata.entity_id = sp.id
                WHERE sp.application_id = sa.id AND ata.agent_id = :agent_id
            )");
        $stmt->execute([':user_id' => (int) $_SESSION['user_id'], ':agent_id' => $myAgentId ?? 0]);
        $mySellers = (int) $stmt->fetchColumn();

        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        \expireOverdueProperties($this->pdo);

        $myExpiring = [];
        if ($myAgentId) {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT sp.id, sa.application_number AS application_id, sp.property_region, sp.property_town,
                       sp.property_status, sp.status_deadline
                FROM agent_task_allocations ata
                JOIN seller_properties sp ON sp.id = ata.entity_id
                JOIN seller_applications sa ON sa.id = sp.application_id
                WHERE ata.agent_id = ? AND sp.property_status IN ('under_offer','sold')
                  AND sp.status_deadline IS NOT NULL
                ORDER BY sp.status_deadline ASC
                LIMIT 5
            ");
            $stmt->execute([$myAgentId]);
            $myExpiring = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $this->render('admin.dashboards.agent-coordinator', [
            'openTasks' => $openTasks,
            'overdueTasks' => $overdueTasks,
            'completedTasks' => $completedTasks,
            'myBuyers' => $myBuyers,
            'mySellers' => $mySellers,
            'upcomingTasks' => $upcomingTasks,
            'myExpiring' => $myExpiring,
        ]);
    }

    public function agentConsultant(): void
    {
        $this->requireRole(['agent_consultant']);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM buyers WHERE source = 'quick_consult' AND loaded_by = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $totalConsultations = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM buyers WHERE source = 'quick_consult' AND loaded_by = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        $stmt->execute([$_SESSION['user_id']]);
        $consultationsThisMonth = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT id, application_number, full_name, email, phone, region, town, created_at
            FROM buyers
            WHERE source = 'quick_consult' AND loaded_by = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recentConsultations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin.dashboards.agent-consultant', [
            'totalConsultations' => $totalConsultations,
            'consultationsThisMonth' => $consultationsThisMonth,
            'recentConsultations' => $recentConsultations,
        ]);
    }
}

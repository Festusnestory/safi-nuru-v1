<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Controller;
use App\Core\Router;
use App\Models\Matching;
use App\Models\Property;

final class MatchingController extends Controller
{
    /**
     * Buyer-matches summary table. Consolidates match-table.php and
     * match-table1.php, which compute the identical $buyerSummary (both
     * ultimately require config/matching-functions.php) and render the
     * identical table markup - the only real difference between the two
     * legacy files is how the sidebar chrome is chosen:
     *  - match-table.php always shows agent_nemu.php (it's only linked from
     *    the agent_coordinator nav in agent_nemu.php itself);
     *  - match-table1.php conditionally shows left-sidebar.php/agent_nemu.php
     *    based on isFullAccess() (it's linked from the admin/manager nav in
     *    left-sidebar.php).
     * $chromeMode preserves that distinction exactly rather than merging it away.
     */
    public function table(string $chromeMode): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $model = new Matching($this->pdo);
        $isAgent = Auth::currentRole() === 'agent_coordinator';
        $agentId = $isAgent ? (\resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0) : 0;
        $buyerSummary = $model->buyerSummary($isAgent, (int) $_SESSION['user_id'], $agentId);

        $this->render('admin.matching.table', [
            'buyerSummary' => $buyerSummary,
            'forceAgentChrome' => $chromeMode === 'agent',
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** Ported from html/material/match-results.php. */
    public function results(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        \expireOverdueProperties($this->pdo);

        $assignTaskCsrf = Auth::csrfToken('assign_agent_task');
        $markSoldCsrf = Auth::csrfToken('mark_property_sold');

        $model = new Matching($this->pdo);
        $isAgent = Auth::currentRole() === 'agent_coordinator';
        $agentId = $isAgent ? (\resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0) : 0;
        $soldProperties = $model->activeMatches($isAgent, (int) $_SESSION['user_id'], $agentId);
        $agents = $model->availableAgents();

        $this->render('admin.matching.results', [
            'soldProperties' => $soldProperties,
            'agents' => $agents,
            'assignTaskCsrf' => $assignTaskCsrf,
            'markSoldCsrf' => $markSoldCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /**
     * Seller portfolio listing. Consolidates agentsellers_matched.php (page
     * chrome, always agent_nemu.php sidebar) and selleragent_table.php (the
     * table content, previously only reachable as an include of the former
     * behind a NURU_SELLER_AGENT_INCLUDE guard).
     */
    public function sellerPortfolio(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $model = new Matching($this->pdo);
        $isAgent = Auth::currentRole() === 'agent_coordinator';
        $agentId = $isAgent ? (\resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0) : 0;
        $sellers = $model->sellerPortfolio($isAgent, (int) $_SESSION['user_id'], $agentId);

        $this->render('admin.matching.seller-portfolio', [
            'sellers' => $sellers,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /**
     * Static vendor demo fragment ("Checkall Table"). Ported byte-for-byte
     * from html/material/matched_sellers.php, which despite its name is an
     * unused MaterialPro template sample (no session guard, no dynamic
     * data, and nothing in the app links to it) - preserved as-is rather
     * than reinterpreted as a real matching page.
     */
    public function checkallDemo(): void
    {
        $this->render('admin.matching.checkall-demo');
    }

    /**
     * JSON endpoint: confirms a buyer/property match. Ported from
     * html/material/match_property.php.
     */
    public function confirmMatch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');
        header('X-Content-Type-Options: nosniff');
        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        require_once \NURU_MATERIAL . '/includes/functions.php';
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $respond = static function (int $code, bool $success, string $message): never {
            http_response_code($code);
            echo json_encode(['success' => $success, 'message' => $message]);
            exit;
        };

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $respond(405, false, 'Method not allowed.');
        }
        if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'property_matching')) {
            $respond(403, false, 'Your session token has expired. Refresh the page and try again.');
        }
        $sellerId = filter_var($_POST['seller_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $buyerId = filter_var($_POST['buyer_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($sellerId === false || $buyerId === false) {
            $respond(422, false, 'Invalid buyer or property selection.');
        }

        $model = new Matching($this->pdo);

        try {
            $this->pdo->beginTransaction();
            $buyer = $model->lockBuyer((int) $buyerId);
            $property = $model->lockProperty((int) $sellerId);
            if (!$buyer || !$property) {
                throw new \RuntimeException('The buyer or property is no longer available.', 404);
            }
            if ($buyer['status'] !== 'approved') {
                throw new \RuntimeException('Only approved buyers can be matched.', 409);
            }
            if ($property['property_status'] !== 'available') {
                throw new \RuntimeException('The property is no longer available for matching.', 409);
            }
            if (Auth::currentRole() === 'agent_coordinator') {
                $agentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
                $buyerAllowed = (int) ($buyer['assigned_agent_id'] ?? 0) === $agentId
                    || (string) ($buyer['loaded_by'] ?? '') === (string) $_SESSION['user_id']
                    || $model->hasBuyerTask((int) $buyerId, $agentId);
                $propertyAllowed = (int) ($property['assigned_agent_id'] ?? 0) === $agentId
                    || (string) ($property['seller_loaded_by'] ?? '') === (string) $_SESSION['user_id']
                    || $model->hasPropertyTask((int) $sellerId, $agentId);
                if (!$buyerAllowed || !$propertyAllowed) {
                    throw new \RuntimeException('You are not assigned to both records.', 403);
                }
            }

            $deadline = \computeStatusDeadline('under_offer');
            $model->matchPropertyToBuyer((int) $sellerId, (int) $buyerId, (string) $buyer['full_name'], $deadline);

            $propertyModel = new Property($this->pdo);
            $propertyModel->recordMatchAudit(
                (int) $sellerId,
                (int) $buyerId,
                (string) $buyer['full_name'],
                'matched',
                (int) $_SESSION['user_id'],
                'Initial buyer-seller match'
            );
            $this->pdo->commit();
            \logActivity((int) $_SESSION['user_id'], 'PROPERTY_MATCHED', "Matched property #{$sellerId} with buyer #{$buyerId}", 'property_matching', 'warning');
            $respond(200, true, 'Property matched successfully.');
        } catch (\RuntimeException $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $status = in_array($error->getCode(), [403, 404, 409], true) ? $error->getCode() : 422;
            $respond($status, false, $error->getMessage());
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Property matching failed: ' . $error->getMessage());
            $respond(500, false, 'The property could not be matched.');
        }
    }
}

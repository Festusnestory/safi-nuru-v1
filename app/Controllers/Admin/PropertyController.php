<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Property;

final class PropertyController extends Controller
{
    public function list(): void
    {
        \App\Core\Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        \expireOverdueProperties($this->pdo);

        $model = new Property($this->pdo);
        $words = 'Properties List';

        if (Auth::currentRole() === 'agent_coordinator') {
            $agentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
            $properties = $model->forAgent($agentId, (int) $_SESSION['user_id']);
            $words = 'My Properties';
        } else {
            $properties = $model->all();
        }

        $this->render('admin.properties.list', [
            'properties' => $properties,
            'words' => $words,
        ]);
    }
}

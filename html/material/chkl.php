<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager', 'agent_coordinator']);
header('Location: ' . (currentRole() === 'agent_coordinator' ? 'agent-tasks-pending.php' : 'tasks-pending.php'));
exit;

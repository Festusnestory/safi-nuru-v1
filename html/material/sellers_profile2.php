<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager', 'agent_coordinator']);
header('Location: sellers-list.php', true, 303);
exit;

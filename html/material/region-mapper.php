<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin', 'manager']);
header('Location: analytics.php');
exit;

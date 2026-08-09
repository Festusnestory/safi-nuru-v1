<?php
/**
 * Agent Form AJAX Handler - Portal Integration with Advanced Security
 * Handles all AJAX requests for the agent form with comprehensive security
 * Author: MiniMax Agent
 */

// Define portal access
if (!defined('PORTAL_ACCESS')) { define('PORTAL_ACCESS', true); }
if (!defined('NURU_API_ACCESS')) { define('NURU_API_ACCESS', true); }

// Set headers for security and JSON response
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// The production agent form submits to ../api/applications/agent/index.php.
// Keep this retired prototype endpoint closed so its older, session-draft
// workflow cannot be used as an alternate application/upload path.
http_response_code(410);
header('Cache-Control: no-store, private, max-age=0');
echo json_encode(['success' => false, 'message' => 'This endpoint has been retired.']);
exit;

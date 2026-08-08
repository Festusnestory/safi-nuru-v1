<?php
/**
 * Agent Form Security Middleware
 * Integrates agent form with advanced security system
 */

// Define access constants
if (!defined('PORTAL_ACCESS')) { define('PORTAL_ACCESS', true); }
if (!defined('NURU_API_ACCESS')) { define('NURU_API_ACCESS', true); }

// Include security integration
require_once __DIR__ . '/../../api/security_integration.php';
require_once __DIR__ . '/../../includes/auth.php';

class AgentSecurityMiddleware {
    private static $initialized = false;
    private static $form_endpoints = [
        'init' => ['rate_limit' => 'api', 'requires_auth' => true],
        'save_step' => ['rate_limit' => 'upload', 'requires_auth' => true, 'requires_csrf' => true],
        'update_step' => ['rate_limit' => 'api', 'requires_auth' => true, 'requires_csrf' => true],
        'get_region' => ['rate_limit' => 'api', 'requires_auth' => true],
        'upload_file' => ['rate_limit' => 'upload', 'requires_auth' => true, 'requires_csrf' => true, 'file_security' => true],
        'submit_form' => ['rate_limit' => 'upload', 'requires_auth' => true, 'requires_csrf' => true, 'audit_log' => true]
    ];
    
    /**
     * Initialize security middleware for agent form
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Initialize security integration
        SecurityIntegration::init();
        
        // Set security headers specific for agent form
        self::setAgentSecurityHeaders();
        
        self::$initialized = true;
        
        // Log initialization
        SecurityIntegration::logEvent('INFO', 'agent_form', 'SECURITY_INIT', 'Agent form security middleware initialized');
    }
    
    /**
     * Process agent form request through security middleware
     */
    public static function processRequest($action = null, $method = 'POST') {
        if (!self::$initialized) {
            self::init();
        }
        
        $request_path = '/portal/agent/' . ($action ?? '');
        
        // Process through general security middleware first
        if (!SecurityIntegration::processRequest($request_path, $method)) {
            // Request was blocked by general security middleware
            return false;
        }
        
        // Apply agent-specific security checks
        return self::processAgentSpecificSecurity($action, $method);
    }
    
    /**
     * Process agent-specific security checks
     */
    private static function processAgentSpecificSecurity($action, $method) {
        if (!$action || !isset(self::$form_endpoints[$action])) {
            SecurityIntegration::logEvent('WARNING', 'agent_form', 'INVALID_ACTION', 
                "Invalid agent form action attempted: $action");
            return false;
        }
        
        $endpoint_config = self::$form_endpoints[$action];
        
        // Check authentication
        if ($endpoint_config['requires_auth']) {
            if (!self::validateAuthentication()) {
                return false;
            }
        }
        
        // Check CSRF token
        if ($endpoint_config['requires_csrf']) {
            if (!self::validateCSRFToken()) {
                return false;
            }
        }
        
        // Log successful security check
        SecurityIntegration::logEvent('INFO', 'agent_form', 'ACCESS_GRANTED', 
            "Access granted for agent form action: $action", [
                'action' => $action,
                'user_id' => PortalAuth::getCurrentUser()['id'] ?? null
            ]);
        
        return true;
    }
    
    /**
     * Validate authentication for agent form
     */
    private static function validateAuthentication() {
        if (!PortalAuth::isAuthenticated()) {
            SecurityIntegration::logEvent('WARNING', 'agent_form', 'AUTH_REQUIRED', 
                'Unauthenticated access attempt to agent form');
            self::sendSecurityResponse(401, 'Authentication required');
            return false;
        }
        
        if (!PortalAuth::hasPermission('manage_agents')) {
            SecurityIntegration::logEvent('WARNING', 'agent_form', 'ACCESS_DENIED', 
                'Access denied to agent form - insufficient permissions', [
                    'user_id' => PortalAuth::getCurrentUser()['id'] ?? null,
                    'required_permission' => 'manage_agents'
                ]);
            self::sendSecurityResponse(403, 'Access denied - insufficient permissions');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate CSRF token using integrated security system
     */
    private static function validateCSRFToken() {
        $action = $_POST['action'] ?? '';
        $token = $_POST['csrf_token'] ?? '';
        
        if (!SecurityIntegration::validateCSRFToken($token, "agent_form_{$action}")) {
            SecurityIntegration::logEvent('WARNING', 'agent_form', 'CSRF_VALIDATION_FAILED', 
                "CSRF token validation failed for agent form action: $action", [
                    'user_id' => PortalAuth::getCurrentUser()['id'] ?? null,
                    'token_provided' => !empty($token),
                    'action' => $action
                ]);
            self::sendSecurityResponse(403, 'CSRF token validation failed');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file upload through security middleware
     */
    public static function validateFileUpload($file_info) {
        if (!self::$initialized) {
            self::init();
        }
        
        // Use integrated file security middleware
        $validation_result = SecurityIntegration::validateFileUpload($file_info);
        
        // Log file upload attempt
        SecurityIntegration::logEvent(
            $validation_result['success'] ? 'INFO' : 'WARNING',
            'agent_form',
            $validation_result['success'] ? 'FILE_UPLOAD_ALLOWED' : 'FILE_UPLOAD_BLOCKED',
            $validation_result['success'] ? 
                'File upload passed security validation' : 
                'File upload failed security validation',
            [
                'filename' => $file_info['name'] ?? 'unknown',
                'file_size' => $file_info['size'] ?? 0,
                'mime_type' => $file_info['type'] ?? 'unknown',
                'errors' => $validation_result['errors'] ?? [],
                'warnings' => $validation_result['warnings'] ?? [],
                'quarantined' => $validation_result['quarantined'] ?? false,
                'user_id' => PortalAuth::getCurrentUser()['id'] ?? null
            ]
        );
        
        return $validation_result;
    }
    
    /**
     * Generate CSRF token for agent form
     */
    public static function generateCSRFToken($action = 'default') {
        if (!self::$initialized) {
            self::init();
        }
        
        return SecurityIntegration::generateCSRFToken("agent_form_{$action}");
    }
    
    /**
     * Sanitize agent form input
     */
    public static function sanitizeInput($data, $context = 'general') {
        return SecurityIntegration::sanitizeInput($data, $context);
    }
    
    /**
     * Log agent form activity for audit trail
     */
    public static function logActivity($action, $description, $data = []) {
        $user = PortalAuth::getCurrentUser();
        $context = array_merge($data, [
            'user_id' => $user['id'] ?? null,
            'username' => $user['username'] ?? null,
            'session_id' => session_id(),
            'form_type' => 'agent_form'
        ]);
        
        SecurityIntegration::logEvent('INFO', 'agent_form', $action, $description, $context);
    }
    
    /**
     * Set security headers for agent form
     */
    private static function setAgentSecurityHeaders() {
        $additional_headers = [
            'X-Agent-Form-Security' => 'enabled',
            'X-Portal-Integration' => 'active',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'
        ];
        
        foreach ($additional_headers as $header => $value) {
            if (!headers_sent()) {
                header($header . ': ' . $value);
            }
        }
    }
    
    /**
     * Send security response
     */
    private static function sendSecurityResponse($status_code, $message) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => 'SECURITY_VIOLATION',
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    /**
     * Get security metrics for agent form
     */
    public static function getAgentFormMetrics($period = '24h') {
        $general_metrics = SecurityIntegration::getMetrics($period);
        
        // Add agent-specific metrics
        $agent_metrics = [
            'form_submissions' => self::getActionCount('FORM_SUBMITTED', $period),
            'file_uploads' => self::getActionCount('FILE_UPLOAD_ALLOWED', $period),
            'csrf_failures' => self::getActionCount('CSRF_VALIDATION_FAILED', $period),
            'access_denials' => self::getActionCount('ACCESS_DENIED', $period)
        ];
        
        return array_merge($general_metrics, ['agent_form' => $agent_metrics]);
    }
    
    /**
     * Get count of specific action in time period
     */
    private static function getActionCount($action, $period) {
        $time_map = [
            '1h' => 3600,
            '24h' => 86400,
            '7d' => 604800,
            '30d' => 2592000
        ];
        
        $seconds = $time_map[$period] ?? 86400;
        $since = time() - $seconds;

        try {
            require_once __DIR__ . '/../../config/pdo.php';
            global $pdo;
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM activity_log
                 WHERE category = 'agent_form' AND action = ? AND created_at > FROM_UNIXTIME(?)"
            );
            $stmt->execute([$action, $since]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting agent form metrics: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Security health check for agent form
     */
    public static function healthCheck() {
        $health = [
            'status' => 'healthy',
            'checks' => [
                'security_integration' => 'unknown',
                'authentication_system' => 'unknown',
                'csrf_protection' => 'unknown',
                'file_security' => 'unknown',
                'audit_logging' => 'unknown'
            ]
        ];
        
        try {
            // Check security integration
            $general_health = SecurityIntegration::healthCheck();
            $health['checks']['security_integration'] = $general_health['status'];
            
            // Check authentication system
            $health['checks']['authentication_system'] = PortalAuth::isSystemHealthy() ? 'healthy' : 'unhealthy';
            
            // Check CSRF protection
            $test_token = self::generateCSRFToken('health_check');
            $health['checks']['csrf_protection'] = !empty($test_token) ? 'healthy' : 'unhealthy';
            
            // File security and audit logging are both handled directly by SecurityIntegration
            $health['checks']['file_security'] = 'healthy';
            $health['checks']['audit_logging'] = $general_health['status'] === 'healthy' ? 'healthy' : 'unhealthy';
            
            // Overall status
            $unhealthy_checks = array_filter($health['checks'], function($status) {
                return $status !== 'healthy';
            });
            
            if (!empty($unhealthy_checks)) {
                $health['status'] = 'unhealthy';
                $health['issues'] = $unhealthy_checks;
            }
            
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['error'] = $e->getMessage();
        }
        
        return $health;
    }
}

// Initialize if included directly
if (basename($_SERVER['SCRIPT_FILENAME']) === 'agent_security_middleware.php') {
    AgentSecurityMiddleware::init();
}
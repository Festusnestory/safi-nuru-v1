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

// Include security middleware first
require_once 'includes/agent_security_middleware.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'includes/form_handler.php';

// Start session 
session_start();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get action from request
$action = $_POST['action'] ?? '';

// Process through security middleware
if (!AgentSecurityMiddleware::processRequest($action, 'POST')) {
    // Request was blocked by security middleware
    exit;
}

try {
    $formHandler = new AgentFormHandler();
    
    // Enhanced action handling with security logging
    switch ($action) {
        case 'init':
            AgentSecurityMiddleware::logActivity('FORM_INIT', 'Agent form initialized');
            handleInit($formHandler);
            break;
            
        case 'save_step':
            AgentSecurityMiddleware::logActivity('STEP_SAVE', 'Agent form step data saved', ['step' => $_POST['step'] ?? 'unknown']);
            handleSaveStep($formHandler);
            break;
            
        case 'update_step':
            AgentSecurityMiddleware::logActivity('STEP_UPDATE', 'Agent form step updated', ['step' => $_POST['step'] ?? 'unknown']);
            handleUpdateStep($formHandler);
            break;
            
        case 'get_region':
            handleGetRegion($formHandler);
            break;
            
        case 'upload_file':
            AgentSecurityMiddleware::logActivity('FILE_UPLOAD', 'File upload attempted', ['file_type' => $_POST['file_type'] ?? 'unknown']);
            handleFileUpload($formHandler);
            break;
            
        case 'submit_form':
            AgentSecurityMiddleware::logActivity('FORM_SUBMIT', 'Agent form submission attempted');
            handleFormSubmission($formHandler);
            break;
            
        default:
            AgentSecurityMiddleware::logActivity('INVALID_ACTION', 'Invalid action attempted', ['action' => $action]);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    // Enhanced error logging with security context
    AgentSecurityMiddleware::logActivity('ERROR', 'Agent form error occurred', [
        'error_message' => $e->getMessage(),
        'action' => $action,
        'stack_trace' => $e->getTraceAsString()
    ]);
    error_log("Agent form AJAX error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

function handleInit($formHandler) {
    echo json_encode([
        'success' => true,
        'session_id' => $formHandler->getSessionId(),
        'current_step' => $formHandler->getCurrentStep(),
        'csrf_token' => AgentSecurityMiddleware::generateCSRFToken('save_step'),
        'towns' => $formHandler->getNamibianTowns(),
        'countries' => $formHandler->getCountries(),
        'form_data' => $formHandler->getFormData(),
        'uploaded_files' => $formHandler->getUploadedFiles(),
        'security' => [
            'csrf_tokens' => [
                'save_step' => AgentSecurityMiddleware::generateCSRFToken('save_step'),
                'update_step' => AgentSecurityMiddleware::generateCSRFToken('update_step'),
                'upload_file' => AgentSecurityMiddleware::generateCSRFToken('upload_file'),
                'submit_form' => AgentSecurityMiddleware::generateCSRFToken('submit_form')
            ],
            'file_security_enabled' => true,
            'rate_limiting_enabled' => true
        ]
    ]);
}

function handleSaveStep($formHandler) {
    $step = (int)($_POST['step'] ?? 0);
    if ($step < 1 || $step > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid step number']);
        return;
    }
    
    // Extract step data from POST
    $stepData = [];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['action', 'step', 'csrf_token'])) {
            $stepData[$key] = AgentSecurityMiddleware::sanitizeInput($value);
        }
    }
    
    // Validate step data
    $errors = $formHandler->validateStepData($stepData, $step);
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors found',
            'errors' => $errors
        ]);
        return;
    }
    
    // Auto-fill region for residential address
    if ($step == 2 && !empty($stepData['town'])) {
        $stepData['region'] = $formHandler->getRegionByTown($stepData['town']);
    }
    
    // Auto-fill region for next of kin address
    if ($step == 3 && !empty($stepData['kin_town'])) {
        $stepData['kin_region'] = $formHandler->getRegionByTown($stepData['kin_town']);
    }
    
    // Auto-fill region for employment address
    if ($step == 4 && !empty($stepData['emp_town'])) {
        $stepData['emp_region'] = $formHandler->getRegionByTown($stepData['emp_town']);
    }
    
    // Save step data
    if ($formHandler->saveFormData($stepData, $step)) {
        echo json_encode(['success' => true, 'message' => 'Step data saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save step data']);
    }
}

function handleUpdateStep($formHandler) {
    $step = (int)($_POST['step'] ?? 0);
    if ($step < 1 || $step > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid step number']);
        return;
    }
    
    if ($formHandler->updateStep($step)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update step']);
    }
}

function handleGetRegion($formHandler) {
    $town = AgentSecurityMiddleware::sanitizeInput($_POST['town'] ?? '');
    if (empty($town)) {
        echo json_encode(['success' => false, 'message' => 'Town name is required']);
        return;
    }
    
    $region = $formHandler->getRegionByTown($town);
    echo json_encode([
        'success' => true,
        'region' => $region
    ]);
}

function handleFileUpload($formHandler) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        return;
    }
    
    $fileType = AgentSecurityMiddleware::sanitizeInput($_POST['file_type'] ?? '', 'alphanumeric');
    if (empty($fileType)) {
        echo json_encode(['success' => false, 'message' => 'File type is required']);
        return;
    }
    
    $file = $_FILES['file'];
    
    // Use integrated security middleware for file validation
    $validation_result = AgentSecurityMiddleware::validateFileUpload($file);
    
    if (!$validation_result['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'File validation failed',
            'errors' => $validation_result['errors'],
            'warnings' => $validation_result['warnings'],
            'quarantined' => $validation_result['quarantined'] ?? false
        ]);
        return;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/agents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }
    
    // Use the safe filename from security validation
    $safeFilename = $validation_result['safe_filename'];
    $filePath = $uploadDir . $safeFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Save file info to database
        $fileInfo = [
            'file_type' => $fileType,
            'original_filename' => $file['name'],
            'stored_filename' => $safeFilename,
            'file_path' => 'uploads/agents/' . $safeFilename,
            'file_size' => $file['size'],
            'mime_type' => $file['type']
        ];
        
        if ($formHandler->saveUploadedFile($fileInfo)) {
            // Log successful file upload
            AgentSecurityMiddleware::logActivity('FILE_UPLOAD_SUCCESS', 'File uploaded successfully', [
                'file_type' => $fileType,
                'original_filename' => $file['name'],
                'stored_filename' => $safeFilename,
                'file_size' => $file['size']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully',
                'original_name' => $file['name'],
                'stored_name' => $safeFilename,
                'warnings' => $validation_result['warnings'] ?? []
            ]);
        } else {
            // Clean up file if database save failed
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Failed to save file information']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    }
}

function handleFormSubmission($formHandler) {
    // Validate all steps are complete
    $formData = $formHandler->getFormData();
    $requiredSteps = [1, 2, 3, 4];
    
    foreach ($requiredSteps as $step) {
        if (!isset($formData["step_$step"])) {
            echo json_encode(['success' => false, 'message' => "Step $step is not complete"]);
            return;
        }
        
        $errors = $formHandler->validateStepData($formData["step_$step"], $step);
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => "Step $step has validation errors",
                'errors' => $errors
            ]);
            return;
        }
    }
    
    // Check if all required files are uploaded
    $uploadedFiles = $formHandler->getUploadedFiles();
    $requiredFiles = ['id_document', 'proof_residence', 'agency_ffc', 'agent_neab', 'agent_ffc'];
    $uploadedFileTypes = array_column($uploadedFiles, 'file_type');
    
    foreach ($requiredFiles as $requiredFile) {
        if (!in_array($requiredFile, $uploadedFileTypes)) {
            echo json_encode(['success' => false, 'message' => "Required file missing: $requiredFile"]);
            return;
        }
    }
    
    // Submit the application
    $result = $formHandler->submitApplication();
    
    if ($result['success']) {
        // Log successful form submission
        AgentSecurityMiddleware::logActivity('FORM_SUBMISSION_SUCCESS', 'Agent form submitted successfully', [
            'application_number' => $result['application_number'] ?? null,
            'files_count' => count($uploadedFiles)
        ]);
    } else {
        // Log failed form submission
        AgentSecurityMiddleware::logActivity('FORM_SUBMISSION_FAILED', 'Agent form submission failed', [
            'error' => $result['message'] ?? 'Unknown error'
        ]);
    }
    
    echo json_encode($result);
}

/**
 * Sanitize input data using security middleware
 */
function sanitizeInput($data) {
    return AgentSecurityMiddleware::sanitizeInput($data);
}

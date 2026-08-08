<?php
/**
 * Buyer Form Access Page
 * Provides access to the buyer application form
 */

// Start session
session_start();

// Define portal access
if (!defined('PORTAL_ACCESS')) { define('PORTAL_ACCESS', true); }

// Include required files
require_once dirname(__DIR__) . '/config/portal_config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Require authentication
PortalAuth::requireAuth();

// Page configuration
$page_title = 'Buyer Application Form';
$page_description = 'Access and test the buyer application form';

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>Buyer Application Form</h5>
            </div>
            <div class="card-body">
                <p class="lead">Access the buyer application form to test functionality or assist potential buyers.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Information:</strong> This form allows potential property buyers to submit their applications and required documents.
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="fas fa-file-alt fa-2x text-primary mb-3"></i>
                                <h6>PHP Version</h6>
                                <p class="text-muted small mb-3">Backend-integrated form with PHP processing</p>
                                <a href="/portal/buyer/" class="btn btn-primary w-100">
                                    <i class="fas fa-external-link-alt me-2"></i>Open PHP Form
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="fas fa-code fa-2x text-success mb-3"></i>
                                <h6>React Version</h6>
                                <p class="text-muted small mb-3">Modern React application (for reference)</p>
                                <a href="/buyer/" class="btn btn-success w-100" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Open React Form
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">Form Features</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Multi-step Process</strong><br>
                            <small class="text-muted">Guided step-by-step application process</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Document Upload</strong><br>
                            <small class="text-muted">Secure file upload for required documents</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Form Validation</strong><br>
                            <small class="text-muted">Real-time validation and error handling</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Progress Tracking</strong><br>
                            <small class="text-muted">Visual progress indicator</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Responsive Design</strong><br>
                            <small class="text-muted">Works on all devices and screen sizes</small>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/portal/applications/?type=buyer" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-2"></i>View Buyer Applications
                    </a>
                    <a href="/portal/users/" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="/portal/reports/" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-chart-bar me-2"></i>View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include dirname(__DIR__) . '/includes/footer.php';
?>
<?php
/**
 * Seller Form Access Page
 * Provides access to the seller application form
 */

// Start session
session_start();

// Define portal access
if (!defined('PORTAL_ACCESS')) { define('PORTAL_ACCESS', true); }

// Include required files
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Require authentication
PortalAuth::requireAuth();

// Page configuration
$page_title = 'Seller Application Form';
$page_description = 'Access and test the seller application form';

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-home me-2 text-primary"></i>Seller Application Form</h5>
            </div>
            <div class="card-body">
                <p class="lead">Access the seller application form to test functionality or assist property sellers.</p>

                <div class="alert alert-success">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Information:</strong> This form allows property owners to submit their properties for listing and sale.
                </div>

                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="fas fa-file-alt fa-2x text-primary mb-3"></i>
                                <h6>Seller Application Form</h6>
                                <p class="text-muted small mb-3">Backend-integrated form with PHP processing</p>
                                <a href="index.php" class="btn btn-primary w-100">
                                    <i class="fas fa-external-link-alt me-2"></i>Open Form
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
                            <strong>Property Details</strong><br>
                            <small class="text-muted">Comprehensive property information collection</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Owner Information</strong><br>
                            <small class="text-muted">Complete owner and contact details</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Document Upload</strong><br>
                            <small class="text-muted">Secure upload for property documents</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Property Images</strong><br>
                            <small class="text-muted">Multiple image upload support</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-start">
                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                        <div>
                            <strong>Smart Validation</strong><br>
                            <small class="text-muted">Namibian address and format validation</small>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
include dirname(__DIR__) . '/includes/footer.php';
?>

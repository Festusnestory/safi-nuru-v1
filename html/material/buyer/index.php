<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../app/autoload.php';
require_once __DIR__ . '/../api/security_integration.php';
require_once __DIR__ . '/../config/turnstile.php';
$csrfToken = SecurityIntegration::generateCSRFToken('buyer_application_submit');
$baseUrl = \App\Core\Router::basePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Application Form - Nuru Real Estate</title>

    <!-- Bootstrap CSS -->
    <link href="<?= $baseUrl ?>/assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/html/material/buyer/css/buyer-form.css?v=<?php echo (int)filemtime(__DIR__ . '/css/buyer-form.css'); ?>" rel="stylesheet">

    <!-- CSRF Token Meta -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">

    <!-- Cloudflare Turnstile -->
    <?php if (TURNSTILE_READY): ?>
    <script>
        window.onBuyerTurnstileLoad = function () {
            window.dispatchEvent(new Event('buyer-turnstile-ready'));
        };
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onBuyerTurnstileLoad&render=explicit" async defer></script>
    <?php endif; ?>
    <script>
        const TurnstileConfig = {
            siteKey: "<?php echo htmlspecialchars(TURNSTILE_SITE_KEY); ?>",
            enabled: <?php echo TURNSTILE_READY ? 'true' : 'false'; ?>,
            required: <?php echo TURNSTILE_ENABLED ? 'true' : 'false'; ?>,
            configured: <?php echo TURNSTILE_CONFIGURED ? 'true' : 'false'; ?>
        };
        window.NURU_API_BASE = <?= json_encode($baseUrl . '/html/material/api') ?>;
    </script>
</head>
<body class="bg-white buyer-form-body">
    <!-- Header -->
    <header class="bg-white py-3 mb-3 buyer-form-header">
        <div class="container">
            <a class="buyer-back-link" href="<?= $baseUrl ?>/login" data-buyer-exit>
                <i class="fas fa-arrow-left me-1"></i>
                Back to login
            </a>
            <div class="mt-2">
                <h1 class="h4 mb-0 text-primary">Buyer Application</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container pb-5">
        <!-- Progress Indicator -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-body py-3">
                        <div class="progress-container">
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar"
                                     style="width: 12.5%;" id="progressBar"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span class="step-label active" data-step="1">Personal</span>
                                <span class="step-label" data-step="2">Marital</span>
                                <span class="step-label" data-step="3">Address</span>
                                <span class="step-label" data-step="4">Next of Kin</span>
                                <span class="step-label" data-step="5">Employment</span>
                                <span class="step-label" data-step="6">Property</span>
                                <span class="step-label" data-step="7">Documents</span>
                                <span class="step-label" data-step="8">Declaration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <!-- Form -->
                        <form id="buyerApplicationForm" novalidate>
                            <!-- Step 1: Personal Details -->
                            <div class="form-step active" id="step-1">
                                <h4 class="mb-4 text-primary">
                                    <i class="fas fa-user me-2"></i>
                                    Personal Details
                                </h4>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="firstName" class="form-label required">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" maxlength="100" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lastName" class="form-label required">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" maxlength="100" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="idType" class="form-label required">ID/Passport Type</label>
                                        <select class="form-select" id="idType" name="idType" required>
                                            <option value="">Select ID Type</option>
                                            <option value="national_id">National ID</option>
                                            <option value="passport">Passport</option>
                                            <option value="drivers_license">Driver's License</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="idNumber" class="form-label required">ID/Passport Number</label>
                                        <input type="text" class="form-control" id="idNumber" name="idNumber" maxlength="13" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="dateOfBirth" class="form-label required">Date of Birth</label>
                                        <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nationality" class="form-label required">Nationality</label>
                                        <select class="form-select" id="nationality" name="nationality" required>
                                            <option value="">Select Nationality</option>
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label required">Gender</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Marital Status -->
                            <div class="form-step" id="step-2">
                                <h4 class="mb-4 text-primary">
                                    <i class="fas fa-heart me-2"></i>
                                    Marital Status
                                </h4>

                                <div class="mb-3">
                                    <label for="maritalStatus" class="form-label required">Marital Status</label>
                                    <select class="form-select" id="maritalStatus" name="maritalStatus" required>
                                        <option value="">Select Status</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="divorced">Divorced</option>
                                        <option value="widowed">Widowed</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Spouse Details (shown when married) -->
                                <div id="spouseDetails" class="d-none">
                                    <h5 class="mb-3 text-secondary">Spouse Details</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseFullName" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="spouseFullName" name="spouseFullName">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseIdPassport" class="form-label">ID/Passport Number</label>
                                            <input type="text" class="form-control" id="spouseIdPassport" name="spouseIdPassport">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseDateOfBirth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="spouseDateOfBirth" name="spouseDateOfBirth">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseContactNumber" class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="spouseContactNumber" name="spouseContactNumber">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseEmail" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="spouseEmail" name="spouseEmail">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional steps will be added via JavaScript for brevity -->
                            <!-- The form structure continues with all 8 steps -->

                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
                                    <i class="fas fa-arrow-left me-2"></i>Previous
                                </button>
                                <div>
                                    <span class="text-muted me-3">Step <span id="currentStepNumber">1</span> of 8</span>
                                    <button type="button" class="btn btn-primary" id="nextBtn">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="buyerSuccessModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-success" id="buyerSuccessModalTitle">
                        <i class="fas fa-check-circle me-2"></i>
                        Application Submitted Successfully
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-3">Your buyer application has been submitted successfully!</p>
                    <div class="alert alert-info">
                        <strong>Application Number: </strong>
                        <span id="applicationNumber"></span>
                    </div>
                    <dl class="row text-start small">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7" id="applicationStatus">Pending review</dd>
                        <dt class="col-5">Submitted</dt>
                        <dd class="col-7" id="applicationSubmittedAt"></dd>
                    </dl>
                    <p class="text-muted small">
                        Please save this application number for your records.
                        You will receive a confirmation email shortly.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <a class="btn btn-primary" href="<?= $baseUrl ?>/login" id="buyerReturnToPortal">
                        Return to Portal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Explicit draft exit recovery for in-app links. Browser refresh/back
         still receive the standard native unsaved-changes warning. -->
    <div class="modal fade" id="buyerExitModal" tabindex="-1" aria-labelledby="buyerExitModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buyerExitModalTitle">Leave this application?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Stay on application"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Your draft is saved in this browser tab. You can keep it for later or discard it before returning to sign in.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Stay</button>
                    <button type="button" class="btn btn-outline-danger" id="buyerDiscardAndLeave">Discard and leave</button>
                    <button type="button" class="btn btn-primary" id="buyerKeepAndLeave">Keep draft and leave</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/html/material/buyer/js/form-data.js?v=<?php echo (int)filemtime(__DIR__ . '/js/form-data.js'); ?>"></script>
    <script src="<?= $baseUrl ?>/html/material/buyer/js/form-steps.js?v=<?php echo (int)filemtime(__DIR__ . '/js/form-steps.js'); ?>"></script>
    <script src="<?= $baseUrl ?>/html/material/buyer/js/form-validation.js?v=<?php echo (int)filemtime(__DIR__ . '/js/form-validation.js'); ?>"></script>
    <script src="<?= $baseUrl ?>/html/material/buyer/js/buyer-form.js?v=<?php echo (int)filemtime(__DIR__ . '/js/buyer-form.js'); ?>"></script>
</body>
</html>

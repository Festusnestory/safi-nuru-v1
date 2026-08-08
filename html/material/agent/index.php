<?php
/**
 * Public Nuru agent application form.
 */

require_once __DIR__ . '/../api/security_integration.php';
require_once __DIR__ . '/../config/turnstile.php';
SecurityIntegration::init();
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
$csrfToken = SecurityIntegration::generateCSRFToken('agent_application_submit');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Application - Nuru Real Estate</title>
    <?php if (TURNSTILE_READY): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    
    <!-- Bootstrap CSS -->
    <link href="../../../assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" integrity="sha384-QuGBSgV5Im3DzL2z+8Ko9/hqNy/N0O7zwvXAtfd1MvPKWa/UbeLV65cfm4BV5Wgq" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Agent Form CSS -->
    <link rel="stylesheet" href="css/agent-form.css?v=<?= filemtime(__DIR__ . '/css/agent-form.css') ?>">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../authentication-login.php">Nuru Real Estate</a>
            <a class="btn btn-outline-light" href="../authentication-login.php">Back to sign in</a>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Portal Navigation Breadcrumb -->
            <div class="col-12">
                <nav aria-label="breadcrumb" class="bg-light py-2">
                    <div class="container">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../authentication-login.php">Sign in</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Agent Application</li>
                        </ol>
                    </div>
                </nav>
            </div>
            
            <!-- Header -->
            <div class="col-12">
                <header class="app-header">
                    <div class="container">
                        <h1 class="app-title">
                            <i class="bi bi-person-badge"></i>
                            Agent Application
                        </h1>
                        <p class="app-subtitle">Apply to join the Nuru agent network</p>
                    </div>
                </header>
            </div>
        </div>
        
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <!-- Progress Indicator -->
                    <div class="progress-container mb-4">
                        <div class="mobile-progress-summary" aria-live="polite">
                            <span>Step <strong id="mobileAgentStep">1</strong> of 5</span>
                            <span id="mobileAgentStepLabel">Personal Details</span>
                            <div class="progress mt-2" aria-label="Application progress">
                                <div class="progress-bar" id="mobileAgentProgress" style="width: 20%"></div>
                            </div>
                        </div>
                        <div class="step-progress">
                            <div class="step-item active" data-step="1">
                                <div class="step-circle">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="step-label">Personal Details</div>
                            </div>
                            <div class="step-item" data-step="2">
                                <div class="step-circle">
                                    <i class="bi bi-house-fill"></i>
                                </div>
                                <div class="step-label">Residential Address</div>
                            </div>
                            <div class="step-item" data-step="3">
                                <div class="step-circle">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="step-label">Next of Kin</div>
                            </div>
                            <div class="step-item" data-step="4">
                                <div class="step-circle">
                                    <i class="bi bi-briefcase-fill"></i>
                                </div>
                                <div class="step-label">Employment Details</div>
                            </div>
                            <div class="step-item" data-step="5">
                                <div class="step-circle">
                                    <i class="bi bi-cloud-upload-fill"></i>
                                </div>
                                <div class="step-label">Upload Documents</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Card -->
                    <div class="form-card">
                        <form id="agentForm" enctype="multipart/form-data">
                            <input type="hidden" id="csrfToken" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="form_type" value="agent">
                            
                            <!-- Security Information -->
                            <div class="security-info d-none">
                                <input type="hidden" id="securityEnabled" value="true">
                                <input type="hidden" id="rateLimitEnabled" value="true">
                                <input type="hidden" id="fileSecurityEnabled" value="true">
                            </div>
                            
                            <!-- Step 1: Personal Details -->
                            <div class="form-step" id="step-1">
                                <div class="step-header">
                                    <h3><i class="bi bi-person-fill"></i> Personal Details</h3>
                                    <p class="text-muted">Please provide your personal information</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="surname" class="form-label required">Surname</label>
                                        <input type="text" class="form-control" id="surname" name="surname" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label required">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="maiden_name" class="form-label">Maiden Name</label>
                                        <input type="text" class="form-control" id="maiden_name" name="maiden_name">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="date_of_birth" class="form-label required">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="id_type" class="form-label required">Type of ID</label>
                                        <select class="form-select" id="id_type" name="id_type" required>
                                            <option value="">Select ID Type</option>
                                            <option value="National ID">National ID</option>
                                            <option value="Passport">Passport</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="id_number" class="form-label required">ID Number</label>
                                        <input type="text" class="form-control" id="id_number" name="id_number" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nationality" class="form-label required">Nationality</label>
                                        <select class="form-select" id="nationality" name="nationality" required>
                                            <option value="">Select Nationality</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Gender</label>
                                        <div class="form-check-container">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="Male" required>
                                                <label class="form-check-label" for="gender_male">Male</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="Female" required>
                                                <label class="form-check-label" for="gender_female">Female</label>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 2: Residential Address -->
                            <div class="form-step d-none" id="step-2">
                                <div class="step-header">
                                    <h3><i class="bi bi-house-fill"></i> Residential Address</h3>
                                    <p class="text-muted">Please provide your residential address details</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="res_erf_no" class="form-label">Erf No.</label>
                                        <input type="text" class="form-control" id="res_erf_no" name="erf_no">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="res_street_name" class="form-label">Street Name</label>
                                        <input type="text" class="form-control" id="res_street_name" name="street_name">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="res_suburb" class="form-label">Suburb</label>
                                        <input type="text" class="form-control" id="res_suburb" name="suburb">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="res_location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="res_location" name="location">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="res_town" class="form-label required">Town</label>
                                        <select class="form-select" id="res_town" name="town" required>
                                            <option value="">Select Town</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="res_region" class="form-label">Region</label>
                                        <input type="text" class="form-control" id="res_region" name="region" readonly>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="res_email" class="form-label required">Email Address</label>
                                        <input type="email" class="form-control" id="res_email" name="email" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="res_mobile" class="form-label required">Mobile Number</label>
                                        <input type="tel" class="form-control" id="res_mobile" name="mobile_number" required>
                                        <div class="form-text">Include country code if applicable</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="res_po_box" class="form-label">P.O.Box/Private Bag</label>
                                        <input type="text" class="form-control" id="res_po_box" name="po_box">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 3: Next of Kin -->
                            <div class="form-step d-none" id="step-3">
                                <div class="step-header">
                                    <h3><i class="bi bi-people-fill"></i> Next of Kin</h3>
                                    <p class="text-muted">Please provide your next of kin information</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_surname" class="form-label required">Surname</label>
                                        <input type="text" class="form-control" id="kin_surname" name="kin_surname" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_first_name" class="form-label required">First Name</label>
                                        <input type="text" class="form-control" id="kin_first_name" name="kin_first_name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_contact" class="form-label required">Contact Number</label>
                                        <input type="tel" class="form-control" id="kin_contact" name="kin_contact_number" required>
                                        <div class="form-text">Include country code if applicable</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="kin_email" name="kin_email">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_erf_no" class="form-label">Erf No.</label>
                                        <input type="text" class="form-control" id="kin_erf_no" name="kin_erf_no">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_street_name" class="form-label">Street Name</label>
                                        <input type="text" class="form-control" id="kin_street_name" name="kin_street_name">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_suburb" class="form-label">Suburb</label>
                                        <input type="text" class="form-control" id="kin_suburb" name="kin_suburb">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="kin_location" name="kin_location">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_town" class="form-label">Town</label>
                                        <select class="form-select" id="kin_town" name="kin_town">
                                            <option value="">Select Town</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kin_region" class="form-label">Region</label>
                                        <input type="text" class="form-control" id="kin_region" name="kin_region" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 4: Employment Details -->
                            <div class="form-step d-none" id="step-4">
                                <div class="step-header">
                                    <h3><i class="bi bi-briefcase-fill"></i> Employment Details</h3>
                                    <p class="text-muted">Please provide your employment information</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_name" class="form-label required">Company Name</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="job_title" class="form-label required">Job Title</label>
                                        <input type="text" class="form-control" id="job_title" name="job_title" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="employment_number" class="form-label">Employment Number</label>
                                        <input type="text" class="form-control" id="employment_number" name="employment_number">
                                    </div>
                                </div>
                                
                                <h5 class="mt-4 mb-3">Monthly Income</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="gross_income" class="form-label required">Gross Income</label>
                                        <div class="input-group">
                                            <span class="input-group-text">N$</span>
                                            <input type="text" class="form-control currency-input" id="gross_income" name="gross_income" required>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="total_deductions" class="form-label required">Total Deductions</label>
                                        <div class="input-group">
                                            <span class="input-group-text">N$</span>
                                            <input type="text" class="form-control currency-input" id="total_deductions" name="total_deductions" required>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="net_pay" class="form-label required">Net Pay</label>
                                        <div class="input-group">
                                            <span class="input-group-text">N$</span>
                                            <input type="text" class="form-control currency-input" id="net_pay" name="net_pay" readonly required aria-describedby="net-pay-help">
                                        </div>
                                        <div class="form-text" id="net-pay-help">Calculated automatically as gross income minus total deductions.</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <h5 class="mt-4 mb-3">Employment Address</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="emp_email" name="emp_email">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_erf_no" class="form-label">Erf No.</label>
                                        <input type="text" class="form-control" id="emp_erf_no" name="emp_erf_no">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_street_name" class="form-label">Street Name</label>
                                        <input type="text" class="form-control" id="emp_street_name" name="emp_street_name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_suburb" class="form-label">Suburb</label>
                                        <input type="text" class="form-control" id="emp_suburb" name="emp_suburb">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="emp_location" name="emp_location">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_town" class="form-label">Town</label>
                                        <select class="form-select" id="emp_town" name="emp_town">
                                            <option value="">Select Town</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="emp_region" class="form-label">Region</label>
                                        <input type="text" class="form-control" id="emp_region" name="emp_region" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 5: Upload Documents -->
                            <div class="form-step d-none" id="step-5">
                                <div class="step-header">
                                    <h3><i class="bi bi-cloud-upload-fill"></i> Upload Supporting Documents</h3>
                                    <p class="text-muted">Please upload all required documents</p>
                                </div>
                                
                                <div class="upload-section">
                                    <div class="upload-item mb-4">
                                        <label class="form-label required" for="id_document">ID Document</label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" class="file-input" id="id_document" name="id_document" data-type="id_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                            <label for="id_document" class="file-upload-label">
                                                <i class="bi bi-cloud-upload"></i>
                                                <span class="upload-text">Choose ID document or drag and drop</span>
                                            </label>
                                            <div class="file-info d-none">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span class="filename"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" aria-label="Remove ID document">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="upload-progress d-none">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="upload-item mb-4">
                                        <label class="form-label required" for="proof_residence">Proof of Residence</label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" class="file-input" id="proof_residence" name="proof_residence" data-type="proof_residence" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                            <label for="proof_residence" class="file-upload-label">
                                                <i class="bi bi-cloud-upload"></i>
                                                <span class="upload-text">Choose proof of residence or drag and drop</span>
                                            </label>
                                            <div class="file-info d-none">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span class="filename"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" aria-label="Remove proof of residence">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="upload-progress d-none">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="upload-item mb-4">
                                        <label class="form-label required" for="agency_ffc">Agency's FFC</label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" class="file-input" id="agency_ffc" name="agency_ffc" data-type="agency_ffc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                            <label for="agency_ffc" class="file-upload-label">
                                                <i class="bi bi-cloud-upload"></i>
                                                <span class="upload-text">Choose agency FFC or drag and drop</span>
                                            </label>
                                            <div class="file-info d-none">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span class="filename"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" aria-label="Remove agency FFC">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="upload-progress d-none">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="upload-item mb-4">
                                        <label class="form-label required" for="agent_neab">Agent's NEAB Card</label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" class="file-input" id="agent_neab" name="agent_neab" data-type="agent_neab" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                            <label for="agent_neab" class="file-upload-label">
                                                <i class="bi bi-cloud-upload"></i>
                                                <span class="upload-text">Choose agent NEAB card or drag and drop</span>
                                            </label>
                                            <div class="file-info d-none">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span class="filename"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" aria-label="Remove agent NEAB card">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="upload-progress d-none">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="upload-item mb-4">
                                        <label class="form-label required" for="agent_ffc">Agent's FFC</label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" class="file-input" id="agent_ffc" name="agent_ffc" data-type="agent_ffc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                            <label for="agent_ffc" class="file-upload-label">
                                                <i class="bi bi-cloud-upload"></i>
                                                <span class="upload-text">Choose agent FFC or drag and drop</span>
                                            </label>
                                            <div class="file-info d-none">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span class="filename"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" aria-label="Remove agent FFC">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="upload-progress d-none">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (TURNSTILE_READY): ?>
                            <div class="cf-turnstile my-3" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>"></div>
                            <?php elseif (TURNSTILE_ENABLED): ?>
                            <div class="alert alert-warning my-3" role="alert">
                                Security verification is temporarily unavailable. Please try again later.
                            </div>
                            <?php endif; ?>

                            <!-- Navigation Buttons -->
                            <div class="form-navigation">
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" id="prevBtn" disabled>
                                        <i class="bi bi-arrow-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" id="nextBtn">
                                        Next <i class="bi bi-arrow-right"></i>
                                    </button>
                                    <button type="submit" class="btn btn-success d-none" id="submitBtn"<?= TURNSTILE_ENABLED && !TURNSTILE_CONFIGURED ? ' disabled aria-disabled="true"' : '' ?>>
                                        <i class="bi bi-check-circle"></i> Submit Application
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Processing...</p>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="bi bi-check-circle-fill"></i> Application Submitted
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h4>Thank you!</h4>
                    <p class="mb-0">Your agent application has been submitted for review.</p>
                    <p class="mt-3"><strong>Application ID:</strong> <span id="applicationId"></span></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success" onclick="window.location.href='../authentication-login.php'">Return to sign in</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Agent Form JS -->
    <script src="js/agent-form.js?v=<?= filemtime(__DIR__ . '/js/agent-form.js') ?>"></script>
</body>
</html>

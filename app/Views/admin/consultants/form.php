<?php
/** @var string $csrfToken */
/** @var string $baseUrl */
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="keywords"
      content="Nuru real estate administration, property management, buyers, sellers, agents, tasks, and reporting"
    />
    <meta
      name="description"
      content="Secure Nuru Real Estate operations portal"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Consulting Agent - Nuru Admin</title>
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/images/favicon.png" />
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?= $baseUrl ?>/assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    
    
    <style>
        /* Buyer Form Custom Styles */
        
        /* Page Wrapper */
        .page-wrapper {
            min-height: 100vh;
            background: #f5f6fa;
            padding: 2rem 0;
        }
        
        /* Form Steps */
        .form-step {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .form-step.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Step Header */
        .step-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .step-header h4 {
            color: #1e88e5;
            font-weight: 600;
        }
        
        /* Horizontal Stepper */
        .stepper-horizontal {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .stepper-item-horizontal {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 80px;
            position: relative;
            cursor: pointer;
        }
        
        .stepper-item-horizontal:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 3px;
            background: #dee2e6;
            z-index: 0;
        }
        
        .stepper-item-horizontal.completed:not(:last-child)::after {
            background: #1e88e5;
        }
        
        .step-counter {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6c757d;
            z-index: 1;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }
        
        .stepper-item-horizontal.active .step-counter {
            background: #1e88e5;
            color: #fff;
        }
        
        .stepper-item-horizontal.completed .step-counter {
            background: #1e88e5;
            color: #fff;
        }
        
        .step-name {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: center;
            font-weight: 500;
        }
        
        .stepper-item-horizontal.active .step-name {
            color: #1e88e5;
            font-weight: 600;
        }
        
        .stepper-item-horizontal.completed .step-name {
            color: #1e88e5;
        }
        
        /* Form Controls */
        .form-control:focus,
        .form-select:focus {
            border-color: #1e88e5;
            box-shadow: 0 0 0 0.2rem rgba(30, 136, 229, 0.15);
        }
        
        .form-control.is-valid:focus,
        .form-select.is-valid:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
        }
        
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }
        
        /* Labels */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.375rem;
        }
        
        .form-label.required::after {
            content: ' *';
            color: #dc3545;
        }
        
        /* Card Styles */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: #1e88e5;
            border-color: #1e88e5;
        }
        
        .btn-primary:hover {
            background-color: #1565c0;
            border-color: #1565c0;
        }
        
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        
        /* Document Upload Cards */
        .document-upload-card {
            border: 2px dashed #dee2e6;
            transition: all 0.2s ease;
            height: 100%;
        }
        
        .document-upload-card:hover {
            border-color: #1e88e5;
            background-color: rgba(30, 136, 229, 0.02);
        }
        
        .document-upload-card.has-file {
            border-color: #198754;
            border-style: solid;
            background-color: rgba(25, 135, 84, 0.03);
        }
        
        /* Upload Zones */
        .upload-zone {
            border: 3px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .upload-zone:hover {
            border-color: #1e88e5;
            background-color: rgba(30, 136, 229, 0.03);
        }
        
        .upload-zone.dragover {
            border-color: #198754;
            background-color: rgba(25, 135, 84, 0.03);
        }
        
        /* File Previews */
        .file-preview {
            position: relative;
            display: inline-block;
            margin: 0.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.075);
            background: #fff;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .file-item .file-info {
            display: flex;
            align-items: center;
        }
        
        .file-item .file-icon {
            margin-right: 0.75rem;
            color: #6c757d;
        }
        
        .file-item .remove-file {
            color: #dc3545;
            cursor: pointer;
        }
        
        /* Declaration Section */
        .declaration-item {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            background: #f8f9fa;
        }
        
        .declaration-item:hover {
            background: #e9ecef;
        }
        
        .declaration-item .form-check-input {
            transform: scale(1.2);
            margin-right: 0.75rem;
        }
        
        .declaration-item .form-check-label {
            cursor: pointer;
            line-height: 1.6;
        }
        
        /* Preferred Area Items */
        .preferred-area-item {
            position: relative;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .preferred-area-item .remove-area {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            color: #dc3545;
            cursor: pointer;
            font-size: 1rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .preferred-area-item .remove-area:hover {
            opacity: 1;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-overlay.d-none {
            display: none;
        }
        
        /* Signature Section */
        .signature-preview {
            max-width: 300px;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        /* Alert Styles */
        .validation-alert {
            margin-bottom: 1rem;
        }
        
        /* Shake Animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.3s ease-in-out;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .stepper-horizontal {
                padding: 1rem;
            }
            
            .step-name {
                font-size: 0.65rem;
            }
            
            .step-counter {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }
        }
        
        /* Spouse Details Section */
        .spouse-details-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-top: 1rem;
        }
        
        /* Employment Details Section */
        .employment-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-top: 1rem;
        }
        
        .employment-section.disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* OTP Input Styles */
        .otp-input {
            letter-spacing: 0.5rem;
            font-size: 1.25rem;
            text-align: center;
            font-family: monospace;
        }
        
        /* Next of Kin Address Section */
        .nok-address-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-top: 1rem;
        }
        
        /* Section Dividers */
        h5.section-divider {
            position: relative;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1rem;
        }
        
        /* File Upload Container */
        .file-upload-container {
            cursor: pointer;
        }
        
        .file-upload-container:hover .upload-zone {
            border-color: #1e88e5;
        }
        
        .file-list {
            margin-top: 1rem;
        }
    </style>
  </head>

  <body>
    <div class="preloader">
      <svg
        class="tea lds-ripple"
        width="37"
        height="48"
        viewbox="0 0 37 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M27.0819 17H3.02508C1.91076 17 1.01376 17.9059 1.0485 19.0197C1.15761 22.5177 1.49703 29.7374 2.5 34C4.07125 40.6778 7.18553 44.8868 8.44856 46.3845C8.79051 46.79 9.29799 47 9.82843 47H20.0218C20.639 47 21.2193 46.7159 21.5659 46.2052C22.6765 44.5687 25.2312 40.4282 27.5 34C28.9757 29.8188 29.084 22.4043 29.0441 18.9156C29.0319 17.8436 28.1539 17 27.0819 17Z"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          d="M29 23.5C29 23.5 34.5 20.5 35.5 25.4999C36.0986 28.4926 34.2033 31.5383 32 32.8713C29.4555 34.4108 28 34 28 34"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          id="teabag"
          fill="#1e88e5"
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M16 25V17H14V25H12C10.3431 25 9 26.3431 9 28V34C9 35.6569 10.3431 37 12 37H18C19.6569 37 21 35.6569 21 34V28C21 26.3431 19.6569 25 18 25H16ZM11 28C11 27.4477 11.4477 27 12 27H18C18.5523 27 19 27.4477 19 28V34C19 34.5523 18.5523 35 18 35H12C11.4477 35 11 34.5523 11 34V28Z"
        ></path>
        <path
          id="steamL"
          d="M17 1C17 1 17 4.5 14 6.5C11 8.5 11 12 11 12"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke="#1e88e5"
        ></path>
        <path
          id="steamR"
          d="M21 6C21 6 21 8.22727 19 9.5C17 10.7727 17 13 17 13"
          stroke="#1e88e5"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        ></path>
      </svg>
    </div>
    <!-- Main Wrapper -->
    <div id="main-wrapper">
      <!-- Topbar header -->
      <?php require NURU_MATERIAL . '/top-bar.php'; ?>

      <!-- Left Sidebar -->
      <?php
        if (\App\Core\Auth::isFullAccess()) {
            require NURU_MATERIAL . '/left-sidebar.php';
        } else {
            require NURU_MATERIAL . '/agent_nemu.php';
        }
      ?>

      <!-- Page wrapper -->
      <div class="page-wrapper">

        
        
        
        <div class="row page-titles">
          <div class="col-md-5 col-12 align-self-center">
            <h3 class="text-themecolor mb-0"></h3>
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active">Consulting Agent</li>
            </ol>
          </div>
        </div>
        
        
        <div class="container-fluid">
          <!-- Breadcrumb -->

          
          <!-- Form Content -->
                       <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-user-tie me-2"></i> Consulting Agent Form
                            </h4>
                        </div>
                        <div class="card-body">
                            <!-- Horizontal Stepper -->
                            <div class="stepper-horizontal" id="stepperHorizontal">
                                <div class="stepper-item-horizontal active" data-step="1">
                                    <div class="step-counter">1</div>
                                    <div class="step-name">Personal</div>
                                </div>
                                <div class="stepper-item-horizontal" data-step="2">
                                    <div class="step-counter">2</div>
                                    <div class="step-name">Property</div>
                                </div>
                            </div>
                            
                            <!-- Form -->
                            <form id="consultingAgentForm" novalidate action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/admin-consulting-agent-processor">
                                <!-- Step 1: Personal Details -->
                                <div class="form-step active" id="step-1">
                                    <h4 class="mb-4 text-primary">
                                        <i class="fas fa-user me-2"></i>
                                        Personal Details
                                    </h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="firstName" class="form-label required">First Name</label>
                                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="lastName" class="form-label required">Last Name</label>
                                            <input type="text" class="form-control" id="lastName" name="lastName" required>
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
                                            <input type="text" class="form-control" id="idNumber" name="idNumber" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="dateOfBirth" class="form-label required">Date of Birth</label>
                                            <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="nationality" class="form-label required">Nationality</label>
                                            <select class="form-select" id="nationality" name="nationality" required>
                                                <option value="">Select Nationality</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="gender" class="form-label required">Gender</label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contactNumber" class="form-label required">Contact Number</label>
                                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" placeholder="e.g., 0811234567" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label required">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: Property Purchase Details (originally step 6) -->
                                <div class="form-step" id="step-2">
                                    <h4 class="mb-4 text-primary">
                                        <i class="fas fa-building me-2"></i>
                                        Property Purchase Details
                                    </h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="propertyType" class="form-label required">Property Type</label>
                                            <select class="form-select" id="propertyType" name="propertyType" required>
                                                <option value="">Select Property Type</option>
                                                <option value="house">House</option>
                                                <option value="apartment">Apartment</option>
                                                <option value="townhouse">Townhouse</option>
                                                <option value="plot">Plot/Land</option>
                                                <option value="commercial">Commercial</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="priceType" class="form-label required">Price Type</label>
                                            <select class="form-select" id="priceType" name="priceType" required>
                                                <option value="">Select Price Type</option>
                                                <option value="fixed">Fixed Price</option>
                                                <option value="negotiable">Negotiable</option>
                                                <option value="auction">Auction</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="propertyValue" class="form-label required">Property Value (NAD)</label>
                                            <input type="number" class="form-control" id="propertyValue" name="propertyValue" min="0" step="0.01" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="downPayment" class="form-label ">Down Payment (NAD)</label>
                                            <input type="number" class="form-control" id="downPayment" name="downPayment" min="0" step="0.01" >
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="loanAmount" class="form-label">Loan Amount (NAD)</label>
                                            <input type="number" class="form-control" id="loanAmount" name="loanAmount" min="0" step="0.01" readonly>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5 class="mb-3 text-secondary">Preferred Areas</h5>
                                        <div id="preferredAreas">
                                            <div class="preferred-area-item">
                                                <div class="row">
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Region</label>
                                                        <select class="form-select preferred-region" name="preferredRegion[]">
                                                            <option value="">Select Region</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Town</label>
                                                        <select class="form-select preferred-town" name="preferredTown[]">
                                                            <option value="">Select Town</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Location</label>
                                                        <input type="text" class="form-control" name="preferredLocation[]">
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Suburb</label>
                                                        <input type="text" class="form-control" name="preferredSuburb[]">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="addPreferredArea">
                                            <i class="fas fa-plus me-2"></i>Add Another Area
                                        </button>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="additionalRequirements" class="form-label">Additional Requirements</label>
                                        <textarea class="form-control" id="additionalRequirements" name="additionalRequirements" rows="4" 
                                                  placeholder="Please describe any specific requirements or preferences for your property..."></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <!-- Navigation Buttons -->
                                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </button>
                                    <div>
                                        <span class="text-muted me-3">Step <span id="currentStepNumber">1</span> of 2</span>
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
        </div>
      </div>
      
      <!-- Footer -->
      <footer class="footer text-center">
        &copy; <?php echo date('Y'); ?> Nuru Real Estate. All Rights Reserved.
      </footer>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
      <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Processing your application...</p>
      </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0 bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-check-circle me-2"></i>
              Application Submitted Successfully!
            </h5>
          </div>
          <div class="modal-body text-center p-4">
            <div class="mb-3">
              <i class="fas fa-user-tie fa-4x text-success mb-3"></i>
              <h4>Congratulations!</h4>
              <p class="lead">The buyer consultation has been logged successfully.</p>
            </div>
            
            <div class="alert alert-info">
              <strong>Application Number: </strong>
              <span id="applicationNumber" class="fw-bold"></span>
            </div>
            
            <div class="text-muted">
              <p class="mb-2">
                <i class="fas fa-info-circle me-1"></i>
                Please save this application number for your records.
              </p>
              <p class="mb-0">
                <i class="fas fa-envelope me-1"></i>
                It is now available in your consultation list.
              </p>
            </div>
          </div>
          <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-primary" onclick="window.location.href=nuruBaseUrl + '/admin/consultant-list'">
              <i class="fas fa-home me-2"></i>
              View My Consultations
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="<?= $baseUrl ?>/assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app.init.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app-style-switcher.js"></script>
    <script src="<?= $baseUrl ?>/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/extra-libs/sparkline/sparkline.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/waves.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/sidebarmenu.js?v=20260720"></script>
    <script src="<?= $baseUrl ?>/dist/js/feather.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/custom.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/nuru-regions.js"></script>
    
     <script>
    const nuruBaseUrl = <?= json_encode($baseUrl) ?>;
    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    
    // Date Utilities
    const DateUtils = {
        getCurrentDate() {
            const now = new Date();
            return now.toISOString().split('T')[0];
        },
        
        calculateAge(dateOfBirth) {
            const today = new Date();
            const birthDate = new Date(dateOfBirth);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age;
        },
        
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        }
    };
    
    // Form Data Configuration
    const FormDataConfig = {
        regions: [
            'Erongo',
            'Hardap',
            'Karas',
            'Kavango East',
            'Kavango West',
            'Khomas',
            'Kunene',
            'Ohangwena',
            'Omaheke',
            'Omusati',
            'Oshana',
            'Oshikoto',
            'Otjozondjupa',
            'Zambezi'
        ],

        // Canonical data, shared with every other form - see
        // assets/js/nuru-regions.js (loaded before this script block).
        townsByRegion: window.NURU_TOWNS_BY_REGION,
        
        nationalities: [
            'Namibian',
            'South African',
            'Botswanan',
            'Zimbabwean',
            'Zambian',
            'Angolan',
            'Other African',
            'European',
            'American',
            'Asian',
            'Other'
        ]
    };
    
    // Utility Functions
    function formatPhoneNumber(phone) {
        // Remove all non-digit characters
        let cleaned = phone.replace(/\D/g, '');
        
        // Handle Namibian phone numbers
        if (cleaned.startsWith('264')) {
            cleaned = cleaned.substring(3);
        }
        
        // Add leading 0 if needed
        if (!cleaned.startsWith('0') && cleaned.length > 0) {
            cleaned = '0' + cleaned;
        }
        
        // Format as xxx xxx xxxx
        if (cleaned.length >= 4) {
            cleaned = cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
        }
        
        return cleaned;
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-NA', {
            style: 'currency',
            currency: 'NAD'
        }).format(amount);
    }
    
    // Populate Dropdowns
    function populateDropdowns() {
        // Nationality dropdown
        const nationalitySelect = document.getElementById('nationality');
        if (nationalitySelect) {
            FormDataConfig.nationalities.forEach(nationality => {
                const option = document.createElement('option');
                option.value = nationality.toLowerCase().replace(/\s+/g, '_');
                option.textContent = nationality;
                nationalitySelect.appendChild(option);
            });
        }
        
        // Region dropdowns
        const regionSelects = document.querySelectorAll('#region, #nokRegion, .preferred-region');
        regionSelects.forEach(select => {
            FormDataConfig.regions.forEach(region => {
                const option = document.createElement('option');
                option.value = region;
                option.textContent = region;
                select.appendChild(option);
            });
        });
    }
    
    // Update town dropdown based on region
    function updateTownOptions(regionSelect, townSelect) {
        const selectedRegion = regionSelect.value;
        townSelect.innerHTML = '<option value="">Select Town</option>';
        
        if (selectedRegion && FormDataConfig.townsByRegion[selectedRegion]) {
            FormDataConfig.townsByRegion[selectedRegion].forEach(town => {
                const option = document.createElement('option');
                option.value = town;
                option.textContent = town;
                townSelect.appendChild(option);
            });
        }
    }
    
    // ============================================
    // FORM VALIDATION
    // ============================================
    
    const FormValidation = {
        patterns: {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^(\+264|0)\s?\d{2}\s?\d{3,4}\s?\d{3}$/,
            idNumber: /^[0-9]{10,13}$/,
            passportNumber: /^[A-Z0-9]{6,9}$/,
            erfNumber: /^[0-9]+$/,
            postalCode: /^[0-9]{4,5}$/
        },
        
        messages: {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            phone: 'Please enter a valid Namibian phone number (e.g., 081 412 6568)',
            idNumber: 'ID number must be 10-13 digits',
            passportNumber: 'Passport number must be 6-9 alphanumeric characters',
            dateOfBirth: 'You must be at least 18 years old',
            currency: 'Please enter a valid amount',
            fileSize: 'File size must not exceed {maxSize}MB',
            fileType: 'Please select a valid file type',
            numeric: 'Please enter a valid number',
            positiveNumber: 'Amount must be greater than zero'
        },
        
        currentStep: 1,
        totalSteps: 2,
        
        stepValidation: {
            1: false,
            2: false
        },
        
        init() {
            this.setupValidationEvents();
            this.setupConditionalValidation();
        },
        
        setupValidationEvents() {
            const formInputs = document.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                input.addEventListener('blur', (e) => {
                    this.validateField(e.target);
                });
                
                input.addEventListener('focus', (e) => {
                    this.clearFieldValidation(e.target);
                });
                
                if (input.type === 'email' || input.type === 'tel' || input.name === 'idNumber') {
                    input.addEventListener('input', (e) => {
                        this.validateField(e.target);
                    });
                }
            });
        },
        
        setupConditionalValidation() {
            // No conditional validation needed for this simplified form
        },
        
        validateField(field) {
            const value = field.value.trim();
            const fieldName = field.name || field.id;
            let isValid = true;
            let errorMessage = '';
            
            if (!field.hasAttribute('required') && !value) {
                this.clearFieldValidation(field);
                return true;
            }
            
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = this.messages.required;
            }
            
            if (value && isValid) {
                switch (field.type) {
                    case 'email':
                        isValid = this.patterns.email.test(value);
                        errorMessage = isValid ? '' : this.messages.email;
                        break;
                    
                    case 'tel':
                        // Remove spaces for validation
                        const normalized = value.replace(/\s+/g, '');

                        // Handle +264 format
                        let testValue = normalized;
                        if (normalized.startsWith('+264')) {
                            testValue = '0' + normalized.substring(4);
                        }

                        // Validate as 10-digit Namibian number starting with 0
                        isValid = /^0\d{9}$/.test(testValue);
                        errorMessage = isValid ? '' : this.messages.phone;
                        break;

                    
                    case 'date':
                        if (fieldName.includes('dateOfBirth')) {
                            const age = DateUtils.calculateAge(value);
                            isValid = age >= 18;
                            errorMessage = isValid ? '' : this.messages.dateOfBirth;
                        }
                        break;
                    
                    case 'number':
                        const numValue = parseFloat(value);
                        isValid = !isNaN(numValue) && numValue >= 0;
                        errorMessage = isValid ? '' : this.messages.positiveNumber;
                        break;
                }
                
                if (isValid) {
                    switch (fieldName) {
                        case 'idNumber':
                            const idType = document.getElementById('idType')?.value;
                            if (idType === 'passport') {
                                isValid = this.patterns.passportNumber.test(value);
                                errorMessage = isValid ? '' : this.messages.passportNumber;
                            } else {
                                isValid = this.patterns.idNumber.test(value);
                                errorMessage = isValid ? '' : this.messages.idNumber;
                            }
                            break;
                        
                        case 'erfNumber':
                            isValid = this.patterns.erfNumber.test(value);
                            errorMessage = isValid ? '' : 'ERF number must contain only numbers';
                            break;
                        
                        case 'monthlyIncome':
                            const income = parseFloat(value);
                            isValid = !isNaN(income) && income >= 0;
                            errorMessage = isValid ? '' : 'Please enter a valid income amount';
                            break;
                    }
                }
            }
            
            if (isValid) {
                this.setFieldValid(field);
            } else {
                this.setFieldInvalid(field, errorMessage);
            }
            
            return isValid;
        },
        
        validateStep(stepNumber) {
            const stepElement = document.getElementById(`step-${stepNumber}`);
            if (!stepElement) return false;
            
            const requiredFields = stepElement.querySelectorAll('input[required], select[required], textarea[required]');
            let isStepValid = true;
            let firstInvalidField = null;
            
            requiredFields.forEach(field => {
                const isFieldValid = this.validateField(field);
                if (!isFieldValid) {
                    isStepValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
            });
            
            // Additional step-specific validations
            switch (stepNumber) {
                case 2:
                    const propertyValue = parseFloat(document.getElementById('propertyValue')?.value || 0);
                    const downPayment = parseFloat(document.getElementById('downPayment')?.value || 0);
                    
                    if (downPayment > propertyValue) {
                        const field = document.getElementById('downPayment');
                        this.setFieldInvalid(field, 'Down payment cannot exceed property value');
                        isStepValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                    break;
            }
            
            this.stepValidation[stepNumber] = isStepValid;
            
            if (!isStepValid && firstInvalidField) {
                this.scrollToField(firstInvalidField);
            }
            
            return isStepValid;
        },
        
        setFieldValid(field) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            this.clearFieldError(field);
        },
        
        setFieldInvalid(field, message) {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            this.showFieldError(field, message);
        },
        
        clearFieldValidation(field) {
            field.classList.remove('is-valid', 'is-invalid');
            this.clearFieldError(field);
        },
        
        showFieldError(field, message) {
            const errorElement = field.parentNode.querySelector('.invalid-feedback');
            if (errorElement) {
                errorElement.textContent = message;
            }
        },
        
        clearFieldError(field) {
            const errorElement = field.parentNode.querySelector('.invalid-feedback');
            if (errorElement) {
                errorElement.textContent = '';
            }
        },
        
        scrollToField(field) {
            field.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            
            field.classList.add('shake');
            setTimeout(() => {
                field.classList.remove('shake');
            }, 500);
            
            setTimeout(() => {
                field.focus();
            }, 300);
        },
        
        showAlert(message, type = 'info') {
            let alertElement = document.querySelector('.validation-alert');
            if (!alertElement) {
                alertElement = document.createElement('div');
                alertElement.className = 'alert validation-alert alert-dismissible fade show';
                alertElement.innerHTML = `
                    <span class="alert-message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const currentStep = document.getElementById(`step-${this.currentStep}`);
                if (currentStep) {
                    currentStep.insertBefore(alertElement, currentStep.firstChild);
                }
            }
            
            alertElement.className = `alert validation-alert alert-${type} alert-dismissible fade show`;
            alertElement.querySelector('.alert-message').textContent = message;
            
            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.remove();
                }
            }, 5000);
        }
    };
    
    // ============================================
    // FORM NAVIGATION
    // ============================================
    
    const FormNavigation = {
        currentStep: 1,
        totalSteps: 2,
        
        init() {
            this.bindEvents();
            this.updateNavigation();
            this.setupEventListeners();
        },
        
        bindEvents() {
            document.getElementById('nextBtn').addEventListener('click', () => this.nextStep());
            document.getElementById('prevBtn').addEventListener('click', () => this.previousStep());
            
            // Stepper click navigation
            document.querySelectorAll('.stepper-item-horizontal').forEach(item => {
                item.addEventListener('click', () => {
                    const stepNum = parseInt(item.dataset.step);
                    if (this.canNavigateToStep(stepNum)) {
                        this.goToStep(stepNum);
                    }
                });
            });
        },
        
        setupEventListeners() {
            // Phone number formatting
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    const formatted = formatPhoneNumber(e.target.value);
                    if (formatted !== e.target.value) {
                        e.target.value = formatted;
                    }
                });
            });
            
            // Property value calculation
            const propertyValueInput = document.getElementById('propertyValue');
            const downPaymentInput = document.getElementById('downPayment');
            const loanAmountInput = document.getElementById('loanAmount');
            
            if (propertyValueInput && downPaymentInput && loanAmountInput) {
                const calculateLoan = () => {
                    const propertyValue = parseFloat(propertyValueInput.value) || 0;
                    const downPayment = parseFloat(downPaymentInput.value) || 0;
                    const loanAmount = Math.max(0, propertyValue - downPayment);
                    loanAmountInput.value = loanAmount.toFixed(2);
                };
                
                propertyValueInput.addEventListener('input', calculateLoan);
                downPaymentInput.addEventListener('input', calculateLoan);
            }
            
            // Add preferred area button
            const addAreaBtn = document.getElementById('addPreferredArea');
            if (addAreaBtn) {
                addAreaBtn.addEventListener('click', () => this.addPreferredArea());
            }
            
            // Preferred region change handlers
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('preferred-region')) {
                    const areaItem = e.target.closest('.preferred-area-item');
                    const townSelect = areaItem.querySelector('.preferred-town');
                    updateTownOptions(e.target, townSelect);
                }
            });
        },
        
        addPreferredArea() {
            const container = document.getElementById('preferredAreas');
            const areaCount = container.children.length;
            
            if (areaCount < 5) {
                const areaHtml = `
                    <div class="preferred-area-item">
                        <span class="remove-area" title="Remove Area">
                            <i class="fas fa-times"></i>
                        </span>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Region</label>
                                <select class="form-select preferred-region" name="preferredRegion[]">
                                    <option value="">Select Region</option>
                                    ${FormDataConfig.regions.map(region => `<option value="${region}">${region}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Town</label>
                                <select class="form-select preferred-town" name="preferredTown[]">
                                    <option value="">Select Town</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="preferredLocation[]">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Suburb</label>
                                <input type="text" class="form-control" name="preferredSuburb[]">
                            </div>
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', areaHtml);
                
                const newArea = container.lastElementChild;
                newArea.querySelector('.remove-area').addEventListener('click', () => {
                    newArea.remove();
                });
            }
        },
        
        nextStep() {
            if (FormValidation.validateStep(this.currentStep)) {
                if (this.currentStep < this.totalSteps) {
                    this.goToStep(this.currentStep + 1);
                } else {
                    this.submitForm();
                }
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.goToStep(this.currentStep - 1);
            }
        },
        
        goToStep(stepNumber) {
            if (stepNumber >= 1 && stepNumber <= this.totalSteps) {
                this.hideStep(this.currentStep);
                this.currentStep = stepNumber;
                this.showStep(this.currentStep);
                this.updateProgressIndicator();
                this.updateNavigation();
                FormValidation.currentStep = this.currentStep;
            }
        },
        
        canNavigateToStep(stepNumber) {
            if (stepNumber <= this.currentStep) {
                return true;
            }
            
            for (let i = 1; i < stepNumber; i++) {
                if (!FormValidation.stepValidation[i]) {
                    return false;
                }
            }
            
            return true;
        },
        
        showStep(stepNumber) {
            const stepElement = document.getElementById(`step-${stepNumber}`);
            if (stepElement) {
                stepElement.classList.add('active');
            }
        },
        
        hideStep(stepNumber) {
            const stepElement = document.getElementById(`step-${stepNumber}`);
            if (stepElement) {
                stepElement.classList.remove('active');
            }
        },
        
        updateProgressIndicator() {
            document.querySelectorAll('.stepper-item-horizontal').forEach((item, index) => {
                const stepNum = index + 1;
                item.classList.remove('active', 'completed');
                
                if (stepNum === this.currentStep) {
                    item.classList.add('active');
                } else if (stepNum < this.currentStep) {
                    item.classList.add('completed');
                }
            });
            
            const stepNumberElement = document.getElementById('currentStepNumber');
            if (stepNumberElement) {
                stepNumberElement.textContent = this.currentStep;
            }
        },
        
        updateNavigation() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            if (prevBtn) {
                prevBtn.disabled = this.currentStep === 1;
            }
            
            if (nextBtn) {
                if (this.currentStep === this.totalSteps) {
                    nextBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Application';
                    nextBtn.className = 'btn btn-success';
                } else {
                    nextBtn.innerHTML = 'Next<i class="fas fa-arrow-right ms-2"></i>';
                    nextBtn.className = 'btn btn-primary';
                }
            }
        },
        
        submitForm() {
            let allValid = true;
            for (let i = 1; i <= this.totalSteps; i++) {
                if (!FormValidation.validateStep(i)) {
                    allValid = false;
                }
            }
            
            if (!allValid) {
                FormValidation.showAlert('Please complete all required fields before submitting.', 'danger');
                return;
            }
            
            // Check if FormData is available
            if (typeof FormData === 'undefined') {
                FormValidation.showAlert('Your browser does not support FormData. Please use a modern browser.', 'danger');
                return;
            }
            
            // Submit the form normally
            this.showLoading();
            
            const form = document.getElementById('consultingAgentForm');
            
            // Use fetch API for AJAX submission
            try {
                const formData = new window.FormData(form);
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    this.hideLoading();
                    if (data.success) {
                        // Show success modal with application number
                        document.getElementById('applicationNumber').textContent = data.application_number;
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                    } else {
                        FormValidation.showAlert(data.error || 'An error occurred while submitting the application.', 'danger');
                    }
                })
                .catch(error => {
                    this.hideLoading();
                    console.error('Error:', error);
                    FormValidation.showAlert('An error occurred while submitting the application. Please try again.', 'danger');
                });
            } catch (error) {
                this.hideLoading();
                console.error('FormData error:', error);
                FormValidation.showAlert('Unable to process form data. Please refresh the page and try again.', 'danger');
            }
        },
        
        showLoading() {
            document.getElementById('loadingOverlay').classList.remove('d-none');
        },
        
        hideLoading() {
            document.getElementById('loadingOverlay').classList.add('d-none');
        }
    };
    
    // ============================================
    // INITIALIZATION
    // ============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        populateDropdowns();
        FormValidation.init();
        FormNavigation.init();
    });
    </script>
  </body>
</html>

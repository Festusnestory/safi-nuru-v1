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
    <title>Seller Registration - Nuru Admin</title>
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
        /* Agent Form Custom Styles - Integrated with Material Pro */
        
        /* Page Wrapper */
        .page-wrapper {
            min-height: calc(100vh - 70px);
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
        
        .step-header h4 i {
            color: #1e88e5;
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
            min-width: 100px;
            position: relative;
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
            font-size: 0.875rem;
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
        
        .btn-success:hover {
            background-color: #157347;
            border-color: #157347;
        }
        
        /* Upload Zone */
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
        
        .upload-zone i {
            color: #6c757d;
        }
        
        /* File Preview */
        .file-preview {
            position: relative;
            display: inline-block;
            margin: 0.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.075);
            background: #fff;
        }
        
        .file-preview img {
            max-width: 150px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        
        .file-preview .remove-btn {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 1.5rem;
            height: 1.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
            transition: opacity 0.2s ease;
        }
        
        .file-preview .remove-btn:hover {
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
        
        .loading-spinner {
            text-align: center;
        }
        
        /* Section Dividers */
        h5.section-divider {
            position: relative;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1rem;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .stepper-horizontal {
                padding: 1rem;
            }
            
            .step-name {
                font-size: 0.75rem;
            }
            
            .step-counter {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }
        }
        
        /* Form Group Spacing */
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem !important;
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
              <li class="breadcrumb-item active">Agent Registration</li>
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
                    <i class="fas fa-user-tie me-2"></i> Agent Registration Form
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
                      <div class="step-name">Address</div>
                    </div>
                    <div class="stepper-item-horizontal" data-step="3">
                      <div class="step-counter">3</div>
                      <div class="step-name">Next of Kin</div>
                    </div>
                    <div class="stepper-item-horizontal" data-step="4">
                      <div class="step-counter">4</div>
                      <div class="step-name">Employment</div>
                    </div>
                    <div class="stepper-item-horizontal" data-step="5">
                      <div class="step-counter">5</div>
                      <div class="step-name">Documents</div>
                    </div>
                  </div>
                  
                  <!-- Form -->
                  <form id="agentForm" enctype="multipart/form-data">
                    <!-- Step 1: Personal Details -->
                    <div class="form-step active" id="step1">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">1</span>
                          <i class="fas fa-user me-2"></i>
                          Personal Details
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
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
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="maiden_name" class="form-label">Maiden Name</label>
                          <input type="text" class="form-control" id="maiden_name" name="maiden_name">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="date_of_birth" class="form-label required">Date of Birth</label>
                          <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                          <div class="invalid-feedback"></div>
                          <div id="ageDisplay" class="small text-muted mt-1"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="id_type" class="form-label required">ID Type</label>
                          <select class="form-select" id="id_type" name="id_type" required>
                            <option value="">Select ID Type</option>
                            <option value="National ID">National ID</option>
                            <option value="Passport">Passport</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="id_number" class="form-label required">ID Number</label>
                          <input type="text" class="form-control" id="id_number" name="id_number" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="nationality" class="form-label required">Nationality</label>
                          <input type="text" class="form-control" id="nationality" name="nationality" value="Namibian" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="gender" class="form-label required">Gender</label>
                          <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Step 2: Residential Address -->
                    <div class="form-step" id="step2">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">2</span>
                          <i class="fas fa-home me-2"></i>
                          Residential Address
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="email" class="form-label required">Email Address</label>
                          <input type="email" class="form-control" id="email" name="email" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="mobile_number" class="form-label required">Mobile Number</label>
                          <input type="tel" class="form-control" id="mobile_number" name="mobile_number" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="residential_region" class="form-label required">Region</label>
                          <select class="form-select" id="residential_region" name="residential_region" required>
                            <option value="">Select Region</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="residential_town" class="form-label required">Town</label>
                          <select class="form-select" id="residential_town" name="residential_town" required>
                            <option value="">Select Town</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="residential_street_name" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="residential_street_name" name="residential_street_name">
                        <div class="invalid-feedback"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="residential_suburb" class="form-label">Suburb</label>
                          <input type="text" class="form-control" id="residential_suburb" name="residential_suburb">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="residential_location" class="form-label">Location</label>
                          <input type="text" class="form-control" id="residential_location" name="residential_location">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="po_box" class="form-label">PO Box</label>
                        <input type="text" class="form-control" id="po_box" name="po_box">
                        <div class="invalid-feedback"></div>
                      </div>
                    </div>
                    
                    <!-- Step 3: Next of Kin -->
                    <div class="form-step" id="step3">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">3</span>
                          <i class="fas fa-users me-2"></i>
                          Next of Kin Information
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
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
                          <label for="kin_contact_number" class="form-label required">Contact Number</label>
                          <input type="tel" class="form-control" id="kin_contact_number" name="kin_contact_number" required>
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
                          <label for="kin_town" class="form-label">Town</label>
                          <input type="text" class="form-control" id="kin_town" name="kin_town">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="kin_region" class="form-label">Region</label>
                          <input type="text" class="form-control" id="kin_region" name="kin_region">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="kin_street_name" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="kin_street_name" name="kin_street_name">
                        <div class="invalid-feedback"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="kin_suburb" class="form-label">Suburb</label>
                          <input type="text" class="form-control" id="kin_suburb" name="kin_suburb">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="kin_location" class="form-label">Location</label>
                          <input type="text" class="form-control" id="kin_location" name="kin_location">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Step 4: Employment Details -->
                    <div class="form-step" id="step4">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">4</span>
                          <i class="fas fa-briefcase me-2"></i>
                          Employment Details
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="company_name" class="form-label required">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" required>
                        <div class="invalid-feedback"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="job_title" class="form-label required">Job Title</label>
                          <input type="text" class="form-control" id="job_title" name="job_title" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="employment_number" class="form-label">Employment Number</label>
                          <input type="text" class="form-control" id="employment_number" name="employment_number">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <label for="gross_income" class="form-label required">Gross Income (NAD)</label>
                          <input type="number" class="form-control" id="gross_income" name="gross_income" step="0.01" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="total_deductions" class="form-label">Total Deductions (NAD)</label>
                          <input type="number" class="form-control" id="total_deductions" name="total_deductions" step="0.01">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="net_pay" class="form-label required">Net Pay (NAD)</label>
                          <input type="number" class="form-control" id="net_pay" name="net_pay" step="0.01" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label for="employment_email" class="form-label">Employment Email</label>
                        <input type="email" class="form-control" id="employment_email" name="employment_email">
                        <div class="invalid-feedback"></div>
                      </div>
                    </div>
                    
                    <!-- Step 5: Document Upload -->
                    <div class="form-step" id="step5">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">5</span>
                          <i class="fas fa-file-upload me-2"></i>
                          Document Upload
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        Please upload clear, legible copies of all required documents. Maximum file size: 10MB per file.
                      </div>
                      
                      <div class="mb-4">
                        <label class="form-label fw-bold mb-3">Required Documents</label>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                            <label class="form-label">ID Document *</label>
                            <div class="upload-zone" id="id_document_zone">
                              <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                              <p class="mb-2">Click to upload or drag and drop</p>
                              <p class="text-muted small">National ID or Passport (PDF, DOC, JPG, PNG)</p>
                              <input type="file" class="d-none" id="id_document" name="id_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.tiff,.bmp" required>
                            </div>
                            <div class="file-list" id="id_documentFileList"></div>
                            <div class="invalid-feedback d-block"></div>
                          </div>
                          
                          <div class="col-md-6 mb-3">
                            <label class="form-label">Proof of Residence *</label>
                            <div class="upload-zone" id="proof_residence_zone">
                              <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                              <p class="mb-2">Click to upload or drag and drop</p>
                              <p class="text-muted small">Municipal bill, bank statement, or lease agreement</p>
                              <input type="file" class="d-none" id="proof_residence" name="proof_residence" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.tiff,.bmp" required>
                            </div>
                            <div class="file-list" id="proof_residenceFileList"></div>
                            <div class="invalid-feedback d-block"></div>
                          </div>
                        </div>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                            <label class="form-label">Agency's FFC *</label>
                            <div class="upload-zone" id="agency_ffc_zone">
                              <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                              <p class="mb-2">Click to upload or drag and drop</p>
                              <p class="text-muted small">Agency Fidelity Fund Certificate</p>
                              <input type="file" class="d-none" id="agency_ffc" name="agency_ffc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.tiff,.bmp" required>
                            </div>
                            <div class="file-list" id="agency_ffcFileList"></div>
                            <div class="invalid-feedback d-block"></div>
                          </div>
                          
                          <div class="col-md-6 mb-3">
                            <label class="form-label">Agent's NEAB Card *</label>
                            <div class="upload-zone" id="agent_neab_zone">
                              <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                              <p class="mb-2">Click to upload or drag and drop</p>
                              <p class="text-muted small">Namibian Estate Agents Board Card</p>
                              <input type="file" class="d-none" id="agent_neab" name="agent_neab" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.tiff,.bmp" required>
                            </div>
                            <div class="file-list" id="agent_neabFileList"></div>
                            <div class="invalid-feedback d-block"></div>
                          </div>
                        </div>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                            <label class="form-label">Agent's FFC *</label>
                            <div class="upload-zone" id="agent_ffc_zone">
                              <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                              <p class="mb-2">Click to upload or drag and drop</p>
                              <p class="text-muted small">Agent Fidelity Fund Certificate</p>
                              <input type="file" class="d-none" id="agent_ffc" name="agent_ffc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.tiff,.bmp" required>
                            </div>
                            <div class="file-list" id="agent_ffcFileList"></div>
                            <div class="invalid-feedback d-block"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                      <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
                        <i class="fas fa-arrow-left mr-2"></i>Previous
                      </button>
                      <div>
                        <span class="text-muted mr-3">Step <span id="currentStepNumber">1</span> of 5</span>
                        <button type="button" class="btn btn-primary" id="nextBtn">
                          Next<i class="fas fa-arrow-right ml-2"></i>
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
		  
		      <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title text-success">
              <i class="fas fa-check-circle me-2"></i>
              Application Submitted Successfully
            </h5>
          </div>
          <div class="modal-body text-center">
            <p class="mb-3">The agent registration has been submitted successfully!</p>
            <div class="alert alert-info">
              <strong>Agent ID: </strong>
              <span id="agentId"></span>
            </div>
            <p class="text-muted small">
              Please save this agent ID for records.
            </p>
          </div>
          <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-primary" onclick="window.location.href=nuruBaseUrl + '/admin/agent-list'">
              <i class="ti-list mr-2"></i>View Agent List
            </button>
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
      const DateUtils = {
        calculateAge: (dateOfBirth) => {
          const today = new Date();
          const birthDate = new Date(dateOfBirth);
          let age = today.getFullYear() - birthDate.getFullYear();
          const monthDiff = today.getMonth() - birthDate.getMonth();
          
          if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
          }
          
          return age;
        },
        
        getCurrentDate: () => {
          return new Date().toISOString().split('T')[0];
        },
        
        getMinBirthDate: () => {
          const date = new Date();
          date.setFullYear(date.getFullYear() - 18);
          return date.toISOString().split('T')[0];
        }
      };
      
      // Format phone number
      const formatPhoneNumber = (phone) => {
        const cleaned = phone.replace(/\D/g, '');
        
        if (cleaned.length === 11 && cleaned.startsWith('264')) {
          return `+264 ${cleaned.substr(3, 2)} ${cleaned.substr(5, 3)} ${cleaned.substr(8)}`;
        }
        
        if (cleaned.length === 10 && cleaned.startsWith('0')) {
          return `${cleaned.substr(0, 3)} ${cleaned.substr(3, 3)} ${cleaned.substr(6)}`;
        }
        
        return phone;
      };
      
    </script>
    
    <!-- Form Validation -->
    <script>
      const FormValidation = {
        patterns: {
          email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
          phone: /^(\+264|0)[0-9]{8,9}$/,
          idNumber: /^[0-9]{10,13}$/
        },
        
        messages: {
          required: 'This field is required',
          email: 'Please enter a valid email address',
          phone: 'Please enter a valid Namibian phone number',
          dateOfBirth: 'You must be at least 18 years old',
          future: 'Date cannot be in the future',
          positiveNumber: 'Amount must be greater than zero'
        },
        
        currentStep: 1,
        
        stepValidation: {
          1: false, 2: false, 3: false, 4: false, 5: false
        },
        
        init() {
          this.setupValidationEvents();
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
            
            if (input.type === 'email' || input.type === 'tel') {
              input.addEventListener('input', (e) => {
                this.validateField(e.target);
              });
            }
          });
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
                // formatPhoneNumber() inserts spaces into the displayed value
                // (e.g. "081 555 6666"); the phone pattern has no \s allowance,
                // so every validly-formatted number failed validation and
                // permanently blocked submission. Strip spaces before testing.
                isValid = this.patterns.phone.test(value.replace(/\s+/g, ''));
                errorMessage = isValid ? '' : this.messages.phone;
                break;
              
              case 'date':
                if (fieldName.includes('date_of_birth')) {
                  const age = DateUtils.calculateAge(value);
                  isValid = age >= 18;
                  errorMessage = isValid ? '' : this.messages.dateOfBirth;
                } else {
                  isValid = new Date(value) <= new Date();
                  errorMessage = isValid ? '' : this.messages.future;
                }
                break;
              
              case 'number':
                const numValue = parseFloat(value);
                isValid = !isNaN(numValue) && numValue > 0;
                errorMessage = isValid ? '' : this.messages.positiveNumber;
                break;
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
          const stepElement = document.getElementById(`step${stepNumber}`);
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
            
            const currentStep = document.getElementById(`step${this.currentStep}`);
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
    </script>
    
    <!-- Form Steps -->
    <script>
      const FormStepper = {
        currentStep: 1,
        totalSteps: 5,
        
        init() {
          this.setupStepNavigation();
          this.bindEvents();
        },
        
        setupStepNavigation() {
          const nextBtn = document.getElementById('nextBtn');
          const prevBtn = document.getElementById('prevBtn');
          
          if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextStep());
          }
          
          if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousStep());
          }
        },
        
        bindEvents() {
          this.setupDocumentUploads();
          this.setupTownRegionMapping();
          this.setupAutoSave();
          this.setupIncomeCalculation();
        },
        
        setupDocumentUploads() {
          const uploadZones = [
            { zone: 'id_document_zone', input: 'id_document' },
            { zone: 'proof_residence_zone', input: 'proof_residence' },
            { zone: 'agency_ffc_zone', input: 'agency_ffc' },
            { zone: 'agent_neab_zone', input: 'agent_neab' },
            { zone: 'agent_ffc_zone', input: 'agent_ffc' }
          ];
          
          uploadZones.forEach(({ zone, input }) => {
            const uploadZone = document.getElementById(zone);
            const fileInput = document.getElementById(input);
            
            if (uploadZone && fileInput) {
              uploadZone.addEventListener('click', () => fileInput.click());
              
              fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target);
              });
              
              uploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
              });
              
              uploadZone.addEventListener('dragleave', () => {
                uploadZone.classList.remove('dragover');
              });
              
              uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                this.handleFileUpload(fileInput);
              });
            }
          });
        },
        
        handleFileUpload(fileInput) {
          const files = Array.from(fileInput.files);
          const fileList = document.getElementById(fileInput.name + 'FileList');
          const uploadZone = fileInput.closest('.upload-zone');
          
          if (fileList) {
            fileList.innerHTML = '';
            
            files.forEach((file, index) => {
              const fileItem = document.createElement('div');
              fileItem.className = 'file-preview d-flex align-items-center p-2 border rounded mb-2';
              const icon = file.type.includes('pdf') ? 'fa-file-pdf text-danger' : 
                          file.type.includes('image') ? 'fa-file-image text-primary' : 
                          'fa-file text-secondary';
              fileItem.innerHTML = `
                <i class="fas ${icon} fa-2x me-3"></i>
                <div class="flex-grow-1">
                  <div class="fw-bold">${file.name}</div>
                  <small class="text-muted">${this.formatFileSize(file.size)}</small>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="FormStepper.removeFile('${fileInput.name}', ${index})">
                  <i class="fas fa-times"></i>
                </button>
              `;
              fileList.appendChild(fileItem);
            });
            
            if (uploadZone && files.length > 0) {
              uploadZone.classList.add('has-file');
            }
          }
        },
        
        removeFile(inputName, indexToRemove) {
          const fileInput = document.getElementById(inputName);
          if (!fileInput) return;
          
          const dt = new DataTransfer();
          const files = Array.from(fileInput.files);
          
          files.forEach((file, index) => {
            if (index !== indexToRemove) {
              dt.items.add(file);
            }
          });
          
          fileInput.files = dt.files;
          this.handleFileUpload(fileInput);
        },
        
        formatFileSize(bytes) {
          if (bytes === 0) return '0 Bytes';
          const k = 1024;
          const sizes = ['Bytes', 'KB', 'MB', 'GB'];
          const i = Math.floor(Math.log(bytes) / Math.log(k));
          return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        // Region-first: populate the Region select from the canonical
        // Namibian region/town data, then fill Town in when Region changes
        // - matches the buyer/seller/public-agent forms' pattern.
        setupTownRegionMapping() {
          const regionSelect = document.getElementById('residential_region');
          const townSelect = document.getElementById('residential_town');
          if (!regionSelect || !townSelect) return;

          Object.keys(window.NURU_TOWNS_BY_REGION).sort().forEach(region => {
            const option = document.createElement('option');
            option.value = region;
            option.textContent = region;
            regionSelect.appendChild(option);
          });

          regionSelect.addEventListener('change', (e) => {
            townSelect.innerHTML = '<option value="">Select Town</option>';
            const towns = window.NURU_TOWNS_BY_REGION[e.target.value] || [];
            towns.forEach(town => {
              const option = document.createElement('option');
              option.value = town;
              option.textContent = town;
              townSelect.appendChild(option);
            });
          });
        },
        
        setupIncomeCalculation() {
          const grossIncome = document.getElementById('gross_income');
          const totalDeductions = document.getElementById('total_deductions');
          const netPay = document.getElementById('net_pay');
          
          if (grossIncome && netPay) {
            const calculateNetPay = () => {
              const gross = parseFloat(grossIncome.value) || 0;
              const deductions = parseFloat(totalDeductions.value) || 0;
              const net = Math.max(0, gross - deductions);
              netPay.value = net.toFixed(2);
            };
            
            grossIncome.addEventListener('input', calculateNetPay);
            totalDeductions.addEventListener('input', calculateNetPay);
          }
        },
        
        setupAutoSave() {
          const formInputs = document.querySelectorAll('input, select, textarea');
          formInputs.forEach(input => {
            input.addEventListener('change', () => {
              this.saveFormData();
            });
          });
          
          setInterval(() => {
            this.saveFormData();
          }, 30000);
        },
        
        nextStep() {
          if (FormValidation.validateStep(this.currentStep)) {
            this.saveFormData();
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
          const stepElement = document.getElementById(`step${stepNumber}`);
          if (stepElement) {
            stepElement.classList.add('active');
          }
        },
        
        hideStep(stepNumber) {
          const stepElement = document.getElementById(`step${stepNumber}`);
          if (stepElement) {
            stepElement.classList.remove('active');
          }
        },
        
        updateProgressIndicator() {
          const stepItems = document.querySelectorAll('.stepper-item-horizontal');
          
          stepItems.forEach((item, index) => {
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
              nextBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Submit Application';
              nextBtn.className = 'btn btn-success';
            } else {
              nextBtn.innerHTML = 'Next<i class="fas fa-arrow-right ml-2"></i>';
              nextBtn.className = 'btn btn-primary';
            }
          }
        },
        
        saveFormData() {
          const form = document.getElementById('agentForm');
          const formData = new FormData(form);
          const data = {};
          
          for (let [key, value] of formData.entries()) {
            if (data[key]) {
              if (Array.isArray(data[key])) {
                data[key].push(value);
              } else {
                data[key] = [data[key], value];
              }
            } else {
              data[key] = value;
            }
          }
          
          localStorage.setItem('nuru-agent-form-data', JSON.stringify(data));
          localStorage.setItem('nuru-agent-current-step', this.currentStep.toString());
        },
        
        loadSavedData() {
          const savedData = localStorage.getItem('nuru-agent-form-data');
          const savedStep = localStorage.getItem('nuru-agent-current-step');
          
          if (savedData) {
            try {
              const data = JSON.parse(savedData);
              this.populateFormData(data);
            } catch (error) {
              console.error('Error loading saved data:', error);
            }
          }
          
          if (savedStep) {
            this.currentStep = parseInt(savedStep);
            this.goToStep(this.currentStep);
          }
        },
        
        populateFormData(data) {
          Object.entries(data).forEach(([key, value]) => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element && element.type !== 'file') {
              if (element.type === 'checkbox' || element.type === 'radio') {
                if (Array.isArray(value)) {
                  value.forEach(val => {
                    const checkbox = document.querySelector(`[name="${key}"][value="${val}"]`);
                    if (checkbox) checkbox.checked = true;
                  });
                } else {
                  element.checked = element.value === value;
                }
              } else {
                element.value = value;
              }
            }
          });
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
          
          AgentForm.submitApplication();
        }
      };
    </script>
    
    <!-- Agent Form Main -->
    <script>
      const AgentForm = {
        apiBaseUrl: '../api',
        csrfToken: null,
        
        init() {
          this.getCSRFToken();
          this.bindEvents();
          this.setupFormInteractions();
        },
        
        getCSRFToken() {
          const metaTag = document.querySelector('meta[name="csrf-token"]');
          if (metaTag) {
            this.csrfToken = metaTag.getAttribute('content');
          }
        },
        
        bindEvents() {
          this.setupPhoneFormatting();
          this.setupRealTimeValidation();
        },
        
        setupFormInteractions() {
          // Age display for date of birth
          const dobInput = document.getElementById('date_of_birth');
          if (dobInput) {
            dobInput.addEventListener('change', (e) => {
              const age = DateUtils.calculateAge(e.target.value);
              const ageDisplay = document.getElementById('ageDisplay');
              if (ageDisplay) {
                ageDisplay.textContent = `Age: ${age} years`;
                ageDisplay.className = age >= 18 ? 'text-success small' : 'text-danger small';
              }
            });
          }
        },
        
        setupPhoneFormatting() {
          const phoneInputs = document.querySelectorAll('input[type="tel"]');
          phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
              const formatted = formatPhoneNumber(e.target.value);
              if (formatted !== e.target.value) {
                e.target.value = formatted;
              }
            });
          });
        },
        
        setupRealTimeValidation() {
          // ID number validation based on type
          const idTypeSelect = document.getElementById('id_type');
          const idNumberInput = document.getElementById('id_number');
          
          if (idTypeSelect && idNumberInput) {
            idTypeSelect.addEventListener('change', () => {
              FormValidation.validateField(idNumberInput);
            });
          }
        },
        
        async submitApplication() {
          try {
            this.showLoadingOverlay();

            const form = document.getElementById('agentForm');
            const formData = new FormData(form);
            formData.append('csrf_token', this.csrfToken);

            const response = await fetch(nuruBaseUrl + '/admin/admin-agent-processor', {
              method: 'POST',
              body: formData
            });

            const result = await response.json();

            this.hideLoadingOverlay();

            if (result.success) {
              this.handleSubmissionSuccess(result);
            } else {
              this.handleSubmissionError(result.error || 'An error occurred while submitting the application.');
            }

          } catch (error) {
            console.error('Submission error:', error);
            this.hideLoadingOverlay();
            this.handleSubmissionError('Network error occurred. Please try again.');
          }
        },
        
        handleSubmissionSuccess(result) {
          localStorage.removeItem('nuru-agent-form-data');
          localStorage.removeItem('nuru-agent-current-step');
          
          const successModal = new bootstrap.Modal(document.getElementById('successModal'));
          document.getElementById('agentId').textContent = result.agent_id;
          successModal.show();
        },
        
        handleSubmissionError(errorMessage) {
          const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <strong>Submission Failed:</strong> ${errorMessage}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          `;
          
          const currentStep = document.getElementById(`step${FormStepper.currentStep}`);
          currentStep.insertAdjacentHTML('afterbegin', alertHtml);
          currentStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        
        showLoadingOverlay() {
          const overlay = document.getElementById('loadingOverlay');
          if (overlay) {
            overlay.classList.remove('d-none');
          }
        },
        
        hideLoadingOverlay() {
          const overlay = document.getElementById('loadingOverlay');
          if (overlay) {
            overlay.classList.add('d-none');
          }
        },
        
        saveAsDraft() {
          FormStepper.saveFormData();
          
          const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle mr-2"></i>
              <strong>Draft Saved!</strong> Your progress has been saved.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          `;
          
          const cardBody = document.querySelector('.card-body');
          cardBody.insertAdjacentHTML('afterbegin', alertHtml);
        }
      };
      
      // Initialize on DOM load
      document.addEventListener('DOMContentLoaded', function() {
        FormValidation.init();
        FormStepper.init();
        AgentForm.init();
        FormStepper.loadSavedData();
      });
    </script>
  </body>
</html>

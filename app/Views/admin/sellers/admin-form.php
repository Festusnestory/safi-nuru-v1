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
        /* Seller Form Custom Styles - Integrated with Material Pro */
        
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
            cursor: pointer;
        }
        
        .stepper-item-horizontal:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: calc(50% + 20px);
            width: calc(100% - 40px);
            height: 2px;
            background: #dee2e6;
        }
        
        .stepper-item-horizontal.completed:not(:last-child)::after {
            background: #198754;
        }
        
        .stepper-item-horizontal.active:not(:last-child)::after {
            background: linear-gradient(to right, #1e88e5, #dee2e6);
        }
        
        .step-icon-horizontal {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            background: #fff;
            border: 2px solid #dee2e6;
            color: #6c757d;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .stepper-item-horizontal.active .step-icon-horizontal {
            background: #1e88e5;
            border-color: #1e88e5;
            color: #fff;
        }
        
        .stepper-item-horizontal.completed .step-icon-horizontal {
            background: #198754;
            border-color: #198754;
            color: #fff;
        }
        
        .step-content-horizontal {
            text-align: center;
            margin-top: 0.75rem;
            flex: 1;
        }
        
        .step-title-horizontal {
            font-size: 0.8rem;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .stepper-item-horizontal.active .step-title-horizontal {
            color: #1e88e5;
            font-weight: 600;
        }
        
        .stepper-item-horizontal.completed .step-title-horizontal {
            color: #198754;
        }
        
        /* Required Field Indicator */
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        /* Form Controls */
        .form-control:focus,
        .form-select:focus {
            border-color: #1e88e5;
            box-shadow: 0 0 0 0.25rem rgba(30, 136, 229, 0.1);
        }
        
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }
        
        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #198754;
            background-image: none;
        }
        
        /* Sale Type Cards */
        .sale-type-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid #dee2e6;
            height: 100%;
        }
        
        .sale-type-card:hover {
            border-color: #1e88e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .sale-type-card.selected {
            border-color: #1e88e5;
            background-color: rgba(30, 136, 229, 0.03);
        }
        
        .sale-type-card i {
            color: #1e88e5;
        }
        
        .sale-type-card .form-check-input {
            transform: scale(1.2);
        }
        
        /* Signature Method Cards */
        .signature-method-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid #dee2e6;
        }
        
        .signature-method-card:hover {
            border-color: #1e88e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .signature-method-card.selected {
            border-color: #1e88e5;
            background-color: rgba(30, 136, 229, 0.03);
        }
        
        .signature-method-card i {
            color: #1e88e5;
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
        
        .document-upload-card i {
            color: #1e88e5;
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
        
        .upload-zone i {
            color: #6c757d;
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
        
        .file-preview img {
            max-width: 150px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        
        .file-preview video {
            max-width: 200px;
            max-height: 150px;
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
        
        .file-info {
            padding: 0.5rem;
            font-size: 0.75rem;
            color: #6c757d;
            background: rgba(0,0,0,0.03);
        }
        
        /* Additional Document Items */
        .additional-doc-item {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff;
            position: relative;
        }
        
        .additional-doc-item .remove-additional-doc {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* House Type Items */
        .house-type-item {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff;
            position: relative;
        }
        
        .house-type-item .remove-house-type {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .loading-spinner {
            text-align: center;
            color: #fff;
        }
        
        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        /* Validation States */
        .was-validated .form-control:valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.2-.2 2.8-2.83c.1-.1.1-.25 0-.35l-1.42-1.41c-.1-.1-.25-.1-.35 0L1.9 3.86 1.5 3.5c-.1-.1-.25-.1-.35 0l-.71.71c-.1.1-.1.25 0 .35l1.06 1.06c.1.1.25.1.35 0Z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .was-validated .form-control:invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.3 4.4 4.4m-4.4 0 4.4-4.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        /* Radio Button Groups */
        .form-check-input:checked {
            background-color: #1e88e5;
            border-color: #1e88e5;
        }
        
        .form-check-input:focus {
            border-color: #1e88e5;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(30, 136, 229, 0.25);
        }
        
        /* Custom Alerts */
        .alert {
            border: none;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }
        
        .alert-info {
            background-color: rgba(13, 202, 240, 0.1);
            color: #055160;
            border-left: 4px solid #0dcaf0;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #0a3622;
            border-left: 4px solid #198754;
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #664d03;
            border-left: 4px solid #ffc107;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(45deg, #1e88e5, #1976d2);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #1976d2, #1e88e5);
            box-shadow: 0 4px 8px rgba(30, 136, 229, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #198754, #157347);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(45deg, #157347, #198754);
            box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
        }
        
        .btn-outline-primary:hover {
            background: #1e88e5;
            border-color: #1e88e5;
        }
        
        /* Input Groups */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.075);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.125);
            border-radius: 0.75rem 0.75rem 0 0 !important;
            background: #fff;
        }
        
        /* Progress Bar */
        .progress {
            background-color: rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            background: linear-gradient(45deg, #1e88e5, #1976d2);
        }
        
        /* Animations */
        @keyframes slideInFromRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideInFromLeft {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .slide-in-right { animation: slideInFromRight 0.3s ease-out; }
        .slide-in-left { animation: slideInFromLeft 0.3s ease-out; }
        
        /* Responsive Design */
        @media (max-width: 991px) {
            .stepper-horizontal {
                padding: 1rem;
            }
            
            .step-title-horizontal {
                font-size: 0.7rem;
            }
            
            .step-icon-horizontal {
                width: 35px;
                height: 35px;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 768px) {
            .stepper-horizontal {
                justify-content: flex-start;
                gap: 0.5rem;
            }
            
            .stepper-item-horizontal {
                min-width: 80px;
            }
            
            .stepper-item-horizontal::after {
                display: none;
            }
            
            .step-title-horizontal {
                display: none;
            }
            
            .step-header h4 {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 576px) {
            .upload-zone {
                padding: 1.5rem 1rem;
            }
            
            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }
        }
        
        /* Print Styles */
        @media print {
            .page-wrapper,
            .loading-overlay,
            #navigationButtons {
                display: none !important;
            }
            
            .form-step {
                display: block !important;
                page-break-inside: avoid;
            }
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
              <li class="breadcrumb-item active">Seller Registration</li>
            </ol>
          </div>
        </div>
		  
		  
        <div class="container-fluid">
          <!-- Breadcrumb -->

          
          <!-- Form Content -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <!-- Header with Progress -->
                  <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                      <h3 class="mb-1 text-primary">
                        <i class="fas fa-user-tie me-2"></i>Seller Application Form
                      </h3>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                      <span class="badge bg-primary fs-6 mb-2">Step <span id="headerStepNumber">1</span> of 9</span>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: 11.11%;" id="headerProgressBar"></div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Horizontal Stepper -->
                  <div class="stepper-horizontal" id="formStepper">
                    <!-- Steps will be populated by JavaScript -->
                  </div>
                  
                  <!-- Form -->
                  <form id="sellerApplicationForm" novalidate>
                    <!-- Step 1: Personal Details -->
                    <div class="form-step active" id="step-1">
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
                          <label for="firstName" class="form-label required">First Name</label>
                          <input type="text" class="form-control" id="firstName" name="firstName" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="middleName" class="form-label">Middle Name</label>
                          <input type="text" class="form-control" id="middleName" name="middleName">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="maidenName" class="form-label">Maiden Name</label>
                          <input type="text" class="form-control" id="maidenName" name="maidenName">
                          <small class="form-text text-muted">If applicable</small>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4 mb-3">
                          <label for="dateOfBirth" class="form-label required">Date of Birth</label>
                          <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                          <div class="invalid-feedback"></div>
                          <div id="ageDisplay" class="small text-muted mt-1"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="idType" class="form-label required">Type of ID</label>
                          <select class="form-select" id="idType" name="idType" required>
                            <option value="">Select ID Type</option>
                            <option value="National ID">National ID</option>
                            <option value="Passport">Passport</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                          <label for="idNumber" class="form-label required">ID Number</label>
                          <input type="text" class="form-control" id="idNumber" name="idNumber" required>
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
                        <div class="col-md-6 mb-4">
                          <label class="form-label required">Gender</label>
                          <div class="d-flex gap-4 mt-2">
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male">
                              <label class="form-check-label" for="genderMale">Male</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female">
                              <label class="form-check-label" for="genderFemale">Female</label>
                            </div>
                          </div>
                          <div class="invalid-feedback d-block"></div>
                        </div>
                      </div>
                    </div>

                    <!-- Step 2: Marital Status -->
                    <div class="form-step" id="step-2">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">2</span>
                          <i class="fas fa-heart me-2"></i>
                          Marital Status
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="mb-4">
                        <label for="maritalStatus" class="form-label required">Marital Status</label>
                        <select class="form-select" id="maritalStatus" name="maritalStatus" required>
                          <option value="">Select Status</option>
                          <option value="Single">Single</option>
                          <option value="Married">Married</option>
                          <option value="Separated">Separated</option>
                          <option value="Divorced">Divorced</option>
                          <option value="Widower">Widower</option>
                        </select>
                        <div class="invalid-feedback"></div>
                      </div>
                      
                      <!-- Spouse Details -->
                      <div id="spouseDetails" class="d-none">
                        <h5 class="mb-3 text-secondary">
                          <i class="fas fa-user-friends me-2"></i>
                          Spouse Details
                        </h5>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                            <label for="spouseSurname" class="form-label">Surname</label>
                            <input type="text" class="form-control" id="spouseSurname" name="spouseSurname">
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-6 mb-3">
                            <label for="spouseFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="spouseFirstName" name="spouseFirstName">
                            <div class="invalid-feedback"></div>
                          </div>
                        </div>
                        
                        <div class="row">
                          <div class="col-md-4 mb-3">
                            <label for="spouseDateOfBirth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="spouseDateOfBirth" name="spouseDateOfBirth">
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-4 mb-3">
                            <label for="spouseIdType" class="form-label">Type of ID</label>
                            <select class="form-select" id="spouseIdType" name="spouseIdType">
                              <option value="">Select ID Type</option>
                              <option value="National ID">National ID</option>
                              <option value="Passport">Passport</option>
                            </select>
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-4 mb-3">
                            <label for="spouseIdNumber" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="spouseIdNumber" name="spouseIdNumber">
                            <div class="invalid-feedback"></div>
                          </div>
                        </div>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                            <label for="spouseNationality" class="form-label">Nationality</label>
                            <select class="form-select" id="spouseNationality" name="spouseNationality">
                              <option value="">Select Nationality</option>
                            </select>
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <div class="d-flex gap-4 mt-2">
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="spouseGender" id="spouseGenderMale" value="Male">
                                <label class="form-check-label" for="spouseGenderMale">Male</label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="spouseGender" id="spouseGenderFemale" value="Female">
                                <label class="form-check-label" for="spouseGenderFemale">Female</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <div class="mb-3">
                          <label for="marriageCertificate" class="form-label">Marriage Certificate</label>
                          <input type="file" class="form-control" id="marriageCertificate" name="marriageCertificate" accept=".pdf,.jpg,.jpeg,.png">
                          <div class="form-text">Upload PDF, JPG, or PNG format (Max 10MB)</div>
                          <div class="invalid-feedback"></div>
                          <div id="marriageCertificatePreview" class="mt-2"></div>
                        </div>
                      </div>
                    </div>

                    <!-- Step 3: Residential Address -->
                    <div class="form-step" id="step-3">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">3</span>
                          <i class="fas fa-map-marker-alt me-2"></i>
                          Residential Address
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="erfNo" class="form-label">ERF No</label>
                          <input type="text" class="form-control" id="erfNo" name="erfNo">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="streetName" class="form-label required">Street Name</label>
                          <input type="text" class="form-control" id="streetName" name="streetName" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="suburb" class="form-label">Suburb</label>
                          <input type="text" class="form-control" id="suburb" name="suburb">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="location" class="form-label">Location</label>
                          <input type="text" class="form-control" id="location" name="location">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="region" class="form-label required">Region</label>
                          <select class="form-select" id="region" name="region" required>
                            <option value="">Select Region</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="town" class="form-label required">Town</label>
                          <select class="form-select" id="town" name="town" required>
                            <option value="">Select Town</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="email" class="form-label required">Email Address</label>
                          <input type="email" class="form-control" id="email" name="email" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="mobileNumber" class="form-label required">Mobile Number</label>
                          <input type="tel" class="form-control" id="mobileNumber" name="mobileNumber" required>
                          <div class="form-text">Format: +264 XX XXX XXXX</div>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="col-md-6 mb-3">
                        <label for="poBox" class="form-label">P.O. Box</label>
                        <input type="text" class="form-control" id="poBox" name="poBox">
                        <div class="invalid-feedback"></div>
                      </div>
                    </div>

                    <!-- Step 4: Next of Kin -->
                    <div class="form-step" id="step-4">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">4</span>
                          <i class="fas fa-users me-2"></i>
                          Next of Kin
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="nokSurname" class="form-label required">Surname</label>
                          <input type="text" class="form-control" id="nokSurname" name="nokSurname" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="nokFirstName" class="form-label required">First Name</label>
                          <input type="text" class="form-control" id="nokFirstName" name="nokFirstName" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="nokContactNumber" class="form-label required">Contact Number</label>
                          <input type="tel" class="form-control" id="nokContactNumber" name="nokContactNumber" required>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="nokEmail" class="form-label required">Email Address</label>
                          <input type="email" class="form-control" id="nokEmail" name="nokEmail" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <h5 class="mb-3 text-secondary">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Next of Kin Address
                      </h5>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="nokErfNo" class="form-label">ERF No</label>
                          <input type="text" class="form-control" id="nokErfNo" name="nokErfNo">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="nokStreetName" class="form-label required">Street Name</label>
                          <input type="text" class="form-control" id="nokStreetName" name="nokStreetName" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="nokSuburb" class="form-label">Suburb</label>
                          <input type="text" class="form-control" id="nokSuburb" name="nokSuburb">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="nokLocation" class="form-label">Location</label>
                          <input type="text" class="form-control" id="nokLocation" name="nokLocation">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="nokRegion" class="form-label required">Region</label>
                          <select class="form-select" id="nokRegion" name="nokRegion" required>
                            <option value="">Select Region</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="nokTown" class="form-label required">Town</label>
                          <select class="form-select" id="nokTown" name="nokTown" required>
                            <option value="">Select Town</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                    </div>

                    <!-- Step 5: Sale Type Selection -->
                    <div class="form-step" id="step-5">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">5</span>
                          <i class="fas fa-building me-2"></i>
                          Sale Type Selection
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="mb-4">
                        <label class="form-label required">Sale Type</label>
                        <div class="row mt-3">
                          <div class="col-md-6 mb-3">
                            <div class="card sale-type-card" data-type="Individual">
                              <div class="card-body text-center">
                                <i class="fas fa-user fa-3x mb-3"></i>
                                <h5>Individual Sale</h5>
                                <p class="text-muted">Selling a single property as an individual</p>
                                <div class="form-check d-flex justify-content-center">
                                  <input class="form-check-input" type="radio" name="saleType" id="saleTypeIndividual" value="Individual">
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6 mb-3">
                            <div class="card sale-type-card" data-type="Property Development">
                              <div class="card-body text-center">
                                <i class="fas fa-city fa-3x mb-3"></i>
                                <h5>Property Development</h5>
                                <p class="text-muted">Developing and selling multiple properties</p>
                                <div class="form-check d-flex justify-content-center">
                                  <input class="form-check-input" type="radio" name="saleType" id="saleTypeDevelopment" value="Property Development">
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="invalid-feedback d-block"></div>
                      </div>
                      
                      <!-- Property Developer Details -->
                      <div id="propertyDeveloperDetails" class="d-none">
                        <h5 class="mb-3 text-secondary">
                          <i class="fas fa-building me-2"></i>
                          Developments
                        </h5>
                        <div class="invalid-feedback d-block" id="developmentsFeedback" style="display:none;"></div>

                        <div id="developmentsContainer"></div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addDevelopment">
                          <i class="fas fa-plus me-2"></i>Add Development
                        </button>
                      </div>
                    </div>

                    <!-- Step 6: Property Purchase Details -->
                    <div class="form-step" id="step-6">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">6</span>
                          <i class="fas fa-home me-2"></i>
                          Property Details
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div id="developmentPropertySkipMessage" class="alert alert-info d-none">
                        <i class="fas fa-info-circle me-2"></i>
                        Property details for this application were already captured under each Development's House Types in the previous step.
                      </div>

                      <div id="individualPropertyDetails">
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="propertyDetailType" class="form-label required">Property Type</label>
                          <select class="form-select" id="propertyDetailType" name="propertyDetailType" required>
                            <option value="">Select Property Type</option>
                            <option value="Single Residential">Single Residential</option>
                            <option value="General Residential">General Residential</option>
                            <option value="Farm">Farm</option>
                            <option value="Commercial/Business">Commercial/Business</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="landType" class="form-label required">Land Type</label>
                          <select class="form-select" id="landType" name="landType" required>
                            <option value="">Select Land Type</option>
                            <option value="Vacant Land">Vacant Land</option>
                            <option value="Existing Property">Existing Property</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="landSize" class="form-label required">Land Size</label>
                          <div class="input-group">
                            <input type="text" class="form-control" id="landSize" name="landSize" required>
                            <span class="input-group-text">m²</span>
                          </div>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="sellingPrice" class="form-label required">Selling Price</label>
                          <div class="input-group">
                            <span class="input-group-text">N$</span>
                            <input type="text" class="form-control money-input" id="sellingPrice" name="sellingPrice" inputmode="decimal" required>
                          </div>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <!-- Existing Property Details -->
                      <div id="existingPropertyDetails" class="d-none">
                        <h5 class="mb-3 text-secondary">
                          <i class="fas fa-home me-2"></i>
                          Existing Property Details
                        </h5>
                        
                        <div class="row">
                          <div class="col-md-4 mb-3">
                            <label for="houseSize" class="form-label">House Size</label>
                            <div class="input-group">
                              <input type="text" class="form-control" id="houseSize" name="houseSize">
                              <span class="input-group-text">m²</span>
                            </div>
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-4 mb-3">
                            <label for="rooms" class="form-label">Number of Rooms</label>
                            <input type="number" class="form-control" id="rooms" name="rooms" min="1">
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-4 mb-3">
                            <label for="bathrooms" class="form-label">Number of Bathrooms</label>
                            <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="1">
                            <div class="invalid-feedback"></div>
                          </div>
                        </div>
                        
                        <div class="mb-3">
                          <label for="additionalFeatures" class="form-label">Additional Features</label>
                          <textarea class="form-control" id="additionalFeatures" name="additionalFeatures" rows="3" placeholder="Describe any additional features like garage, garden, pool, etc."></textarea>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <!-- Property Address -->
                      <h5 class="mb-3 text-secondary">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Property Address
                      </h5>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="propertyErfNo" class="form-label">ERF No</label>
                          <input type="text" class="form-control" id="propertyErfNo" name="propertyErfNo">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="propertyStreetName" class="form-label required">Street Name</label>
                          <input type="text" class="form-control" id="propertyStreetName" name="propertyStreetName" required>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="propertySuburb" class="form-label">Suburb</label>
                          <input type="text" class="form-control" id="propertySuburb" name="propertySuburb">
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="propertyLocation" class="form-label">Location</label>
                          <input type="text" class="form-control" id="propertyLocation" name="propertyLocation">
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label for="propertyRegion" class="form-label required">Region</label>
                          <select class="form-select" id="propertyRegion" name="propertyRegion" required>
                            <option value="">Select Region</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label for="propertyTown" class="form-label required">Town</label>
                          <select class="form-select" id="propertyTown" name="propertyTown" required>
                            <option value="">Select Town</option>
                          </select>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      </div>
                    </div>

                    <!-- Step 7: Document Upload -->
                    <div class="form-step" id="step-7">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">7</span>
                          <i class="fas fa-file-upload me-2"></i>
                          Document Upload
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-4">
                          <div class="card document-upload-card">
                            <div class="card-body">
                              <h6 class="card-title">
                                <i class="fas fa-id-card me-2"></i>
                                ID Document <span class="text-danger">*</span>
                              </h6>
                              <input type="file" class="form-control mb-2" id="idDocument" name="idDocument" accept=".pdf,.jpg,.jpeg,.png" required>
                              <small class="text-muted">Upload PDF, JPG, or PNG (Max 10MB)</small>
                              <div class="invalid-feedback"></div>
                              <div id="idDocumentPreview" class="mt-2"></div>
                            </div>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                          <div class="card document-upload-card">
                            <div class="card-body">
                              <h6 class="card-title">
                                <i class="fas fa-home me-2"></i>
                                Proof of Residence <span class="text-danger">*</span>
                              </h6>
                              <input type="file" class="form-control mb-2" id="proofOfResidence" name="proofOfResidence" accept=".pdf,.jpg,.jpeg,.png" required>
                              <small class="text-muted">Utility bill, bank statement, etc.</small>
                              <div class="invalid-feedback"></div>
                              <div id="proofOfResidencePreview" class="mt-2"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-4">
                          <div class="card document-upload-card">
                            <div class="card-body">
                              <h6 class="card-title">
                                <i class="fas fa-certificate me-2"></i>
                                Title Deed <span class="text-danger">*</span>
                              </h6>
                              <input type="file" class="form-control mb-2" id="titleDeed" name="titleDeed" accept=".pdf,.jpg,.jpeg,.png" required>
                              <small class="text-muted">Property title deed document</small>
                              <div class="invalid-feedback"></div>
                              <div id="titleDeedPreview" class="mt-2"></div>
                            </div>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-4" id="marriageCertificateCard" style="display: none;">
                          <div class="card document-upload-card">
                            <div class="card-body">
                              <h6 class="card-title">
                                <i class="fas fa-ring me-2"></i>
                                Marriage Certificate
                              </h6>
                              <input type="file" class="form-control mb-2" id="marriageCertificateDoc" name="marriageCertificateDoc" accept=".pdf,.jpg,.jpeg,.png">
                              <small class="text-muted">Required if married</small>
                              <div class="invalid-feedback"></div>
                              <div id="marriageCertificateDocPreview" class="mt-2"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mb-4">
                        <h5 class="text-secondary">
                          <i class="fas fa-plus-circle me-2"></i>
                          Additional Documents (Optional)
                        </h5>
                        <div id="additionalDocuments"></div>
                        <button type="button" class="btn btn-outline-primary" id="addAdditionalDocument">
                          <i class="fas fa-plus me-2"></i>Add Additional Document
                        </button>
                      </div>
                    </div>

                    <!-- Step 8: Property Images/Video -->
                    <div class="form-step" id="step-8">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">8</span>
                          <i class="fas fa-camera me-2"></i>
                          Property Images/Video
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Upload high-quality images and videos of your property to attract potential buyers. 
                        We recommend including exterior, interior, and key feature shots.
                      </div>
                      
                      <div class="mb-4">
                        <h5 class="text-secondary">
                          <i class="fas fa-images me-2"></i>
                          Property Images <span class="text-danger">*</span>
                        </h5>
                        <div class="upload-zone" id="imageUploadZone">
                          <div class="upload-zone-content">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                            <h6>Drag & Drop Images Here</h6>
                            <p class="text-muted">or click to select files</p>
                            <input type="file" class="form-control d-none" id="propertyImages" name="propertyImages" multiple accept="image/*" required>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('propertyImages').click()">
                              Choose Images
                            </button>
                          </div>
                        </div>
                        <small class="form-text text-muted">
                          Supported formats: JPG, PNG, WebP. Maximum 20 images, 10MB each.
                        </small>
                        <div class="invalid-feedback"></div>
                        <div id="imagePreviewContainer" class="mt-3 row"></div>
                      </div>
                      
                      <div class="mb-4">
                        <h5 class="text-secondary">
                          <i class="fas fa-video me-2"></i>
                          Property Videos (Optional)
                        </h5>
                        <div class="upload-zone" id="videoUploadZone">
                          <div class="upload-zone-content">
                            <i class="fas fa-video fa-3x mb-3"></i>
                            <h6>Drag & Drop Videos Here</h6>
                            <p class="text-muted">or click to select files</p>
                            <input type="file" class="form-control d-none" id="propertyVideos" name="propertyVideos" multiple accept="video/*">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('propertyVideos').click()">
                              Choose Videos
                            </button>
                          </div>
                        </div>
                        <small class="form-text text-muted">
                          Supported formats: MP4, MOV, AVI. Maximum 5 videos, 100MB each.
                        </small>
                        <div id="videoPreviewContainer" class="mt-3 row"></div>
                      </div>
                    </div>

                    <!-- Step 9: Acknowledgment and Declaration -->
                    <div class="form-step" id="step-9">
                      <div class="step-header">
                        <h4 class="d-flex align-items-center">
                          <span class="badge bg-primary me-2">9</span>
                          <i class="fas fa-check-circle me-2"></i>
                          Acknowledgment and Declaration
                        </h4>
                        <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                      </div>
                      
                      <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please carefully read and accept all declarations before submitting your application.
                      </div>
                      
                      <div class="mb-4">
                        <h5 class="mb-3">Declarations</h5>
                        
                        <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" id="certificationDeclaration" name="certificationDeclaration" required>
                          <label class="form-check-label" for="certificationDeclaration">
                            <strong>Certification:</strong> I certify that all information provided in this application is true, complete, and accurate to the best of my knowledge.
                          </label>
                          <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" id="authorizationDeclaration" name="authorizationDeclaration" required>
                          <label class="form-check-label" for="authorizationDeclaration">
                            <strong>Authorization:</strong> I authorize Nuru Real Estate to verify the information provided and to contact me regarding this application.
                          </label>
                          <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" id="indemnificationDeclaration" name="indemnificationDeclaration" required>
                          <label class="form-check-label" for="indemnificationDeclaration">
                            <strong>Indemnification:</strong> I understand that providing false information may result in rejection of this application and potential legal consequences.
                          </label>
                          <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" id="commissionFeesDeclaration" name="commissionFeesDeclaration" required>
                          <label class="form-check-label" for="commissionFeesDeclaration">
                            <strong>Commission Fees:</strong> I acknowledge and agree to the commission fees structure as outlined in the seller agreement.
                          </label>
                          <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="form-check mb-3">
                          <input class="form-check-input" type="checkbox" id="propertyRightsDeclaration" name="propertyRightsDeclaration" required>
                          <label class="form-check-label" for="propertyRightsDeclaration">
                            <strong>Property Rights:</strong> I confirm that I have the legal right to sell the property described in this application.
                          </label>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <!-- Signature -->
                      <div class="mb-4">
                        <h5 class="mb-3">Digital Signature</h5>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                            <label for="signatureLocation" class="form-label required">Signature Location</label>
                            <input type="text" class="form-control" id="signatureLocation" name="signatureLocation" required>
                            <div class="invalid-feedback"></div>
                          </div>
                          <div class="col-md-6 mb-3">
                            <label for="signatureDate" class="form-label required">Signature Date</label>
                            <input type="date" class="form-control" id="signatureDate" name="signatureDate" required readonly>
                            <div class="invalid-feedback"></div>
                          </div>
                        </div>
                        
                        <div class="mb-3">
                          <label class="form-label required">Signature Method</label>
                          <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                              <div class="card signature-method-card" data-method="upload">
                                <div class="card-body text-center">
                                  <i class="fas fa-upload fa-2x mb-2"></i>
                                  <h6>Upload Signature</h6>
                                  <p class="text-muted small">Upload an image of your signature</p>
                                  <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input" type="radio" name="signatureType" id="signatureUpload" value="upload">
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6 mb-3">
                              <div class="card signature-method-card" data-method="otp">
                                <div class="card-body text-center">
                                  <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                                  <h6>SMS Verification</h6>
                                  <p class="text-muted small">Not available yet — use a drawn or uploaded signature</p>
                                  <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input" type="radio" name="signatureType" id="signatureOTP" value="otp" disabled>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="invalid-feedback d-block"></div>
                        </div>
                        
                        <div id="signatureUploadContainer" class="d-none mb-3">
                          <label for="signatureFile" class="form-label">Upload Signature Image</label>
                          <input type="file" class="form-control" id="signatureFile" name="signatureFile" accept="image/*">
                          <small class="form-text text-muted">Supported formats: JPG, PNG (Max 5MB)</small>
                          <div class="invalid-feedback"></div>
                          <div id="signaturePreview" class="mt-2"></div>
                        </div>
                        
                        <div id="otpContainer" class="d-none mb-3">
                          <label for="otpNumber" class="form-label">Enter OTP</label>
                          <div class="input-group">
                            <input type="text" class="form-control" id="otpNumber" name="otpNumber" maxlength="6">
                            <button type="button" class="btn btn-outline-primary" id="sendOTP">Send OTP</button>
                          </div>
                          <small class="form-text text-muted">SMS verification is not available for this application.</small>
                          <div class="invalid-feedback"></div>
                        </div>
                      </div>
                      
                      <!-- Final Submit -->
                      <div class="text-center">
                        <div class="alert alert-success">
                          <i class="fas fa-check-circle me-2"></i>
                          You're ready to submit your seller application!
                        </div>
                        <button type="submit" class="btn btn-success btn-lg">
                          <i class="fas fa-paper-plane me-2"></i>
                          Submit Application
                        </button>
                      </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top" id="navigationButtons">
                      <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
                        <i class="fas fa-arrow-left me-2"></i>Previous
                      </button>
                      <div class="text-center">
                        <span class="text-muted me-3">Step <span id="currentStepNumber">1</span> of 9</span>
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
              <i class="fas fa-home fa-4x text-success mb-3"></i>
              <h4>Congratulations!</h4>
              <p class="lead">Your seller application has been submitted successfully.</p>
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
                You will receive a confirmation email within the next few minutes.
              </p>
            </div>
          </div>
          <div class="modal-footer border-0 justify-content-center">
            <button type="button" class="btn btn-primary" onclick="window.location.href='<?= $baseUrl ?>/html/material/<?= currentRole() === 'agent_coordinator' ? 'dashboard_3.php' : (currentRole() === 'manager' ? 'dashboard_2.php' : 'admin.php') ?>'">
              <i class="fas fa-home me-2"></i>
              Return to Dashboard
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

    <!-- Form Data Configuration - renamed to avoid conflict with native FormData -->
    <script>
    // Absolute so the processor endpoint resolves correctly regardless of
    // whether this page was reached via the clean /admin/seller-admin-form
    // route or the legacy html/material/seller_admin_form.php URL.
    const nuruSellerAdminFormBaseUrl = <?= json_encode($baseUrl) ?>;
    // Form Data - Dropdowns and data management for Seller Form
    const AppFormData = {
        // Canonical data, shared with every other form - see
        // assets/js/nuru-regions.js (loaded before this script block).
        townsByRegion: window.NURU_TOWNS_BY_REGION,
        nationalities: [
            'Namibian', 'South African', 'Angolan', 'Zambian', 'Zimbabwean', 'Botswanan',
            'British', 'German', 'Dutch', 'Portuguese', 'American', 'Canadian', 'Australian',
            'Indian', 'Chinese', 'Other'
        ],
        houseTypes: [
            '1 Bedroom', '2 Bedroom', '3 Bedroom', '4 Bedroom', '5+ Bedroom',
            'Studio', 'Apartment', 'Townhouse', 'Villa', 'Duplex'
        ],
        
        init() {
            this.populateNationalities();
            this.populateRegions();
            this.setupFormDataBinding();
        },
        
        populateNationalities() {
            const nationalitySelects = document.querySelectorAll('#nationality, #spouseNationality');
            nationalitySelects.forEach(select => {
                const placeholder = select.querySelector('option[value=""]');
                select.innerHTML = '';
                if (placeholder) select.appendChild(placeholder);
                this.nationalities.forEach(nationality => {
                    const option = document.createElement('option');
                    option.value = nationality;
                    option.textContent = nationality;
                    select.appendChild(option);
                });
            });
        },
        
        populateRegions() {
            const regionSelects = document.querySelectorAll('#region, #nokRegion, #propertyRegion');
            regionSelects.forEach(select => {
                const placeholder = select.querySelector('option[value=""]');
                select.innerHTML = '';
                if (placeholder) select.appendChild(placeholder);
                Object.keys(this.townsByRegion).forEach(region => {
                    const option = document.createElement('option');
                    option.value = region;
                    option.textContent = region;
                    select.appendChild(option);
                });
            });
        },
        
        populateTowns(regionValue, townSelectId) {
            const townSelect = document.getElementById(townSelectId);
            if (!townSelect || !regionValue) return;
            townSelect.innerHTML = '<option value="">Select Town</option>';
            const towns = this.townsByRegion[regionValue] || [];
            towns.forEach(town => {
                const option = document.createElement('option');
                option.value = town;
                option.textContent = town;
                townSelect.appendChild(option);
            });
        },

        // By-reference region/town population for dynamically-created selects
        populateRegionsInto(selectEl) {
            if (!selectEl) return;
            selectEl.innerHTML = '<option value="">Select Region</option>';
            Object.keys(this.townsByRegion).forEach(region => {
                const option = document.createElement('option');
                option.value = region;
                option.textContent = region;
                selectEl.appendChild(option);
            });
        },

        populateTownsInto(regionValue, selectEl) {
            if (!selectEl) return;
            selectEl.innerHTML = '<option value="">Select Town</option>';
            const towns = this.townsByRegion[regionValue] || [];
            towns.forEach(town => {
                const option = document.createElement('option');
                option.value = town;
                option.textContent = town;
                selectEl.appendChild(option);
            });
        },
        
        setupFormDataBinding() {
            const form = document.getElementById('sellerApplicationForm');
            if (!form) return;
            form.addEventListener('change', () => this.saveFormData());
            form.addEventListener('input', () => {
                clearTimeout(this.saveTimeout);
                this.saveTimeout = setTimeout(() => this.saveFormData(), 1000);
            });
        },
        
        saveFormData() {
            const form = document.getElementById('sellerApplicationForm');
            if (!form) return;
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (data[key]) {
                    if (Array.isArray(data[key])) data[key].push(value);
                    else data[key] = [data[key], value];
                } else {
                    data[key] = value;
                }
            }
            localStorage.setItem('sellerApplicationData', JSON.stringify(data));
        },
        
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        isValidNamibianId(idNumber) {
            return /^\d{11}$/.test(idNumber);
        },
        
        isValidPassport(passportNumber) {
            return /^[A-Z0-9]{6,9}$/i.test(passportNumber);
        },
        
        calculateAge(dateOfBirth) {
            if (!dateOfBirth) return 0;
            const today = new Date();
            const birthDate = new Date(dateOfBirth);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) age--;
            return age;
        },
        
        getCurrentDate() {
            return new Date().toISOString().split('T')[0];
        },
        
        formatPhoneNumber(phone) {
            if (!phone) return '';
            const cleaned = phone.replace(/\D/g, '');
            if (cleaned.startsWith('264')) {
                return `+${cleaned.slice(0, 3)} ${cleaned.slice(3, 5)} ${cleaned.slice(5)}`;
            } else if (cleaned.startsWith('0')) {
                return `${cleaned.slice(0, 3)} ${cleaned.slice(3)}`;
            } else if (cleaned.length >= 8) {
                return `${cleaned.slice(0, 2)} ${cleaned.slice(2)}`;
            }
            return phone;
        },
        
        clearFormData() {
            localStorage.removeItem('sellerApplicationData');
            this.currentData = {};
            document.getElementById('sellerApplicationForm').reset();
        }
    };

    // Form Validation
    const FormValidation = {
        validationRules: {
            surname: { required: true, minLength: 2, pattern: /^[a-zA-Z\s'-]+$/ },
            firstName: { required: true, minLength: 2, pattern: /^[a-zA-Z\s'-]+$/ },
            dateOfBirth: { required: true, type: 'date', minAge: 18, maxAge: 120 },
            idType: { required: true },
            idNumber: { required: true, custom: 'validateIdNumber' },
            nationality: { required: true },
            gender: { required: true },
            maritalStatus: { required: true },
            streetName: { required: true, minLength: 3 },
            region: { required: true },
            town: { required: true },
            email: { required: true, type: 'email' },
            mobileNumber: { required: true, custom: 'validatePhoneNumber' },
            nokSurname: { required: true, minLength: 2, pattern: /^[a-zA-Z\s'-]+$/ },
            nokFirstName: { required: true, minLength: 2, pattern: /^[a-zA-Z\s'-]+$/ },
            nokContactNumber: { required: true, custom: 'validatePhoneNumber' },
            nokEmail: { required: true, type: 'email' },
            nokStreetName: { required: true, minLength: 3 },
            nokRegion: { required: true },
            nokTown: { required: true },
            saleType: { required: true },
            propertyDetailType: { required: true },
            landType: { required: true },
            landSize: { required: true, type: 'number', min: 1 },
            sellingPrice: { required: true, custom: 'validateCurrency' },
            propertyStreetName: { required: true, minLength: 3 },
            propertyRegion: { required: true },
            propertyTown: { required: true },
            propertyImages: { required: true, type: 'file', multiple: true },
            certificationDeclaration: { required: true, type: 'checkbox' },
            authorizationDeclaration: { required: true, type: 'checkbox' },
            indemnificationDeclaration: { required: true, type: 'checkbox' },
            commissionFeesDeclaration: { required: true, type: 'checkbox' },
            propertyRightsDeclaration: { required: true, type: 'checkbox' },
            signatureLocation: { required: true, minLength: 2 },
            signatureDate: { required: true, type: 'date' },
            signatureType: { required: true }
        },
        errorMessages: {
            required: 'This field is required',
            minLength: 'Must be at least {min} characters long',
            pattern: 'Please enter a valid value',
            email: 'Please enter a valid email address',
            minAge: 'Must be at least {min} years old',
            phone: 'Please enter a valid Namibian phone number',
            currency: 'Please enter a valid amount',
            checkbox: 'You must agree to this declaration'
        },
        allowedFileTypes: {
            documents: ['pdf', 'jpg', 'jpeg', 'png'],
            images: ['jpg', 'jpeg', 'png', 'webp'],
            videos: ['mp4', 'mov', 'avi'],
            signature: ['jpg', 'jpeg', 'png']
        },
        fileSizeLimits: { documents: 10, images: 10, videos: 100, signature: 5 },
        
        init() {
            this.setupRealTimeValidation();
            this.setupFormSubmissionValidation();
            this.setupConditionalValidation();
        },
        
        setupRealTimeValidation() {
            const form = document.getElementById('sellerApplicationForm');
            if (!form) return;
            form.addEventListener('blur', (e) => {
                if (e.target.matches('input, select, textarea')) this.validateField(e.target);
            }, true);
            form.addEventListener('change', (e) => {
                if (e.target.matches('input[type="radio"], input[type="checkbox"], select')) {
                    this.validateField(e.target);
                }
            });
            form.addEventListener('input', (e) => {
                if (e.target.matches('input[type="text"], input[type="email"], input[type="tel"], textarea')) {
                    clearTimeout(e.target.validationTimeout);
                    e.target.validationTimeout = setTimeout(() => this.validateField(e.target), 500);
                }
            });
        },
        
        setupFormSubmissionValidation() {
            const form = document.getElementById('sellerApplicationForm');
            if (!form) return;
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (this.validateForm()) {
                    SellerForm.submitApplication();
                }
            });
        },
        
        setupConditionalValidation() {
            document.getElementById('maritalStatus')?.addEventListener('change', (e) => {
                const spouseSection = document.getElementById('spouseDetails');
                const marriageCertCard = document.getElementById('marriageCertificateCard');
                if (e.target.value === 'Married') {
                    spouseSection?.classList.remove('d-none');
                    marriageCertCard?.style.setProperty('display', 'block');
                    // Add spouse gender to validation rules when visible
                    FormValidation.validationRules.spouseGender = { required: true };
                } else {
                    spouseSection?.classList.add('d-none');
                    marriageCertCard?.style.setProperty('display', 'none');
                    // Remove spouse gender from validation rules when hidden
                    delete FormValidation.validationRules.spouseGender;
                }
            });
            
            document.querySelectorAll('input[name="saleType"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const developerSection = document.getElementById('propertyDeveloperDetails');
                    const isDevelopment = e.target.value === 'Property Development';
                    if (isDevelopment) {
                        developerSection?.classList.remove('d-none');
                    } else {
                        developerSection?.classList.add('d-none');
                        const developmentsContainer = document.getElementById('developmentsContainer');
                        if (developmentsContainer) {
                            developmentsContainer.innerHTML = '';
                        }
                    }
                    SellerForm.toggleIndividualPropertyStep(isDevelopment);
                });
            });
            
            document.getElementById('landType')?.addEventListener('change', (e) => {
                const existingPropertySection = document.getElementById('existingPropertyDetails');
                if (e.target.value === 'Existing Property') {
                    existingPropertySection?.classList.remove('d-none');
                } else {
                    existingPropertySection?.classList.add('d-none');
                }
            });
            
            document.querySelectorAll('input[name="signatureType"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const uploadContainer = document.getElementById('signatureUploadContainer');
                    const otpContainer = document.getElementById('otpContainer');
                    if (e.target.value === 'upload') {
                        uploadContainer?.classList.remove('d-none');
                        otpContainer?.classList.add('d-none');
                    } else if (e.target.value === 'otp') {
                        uploadContainer?.classList.add('d-none');
                        otpContainer?.classList.remove('d-none');
                    }
                });
            });
        },
        
        validateField(field) {
            const fieldName = field.name;
            const rule = this.validationRules[fieldName];
            if (!rule) return true;
            
            // Special handling for radio button groups
            if (field.type === 'radio' && rule.required) {
                const radioGroup = document.querySelectorAll(`input[name="${fieldName}"]`);
                if (radioGroup.length > 0) {
                    const isAnyChecked = Array.from(radioGroup).some(radio => radio.checked);
                    if (!isAnyChecked) {
                        // Show error on all radio buttons in the group
                        radioGroup.forEach(radio => {
                            this.displayFieldError(radio, this.errorMessages.required);
                        });
                        return false;
                    } else {
                        // Clear errors on all radio buttons in the group
                        radioGroup.forEach(radio => {
                            this.clearFieldValidation(radio);
                        });
                        return true;
                    }
                }
            }
            
            const value = this.getFieldValue(field);
            const errors = [];
            
            if (rule.required && this.isEmpty(value)) {
                errors.push(this.errorMessages.required);
            }
            
            if (this.isEmpty(value) && !rule.required) {
                this.clearFieldValidation(field);
                return true;
            }
            
            if (rule.type === 'email' && !AppFormData.isValidEmail(value)) {
                errors.push(this.errorMessages.email);
            }
            
            if (rule.minLength && value.length < rule.minLength) {
                errors.push(this.errorMessages.minLength.replace('{min}', rule.minLength));
            }
            
            if (rule.pattern && !rule.pattern.test(value)) {
                errors.push(this.errorMessages.pattern);
            }
            
            if (rule.custom) {
                const customError = this[rule.custom](field, value);
                if (customError) errors.push(customError);
            }
            
            if (rule.minAge || rule.maxAge) {
                const age = AppFormData.calculateAge(value);
                if (rule.minAge && age < rule.minAge) {
                    errors.push(this.errorMessages.minAge.replace('{min}', rule.minAge));
                }
            }
            
            if (rule.type === 'checkbox' && !field.checked) {
                errors.push(this.errorMessages.checkbox);
            }
            
            if (errors.length > 0) {
                this.displayFieldError(field, errors[0]);
                return false;
            } else {
                this.displayFieldSuccess(field);
                return true;
            }
        },
        
        validateIdNumber(field, value) {
            const idType = document.getElementById('idType')?.value;
            if (idType === 'National ID') {
                return AppFormData.isValidNamibianId(value) ? null : 'Please enter a valid 11-digit ID number';
            } else if (idType === 'Passport') {
                return AppFormData.isValidPassport(value) ? null : 'Please enter a valid passport number';
            }
            return null;
        },
        
        validatePhoneNumber(field, value) {
            const cleaned = value.replace(/\D/g, '');
            const patterns = [/^264\d{8,9}$/, /^0\d{8,9}$/, /^\d{8,9}$/];
            const isValid = patterns.some(pattern => pattern.test(cleaned));
            return isValid ? null : this.errorMessages.phone;
        },
        
        validateCurrency(field, value) {
            const numValue = parseFloat(value.replace(/[^\d.]/g, ''));
            if (isNaN(numValue) || numValue <= 0) {
                return this.errorMessages.currency;
            }
            return null;
        },
        
        getFieldValue(field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked ? field.value : '';
            }
            return field.value.trim();
        },
        
        isEmpty(value) {
            return value === null || value === undefined || value === '';
        },
        
        displayFieldError(field, message) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            
            // For radio buttons, find the feedback element in the parent container
            if (field.type === 'radio') {
                const parent = field.closest('.mb-4') || field.closest('.mb-3');
                if (parent) {
                    const feedback = parent.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = message;
                        feedback.style.display = 'block';
                    }
                }
            } else {
                const feedback = field.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = message;
                    feedback.style.display = 'block';
                }
            }
        },
        
        displayFieldSuccess(field) {
            field.classList.add('is-valid');
            field.classList.remove('is-invalid');
            
            // For radio buttons, find the feedback element in the parent container
            if (field.type === 'radio') {
                const parent = field.closest('.mb-4') || field.closest('.mb-3');
                if (parent) {
                    const feedback = parent.querySelector('.invalid-feedback');
                    if (feedback) feedback.style.display = 'none';
                }
            } else {
                const feedback = field.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.style.display = 'none';
            }
        },
        
        clearFieldValidation(field) {
            field.classList.remove('is-valid', 'is-invalid');
            
            // For radio buttons, find the feedback element in the parent container
            if (field.type === 'radio') {
                const parent = field.closest('.mb-4') || field.closest('.mb-3');
                if (parent) {
                    const feedback = parent.querySelector('.invalid-feedback');
                    if (feedback) feedback.style.display = 'none';
                }
            } else {
                const feedback = field.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.style.display = 'none';
            }
        },
        
        validateForm() {
            const form = document.getElementById('sellerApplicationForm');
            if (!form) return false;
            let isValid = true;
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                // See validateStep() - #individualPropertyDetails is hidden/cleared
                // for Property Development sale type, but has no conditional rule.
                if (field.closest('#individualPropertyDetails.d-none')) {
                    return;
                }
                if (!this.validateField(field)) isValid = false;
            });
            form.classList.add('was-validated');
            return isValid;
        },
        
        validateStep(stepNumber) {
            const step = document.getElementById(`step-${stepNumber}`);
            if (!step) return true;
            let isValid = true;
            const fields = step.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                // #individualPropertyDetails (step 6) is hidden and cleared by
                // SellerForm.toggleIndividualPropertyStep() when saleType is
                // Property Development, but this.validationRules has no
                // conditional mechanism - without this check these fields
                // stayed unconditionally required and permanently blocked
                // every Property Development submission from passing step 6.
                if (field.closest('#individualPropertyDetails.d-none')) {
                    return;
                }
                if (!this.validateField(field)) isValid = false;
            });
            return isValid;
        }
    };

    // Form Stepper
    const FormStepper = {
        currentStep: 1,
        totalSteps: 9,
        completedSteps: new Set(),
        steps: [
            { id: 1, title: 'Personal', icon: 'fas fa-user' },
            { id: 2, title: 'Marital', icon: 'fas fa-heart' },
            { id: 3, title: 'Address', icon: 'fas fa-map-marker-alt' },
            { id: 4, title: 'Next of Kin', icon: 'fas fa-users' },
            { id: 5, title: 'Sale Type', icon: 'fas fa-building' },
            { id: 6, title: 'Property', icon: 'fas fa-home' },
            { id: 7, title: 'Documents', icon: 'fas fa-file-upload' },
            { id: 8, title: 'Images', icon: 'fas fa-camera' },
            { id: 9, title: 'Declaration', icon: 'fas fa-check-circle' }
        ],
        
        init() {
            this.createStepper();
            this.setupNavigation();
            this.updateProgress();
        },
        
        createStepper() {
            const stepperContainer = document.getElementById('formStepper');
            if (!stepperContainer) return;
            stepperContainer.innerHTML = '';
            this.steps.forEach((step, index) => {
                const stepElement = document.createElement('div');
                stepElement.className = 'stepper-item-horizontal';
                stepElement.dataset.step = step.id;
                if (step.id === this.currentStep) stepElement.classList.add('active');
                if (this.completedSteps.has(step.id)) stepElement.classList.add('completed');
                stepElement.innerHTML = `
                    <div class="step-icon-horizontal">
                        ${this.completedSteps.has(step.id) ? '<i class="fas fa-check"></i>' : `<i class="${step.icon}"></i>`}
                    </div>
                    <div class="step-content-horizontal">
                        <div class="step-title-horizontal">${step.title}</div>
                    </div>
                `;
                stepElement.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetStep = parseInt(step.id);
                    if (this.canNavigateToStep(targetStep)) {
                        this.goToStep(targetStep);
                    }
                });
                stepperContainer.appendChild(stepElement);
            });
        },
        
        setupNavigation() {
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            if (nextBtn) nextBtn.addEventListener('click', () => this.nextStep());
            if (prevBtn) prevBtn.addEventListener('click', () => this.previousStep());
            this.updateNavigationVisibility();
        },
        
        updateNavigationVisibility() {
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const navigationContainer = document.getElementById('navigationButtons');
            if (this.currentStep === this.totalSteps) {
                if (navigationContainer) navigationContainer.style.display = 'none';
            } else {
                if (navigationContainer) navigationContainer.style.display = 'flex';
                if (prevBtn) prevBtn.disabled = this.currentStep === 1;
            }
        },
        
        canNavigateToStep(stepNumber) {
            return stepNumber === this.currentStep || 
                   this.completedSteps.has(stepNumber) || 
                   (stepNumber === this.currentStep + 1 && FormValidation.validateStep(this.currentStep));
        },
        
        goToStep(stepNumber) {
            if (!this.canNavigateToStep(stepNumber)) return false;
            this.hideCurrentStep();
            const previousStep = this.currentStep;
            this.currentStep = stepNumber;
            this.showCurrentStep(stepNumber > previousStep ? 'right' : 'left');
            this.updateStepper();
            this.updateProgress();
            this.updateNavigationVisibility();
            this.updateStepNumbers();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return true;
        },
        
        nextStep() {
            if (this.currentStep >= this.totalSteps) return false;
            if (!FormValidation.validateStep(this.currentStep)) return false;

            // Step 5 (Sale Type Selection): validateStep() doesn't cover the
            // dynamically-created Developments/House Types fields, so validate
            // them separately when that path is active.
            if (this.currentStep === 5) {
                const saleType = document.querySelector('input[name="saleType"]:checked')?.value;
                if (saleType === 'Property Development' && !SellerForm.validateDevelopmentsSection()) {
                    return false;
                }
            }

            this.markStepComplete(this.currentStep);
            return this.goToStep(this.currentStep + 1);
        },
        
        previousStep() {
            if (this.currentStep <= 1) return false;
            return this.goToStep(this.currentStep - 1);
        },
        
        markStepComplete(stepNumber) {
            this.completedSteps.add(stepNumber);
            this.updateStepper();
            this.updateProgress();
        },
        
        hideCurrentStep() {
            const currentStepElement = document.getElementById(`step-${this.currentStep}`);
            if (currentStepElement) {
                currentStepElement.classList.remove('active', 'slide-in-right', 'slide-in-left');
            }
        },
        
        showCurrentStep(direction = 'right') {
            const newStepElement = document.getElementById(`step-${this.currentStep}`);
            if (newStepElement) {
                newStepElement.classList.add('active');
                setTimeout(() => {
                    newStepElement.classList.add(direction === 'right' ? 'slide-in-right' : 'slide-in-left');
                }, 50);
            }
        },
        
        updateStepper() {
            const stepperItems = document.querySelectorAll('.stepper-item-horizontal');
            stepperItems.forEach((item, index) => {
                const stepNumber = index + 1;
                const stepIcon = item.querySelector('.step-icon-horizontal');
                item.classList.remove('active', 'completed');
                if (stepNumber === this.currentStep) {
                    item.classList.add('active');
                } else if (this.completedSteps.has(stepNumber)) {
                    item.classList.add('completed');
                    if (stepIcon) stepIcon.innerHTML = '<i class="fas fa-check"></i>';
                }
                if (!this.completedSteps.has(stepNumber) && stepIcon) {
                    stepIcon.innerHTML = `<i class="${this.steps[index].icon}"></i>`;
                }
            });
        },
        
        updateProgress() {
            const progressPercentage = (this.currentStep / this.totalSteps) * 100;
            const progressBar = document.getElementById('headerProgressBar');
            if (progressBar) progressBar.style.width = progressPercentage + '%';
            const stepNumberElements = document.querySelectorAll('#currentStepNumber, #headerStepNumber');
            stepNumberElements.forEach(element => element.textContent = this.currentStep);
        },
        
        updateStepNumbers() {
            const stepNumberElements = document.querySelectorAll('#currentStepNumber, #headerStepNumber');
            stepNumberElements.forEach(element => element.textContent = this.currentStep);
        }
    };

    // Seller Form
    const SellerForm = {
        apiBaseUrl: '../api',
        csrfToken: null,
        additionalDocumentCount: 0,
        
        init() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) this.csrfToken = metaTag.getAttribute('content');
            this.bindEvents();
            this.setupInteractions();
            this.setupFileHandling();
            this.setupDynamicSections();
        },
        
        bindEvents() {
            const signatureDateInput = document.getElementById('signatureDate');
            if (signatureDateInput) signatureDateInput.value = AppFormData.getCurrentDate();
            this.setupPhoneFormatting();
            this.setupCurrencyFormatting();
            this.setupRegionTownDependencies();
            this.setupAgeCalculation();
        },
        
        setupPhoneFormatting() {
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    const formatted = AppFormData.formatPhoneNumber(e.target.value);
                    if (formatted !== e.target.value) e.target.value = formatted;
                });
            });
        },
        
        setupCurrencyFormatting() {
            // Was previously round-tripping every keystroke through
            // parseFloat()+toLocaleString(), which strips a trailing "."
            // immediately - making it impossible to ever type cents.
            // Dynamically-added .money-input fields (house types) attach
            // their own formatting in addHouseType() when created.
            this.attachMoneyFormatting(document.getElementById('sellingPrice'));
        },
        
        setupRegionTownDependencies() {
            document.getElementById('region')?.addEventListener('change', (e) => {
                AppFormData.populateTowns(e.target.value, 'town');
            });
            document.getElementById('nokRegion')?.addEventListener('change', (e) => {
                AppFormData.populateTowns(e.target.value, 'nokTown');
            });
            document.getElementById('propertyRegion')?.addEventListener('change', (e) => {
                AppFormData.populateTowns(e.target.value, 'propertyTown');
            });
        },
        
        setupAgeCalculation() {
            const dobInput = document.getElementById('dateOfBirth');
            if (dobInput) {
                dobInput.addEventListener('change', (e) => {
                    const age = AppFormData.calculateAge(e.target.value);
                    let ageDisplay = document.getElementById('ageDisplay');
                    if (!ageDisplay) {
                        ageDisplay = document.createElement('div');
                        ageDisplay.id = 'ageDisplay';
                        dobInput.parentNode.appendChild(ageDisplay);
                    }
                    ageDisplay.textContent = `Age: ${age} years`;
                    ageDisplay.className = age >= 18 ? 'small text-success' : 'small text-danger';
                });
            }
        },
        
        setupInteractions() {
            const saleTypeCards = document.querySelectorAll('.sale-type-card');
            saleTypeCards.forEach(card => {
                card.addEventListener('click', () => {
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            document.querySelectorAll('input[name="saleType"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    saleTypeCards.forEach(card => {
                        card.classList.remove('selected');
                        if (card.dataset.type === e.target.value) card.classList.add('selected');
                    });
                });
            });
            
            const signatureMethodCards = document.querySelectorAll('.signature-method-card');
            signatureMethodCards.forEach(card => {
                card.addEventListener('click', () => {
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            document.getElementById('sendOTP')?.addEventListener('click', () => this.sendOTP());
        },
        
        setupFileHandling() {
            this.setupDragAndDrop();
            document.addEventListener('change', (e) => {
                if (e.target.type === 'file') this.handleFileSelection(e.target);
            });
        },
        
        setupDragAndDrop() {
            const uploadZones = document.querySelectorAll('.upload-zone');
            uploadZones.forEach(zone => {
                const fileInput = zone.querySelector('input[type="file"]');
                if (!fileInput) return;
                zone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    zone.classList.add('dragover');
                });
                zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
                zone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    zone.classList.remove('dragover');
                    const files = Array.from(e.dataTransfer.files);
                    this.handleDroppedFiles(fileInput, files);
                });
                zone.addEventListener('click', () => fileInput.click());
            });
        },
        
        handleFileSelection(fileInput) {
            const files = Array.from(fileInput.files);
            this.processFiles(fileInput, files);
        },
        
        handleDroppedFiles(fileInput, files) {
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            this.processFiles(fileInput, files);
        },
        
        processFiles(fileInput, files) {
            if (files.length === 0) return;
            const card = fileInput.closest('.document-upload-card');
            if (card && files.length > 0) card.classList.add('has-file');
            if (fileInput.id === 'propertyImages') this.handlePropertyImages(files);
            else if (fileInput.id === 'propertyVideos') this.handlePropertyVideos(files);
            else this.handleDocumentUpload(fileInput, files);
        },
        
        handlePropertyImages(files) {
            const container = document.getElementById('imagePreviewContainer');
            if (!container) return;
            container.innerHTML = '';
            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const preview = this.createImagePreview(file, index, 'propertyImages');
                    container.appendChild(preview);
                }
            });
        },
        
        handlePropertyVideos(files) {
            const container = document.getElementById('videoPreviewContainer');
            if (!container) return;
            container.innerHTML = '';
            files.forEach((file, index) => {
                if (file.type.startsWith('video/')) {
                    const preview = this.createVideoPreview(file, index, 'propertyVideos');
                    container.appendChild(preview);
                }
            });
        },
        
        handleDocumentUpload(fileInput, files) {
            const previewContainer = document.getElementById(fileInput.id + 'Preview');
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            files.forEach((file, index) => {
                const preview = this.createDocumentPreview(file, index, fileInput.id);
                previewContainer.appendChild(preview);
            });
        },
        
        createImagePreview(file, index, inputId) {
            const preview = document.createElement('div');
            preview.className = 'col-md-3 mb-3';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'img-fluid rounded';
            img.alt = file.name;
            preview.innerHTML = `
                <div class="position-relative">
                    ${img.outerHTML}
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" onclick="SellerForm.removeFile('${inputId}', ${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="small text-muted mt-1">${file.name}</div>
                </div>
            `;
            return preview;
        },
        
        createVideoPreview(file, index, inputId) {
            const preview = document.createElement('div');
            preview.className = 'col-md-4 mb-3';
            const video = document.createElement('video');
            video.src = URL.createObjectURL(file);
            video.className = 'w-100 rounded';
            video.controls = true;
            preview.innerHTML = `
                <div class="position-relative">
                    ${video.outerHTML}
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" onclick="SellerForm.removeFile('${inputId}', ${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="small text-muted mt-1">${file.name}</div>
                </div>
            `;
            return preview;
        },
        
        createDocumentPreview(file, index, inputId) {
            const preview = document.createElement('div');
            preview.className = 'file-preview d-flex align-items-center p-2 border rounded mb-2';
            const icon = file.type.includes('pdf') ? 'fa-file-pdf text-danger' : 
                        file.type.includes('image') ? 'fa-file-image text-primary' : 
                        'fa-file text-secondary';
            preview.innerHTML = `
                <i class="fas ${icon} fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold">${file.name}</div>
                    <small class="text-muted">${this.formatFileSize(file.size)}</small>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="SellerForm.removeFile('${inputId}', ${index})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            return preview;
        },
        
        removeFile(inputId, index) {
            const fileInput = document.getElementById(inputId);
            if (!fileInput) return;
            const dt = new DataTransfer();
            const files = Array.from(fileInput.files);
            files.forEach((file, i) => {
                if (i !== index) dt.items.add(file);
            });
            fileInput.files = dt.files;
            this.processFiles(fileInput, Array.from(fileInput.files));
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        setupDynamicSections() {
            document.getElementById('addAdditionalDocument')?.addEventListener('click', () => {
                this.addAdditionalDocument();
            });
            document.getElementById('addDevelopment')?.addEventListener('click', () => {
                this.addDevelopment();
            });
            const developmentsContainer = document.getElementById('developmentsContainer');
            if (developmentsContainer) {
                developmentsContainer.addEventListener('input', () => this.updateDevelopmentsUnlockState());
                developmentsContainer.addEventListener('change', () => this.updateDevelopmentsUnlockState());
            }
        },

        // Show/hide Step 6's single-property fields and toggle their `required`
        // attribute so FormValidation.validateStep(6) doesn't block on hidden
        // fields.
        toggleIndividualPropertyStep(isDevelopment) {
            const individualSection = document.getElementById('individualPropertyDetails');
            const skipMessage = document.getElementById('developmentPropertySkipMessage');
            if (!individualSection) return;

            if (isDevelopment) {
                individualSection.classList.add('d-none');
                skipMessage?.classList.remove('d-none');
                individualSection.querySelectorAll('[required]').forEach(field => {
                    field.dataset.wasRequired = 'true';
                    field.removeAttribute('required');
                    field.value = '';
                    FormValidation.clearFieldValidation(field);
                });
            } else {
                individualSection.classList.remove('d-none');
                skipMessage?.classList.add('d-none');
                individualSection.querySelectorAll('[data-was-required="true"]').forEach(field => {
                    field.setAttribute('required', 'required');
                });
            }
        },

        // Add a new Development block (Development 1, 2, 3...)
        addDevelopment() {
            const container = document.getElementById('developmentsContainer');
            if (!container) return;

            const devIndex = container.children.length + 1;

            const devBlock = document.createElement('div');
            devBlock.className = 'card mb-3 development-block';
            devBlock.dataset.devIndex = devIndex;
            devBlock.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 development-label">Development ${devIndex}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-development" onclick="SellerForm.removeDevelopment(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Development Name</label>
                            <input type="text" class="form-control" name="devName" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Region</label>
                            <select class="form-select" name="devRegion" required>
                                <option value="">Select Region</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Town</label>
                            <select class="form-select" name="devTown" required>
                                <option value="">Select Town</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="devLocation">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Suburb</label>
                            <input type="text" class="form-control" name="devSuburb">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <h6 class="mt-3 mb-2">House Types</h6>
                    <div class="house-types-list"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm add-house-type" disabled>
                        <i class="fas fa-plus me-2"></i>Add House Type
                    </button>
                </div>
            `;

            container.appendChild(devBlock);

            const regionSelect = devBlock.querySelector('[name="devRegion"]');
            const townSelect = devBlock.querySelector('[name="devTown"]');
            AppFormData.populateRegionsInto(regionSelect);
            regionSelect.addEventListener('change', () => {
                AppFormData.populateTownsInto(regionSelect.value, townSelect);
            });

            const addHouseTypeButton = devBlock.querySelector('.add-house-type');
            addHouseTypeButton.addEventListener('click', () => {
                this.addHouseType(devBlock);
            });

            this.addHouseType(devBlock);
            this.updateDevelopmentsUnlockState();
        },

        // Add a new House Type block within a Development (House Type 1, 2, 3...)
        addHouseType(devBlock) {
            const list = devBlock.querySelector('.house-types-list');
            if (!list) return;

            const htIndex = list.children.length + 1;
            const developmentPropertyTypes = [
                'Free Standing House Unit',
                'General Residential House Unit',
                'Business/Commercial Property',
                'Farm Property',
                'Institutional Property'
            ];

            const htBlock = document.createElement('div');
            htBlock.className = 'card mb-2 house-type-block bg-light';
            htBlock.dataset.houseIndex = htIndex;
            htBlock.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="house-type-label">House Type ${htIndex}</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-house-type" onclick="SellerForm.removeHouseType(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label required">Property Zooning Status</label>
                            <select class="form-select" name="htPropertyType" required>
                                <option value="">Select Property Zooning Status</option>
                                ${developmentPropertyTypes.map(type => `<option value="${type}">${type}</option>`).join('')}
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label required">Number of House Units</label>
                            <input type="number" class="form-control" name="htUnits" min="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label required">Property Type</label>
                            <select class="form-select" name="htLandType" required>
                                <option value="">Select Property Type</option>
                                <option value="Vacant Land">Vacant Land</option>
                                <option value="Existing Property">Existing Property</option>
                                <option value="Plot and Plan">Plot and Plan</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label required">Land Size</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="htLandSize" required>
                                <span class="input-group-text">m&sup2;</span>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label required">House Size</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="htHouseSize" required>
                                <span class="input-group-text">m&sup2;</span>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label required">Selling Price</label>
                            <div class="input-group">
                                <span class="input-group-text">N$</span>
                                <input type="text" class="form-control money-input" name="htSellingPrice" inputmode="decimal" required>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label required">No. of Rooms</label>
                            <input type="number" class="form-control" name="htRooms" min="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label required">No. of Bathrooms</label>
                            <input type="number" class="form-control" name="htBathrooms" min="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label required">Additional Features</label>
                        <textarea class="form-control" name="htAdditionalFeatures" rows="2" required></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            `;

            list.appendChild(htBlock);
            this.attachMoneyFormatting(htBlock.querySelector('.money-input'));
            this.updateDevelopmentsUnlockState();
        },

        // Remove a house type, but never the last remaining one in its development
        removeHouseType(button) {
            const htBlock = button.closest('.house-type-block');
            const list = htBlock.closest('.house-types-list');
            if (list.querySelectorAll('.house-type-block').length <= 1) {
                return;
            }
            htBlock.remove();
            this.renumberHouseTypes(list.closest('.development-block'));
            this.updateDevelopmentsUnlockState();
        },

        // Remove a development, but never the last remaining one
        removeDevelopment(button) {
            const devBlock = button.closest('.development-block');
            const container = document.getElementById('developmentsContainer');
            if (container.querySelectorAll(':scope > .development-block').length <= 1) {
                return;
            }
            devBlock.remove();
            this.renumberDevelopments();
            this.updateDevelopmentsUnlockState();
        },

        renumberHouseTypes(devBlock) {
            devBlock.querySelectorAll('.house-type-block').forEach((block, i) => {
                block.dataset.houseIndex = i + 1;
                const label = block.querySelector('.house-type-label');
                if (label) label.textContent = `House Type ${i + 1}`;
            });
        },

        renumberDevelopments() {
            document.querySelectorAll('#developmentsContainer > .development-block').forEach((block, i) => {
                block.dataset.devIndex = i + 1;
                const label = block.querySelector('.development-label');
                if (label) label.textContent = `Development ${i + 1}`;
            });
        },

        // Format a raw string as "456,960.78" - comma thousands separators added
        // automatically, decimal point typed manually, max 2 decimal digits
        formatMoneyValue(rawValue) {
            let value = rawValue.replace(/[^\d.]/g, '');
            const firstDot = value.indexOf('.');
            if (firstDot !== -1) {
                value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
            }
            let [intPart, decPart] = value.split('.');
            intPart = (intPart || '').replace(/^0+(?=\d)/, '');
            if (decPart !== undefined) {
                decPart = decPart.slice(0, 2);
            }
            const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (decPart !== undefined) {
                return `${withCommas}.${decPart}`;
            }
            return value.endsWith('.') ? `${withCommas}.` : withCommas;
        },

        attachMoneyFormatting(inputEl) {
            if (!inputEl) return;
            inputEl.addEventListener('input', (e) => {
                const input = e.target;
                const before = input.value;
                const cursorFromEnd = before.length - (input.selectionStart ?? before.length);
                const formatted = this.formatMoneyValue(before);
                input.value = formatted;
                const newPos = Math.max(0, formatted.length - cursorFromEnd);
                input.setSelectionRange(newPos, newPos);
            });
        },

        isHouseTypeBlockComplete(block) {
            const requiredFields = block.querySelectorAll('[required]');
            return Array.from(requiredFields).every(field => field.value.trim() !== '');
        },

        updateDevelopmentsUnlockState() {
            const container = document.getElementById('developmentsContainer');
            const addDevelopmentButton = document.getElementById('addDevelopment');
            if (!container || !addDevelopmentButton) return;

            const devBlocks = Array.from(container.querySelectorAll(':scope > .development-block'));

            devBlocks.forEach(devBlock => {
                const houseTypeBlocks = Array.from(devBlock.querySelectorAll('.house-type-block'));
                const addHouseTypeButton = devBlock.querySelector('.add-house-type');
                if (addHouseTypeButton) {
                    const lastHouseType = houseTypeBlocks[houseTypeBlocks.length - 1];
                    addHouseTypeButton.disabled = !lastHouseType || !this.isHouseTypeBlockComplete(lastHouseType);
                }

                const onlyHouseType = houseTypeBlocks.length <= 1;
                houseTypeBlocks.forEach(htBlock => {
                    const removeBtn = htBlock.querySelector('.remove-house-type');
                    if (removeBtn) removeBtn.disabled = onlyHouseType;
                });
            });

            const onlyDevelopment = devBlocks.length <= 1;
            devBlocks.forEach(devBlock => {
                const removeBtn = devBlock.querySelector('.remove-development');
                if (removeBtn) removeBtn.disabled = onlyDevelopment;
            });

            if (devBlocks.length === 0) {
                addDevelopmentButton.disabled = false;
            } else {
                const lastDev = devBlocks[devBlocks.length - 1];
                const firstHouseType = lastDev.querySelector('.house-type-block');
                addDevelopmentButton.disabled = !firstHouseType || !this.isHouseTypeBlockComplete(firstHouseType);
            }
        },

        collectDevelopmentsData() {
            const developments = [];
            document.querySelectorAll('#developmentsContainer > .development-block').forEach((devBlock) => {
                const dev = {
                    development_name: devBlock.querySelector('[name="devName"]')?.value || '',
                    region: devBlock.querySelector('[name="devRegion"]')?.value || '',
                    town: devBlock.querySelector('[name="devTown"]')?.value || '',
                    location: devBlock.querySelector('[name="devLocation"]')?.value || '',
                    suburb: devBlock.querySelector('[name="devSuburb"]')?.value || '',
                    house_types: []
                };
                devBlock.querySelectorAll('.house-type-block').forEach((htBlock) => {
                    dev.house_types.push({
                        property_type: htBlock.querySelector('[name="htPropertyType"]')?.value || '',
                        number_of_units: htBlock.querySelector('[name="htUnits"]')?.value || '',
                        land_type: htBlock.querySelector('[name="htLandType"]')?.value || '',
                        land_size: htBlock.querySelector('[name="htLandSize"]')?.value || '',
                        house_size: htBlock.querySelector('[name="htHouseSize"]')?.value || '',
                        selling_price: htBlock.querySelector('[name="htSellingPrice"]')?.value || '',
                        rooms: htBlock.querySelector('[name="htRooms"]')?.value || '',
                        bathrooms: htBlock.querySelector('[name="htBathrooms"]')?.value || '',
                        additional_features: htBlock.querySelector('[name="htAdditionalFeatures"]')?.value || ''
                    });
                });
                developments.push(dev);
            });
            return developments;
        },

        validateDevelopmentsSection() {
            const feedback = document.getElementById('developmentsFeedback');
            const devBlocks = document.querySelectorAll('#developmentsContainer > .development-block');

            if (devBlocks.length === 0) {
                if (feedback) {
                    feedback.textContent = 'Please add at least one Development.';
                    feedback.style.display = 'block';
                }
                return false;
            }

            let isValid = true;
            let firstInvalidField = null;

            devBlocks.forEach(devBlock => {
                const houseTypeBlocks = devBlock.querySelectorAll('.house-type-block');
                if (houseTypeBlocks.length === 0) {
                    isValid = false;
                }
                devBlock.querySelectorAll('[required]').forEach(field => {
                    if (field.value.trim() === '') {
                        isValid = false;
                        FormValidation.displayFieldError(field, FormValidation.errorMessages.required);
                        if (!firstInvalidField) firstInvalidField = field;
                    } else {
                        FormValidation.displayFieldSuccess(field);
                    }
                });
            });

            if (feedback) {
                feedback.textContent = isValid ? '' : 'Please complete all required Development and House Type fields.';
                feedback.style.display = isValid ? 'none' : 'block';
            }

            if (!isValid && firstInvalidField) {
                firstInvalidField.focus();
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            return isValid;
        },
        
        addAdditionalDocument() {
            this.additionalDocumentCount++;
            const container = document.getElementById('additionalDocuments');
            const docItem = document.createElement('div');
            docItem.className = 'additional-doc-item';
            docItem.innerHTML = `
                <button type="button" class="remove-additional-doc" onclick="this.closest('.additional-doc-item').remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Document Name</label>
                        <input type="text" class="form-control" name="additionalDocName[]" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Document File</label>
                        <input type="file" class="form-control" name="additionalDocFile[]" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>
            `;
            container.appendChild(docItem);
        },
        
        async sendOTP() {
            alert('SMS verification is not available. Please select a drawn or uploaded signature.');
        },
        
        async submitApplication() {
            try {
                if (!FormValidation.validateForm()) {
                    alert('Please fill in all required fields correctly.');
                    return;
                }
                
                this.showLoadingOverlay();
                
                // Collect form data (await since it handles file uploads)
                const formData = await this.collectFormData();
                
                // Submit to server
                const response = await fetch(nuruSellerAdminFormBaseUrl + '/admin/seller-admin-processor', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                this.hideLoadingOverlay();
                
                if (result.success) {
                    this.handleSubmissionSuccess(result.data);
                } else {
                    alert(result.error || 'An error occurred. Please try again.');
                }
            } catch (error) {
                console.error('Submission error:', error);
                this.hideLoadingOverlay();
                alert('An error occurred. Please try again.');
            }
        },
        
        async collectFormData() {
            const form = document.getElementById('sellerApplicationForm');
            const formData = new FormData(form);
            const data = {};
            
            // Collect regular form fields
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
            
            // Handle radio buttons that might not be in FormData if not checked
            const radioGroups = ['gender', 'saleType', 'signatureType'];
            radioGroups.forEach(name => {
                const checkedRadio = document.querySelector(`input[name="${name}"]:checked`);
                if (checkedRadio) {
                    data[name] = checkedRadio.value;
                }
            });
            
            // Handle spouse gender
            const spouseGenderRadio = document.querySelector('input[name="spouseGender"]:checked');
            if (spouseGenderRadio) {
                data.spouseGender = spouseGenderRadio.value;
            }
            
            // Handle conditional sections
            const maritalStatus = document.getElementById('maritalStatus')?.value;
            if (maritalStatus !== 'Married') {
                delete data.spouseSurname;
                delete data.spouseFirstName;
                delete data.spouseDateOfBirth;
                delete data.spouseIdType;
                delete data.spouseIdNumber;
                delete data.spouseNationality;
                delete data.spouseGender;
            }
            
            const landType = document.getElementById('landType')?.value;
            if (landType !== 'Existing Property') {
                delete data.houseSize;
                delete data.rooms;
                delete data.bathrooms;
                delete data.additionalFeatures;
            }
            
            const saleType = document.querySelector('input[name="saleType"]:checked')?.value;
            if (saleType === 'Property Development') {
                data.developments = this.collectDevelopmentsData();
            }
            
            const signatureType = document.querySelector('input[name="signatureType"]:checked')?.value;
            if (signatureType !== 'upload') {
                delete data.signatureFile;
            } else {
                // Handle signature file upload
                const signatureFileInput = document.getElementById('signatureFile');
                if (signatureFileInput && signatureFileInput.files[0]) {
                    data.signatureFile = await this.prepareFileForSubmission(signatureFileInput.files[0]);
                }
            }
            
            // Handle document uploads
            const documentFields = ['id_document', 'proof_of_residence', 'title_deed', 'marriageCertificate'];
            for (const fieldName of documentFields) {
                const fileInput = document.getElementById(fieldName);
                if (fileInput && fileInput.files[0]) {
                    data[fieldName] = await this.prepareFileForSubmission(fileInput.files[0], fieldName);
                }
            }
            
            // Handle property images
            const propertyImagesInput = document.getElementById('propertyImages');
            if (propertyImagesInput && propertyImagesInput.files.length > 0) {
                data.propertyImages = await this.prepareFilesForSubmission(Array.from(propertyImagesInput.files), 'propertyImages');
            }
            
            // Handle property videos
            const propertyVideosInput = document.getElementById('propertyVideos');
            if (propertyVideosInput && propertyVideosInput.files.length > 0) {
                data.propertyVideos = await this.prepareFilesForSubmission(Array.from(propertyVideosInput.files), 'propertyVideos');
            }
            
            // Handle additional documents
            const additionalDocNames = [];
            const additionalDocFiles = { files: [] };
            document.querySelectorAll('input[name="additionalDocName[]"]').forEach((input, index) => {
                additionalDocNames.push(input.value);
            });
            // Process additional doc files sequentially
            const additionalDocFileInputs = document.querySelectorAll('input[name="additionalDocFile[]"]');
            for (const input of additionalDocFileInputs) {
                if (input.files[0]) {
                    const fileData = await this.prepareFileForSubmission(input.files[0], 'additionalDoc');
                    additionalDocFiles.files.push(fileData.files[0]);
                }
            }
            if (additionalDocNames.length > 0) {
                data.additionalDocName = additionalDocNames;
                data.additionalDocFile = additionalDocFiles;
            }
            
            // Handle declarations - explicit id-to-key map (checkbox names end in
            // "Declaration", so a prefix-based selector never matches them; PHP
            // expects these exact keys: certification, authorization, indemnification,
            // commission, property_rights)
            const declarationFieldMap = {
                certificationDeclaration: 'certification',
                authorizationDeclaration: 'authorization',
                indemnificationDeclaration: 'indemnification',
                commissionFeesDeclaration: 'commission',
                propertyRightsDeclaration: 'property_rights'
            };
            const declarations = {};
            Object.keys(declarationFieldMap).forEach(checkboxId => {
                const checkbox = document.getElementById(checkboxId);
                declarations[declarationFieldMap[checkboxId]] = (checkbox && checkbox.checked) ? 'on' : '';
            });
            data.declarations = declarations;
            
            return data;
        },
        
        async prepareFileForSubmission(file, fieldName) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = function() {
                    const base64 = reader.result.split(',')[1];
                    resolve({
                        files: [{
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            content: base64
                        }]
                    });
                };
                reader.readAsDataURL(file);
            });
        },
        
        async prepareFilesForSubmission(files, fieldName) {
            const fileArray = { files: [] };
            for (const file of files) {
                const fileData = await this.prepareFileForSubmission(file, fieldName);
                fileArray.files.push(fileData.files[0]);
            }
            return fileArray;
        },
        
        handleSubmissionSuccess(result) {
            AppFormData.clearFormData();
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            const applicationNumber = document.getElementById('applicationNumber');
            if (applicationNumber && result.application_number) {
                applicationNumber.textContent = result.application_number;
            }
            modal.show();
        },
        
        showLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.remove('d-none');
        },
        
        hideLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.add('d-none');
        }
    };

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', function() {
        AppFormData.init();
        FormValidation.init();
        FormStepper.init();
        SellerForm.init();
    });
    </script>
  </body>
</html>

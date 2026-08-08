<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (
    isset($_SESSION['user_id'], $_SESSION['role'])
    && in_array($_SESSION['role'], ['admin', 'manager', 'agent_coordinator'], true)
) {
    header('Location: ../property_admin_form.php', true, 303);
    exit;
}
require_once __DIR__ . '/../../../app/autoload.php';
require_once __DIR__ . '/../api/security_integration.php';
require_once __DIR__ . '/../config/turnstile.php';
$csrfToken = SecurityIntegration::generateCSRFToken('seller_application_submit');
$baseUrl = \App\Core\Router::basePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Application Form - Nuru Real Estate</title>

    <!-- Bootstrap CSS -->
    <link href="<?= $baseUrl ?>/assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/html/material/seller/css/seller-form.css?v=<?= filemtime(__DIR__ . '/css/seller-form.css') ?>" rel="stylesheet">

    <!-- CSRF Token Meta -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">

    <!-- Cloudflare Turnstile -->
    <?php if (TURNSTILE_READY): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    <script>window.NURU_API_BASE = <?= json_encode($baseUrl . '/html/material/api') ?>;</script>
</head>
<body class="bg-white seller-form-body">
    <!-- Header -->
    <header class="bg-white py-3 mb-3 seller-form-header">
        <div class="container">
            <a class="seller-back-link" href="<?= $baseUrl ?>/login">
                <i class="fas fa-arrow-left me-1"></i>
                Back to login
            </a>
            <div class="row align-items-center mt-2">
                <div class="col-md-8">
                    <h1 class="h4 mb-2 text-primary">Seller Application</h1>
                    <div class="seller-mobile-progress d-md-none" aria-live="polite">
                        <span>Step <strong id="mobileStepNumber">1</strong> of 9</span>
                        <div class="progress mt-2" aria-label="Application progress">
                            <div class="progress-bar bg-primary" id="mobileProgressBar" style="width: 11.11%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <span class="badge bg-secondary fs-6">Step <span id="headerStepNumber">1</span> of 9</span>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar"
                             style="width: 11.11%;" id="headerProgressBar"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container pb-5">
        <!-- Progress Indicator (same simple pattern as the buyer form) -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-body py-3">
                        <div class="progress-container">
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar"
                                     style="width: 11.11%;" id="progressBar"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span class="step-label active" data-step="1">Personal</span>
                                <span class="step-label" data-step="2">Marital</span>
                                <span class="step-label" data-step="3">Address</span>
                                <span class="step-label" data-step="4">Next of Kin</span>
                                <span class="step-label" data-step="5">Sale Type</span>
                                <span class="step-label" data-step="6">Property</span>
                                <span class="step-label" data-step="7">Documents</span>
                                <span class="step-label" data-step="8">Media</span>
                                <span class="step-label" data-step="9">Declaration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Form Area -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <!-- Form -->
                        <form id="sellerApplicationForm" novalidate>
                            <!-- Step 1: Personal Details -->
                            <div class="form-step active" id="step-1">
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
                                        <i class="fas fa-user me-2"></i>
                                        Personal Details
                                    </h4>
                                    <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="surname" class="form-label required">Surname</label>
                                        <input type="text" class="form-control" id="surname" name="surname" maxlength="100" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="firstName" class="form-label required">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" maxlength="100" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="middleName" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middleName" name="middleName" maxlength="100">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="maidenName" class="form-label">Maiden Name</label>
                                        <input type="text" class="form-control" id="maidenName" name="maidenName" maxlength="100">
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
                                        <input type="text" class="form-control" id="idNumber" name="idNumber" maxlength="13" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nationality" class="form-label required">Nationality</label>
                                        <select class="form-select" id="nationality" name="nationality" required>
                                            <option value="">Select Nationality</option>
                                            <!-- Will be populated by JavaScript -->
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
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
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
                                
                                <!-- Spouse Details (shown when married) -->
                                <div id="spouseDetails" class="d-none">
                                    <h5 class="mb-3 text-secondary">
                                        <i class="fas fa-user-friends me-2"></i>
                                        Spouse Details
                                    </h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseSurname" class="form-label required">Surname</label>
                                            <input type="text" class="form-control" id="spouseSurname" name="spouseSurname">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseFirstName" class="form-label required">First Name</label>
                                            <input type="text" class="form-control" id="spouseFirstName" name="spouseFirstName">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="spouseDateOfBirth" class="form-label required">Date of Birth</label>
                                            <input type="date" class="form-control" id="spouseDateOfBirth" name="spouseDateOfBirth">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="spouseIdType" class="form-label required">Type of ID</label>
                                            <select class="form-select" id="spouseIdType" name="spouseIdType">
                                                <option value="">Select ID Type</option>
                                                <option value="National ID">National ID</option>
                                                <option value="Passport">Passport</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="spouseIdNumber" class="form-label required">ID Number</label>
                                            <input type="text" class="form-control" id="spouseIdNumber" name="spouseIdNumber">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="spouseNationality" class="form-label required">Nationality</label>
                                            <select class="form-select" id="spouseNationality" name="spouseNationality">
                                                <option value="">Select Nationality</option>
                                                <!-- Will be populated by JavaScript -->
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <fieldset class="col-md-6 mb-3">
                                            <legend class="form-label required">Gender</legend>
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
                                            <div class="invalid-feedback"></div>
                                        </fieldset>
                                    </div>
                                    
                                </div>
                            </div>

                            <!-- Step 3: Residential Address -->
                            <div class="form-step" id="step-3">
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
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
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="town" class="form-label required">Town</label>
                                        <select class="form-select" id="town" name="town" required>
                                            <option value="">Select Town</option>
                                            <!-- Will be populated by JavaScript -->
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
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
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
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nokTown" class="form-label required">Town</label>
                                        <select class="form-select" id="nokTown" name="nokTown" required>
                                            <option value="">Select Town</option>
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Sale Type Selection -->
                            <div class="form-step" id="step-5">
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
                                        <i class="fas fa-building me-2"></i>
                                        Sale Type Selection
                                    </h4>
                                    <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                                </div>
                                
                                <fieldset class="mb-4">
                                    <legend class="form-label required">Sale Type</legend>
                                    <div class="row mt-3">
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 sale-type-card" data-type="Individual">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-user fa-3x text-primary mb-3"></i>
                                                    <h5>Individual Sale</h5>
                                                    <p class="text-muted">Selling a single property as an individual</p>
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input class="form-check-input" type="radio" name="saleType" id="saleTypeIndividual" value="Individual" aria-label="Individual sale">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 sale-type-card" data-type="Property Development">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-city fa-3x text-primary mb-3"></i>
                                                    <h5>Property Development</h5>
                                                    <p class="text-muted">Developing and selling multiple properties</p>
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input class="form-check-input" type="radio" name="saleType" id="saleTypeDevelopment" value="Property Development" aria-label="Property development sale">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-block"></div>
                                </fieldset>
                                
                                <!-- Property Developer Details (shown when Property Development selected) -->
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
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
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
                                        <label for="salePricingType" class="form-label required">Sale Type</label>
                                        <select class="form-select" id="salePricingType" name="salePricingType" required>
                                            <option value="">Select Sale Type</option>
                                            <option value="plot_and_plan">Plot &amp; Plan</option>
                                            <option value="existing_house">Existing House</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <!-- Plot & Plan pricing (shown when Sale Type = Plot & Plan) -->
                                <div id="plotAndPlanPricing" class="d-none">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="plotSellingPrice" class="form-label required">Plot Selling Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">N$</span>
                                                <input type="text" class="form-control money-input sale-pricing-input" id="plotSellingPrice" name="plotSellingPrice" inputmode="decimal">
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="constructionAmount" class="form-label required">Construction Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">N$</span>
                                                <input type="text" class="form-control money-input sale-pricing-input" id="constructionAmount" name="constructionAmount" inputmode="decimal">
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="agentCommissionFeesPP" class="form-label required">Agent Commission Fees</label>
                                            <div class="input-group">
                                                <span class="input-group-text">N$</span>
                                                <input type="text" class="form-control money-input sale-pricing-input" id="agentCommissionFeesPP" name="agentCommissionFeesPP" inputmode="decimal">
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Existing House pricing (shown when Sale Type = Existing House) -->
                                <div id="existingHousePricing" class="d-none">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="propertySellingPrice" class="form-label required">Property Selling Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">N$</span>
                                                <input type="text" class="form-control money-input sale-pricing-input" id="propertySellingPrice" name="propertySellingPrice" inputmode="decimal">
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="agentCommissionFeesEH" class="form-label required">Agent Commission Fees</label>
                                            <div class="input-group">
                                                <span class="input-group-text">N$</span>
                                                <input type="text" class="form-control money-input sale-pricing-input" id="agentCommissionFeesEH" name="agentCommissionFeesEH" inputmode="decimal">
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="landSize" class="form-label required">Land Size</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="landSize" name="landSize" min="1" step="0.01" inputmode="decimal" aria-describedby="landSizeHelp" required>
                                            <span class="input-group-text">m²</span>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text" id="landSizeHelp">Enter a value greater than 0 square metres.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="sellingPrice" class="form-label required">Total Selling Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">N$</span>
                                            <input type="text" class="form-control money-input" id="sellingPrice" name="sellingPrice" inputmode="decimal" aria-describedby="sellingPriceHelp" required readonly>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text" id="sellingPriceHelp">Calculated automatically from the Sale Type fields above.</div>
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
                                        <textarea class="form-control" id="additionalFeatures" name="additionalFeatures" rows="3" 
                                                  placeholder="Describe any additional features like garage, garden, pool, etc."></textarea>
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
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="propertyTown" class="form-label required">Town</label>
                                        <select class="form-select" id="propertyTown" name="propertyTown" required>
                                            <option value="">Select Town</option>
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- Step 7: Document Upload -->
                            <div class="form-step" id="step-7">
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
                                        <i class="fas fa-file-upload me-2"></i>
                                        Document Upload
                                    </h4>
                                    <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                                </div>
                                
                                <!-- Required Documents -->
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card document-upload-card">
                                            <div class="card-body">
                                                <label class="card-title" for="idDocument">
                                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                                    ID Document <span class="text-danger">*</span>
                                                </label>
                                                <input type="file" class="form-control mb-2" id="idDocument" name="idDocument" 
                                                       accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">Upload PDF, JPG, or PNG (Max 10MB)</small>
                                                <div class="invalid-feedback"></div>
                                                <div id="idDocumentPreview" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <div class="card document-upload-card">
                                            <div class="card-body">
                                                <label class="card-title" for="proofOfResidence">
                                                    <i class="fas fa-home me-2 text-primary"></i>
                                                    Proof of Residence <span class="text-danger">*</span>
                                                </label>
                                                <input type="file" class="form-control mb-2" id="proofOfResidence" name="proofOfResidence" 
                                                       accept=".pdf,.jpg,.jpeg,.png" required>
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
                                                <label class="card-title" for="titleDeed">
                                                    <i class="fas fa-certificate me-2 text-primary"></i>
                                                    Title Deed <span class="text-danger">*</span>
                                                </label>
                                                <input type="file" class="form-control mb-2" id="titleDeed" name="titleDeed" 
                                                       accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">Property title deed document</small>
                                                <div class="invalid-feedback"></div>
                                                <div id="titleDeedPreview" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4" id="marriageCertificateCard" style="display: none;">
                                        <div class="card document-upload-card">
                                            <div class="card-body">
                                                <label class="card-title required" for="marriageCertificateDoc">
                                                    <i class="fas fa-ring me-2 text-primary"></i>
                                                    Marriage Certificate
                                                </label>
                                                <input type="file" class="form-control mb-2" id="marriageCertificateDoc" name="marriageCertificateDoc"
                                                       accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="text-muted">Required if married</small>
                                                <div class="invalid-feedback"></div>
                                                <div id="marriageCertificateDocPreview" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Additional Documents -->
                                <div class="mb-4">
                                    <h5 class="text-secondary">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Additional Documents (Optional)
                                    </h5>
                                    <div id="additionalDocuments">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="addAdditionalDocument">
                                        <i class="fas fa-plus me-2"></i>Add Additional Document
                                    </button>
                                </div>
                            </div>

                            <!-- Step 8: Property Images/Video -->
                            <div class="form-step" id="step-8">
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
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
                                
                                <!-- Property Images -->
                                <div class="mb-4">
                                    <label class="text-secondary h5" id="propertyImagesLabel" for="propertyImages">
                                        <i class="fas fa-images me-2"></i>
                                        Property Images <span class="text-danger">*</span>
                                    </label>
                                    <div class="upload-zone" id="imageUploadZone" role="group" aria-labelledby="propertyImagesLabel">
                                        <div class="upload-zone-content">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <h6>Drag & Drop Images Here</h6>
                                            <p class="text-muted">or click to select files</p>
                                            <input type="file" class="form-control d-none" id="propertyImages" name="propertyImages" 
                                                   multiple accept="image/*" required>
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
                                
                                <!-- Property Videos -->
                                <div class="mb-4">
                                    <label class="text-secondary h5" id="propertyVideosLabel" for="propertyVideos">
                                        <i class="fas fa-video me-2"></i>
                                        Property Videos (Optional)
                                    </label>
                                    <div class="upload-zone" id="videoUploadZone" role="group" aria-labelledby="propertyVideosLabel">
                                        <div class="upload-zone-content">
                                            <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                            <h6>Drag & Drop Videos Here</h6>
                                            <p class="text-muted">or click to select files</p>
                                            <input type="file" class="form-control d-none" id="propertyVideos" name="propertyVideos" 
                                                   multiple accept="video/*">
                                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('propertyVideos').click()">
                                                Choose Videos
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Supported formats: MP4, MOV, AVI. Maximum 3 videos, 25MB each.
                                    </small>
                                    <div class="invalid-feedback"></div>
                                    <div id="videoPreviewContainer" class="mt-3 row"></div>
                                </div>
                            </div>

                            <!-- Step 9: Acknowledgment and Declaration -->
                            <div class="form-step" id="step-9">
                                <div class="step-header mb-4">
                                    <h4 class="text-primary mb-2">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Acknowledgment and Declaration
                                    </h4>
                                    <div class="bg-primary" style="height: 3px; width: 50px; border-radius: 2px;"></div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Please carefully read and accept all declarations before submitting your application.
                                </div>
                                
                                <!-- Declarations -->
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
                                    
                                    <fieldset class="mb-3">
                                        <legend class="form-label required">Signature Method</legend>
                                        <div class="row mt-3">
                                            <div class="col-12 mb-3">
                                                <div class="card signature-method-card" data-method="upload">
                                                    <div class="card-body text-center">
                                                        <i class="fas fa-upload fa-2x text-primary mb-2"></i>
                                                        <h6>Upload Signature</h6>
                                                        <p class="text-muted small">Upload an image of your signature</p>
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input" type="radio" name="signatureType" id="signatureUpload" value="upload" aria-label="Upload a signature">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback d-block"></div>
                                    </fieldset>
                                    
                                    <!-- Signature Upload -->
                                    <div id="signatureUploadContainer" class="d-none mb-3">
                                        <label for="signatureFile" class="form-label">Upload Signature Image</label>
                                        <input type="file" class="form-control" id="signatureFile" name="signatureFile" accept="image/*">
                                        <small class="form-text text-muted">Supported formats: JPG, PNG (Max 5MB)</small>
                                        <div class="invalid-feedback"></div>
                                        <div id="signaturePreview" class="mt-2"></div>
                                    </div>
                                    
                                    <?php if (TURNSTILE_READY): ?>
                                    <div class="cf-turnstile mb-3" data-sitekey="<?php echo htmlspecialchars(TURNSTILE_SITE_KEY); ?>"></div>
                                    <?php elseif (TURNSTILE_ENABLED): ?>
                                    <div class="alert alert-warning mb-3" role="alert">
                                        Security verification is temporarily unavailable. Please try again later.
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Final Submit -->
                                <div class="text-center">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        You're ready to submit your seller application!
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg"<?= TURNSTILE_ENABLED && !TURNSTILE_CONFIGURED ? ' disabled aria-disabled="true"' : '' ?>>
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
    </main>

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
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="sellerSuccessModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 bg-success text-white">
                    <h5 class="modal-title" id="sellerSuccessModalTitle">
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
                    <dl class="row text-start small">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7" id="applicationStatus">Submitted</dd>
                        <dt class="col-5">Submitted</dt>
                        <dd class="col-7" id="applicationSubmittedAt"></dd>
                    </dl>
                    
                    <div class="text-muted">
                        <p class="mb-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Please save this application number for your records.
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-1"></i>
                            If email delivery is configured, your secure login instructions will arrive shortly.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <a class="btn btn-primary" href="<?= $baseUrl ?>/login" id="sellerReturnToLogin">
                        <i class="fas fa-home me-2"></i>
                        Return to login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/html/material/seller/js/form-data.js?v=<?= filemtime(__DIR__ . '/js/form-data.js') ?>"></script>
    <script src="<?= $baseUrl ?>/html/material/seller/js/form-validation.js?v=<?= filemtime(__DIR__ . '/js/form-validation.js') ?>"></script>
    <script src="<?= $baseUrl ?>/html/material/seller/js/form-steps.js?v=<?= filemtime(__DIR__ . '/js/form-steps.js') ?>"></script>
    <script src="<?= $baseUrl ?>/html/material/seller/js/seller-form.js?v=<?= filemtime(__DIR__ . '/js/seller-form.js') ?>"></script>

    <!-- Background -->
    <div class="fixed-bg"></div>

    <!-- MiniMax Agent Attribution -->
    <div class="position-fixed bottom-0 end-0 m-3 bg-primary text-white px-3 py-2 rounded shadow-sm small">
        All right reserved by NURU
    </div>
</body>
</html>

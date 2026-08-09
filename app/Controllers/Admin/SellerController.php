<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Controller;
use App\Core\Router;
use App\Models\Seller;

/**
 * Internal staff-facing seller application management: list, profile,
 * intake wizard + its JSON processor, and the approve/reject review flow.
 * Ported from html/material/{sellers-list,sellerlist_table,sellers_profile,
 * seller_admin_form,seller_admin_processor}.php and
 * html/material/config/review_seller_application.php - logic is preserved
 * as closely as possible, including a couple of pre-existing quirks called
 * out inline where changing them would alter behavior.
 *
 * Not the same folder as html/material/seller/ (the public seller
 * application form), which is separate, already-migrated, already-designed
 * work with its own /seller route.
 */
final class SellerController extends Controller
{
    private const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'gif', 'tiff', 'bmp'];
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'webm'];

    /** GET /admin/sellers-list - html/material/sellers-list.php + sellerlist_table.php */
    public function list(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        Auth::boot();
        require_once \NURU_MATERIAL . '/config/id_tokens.php';

        $sellerManagementCsrf = \csrfToken('seller_management');

        $role = Auth::currentRole();
        $userId = (int) $_SESSION['user_id'];
        $agentId = 0;
        $words = 'Seller List';

        if ($role === 'agent_coordinator') {
            $agentId = \resolveAgentId($this->pdo, $userId) ?? 0;
            $words = ' My Sellers';
        }

        $model = new Seller($this->pdo);
        $sellers = $model->listForRole($role, $userId, $agentId);

        $this->render('admin.sellers.list', [
            'sellers' => $sellers,
            'words' => $words,
            'sellerManagementCsrf' => $sellerManagementCsrf,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** GET /admin/sellers-profile?id=... - html/material/sellers_profile.php */
    public function profile(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        Auth::boot();
        require_once \NURU_MATERIAL . '/config/id_tokens.php';

        $sellerId = \portalDecodeId($_GET['id'] ?? null);
        if ($sellerId === null) {
            http_response_code(400);
            exit('Invalid seller reference.');
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');

        $model = new Seller($this->pdo);
        $applicationData = $model->findApplication($sellerId);
        if (!$applicationData) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Application not found']);
            exit;
        }

        // agent_coordinator is scoped to their own assigned applications only -
        // otherwise any agent could view every seller's full PII, not just the
        // ones actually assigned to them (same principle as checklist.php).
        if (Auth::currentRole() === 'agent_coordinator') {
            $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);
            $visible = $model->isVisibleToAgent(
                $sellerId,
                isset($applicationData['assigned_agent_id']) ? (int) $applicationData['assigned_agent_id'] : null,
                $myAgentId,
                (int) $_SESSION['user_id']
            );
            if (!$visible) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'You are not assigned to this application']);
                exit;
            }
        }

        $application = $model->profile($sellerId, $applicationData);

        $this->render('admin.sellers.profile', [
            'application' => $application,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** GET /admin/seller-admin-form - html/material/seller_admin_form.php */
    public function formPage(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $csrfToken = Auth::csrfToken('staff_seller_form');

        $this->render('admin.sellers.admin-form', [
            'csrfToken' => $csrfToken,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /**
     * POST /admin/seller-admin-processor - html/material/seller_admin_processor.php
     * JSON intake for the wizard above. Unlike the page-rendering methods,
     * the original endpoint returns its own JSON 403 (sessionHasAuthoritativeRole)
     * instead of requireRole()'s redirect, since this is called from fetch()
     * and a redirect response would not be usable JSON - preserved as-is.
     */
    public function store(): void
    {
        Bootstrap::init();
        header('Content-Type: application/json');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        Auth::boot();
        require_once \NURU_MATERIAL . '/config/account_provisioning.php';

        if (!\sessionHasAuthoritativeRole(['admin', 'manager', 'agent_coordinator'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if (!\validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'staff_seller_form')) {
            http_response_code(419);
            echo json_encode(['success' => false, 'error' => 'Your session has expired. Please reload and try again.']);
            exit;
        }

        // Enable error reporting for debugging (but prevent HTML output)
        error_reporting(E_ALL);
        ini_set('display_errors', '0'); // Don't display errors to prevent HTML output
        ini_set('log_errors', '1');     // But still log them

        $this->debugLog('=== SELLER API CALL STARTED ===');
        $this->debugLog('Request Method: ' . $_SERVER['REQUEST_METHOD']);
        $this->debugLog('Content Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Not Set'));

        $pdo = $this->pdo;

        // Check if data is being received
        $rawInput = file_get_contents('php://input');
        $this->debugLog('Raw input received: ' . strlen($rawInput) . ' characters');
        if ($rawInput) {
            $this->debugLog('Raw input received without recording applicant data');
        }

        $this->debugLog('Parsing input data...');

        // ===============================================================
        // CHECK FOR DUPLICATE SUBMISSION
        // ===============================================================
        $requestSignature = md5($rawInput . ($_SESSION['user_id'] ?? session_id()));
        $lastSignatureFile = \NURU_MATERIAL . '/logs/last_submission_' . session_id() . '.txt';

        // Check for recent duplicate submission (within last 10 seconds)
        if (file_exists($lastSignatureFile)) {
            $lastSubmission = json_decode(file_get_contents($lastSignatureFile), true);
            if ($lastSubmission && isset($lastSubmission['signature']) && $lastSubmission['signature'] === $requestSignature) {
                $timeDiff = time() - ($lastSubmission['time'] ?? 0);
                if ($timeDiff < 10) {
                    $this->debugLog("DUPLICATE SUBMISSION DETECTED - Rejecting request (submitted $timeDiff seconds ago)");
                    echo json_encode([
                        'success' => false,
                        'error' => 'Duplicate submission detected. Please wait before submitting again.',
                        'duplicate' => true,
                    ]);
                    exit;
                }
            }
        }

        $applicationNumber = null;
        file_put_contents($lastSignatureFile, json_encode([
            'signature' => $requestSignature,
            'time' => time(),
            'application' => $applicationNumber,
        ]), LOCK_EX);

        $this->debugLog('Duplicate submission check passed');

        $model = new Seller($pdo);

        try {
            $this->debugLog('Testing database connection...');
            $result = $pdo->query('SELECT 1');
            $result->fetch();
            $this->debugLog('Database connection successful');
            $this->debugLog('Database test query result: SUCCESS');

            // Start transaction
            $this->debugLog('Starting seller transaction...');
            $pdo->beginTransaction();
            $this->debugLog('Transaction started successfully');

            $this->debugLog('Checking for data...');

            // Parse JSON data
            $data = null;
            if ($rawInput) {
                $this->debugLog('Using JSON input');
                $data = json_decode($rawInput, true);
                if ($data === null) {
                    throw new \Exception('Invalid JSON input');
                }
                $this->debugLog('JSON decoded successfully: YES');
                $this->debugLog('Data variable type: ' . gettype($data));
                $this->debugLog('Data keys: ' . implode(', ', array_keys($data)));
                $this->debugLog('Data array size: ' . count($data));
            } else {
                $this->debugLog('No input data received');
                throw new \Exception('No data received');
            }

            $this->debugLog('Data validation passed');

            if (($data['signatureType'] ?? '') === 'otp') {
                throw new \Exception('SMS verification is not available. Please use a drawn or uploaded signature.');
            }

            // Server-side email validation - there was previously none at all, only
            // the client-side <input type="email">, which is trivially bypassed by
            // posting directly to this endpoint. An unvalidated email also becomes
            // an email-header-injection vector (CRLF in the value) the moment a
            // working SMTP relay is configured, so this matters even though mail()
            // isn't currently delivering anything in this environment.
            foreach (['email' => 'email', 'nokEmail' => 'next of kin email'] as $field => $label) {
                if (!empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->debugLog("Rejected invalid $label: {$data[$field]}");
                    throw new \Exception("Please provide a valid $label address");
                }
            }

            // Generate application number
            $this->debugLog('Generating seller application number...');
            $applicationNumber = $this->generateSellerApplicationNumber($pdo);
            $this->debugLog('Generated application number: ' . $applicationNumber);

            // ===============================================================
            // 1. INSERT MAIN SELLER APPLICATION
            // ===============================================================
            $this->debugLog('Preparing main seller application insert statement...');

            $stmt = $pdo->prepare("
                INSERT INTO seller_applications (application_number, status, submission_date, created_at)
                VALUES (?, 'submitted', NOW(), NOW())
            ");

            $this->debugLog("Execute parameters: [\"$applicationNumber\"]");
            $result = $stmt->execute([$applicationNumber]);
            $this->debugLog('Main application insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

            if (!$result) {
                throw new \Exception('Failed to insert main seller application');
            }

            $application_id = $pdo->lastInsertId();
            $this->debugLog('Inserted application ID: ' . $application_id);

            // ===============================================================
            // 2. INSERT PERSONAL DETAILS
            // ===============================================================
            if (!empty($data['firstName']) || !empty($data['surname'])) {
                $this->debugLog('Processing personal details...');

                $age = null;
                if (!empty($data['dateOfBirth'])) {
                    $birthDate = new \DateTime($data['dateOfBirth']);
                    $today = new \DateTime();
                    $age = $today->diff($birthDate)->y;
                }

                $loaded_by = 'Self Loaded';
                if (!empty($_SESSION['user_id'])) {
                    $loaded_by = $_SESSION['user_id'];
                }

                $stmt = $pdo->prepare("
                    INSERT INTO seller_personal_details
                    (application_id, surname, first_name, middle_name, maiden_name, date_of_birth, age, id_type, id_number, nationality, gender, created_at, loaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");

                $this->debugLog('Personal details parameters: ' . json_encode([
                    $data['surname'] ?? null,
                    $data['firstName'] ?? null,
                    $data['middleName'] ?? null,
                    $data['maidenName'] ?? null,
                    $data['dateOfBirth'] ?? null,
                    $age,
                    $data['idType'] ?? 'National ID',
                    $data['idNumber'] ?? null,
                    $data['nationality'] ?? null,
                    $data['gender'] ?? null,
                ]));

                $result = $stmt->execute([
                    $application_id,
                    $data['surname'] ?? null,
                    $data['firstName'] ?? null,
                    $data['middleName'] ?? null,
                    $data['maidenName'] ?? null,
                    $data['dateOfBirth'] ?? null,
                    $age,
                    $data['idType'] ?? 'National ID',
                    $data['idNumber'] ?? null,
                    $data['nationality'] ?? null,
                    $data['gender'] ?? null,
                    $loaded_by,
                ]);

                $adminUserId = $this->createAdminUserIfNotExists($pdo, $data);
                if ($adminUserId) {
                    $pdo->prepare('UPDATE seller_applications SET user_id = ? WHERE id = ?')
                        ->execute([$adminUserId, $application_id]);
                }
                $this->debugLog('Personal details insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

                if (!$result) {
                    throw new \Exception('Failed to insert personal details');
                }
            }

            // ===============================================================
            // 3. INSERT MARITAL STATUS
            // ===============================================================
            if (!empty($data['maritalStatus'])) {
                $this->debugLog('Processing marital status...');

                $stmt = $pdo->prepare("
                    INSERT INTO seller_marital_status
                    (application_id, marital_status, spouse_surname, spouse_first_name, spouse_date_of_birth, spouse_id_type, spouse_id_number, spouse_nationality, spouse_gender, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $this->debugLog('Marital status parameters: ' . json_encode([
                    $data['maritalStatus'],
                    $data['spouseSurname'] ?? null,
                    $data['spouseFirstName'] ?? null,
                    $data['spouseDateOfBirth'] ?? null,
                    $data['spouseIdType'] ?? null,
                    $data['spouseIdNumber'] ?? null,
                    $data['spouseNationality'] ?? null,
                    $data['spouseGender'] ?? null,
                ]));

                $result = $stmt->execute([
                    $application_id,
                    $data['maritalStatus'],
                    $data['spouseSurname'] ?? null,
                    $data['spouseFirstName'] ?? null,
                    $data['spouseDateOfBirth'] ?? null,
                    $data['spouseIdType'] ?? null,
                    $data['spouseIdNumber'] ?? null,
                    $data['spouseNationality'] ?? null,
                    $data['spouseGender'] ?? null,
                ]);

                $this->debugLog('Marital status insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

                if (!$result) {
                    throw new \Exception('Failed to insert marital status');
                }
            }

            // ===============================================================
            // 4. INSERT RESIDENTIAL ADDRESS
            // ===============================================================
            if (!empty($data['streetName']) || !empty($data['region'])) {
                $this->debugLog('Processing residential address...');

                $stmt = $pdo->prepare("
                    INSERT INTO seller_residential_address
                    (application_id, erf_no, street_name, suburb, location, region, town, email, mobile_number, po_box, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $this->debugLog('Residential address parameters: ' . json_encode([
                    $data['erfNo'] ?? null,
                    $data['streetName'] ?? null,
                    $data['suburb'] ?? null,
                    $data['location'] ?? null,
                    $data['region'] ?? null,
                    $data['town'] ?? null,
                    $data['email'] ?? null,
                    $data['mobileNumber'] ?? null,
                    $data['poBox'] ?? null,
                ]));

                $result = $stmt->execute([
                    $application_id,
                    $data['erfNo'] ?? null,
                    $data['streetName'] ?? null,
                    $data['suburb'] ?? null,
                    $data['location'] ?? null,
                    $data['region'] ?? null,
                    $data['town'] ?? null,
                    $data['email'] ?? null,
                    $data['mobileNumber'] ?? null,
                    $data['poBox'] ?? null,
                ]);

                $this->debugLog('Residential address insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

                if (!$result) {
                    throw new \Exception('Failed to insert residential address');
                }
            }

            // ===============================================================
            // 5. INSERT NEXT OF KIN
            // ===============================================================
            if (!empty($data['nokFirstName']) || !empty($data['nokSurname'])) {
                $this->debugLog('Processing next of kin...');

                $stmt = $pdo->prepare("
                    INSERT INTO seller_next_of_kin
                    (application_id, nok_surname, nok_first_name, nok_contact_number, nok_email, nok_erf_no, nok_street_name, nok_suburb, nok_location, nok_region, nok_town, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $this->debugLog('Next of kin parameters: ' . json_encode([
                    $data['nokSurname'] ?? null,
                    $data['nokFirstName'] ?? null,
                    $data['nokContactNumber'] ?? null,
                    $data['nokEmail'] ?? null,
                    $data['nokErfNo'] ?? null,
                    $data['nokStreetName'] ?? null,
                    $data['nokSuburb'] ?? null,
                    $data['nokLocation'] ?? null,
                    $data['nokRegion'] ?? null,
                    $data['nokTown'] ?? null,
                ]));

                $result = $stmt->execute([
                    $application_id,
                    $data['nokSurname'] ?? null,
                    $data['nokFirstName'] ?? null,
                    $data['nokContactNumber'] ?? null,
                    $data['nokEmail'] ?? null,
                    $data['nokErfNo'] ?? null,
                    $data['nokStreetName'] ?? null,
                    $data['nokSuburb'] ?? null,
                    $data['nokLocation'] ?? null,
                    $data['nokRegion'] ?? null,
                    $data['nokTown'] ?? null,
                ]);

                $this->debugLog('Next of kin insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

                if (!$result) {
                    throw new \Exception('Failed to insert next of kin');
                }
            }

            // ===============================================================
            // 6. INSERT PROPERTY DETAILS
            // ===============================================================
            $propertyId = null; // guards property images/videos inserts below if this step is ever skipped
            if (!empty($data['propertyDetailType']) || !empty($data['propertyStreetName'])) {
                $this->debugLog('Processing property details...');

                $stmt = $pdo->prepare("
                    INSERT INTO seller_properties
                    (application_id, property_detail_type, land_type, land_size, selling_price, house_size, number_of_rooms, number_of_bathrooms, additional_features, property_erf_no, property_street_name, property_suburb, property_location, property_region, property_town, listing_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $this->debugLog('Property details parameters: ' . json_encode([
                    $data['propertyDetailType'] ?? null,
                    $data['landType'] ?? null,
                    $data['landSize'] ?? null,
                    (float) $data['sellingPrice'] ?? null,
                    $data['houseSize'] ?? null,
                    $data['rooms'] ?? null,
                    $data['bathrooms'] ?? null,
                    $data['additionalFeatures'] ?? null,
                    $data['propertyErfNo'] ?? null,
                    $data['propertyStreetName'] ?? null,
                    $data['propertySuburb'] ?? null,
                    $data['propertyLocation'] ?? null,
                    $data['propertyRegion'] ?? null,
                    $data['propertyTown'] ?? null,
                ]));

                $rawSellingPrice = $data['sellingPrice'] ?? null;

                $cleanSellingPrice = null;
                if ($rawSellingPrice !== null && $rawSellingPrice !== '') {
                    // remove commas, spaces, and currency symbols (N$, $, etc.)
                    $clean = preg_replace('/[^\d.\-]/', '', (string) $rawSellingPrice);
                    $cleanSellingPrice = ($clean === '' ? null : (float) $clean);
                }

                $result = $stmt->execute([
                    $application_id,
                    $data['propertyDetailType'] ?? null,
                    $data['landType'] ?? null,
                    $data['landSize'] ?? null,
                    $cleanSellingPrice ?? null,
                    $data['houseSize'] ?? null,
                    $data['rooms'] ?? null,
                    $data['bathrooms'] ?? null,
                    $data['additionalFeatures'] ?? null,
                    $data['propertyErfNo'] ?? null,
                    $data['propertyStreetName'] ?? null,
                    $data['propertySuburb'] ?? null,
                    $data['propertyLocation'] ?? null,
                    $data['propertyRegion'] ?? null,
                    $data['propertyTown'] ?? null,
                ]);

                $this->debugLog('Property details insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

                if (!$result) {
                    throw new \Exception('Failed to insert property details');
                }
                $propertyId = $pdo->lastInsertId();
            }

            // ===============================================================
            // 7. INSERT SALE TYPE
            // ===============================================================
            if (!empty($data['saleType'])) {
                $this->debugLog('Processing sale type...');

                $stmt = $pdo->prepare('
                    INSERT INTO seller_sale_type
                    (application_id, sale_type, developer_name, property_type)
                    VALUES (?, ?, ?, ?)
                ');

                $this->debugLog('Sale type parameters: ' . json_encode([
                    $data['saleType'],
                    $data['developerName'] ?? null,
                    $data['propertyType'] ?? null,
                ]));

                $result = $stmt->execute([
                    $application_id,
                    $data['saleType'],
                    $data['developerName'] ?? null,
                    $data['propertyType'] ?? null,
                ]);

                $this->debugLog('Sale type insert result: ' . ($result ? 'SUCCESS' : 'FAILURE'));

                if (!$result) {
                    throw new \Exception('Failed to insert sale type');
                }
            }

            // ===============================================================
            // 7b. INSERT PROPERTY DEVELOPMENTS + GENERATED UNIT PROPERTIES
            // Each house type's unit count generates that many real, individually
            // matchable seller_properties rows (not a single row with a mutable
            // counter), so units work with the existing matching/task/checklist/
            // countdown pipeline unchanged. development_house_type_id links each
            // generated row back to its house type for live remaining-stock counts.
            // ===============================================================
            if (($data['saleType'] ?? null) === 'Property Development' && !empty($data['developments']) && is_array($data['developments'])) {
                $this->debugLog('Processing property developments...');

                $propertyTypeMap = [
                    'Free Standing House Unit' => 'Single Residential',
                    'General Residential House Unit' => 'General Residential',
                    'Business/Commercial Property' => 'Commercial/Business',
                    'Farm Property' => 'Farm',
                    'Institutional Property' => 'Institutional',
                ];

                $devStmt = $pdo->prepare('
                    INSERT INTO seller_developments
                    (application_id, development_index, development_name, region, town, location, suburb, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ');

                $houseTypeStmt = $pdo->prepare('
                    INSERT INTO seller_development_house_types
                    (development_id, house_type_index, property_type, number_of_units, house_size, land_type, land_size, selling_price, number_of_rooms, number_of_bathrooms, additional_features, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ');

                $unitStmt = $pdo->prepare('
                    INSERT INTO seller_properties
                    (application_id, property_detail_type, land_type, land_size, house_size, selling_price, number_of_rooms, number_of_bathrooms, additional_features, property_street_name, property_suburb, property_location, property_region, property_town, development_house_type_id, listing_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ');

                foreach ($data['developments'] as $devIndex => $development) {
                    if (empty($development['development_name'])) {
                        continue;
                    }

                    $devStmt->execute([
                        $application_id,
                        $devIndex + 1,
                        $development['development_name'],
                        $development['region'] ?? null,
                        $development['town'] ?? null,
                        $development['location'] ?? null,
                        $development['suburb'] ?? null,
                    ]);
                    $developmentId = $pdo->lastInsertId();

                    $houseTypes = $development['house_types'] ?? [];
                    foreach ($houseTypes as $htIndex => $houseType) {
                        if (empty($houseType['property_type'])) {
                            continue;
                        }

                        // Comma-formatted monetary/size input ("456,960.78") is stripped
                        // down to plain digits/decimal here regardless of formatting.
                        $rawLandSize = $houseType['land_size'] ?? null;
                        $landSize = $rawLandSize !== null ? preg_replace('/[^\d.\-]/', '', (string) $rawLandSize) : null;

                        $rawHouseSize = $houseType['house_size'] ?? null;
                        $houseSize = $rawHouseSize !== null ? preg_replace('/[^\d.\-]/', '', (string) $rawHouseSize) : null;

                        $rawPrice = $houseType['selling_price'] ?? null;
                        $sellingPrice = $rawPrice !== null ? preg_replace('/[^\d.\-]/', '', (string) $rawPrice) : null;

                        $houseTypeStmt->execute([
                            $developmentId,
                            $htIndex + 1,
                            $houseType['property_type'],
                            (int) ($houseType['number_of_units'] ?? 1),
                            $houseSize !== '' ? $houseSize : null,
                            $houseType['land_type'] ?? null,
                            $landSize !== '' ? $landSize : null,
                            $sellingPrice !== '' ? $sellingPrice : null,
                            $houseType['rooms'] ?? null,
                            $houseType['bathrooms'] ?? null,
                            $houseType['additional_features'] ?? null,
                        ]);
                        $houseTypeId = $pdo->lastInsertId();

                        $unitCount = max(1, (int) ($houseType['number_of_units'] ?? 1));
                        $mappedPropertyType = $propertyTypeMap[$houseType['property_type']] ?? 'General Residential';

                        for ($i = 1; $i <= $unitCount; $i++) {
                            $streetLabel = sprintf('%s - %s #%d', $development['development_name'], $houseType['property_type'], $i);

                            $unitStmt->execute([
                                $application_id,
                                $mappedPropertyType,
                                $houseType['land_type'] ?? null,
                                $landSize !== '' ? $landSize : null,
                                $houseSize !== '' ? $houseSize : null,
                                $sellingPrice !== '' ? $sellingPrice : null,
                                $houseType['rooms'] ?? null,
                                $houseType['bathrooms'] ?? null,
                                $houseType['additional_features'] ?? null,
                                $streetLabel,
                                $development['suburb'] ?? null,
                                $development['location'] ?? null,
                                $development['region'] ?? null,
                                $development['town'] ?? null,
                                $houseTypeId,
                            ]);

                            if ($propertyId === null) {
                                $propertyId = $pdo->lastInsertId();
                            }
                        }
                    }
                }

                $this->debugLog('Property developments processed successfully');
            }

            // ===============================================================
            // 8. INSERT DOCUMENTS
            // ===============================================================
            $documentTypes = ['id_document', 'proof_of_residence', 'title_deed', 'marriage_certificate'];

            foreach ($documentTypes as $docType) {
                $fileField = $docType;

                // getFileInputData() always returns {files:[],hasFiles:false} for an untouched
                // input, never null/undefined - checking !empty($data[$fileField]) was always
                // true (non-empty wrapper object) and created a phantom "unknown"/0-byte document
                // row for every unselected optional file (e.g. marriage_certificate when not
                // married). Must check the files array itself is non-empty.
                if (!empty($data[$fileField]['files']) && is_array($data[$fileField]['files'])) {
                    $this->debugLog("Processing $docType document...");

                    $fileData = $data[$fileField];
                    $fileName = $fileData['files'][0]['name'] ?? 'unknown';
                    $fileSize = $fileData['files'][0]['size'] ?? 0;
                    $fileType = $fileData['files'][0]['type'] ?? 'application/octet-stream';
                    $fileContent = $fileData['files'][0]['content'] ?? null;

                    // Never trust the client-supplied filename for the saved path -
                    // it's fully attacker-controlled and was previously concatenated
                    // in directly, allowing both path traversal and arbitrary
                    // extensions (e.g. a ".php" upload saved into the web root).
                    // The original name is still stored as original_filename for
                    // display; only the on-disk name is regenerated.
                    $safeName = $this->safeUploadFilename($fileName, self::ALLOWED_DOCUMENT_EXTENSIONS);
                    if ($safeName === null) {
                        $this->debugLog("ERROR: Rejected $docType upload with disallowed extension: $fileName");
                        throw new \Exception("File type not allowed for $docType");
                    }

                    // Create upload directory if it doesn't exist
                    $uploadDir = \NURU_MATERIAL . "/uploads/seller/$docType/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Save file to disk
                    $filePath = "uploads/seller/$docType/" . $safeName;
                    $fullPath = \NURU_MATERIAL . '/' . $filePath;

                    $decodedContent = base64_decode((string) $fileContent, true);

                    if ($decodedContent !== false) {
                        $saveResult = file_put_contents($fullPath, $decodedContent);
                        if ($saveResult !== false) {
                            $this->debugLog("File saved to: $fullPath ($saveResult bytes)");
                        } else {
                            $this->debugLog('ERROR: Failed to save file to: ' . $fullPath . ' - ' . (error_get_last()['message'] ?? 'unknown'));
                            throw new \Exception("Failed to save file: $fullPath");
                        }
                    } else {
                        $this->debugLog("ERROR: Invalid base64 content for $docType");
                        throw new \Exception("Invalid base64 content for $docType");
                    }

                    $fileHash = md5($filePath . $fileSize);

                    $stmt = $pdo->prepare('
                        INSERT INTO seller_documents
                        (application_id, document_type, document_name, original_filename, file_path, file_size, mime_type, file_hash)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ');

                    $this->debugLog("Document parameters for $docType: " . json_encode([
                        $docType,
                        $fileName,
                        $filePath,
                        $fileSize,
                        $fileType,
                    ]));

                    $result = $stmt->execute([
                        $application_id,
                        $docType,
                        $docType,
                        $fileName,
                        $filePath,
                        $fileSize,
                        $fileType,
                        $fileHash,
                    ]);

                    $this->debugLog("$docType document insert result: " . ($result ? 'SUCCESS' : 'FAILURE'));

                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        $this->debugLog("Warning: Failed to insert $docType document - " . implode(' - ', $errorInfo));
                    }
                } else {
                    $this->debugLog("No $docType document found in data (field: $fileField)");
                }
            }

            // ===============================================================
            // 8.1. INSERT ADDITIONAL DOCUMENTS
            // ===============================================================
            if (!empty($data['additionalDocName']) && !empty($data['additionalDocFile'])) {
                $this->debugLog('Processing additional documents...');

                $additionalDocNames = is_array($data['additionalDocName']) ? $data['additionalDocName'] : [$data['additionalDocName']];
                $additionalDocFiles = $data['additionalDocFile'];

                if (is_array($additionalDocNames) && is_array($additionalDocFiles['files'])) {
                    foreach ($additionalDocFiles['files'] as $index => $file) {
                        if (!empty($file['name']) && !empty($additionalDocNames[$index])) {
                            $docName = $additionalDocNames[$index];
                            $fileName = $file['name'];
                            $safeName = $this->safeUploadFilename($fileName, self::ALLOWED_DOCUMENT_EXTENSIONS);
                            if ($safeName === null) {
                                $this->debugLog("ERROR: Rejected additional document upload with disallowed extension: $fileName");
                                continue;
                            }
                            $filePath = "uploads/seller/additional_documents/$safeName";
                            $fullFilePath = \NURU_MATERIAL . '/' . $filePath;
                            $fileSize = $file['size'] ?? 0;
                            $fileType = $file['type'] ?? 'application/octet-stream';
                            $fileHash = md5($filePath . $fileSize);

                            $addDocDir = \NURU_MATERIAL . '/uploads/seller/additional_documents/';
                            if (!is_dir($addDocDir)) {
                                mkdir($addDocDir, 0755, true);
                            }

                            if (!empty($file['content'])) {
                                $addDocDecoded = base64_decode($file['content'], true);
                                if ($addDocDecoded !== false) {
                                    $addDocSaveResult = file_put_contents($fullFilePath, $addDocDecoded);
                                    if ($addDocSaveResult !== false) {
                                        $this->debugLog("Additional document saved to: $fullFilePath ($addDocSaveResult bytes)");
                                    } else {
                                        $this->debugLog('ERROR: Failed to save additional document: ' . (error_get_last()['message'] ?? 'unknown'));
                                    }
                                } else {
                                    $this->debugLog("ERROR: Invalid base64 content for additional document $fileName");
                                }
                            }

                            $stmt = $pdo->prepare('
                                INSERT INTO seller_additional_documents
                                (application_id, document_name, original_filename, file_path, file_size, mime_type, file_hash)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ');

                            $result = $stmt->execute([
                                $application_id,
                                $docName,
                                $fileName,
                                $filePath,
                                $fileSize,
                                $fileType,
                                $fileHash,
                            ]);

                            $this->debugLog("Additional document insert result for $docName ($fileName): " . ($result ? 'SUCCESS' : 'FAILURE'));
                        }
                    }
                }
            }

            // ===============================================================
            // 9. INSERT PROPERTY IMAGES
            // ===============================================================
            if (!empty($data['propertyImages'])) {
                $this->debugLog('Processing property images...');

                $images = $data['propertyImages'];
                if (is_array($images['files'])) {
                    foreach ($images['files'] as $index => $file) {
                        if (!empty($file['name'])) {
                            $imageName = 'property_image_' . $index . '_' . time();
                            $imagePath = "uploads/seller/property_images/$imageName";
                            $fullImagePath = \NURU_MATERIAL . '/' . $imagePath;
                            $originalFilename = $file['name'];
                            $fileSize = $file['size'] ?? 0;
                            $mimeType = $file['type'] ?? 'image/jpeg';
                            $fileHash = md5($imagePath . $fileSize);
                            $isPrimary = $index === 0 ? 1 : 0;

                            $imgDir = \NURU_MATERIAL . '/uploads/seller/property_images/';
                            if (!is_dir($imgDir)) {
                                mkdir($imgDir, 0755, true);
                            }

                            if (!empty($file['content'])) {
                                $imgDecoded = base64_decode($file['content'], true);
                                if ($imgDecoded !== false) {
                                    $imgSaveResult = file_put_contents($fullImagePath, $imgDecoded);
                                    if ($imgSaveResult !== false) {
                                        $this->debugLog("Property image saved to: $fullImagePath ($imgSaveResult bytes)");
                                    } else {
                                        $this->debugLog('ERROR: Failed to save property image: ' . (error_get_last()['message'] ?? 'unknown'));
                                    }
                                } else {
                                    $this->debugLog("ERROR: Invalid base64 content for property image $originalFilename");
                                }
                            }

                            $stmt = $pdo->prepare('
                                INSERT INTO seller_property_images
                                (application_id, image_name, original_filename, file_path, file_size, mime_type, file_hash, image_order, is_primary, propertyId)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ');

                            $result = $stmt->execute([
                                $application_id,
                                $imageName,
                                $originalFilename,
                                $imagePath,
                                $fileSize,
                                $mimeType,
                                $fileHash,
                                $index,
                                $isPrimary,
                                $propertyId,
                            ]);

                            $this->debugLog("Property image insert result for $originalFilename: " . ($result ? 'SUCCESS' : 'FAILURE'));
                        }
                    }
                }
            }

            // ===============================================================
            // 10. INSERT PROPERTY VIDEOS
            // ===============================================================
            if (!empty($data['propertyVideos'])) {
                $this->debugLog('Processing property videos...');

                $videos = $data['propertyVideos'];
                if (is_array($videos['files'])) {
                    foreach ($videos['files'] as $index => $file) {
                        if (!empty($file['name'])) {
                            $videoName = 'property_video_' . $index . '_' . time();
                            $videoPath = "uploads/seller/property_videos/$videoName";
                            $fullVideoPath = \NURU_MATERIAL . '/' . $videoPath;
                            $originalFilename = $file['name'];
                            $fileSize = $file['size'] ?? 0;
                            $mimeType = $file['type'] ?? 'video/mp4';
                            $fileHash = md5($videoPath . $fileSize);

                            $vidDir = \NURU_MATERIAL . '/uploads/seller/property_videos/';
                            if (!is_dir($vidDir)) {
                                mkdir($vidDir, 0755, true);
                            }

                            if (!empty($file['content'])) {
                                $vidDecoded = base64_decode($file['content'], true);
                                if ($vidDecoded !== false) {
                                    $vidSaveResult = file_put_contents($fullVideoPath, $vidDecoded);
                                    if ($vidSaveResult !== false) {
                                        $this->debugLog("Property video saved to: $fullVideoPath ($vidSaveResult bytes)");
                                    } else {
                                        $this->debugLog('ERROR: Failed to save property video: ' . (error_get_last()['message'] ?? 'unknown'));
                                    }
                                } else {
                                    $this->debugLog("ERROR: Invalid base64 content for property video $originalFilename");
                                }
                            }

                            $stmt = $pdo->prepare('
                                INSERT INTO seller_property_videos
                                (application_id, video_name, original_filename, file_path, file_size, mime_type, file_hash, video_order, propertyId)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ');

                            $result = $stmt->execute([
                                $application_id,
                                $videoName,
                                $originalFilename,
                                $videoPath,
                                $fileSize,
                                $mimeType,
                                $fileHash,
                                $index,
                                $propertyId,
                            ]);

                            $this->debugLog("Property video insert result for $originalFilename: " . ($result ? 'SUCCESS' : 'FAILURE'));
                        }
                    }
                }
            }

            // ===============================================================
            // 11. INSERT DECLARATIONS
            // ===============================================================
            $this->debugLog('Raw declarations data: ' . json_encode($data['declarations'] ?? 'NOT FOUND'));

            $certification = 0;
            $authorization = 0;
            $indemnification = 0;
            $commission = 0;
            $propertyRights = 0;

            if (isset($data['declarations'])) {
                $this->debugLog('Processing declarations...');
                $declarations = $data['declarations'];
                $this->debugLog('Declarations raw value: ' . json_encode($declarations));

                if (is_array($declarations) && !empty($declarations) && array_keys($declarations) !== range(0, count($declarations) - 1)) {
                    $this->debugLog('Processing as associative array');
                    $certification = !empty($declarations['certification']) ? 1 : 0;
                    $authorization = !empty($declarations['authorization']) ? 1 : 0;
                    $indemnification = !empty($declarations['indemnification']) ? 1 : 0;
                    $commission = !empty($declarations['commission']) ? 1 : 0;
                    $propertyRights = !empty($declarations['property_rights']) ? 1 : 0;
                } elseif (is_array($declarations) && !empty($declarations)) {
                    $this->debugLog('Processing as indexed array with ' . count($declarations) . ' items');
                    $certification = (!empty($declarations[0]) && $declarations[0] === 'on') ? 1 : 0;
                    $authorization = (!empty($declarations[1]) && $declarations[1] === 'on') ? 1 : 0;
                    $indemnification = (!empty($declarations[2]) && $declarations[2] === 'on') ? 1 : 0;
                    $commission = (!empty($declarations[3]) && $declarations[3] === 'on') ? 1 : 0;
                    $propertyRights = (!empty($declarations[4]) && $declarations[4] === 'on') ? 1 : 0;

                    if ($certification === 0) {
                        $certification = in_array('certification', $declarations, true) ? 1 : 0;
                    }
                    if ($authorization === 0) {
                        $authorization = in_array('authorization', $declarations, true) ? 1 : 0;
                    }
                    if ($indemnification === 0) {
                        $indemnification = in_array('indemnification', $declarations, true) ? 1 : 0;
                    }
                    if ($commission === 0) {
                        $commission = in_array('commission', $declarations, true) ? 1 : 0;
                    }
                    if ($propertyRights === 0) {
                        $propertyRights = in_array('property_rights', $declarations, true) ? 1 : 0;
                    }
                } else {
                    $this->debugLog('Checking individual declaration fields');
                    $certification = !empty($data['certification']) ? 1 : 0;
                    $authorization = !empty($data['authorization']) ? 1 : 0;
                    $indemnification = !empty($data['indemnification']) ? 1 : 0;
                    $commission = !empty($data['commission']) ? 1 : 0;
                    $propertyRights = !empty($data['property_rights']) ? 1 : 0;
                }

                $this->debugLog("Final declarations - certification: $certification, authorization: $authorization, indemnification: $indemnification, commission: $commission, propertyRights: $propertyRights");

                $stmt = $pdo->prepare('
                    INSERT INTO seller_declarations
                    (application_id, certification_declaration, authorization_declaration, indemnification_declaration, commission_fees_declaration, property_rights_declaration, signature_location, signature_date, signature_type, signature_file_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');

                $this->debugLog('Declaration parameters: ' . json_encode([
                    $certification, $authorization, $indemnification, $commission, $propertyRights,
                    $data['signatureLocation'] ?? null,
                    $data['signatureDate'] ?? null,
                    $data['signatureType'] ?? 'upload',
                ]));

                $signatureFilePath = null;
                if (!empty($data['signatureFile']['files'][0]['name'])) {
                    $signatureFileName = $data['signatureFile']['files'][0]['name'];
                    $signatureSafeName = $this->safeUploadFilename($signatureFileName, self::ALLOWED_IMAGE_EXTENSIONS);
                    if ($signatureSafeName === null) {
                        $this->debugLog("ERROR: Rejected signature upload with disallowed extension: $signatureFileName");
                        throw new \Exception('File type not allowed for signature');
                    }
                    $signatureFilePath = "uploads/seller/signature/$signatureSafeName";
                    $fullSignaturePath = \NURU_MATERIAL . '/' . $signatureFilePath;
                    $signatureDir = \NURU_MATERIAL . '/uploads/seller/signature/';

                    if (!is_dir($signatureDir)) {
                        mkdir($signatureDir, 0755, true);
                    }

                    $sigFileData = $data['signatureFile']['files'][0];
                    if (!empty($sigFileData['content'])) {
                        $sigDecoded = base64_decode($sigFileData['content'], true);
                        if ($sigDecoded !== false) {
                            $sigSaveResult = file_put_contents($fullSignaturePath, $sigDecoded);
                            if ($sigSaveResult !== false) {
                                $this->debugLog("Signature file saved to: $fullSignaturePath ($sigSaveResult bytes)");
                            } else {
                                $this->debugLog('ERROR: Failed to save signature file: ' . (error_get_last()['message'] ?? 'unknown'));
                            }
                        } else {
                            $this->debugLog('ERROR: Invalid base64 content for signature file');
                        }
                    } else {
                        $this->debugLog('No signature file content found - checking for direct upload');
                    }
                }

                $result = $stmt->execute([
                    $application_id,
                    $certification,
                    $authorization,
                    $indemnification,
                    $commission,
                    $propertyRights,
                    $data['signatureLocation'] ?? null,
                    $data['signatureDate'] ?? null,
                    $data['signatureType'] ?? 'upload',
                    $signatureFilePath,
                ]);

                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    $this->debugLog('Declaration insert error: ' . implode(' - ', $errorInfo));
                    throw new \Exception('Failed to insert declarations: ' . implode(' - ', $errorInfo));
                }

                $this->debugLog("Declarations insert result: SUCCESS - certification=$certification, authorization=$authorization, indemnification=$indemnification, commission=$commission, propertyRights=$propertyRights");
            } else {
                $this->debugLog('No declarations data found in request');
            }

            // ===============================================================
            // Commit Transaction
            // ===============================================================
            $this->debugLog('Committing seller transaction...');

            if (!$pdo->inTransaction()) {
                $errorInfo = $pdo->errorInfo();
                $pdoError = $errorInfo[2] ?? 'Unknown database error';
                $this->debugLog("ERROR: No active transaction - transaction was likely auto-rolled back. PDO Error: $pdoError");
                throw new \Exception('Transaction was rolled back due to an error. Please check the logs. Database error: ' . $pdoError);
            }

            $commitResult = $pdo->commit();
            $this->debugLog('Commit result: ' . ($commitResult ? 'SUCCESS' : 'FAILURE'));

            if (!$commitResult) {
                throw new \Exception('Failed to commit transaction');
            }

            $stmt = $pdo->prepare("
                UPDATE seller_applications
                SET status = 'submitted', submission_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$application_id]);

            $this->debugLog('Application status updated to submitted');

            $response = [
                'success' => true,
                'message' => 'Seller application submitted successfully!',
                'data' => [
                    'application_number' => $applicationNumber,
                    'application_id' => $application_id,
                    'status' => 'submitted',
                    'submission_date' => date('Y-m-d H:i:s'),
                ],
            ];

            $this->debugLog('Sending success response');
            echo json_encode($response, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            $this->debugLog('=== EXCEPTION CAUGHT ===');
            $this->debugLog('Exception Message: ' . $e->getMessage());
            $this->debugLog('Exception File: ' . $e->getFile());
            $this->debugLog('Exception Line: ' . $e->getLine());
            $this->debugLog('Exception Code: ' . $e->getCode());

            $trace = $e->getTrace();
            $this->debugLog('Exception Trace Details:');
            foreach ($trace as $i => $traceItem) {
                $this->debugLog("  [$i] " . ($traceItem['file'] ?? 'N/A') . ':' . ($traceItem['line'] ?? 'N/A') . ' - ' . ($traceItem['function'] ?? 'N/A'));
            }

            $lastErr = error_get_last();
            $this->debugLog('Last PHP Error: ' . ($lastErr['message'] ?? 'none'));
            $this->debugLog('Current PHP Version: ' . phpversion());
            $this->debugLog('Server request context captured without sensitive headers');

            if (isset($pdo) && $pdo->inTransaction()) {
                $this->debugLog('Rolling back transaction...');
                try {
                    $pdo->rollBack();
                    $this->debugLog('Transaction rolled back successfully');
                } catch (\Exception $rollbackException) {
                    $this->debugLog('Rollback failed: ' . $rollbackException->getMessage());
                }
            }

            $this->debugLog('Sending error response to client');

            // File paths, line numbers, stack traces, and PHP version were previously
            // sent to the CLIENT on every error - a reconnaissance gift to an attacker
            // (server layout, exact source lines, version for CVE targeting). All of
            // this is already captured above via debugLog() for real debugging; the
            // client only needs the user-facing message.
            $errorResponse = [
                'success' => false,
                'error' => 'Unable to submit the application. Please review the form and try again.',
                'application_number' => $applicationNumber ?? null,
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            echo json_encode($errorResponse, JSON_PRETTY_PRINT);
            $this->debugLog('=== EXCEPTION HANDLING COMPLETED ===');
        }
    }

    /**
     * POST /admin/review-seller-application - html/material/config/review_seller_application.php
     * Approve/reject a submitted application. Note: unlike store() above,
     * this endpoint's original used requireRole() (redirect-on-failure)
     * rather than sessionHasAuthoritativeRole() even though it is also
     * called from fetch() - kept exactly as-is rather than "fixed", since
     * that's a behavior change outside this migration's scope.
     */
    public function review(): void
    {
        Bootstrap::init();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');

        Auth::boot();
        require_once \NURU_MATERIAL . '/includes/functions.php';
        $this->requireRole(['admin', 'manager']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->reviewResponse(405, false, 'Method not allowed.');
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || !\validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'seller_management')) {
            $this->reviewResponse(403, false, 'Your session token has expired. Refresh the page and try again.');
        }
        $applicationId = filter_var($data['application_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $decision = (string) ($data['decision'] ?? '');
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($applicationId === false || !in_array($decision, ['approve', 'reject'], true) || mb_strlen($reason) > 1000) {
            $this->reviewResponse(422, false, 'Invalid seller review request.');
        }
        if ($decision === 'reject' && $reason === '') {
            $this->reviewResponse(422, false, 'A rejection reason is required.');
        }

        $model = new Seller($this->pdo);

        try {
            $this->pdo->beginTransaction();
            $application = $model->lockApplicationForReview((int) $applicationId);
            if (!$application) {
                $this->pdo->rollBack();
                $this->reviewResponse(404, false, 'Seller application not found.');
            }
            if (!in_array($application['status'], ['submitted', 'under_review'], true)) {
                $this->pdo->rollBack();
                $this->reviewResponse(409, false, 'This seller application has already been reviewed.');
            }

            if ($decision === 'approve') {
                $model->approve((int) $applicationId);
                $isActive = 1;
            } else {
                $model->reject((int) $applicationId, $reason);
                $isActive = 0;
            }
            if (!empty($application['user_id'])) {
                $model->setLinkedUserActive((int) $application['user_id'], $isActive);
            }
            $this->pdo->commit();
            $activity = $decision === 'approve' ? 'SELLER_APPROVED' : 'SELLER_REJECTED';
            \logActivity((int) $_SESSION['user_id'], $activity, "Seller application {$application['application_number']} {$decision}d", 'seller_form', $decision === 'reject' ? 'warning' : 'info');
            $this->reviewResponse(200, true, $decision === 'approve' ? 'Seller application approved.' : 'Seller application rejected.');
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Seller application review failed: ' . $e->getMessage());
            $this->reviewResponse(500, false, 'The seller application could not be reviewed.');
        }
    }

    private function reviewResponse(int $status, bool $success, string $message): never
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function debugLog(string $message): void
    {
        if (getenv('NURU_DEBUG_LOG') !== '1'
            || strtolower((string) (getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
            return;
        }
        $timestamp = date('Y-m-d H:i:s');
        $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $message), 0, 1000);
        $logMessage = "[$timestamp] $safeMessage\n";
        error_log($logMessage, 3, \NURU_MATERIAL . '/logs/debug.log');
    }

    /**
     * Allowed upload extensions, matching what the client-side forms already
     * advertise via <input accept>. Server-side enforcement is the only one that
     * actually matters - the client-side accept attribute is trivially bypassed
     * by posting directly to this endpoint. Never trust the client-supplied
     * filename for the saved path (path traversal risk); always regenerate one.
     */
    private function safeUploadFilename(string $originalName, array $allowedExtensions): ?string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return null;
        }
        return bin2hex(random_bytes(16)) . '.' . $ext;
    }

    private function createAdminUserIfNotExists(\PDO $pdo, array $data): mixed
    {
        if (empty($data['email'])) {
            return null;
        }

        // Check if user already exists
        $check = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
        $check->execute([$data['email']]);
        $existingUserId = $check->fetchColumn();

        if ($existingUserId) {
            return $existingUserId; // Do NOT resend email
        }

        // Previously hardcoded role='manager' here, silently granting full
        // internal admin access to anyone who submitted a seller application.
        // Fixed to use the dedicated, properly-scoped 'seller' role
        // (dashboard_5.php - own application only, no sidebar).
        $temporaryPassword = \generateTemporaryPassword();
        $defaultPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $role = 'seller';

        $username = strtolower(
            preg_replace('/\s+/', '.', $data['firstName'] . '.' . $data['surname'])
        );

        $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['surname'] ?? ''));

        $stmt = $pdo->prepare('
            INSERT INTO admin_users
            (username, email, password_hash, full_name, role, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ');

        $stmt->execute([
            $username,
            $data['email'],
            $defaultPassword,
            $fullName,
            $role,
        ]);

        $adminUserId = $pdo->lastInsertId();

        $mailSent = \sendTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword);
        $this->debugLog("Login email sent to {$data['email']}: " . ($mailSent ? 'YES' : 'NO'));

        return $adminUserId;
    }

    /**
     * FIXED: removed nested transaction + LOCK/UNLOCK TABLES (these break outer transactions)
     */
    private function generateSellerApplicationNumber(\PDO $pdo): string
    {
        $prefix = 'SELL';
        $date = date('Ymd');

        $stmt = $pdo->prepare('
            SELECT COALESCE(MAX(CAST(SUBSTRING(application_number, 15) AS UNSIGNED)), 0) AS max_seq
            FROM seller_applications
            WHERE application_number LIKE ?
        ');
        $pattern = "$prefix-$date-%";
        $stmt->execute([$pattern]);

        $maxSeq = (int) ($stmt->fetchColumn() ?: 0);
        $nextSeq = $maxSeq + 1;

        $applicationNumber = sprintf('%s-%s-%04d', $prefix, $date, $nextSeq);
        $this->debugLog("Generated application number: $applicationNumber");

        return $applicationNumber;
    }
}

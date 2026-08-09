<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Controller;
use App\Core\Router;
use App\Models\Buyer;

/**
 * Buyer application management for staff (admin/manager/agent_coordinator/
 * agent_consultant), ported from buyers-list.php, buyerlist_table.php,
 * buyer_admin_form.php, admin_buyer_processor.php, buyers_profile.php and
 * config/approve_buyer.php + config/delete_buyer.php.
 *
 * store() keeps admin_buyer_processor.php's form-processing logic inline
 * rather than splitting it into several Model calls - it's one linear,
 * security-sensitive transaction (validation, file uploads, several related
 * inserts, account provisioning) tightly coupled to this one request, the
 * same judgement call already made for AuthController::login().
 */
final class BuyerController extends Controller
{
    private const ALLOWED_UPLOAD_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'gif', 'tiff', 'bmp'];

    /** buyers-list.php: role-scoped buyer list (agent_coordinator sees only their own). */
    public function list(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        require_once \NURU_MATERIAL . '/config/id_tokens.php';

        $this->render('admin.buyers.list', [
            'csrfToken' => Auth::csrfToken('buyer_management'),
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** buyer_admin_form.php: staff-facing buyer application wizard. */
    public function form(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager']);

        $this->render('admin.buyers.form', [
            'csrfToken' => Auth::csrfToken('staff_buyer_form'),
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** buyers_profile.php: full profile for a single buyer, with role-scoped ownership checks. */
    public function profile(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator', 'agent_consultant']);

        require_once \NURU_MATERIAL . '/config/id_tokens.php';

        $errorMessage = null;
        $errorStatus = null;
        $buyer = null;
        $documents = [];

        if (empty($_GET['id'])) {
            $errorMessage = 'Buyer reference missing';
        } else {
            $buyerId = \portalDecodeId($_GET['id']);
            if ($buyerId === null) {
                $errorMessage = 'Invalid or corrupted buyer reference';
            } else {
                $model = new Buyer($this->pdo);
                $buyer = $model->findProfile($buyerId);

                if (!$buyer) {
                    $errorMessage = 'Buyer not found';
                } elseif (Auth::currentRole() === 'agent_coordinator') {
                    $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
                    if (!$model->isVisibleToAgent($buyerId, $myAgentId, (int) $_SESSION['user_id'])) {
                        $errorMessage = 'You are not assigned to this buyer';
                        $errorStatus = 403;
                    }
                } elseif (Auth::currentRole() === 'agent_consultant') {
                    if (!$model->isVisibleToConsultant($buyerId, (int) $_SESSION['user_id'])) {
                        $errorMessage = 'You do not have access to this consultation';
                        $errorStatus = 403;
                    }
                }

                if ($errorMessage === null) {
                    $documents = $model->documents($buyerId);
                }
            }
        }

        $this->render('admin.buyers.profile', [
            'errorMessage' => $errorMessage,
            'errorStatus' => $errorStatus,
            'buyer' => $buyer,
            'documents' => $documents,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** config/approve_buyer.php: AJAX endpoint, moves a pending buyer to approved. */
    public function approve(): void
    {
        header('Content-Type: application/json');
        Auth::boot();
        require_once \NURU_MATERIAL . '/includes/functions.php';

        if (!\sessionHasAuthoritativeRole(['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        if (!Auth::validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'buyer_management')) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload and try again.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $buyerId = isset($data['buyer_id']) ? (int) $data['buyer_id'] : 0;

        if (!$buyerId) {
            echo json_encode(['success' => false, 'message' => 'Invalid buyer ID']);
            exit;
        }

        $model = new Buyer($this->pdo);
        try {
            if (!$model->approve($buyerId)) {
                echo json_encode(['success' => false, 'message' => 'Buyer not found or already reviewed']);
                exit;
            }
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Buyer approval failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to approve the buyer.']);
            exit;
        }

        \logActivity((int) $_SESSION['user_id'], 'BUYER_APPROVED', "Buyer #$buyerId approved", 'buyer_form');

        echo json_encode(['success' => true]);
    }

    /** config/delete_buyer.php: AJAX endpoint, moves a pending buyer to rejected (never deletes the row). */
    public function reject(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private, max-age=0');
        Auth::boot();
        require_once \NURU_MATERIAL . '/includes/functions.php';

        if (!\sessionHasAuthoritativeRole(['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        if (!Auth::validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, 'buyer_management')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Your session token has expired. Refresh the page and try again.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $buyerId = filter_var($data['buyer_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($buyerId === false) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid buyer ID']);
            exit;
        }

        $model = new Buyer($this->pdo);
        try {
            if (!$model->reject((int) $buyerId)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Buyer not found or already reviewed']);
                exit;
            }
            \logActivity((int) $_SESSION['user_id'], 'BUYER_REJECTED', "Buyer #{$buyerId} rejected", 'buyer_form', 'warning');
            echo json_encode(['success' => true, 'message' => 'Buyer application rejected']);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Buyer rejection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to reject the buyer.']);
        }
    }

    /**
     * admin_buyer_processor.php: handles the staff buyer application form
     * POST. Logic ported near-verbatim (validation rules, insert order,
     * upload handling, account provisioning) - see class doc comment for
     * why this stays as one method instead of being split into the Model.
     */
    public function store(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        Auth::boot();
        require_once \NURU_MATERIAL . '/config/account_provisioning.php';
        require_once \NURU_MATERIAL . '/api/security_integration.php';

        $pdo = $this->pdo;

        try {
            if (!$pdo) {
                throw new \Exception('Database connection failed');
            }

            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                throw new \Exception('Invalid request method');
            }

            if (!\sessionHasAuthoritativeRole(['admin', 'manager'])) {
                http_response_code(403);
                throw new \Exception('You are not authorized to submit this form');
            }

            $data = $_POST;

            if (empty($data)) {
                throw new \Exception('No form data submitted');
            }

            if (!Auth::validCsrfToken($data['csrf_token'] ?? null, 'staff_buyer_form')) {
                http_response_code(419);
                throw new \Exception('Your session has expired. Please reload and try again.');
            }

            // Server-side email validation - there was previously none at all, only
            // the client-side <input type="email">, which is trivially bypassed by
            // posting directly to this endpoint. An unvalidated email also becomes
            // an email-header-injection vector (CRLF in the value) the moment a
            // working SMTP relay is configured, so this matters even though mail()
            // isn't currently delivering anything in this environment.
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->buyerDebugLog("Rejected invalid email: {$data['email']}");
                throw new \Exception('Please provide a valid email address');
            }

            $this->buyerDebugLog('Buyer form submitted');

            $pdo->beginTransaction();

            $applicationNumber = $this->generateBuyerApplicationNumber($pdo);

            // Build full name from first and last name
            $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));

            // Prepare address
            $address = '';
            if (!empty($data['addressLine1'])) {
                $address = $data['addressLine1'];
                if (!empty($data['addressLine2'])) {
                    $address .= ', ' . $data['addressLine2'];
                }
            }

            // 1. Insert buyer main record
            $stmt = $pdo->prepare('
                INSERT INTO buyers (
                    application_number, full_name, nationality, gender, property_type, price_type, date_of_birth, id_type, id_number,
                    marital_status, phone, email, region, town, address,
                    employment_type, employer_name, position, monthly_income,
                    property_value, down_payment, loan_amount, loaded_by, additional_requirements,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                $applicationNumber,
                $fullName,
                $data['nationality'] ?? null,
                $data['gender'] ?? null,
                $data['propertyType'] ?? null,
                $data['priceType'] ?? null,
                $data['dateOfBirth'] ?? null,
                $data['idType'] ?? null,
                $data['idNumber'] ?? null,
                $data['maritalStatus'] ?? null,
                $data['contactNumber'] ?? null,
                $data['email'] ?? null,
                $data['region'] ?? null,
                $data['town'] ?? null,
                $address ?: null,
                $data['employmentType'] ?? null,
                $data['employerName'] ?? null,
                $data['jobTitle'] ?? null,
                isset($data['monthlyIncome']) ? (float) $data['monthlyIncome'] : 0,
                isset($data['propertyValue']) ? (float) $data['propertyValue'] : 0,
                isset($data['downPayment']) ? (float) $data['downPayment'] : 0,
                isset($data['loanAmount']) ? (float) $data['loanAmount'] : 0,
                $_SESSION['user_id'] ?? 'Self Loaded',
                $data['additionalRequirements'] ?? '',
            ]);

            $buyerId = (int) $pdo->lastInsertId();

            $this->buyerDebugLog('Buyer created with ID: ' . $buyerId);

            // 2. Spouse info (if married)
            if (($data['maritalStatus'] ?? '') === 'married' && !empty($data['spouseFullName'])) {
                $stmt = $pdo->prepare('
                    INSERT INTO buyer_spouse (buyer_id, full_name, id_passport, date_of_birth, phone, email)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $buyerId,
                    $data['spouseFullName'] ?? null,
                    $data['spouseIdPassport'] ?? null,
                    $data['spouseDateOfBirth'] ?? null,
                    $data['spouseContactNumber'] ?? null,
                    $data['spouseEmail'] ?? null,
                ]);
            }

            // 3. Next of kin
            if (!empty($data['nokFirstName']) || !empty($data['nokLastName'])) {
                $nokFullName = trim(($data['nokFirstName'] ?? '') . ' ' . ($data['nokLastName'] ?? ''));
                $nokAddress = '';
                if (!empty($data['nokAddress'])) {
                    $nokAddress = $data['nokAddress'];
                }

                $stmt = $pdo->prepare('
                    INSERT INTO buyer_next_of_kin (buyer_id, full_name, relationship, phone, email, region, town, address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $buyerId,
                    $nokFullName,
                    $data['nokRelationship'] ?? null,
                    $data['nokContactNumber'] ?? null,
                    $data['nokEmail'] ?? null,
                    $data['nokRegion'] ?? null,
                    $data['nokTown'] ?? null,
                    $nokAddress ?: null,
                ]);
            }

            // 4. Preferred areas
            if (!empty($data['preferredRegion']) && is_array($data['preferredRegion'])) {
                $stmt = $pdo->prepare('INSERT INTO buyer_preferred_areas (buyer_id, region, town, location, suburb) VALUES (?, ?, ?, ?, ?)');

                $regions = $data['preferredRegion'];
                $towns = $data['preferredTown'] ?? [];
                $locations = $data['preferredLocation'] ?? [];
                $suburbs = $data['preferredSuburb'] ?? [];

                $count = count($regions);
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($regions[$i])) {
                        $stmt->execute([
                            $buyerId,
                            $regions[$i] ?? null,
                            $towns[$i] ?? null,
                            $locations[$i] ?? null,
                            $suburbs[$i] ?? null,
                        ]);
                    }
                }
            }

            // 5. Additional requirements
            if (!empty($data['additionalRequirements'])) {
                $stmt = $pdo->prepare('UPDATE buyers SET additional_requirements = ? WHERE id = ?');
                $stmt->execute([$data['additionalRequirements'], $buyerId]);
            }

            // 6. Declarations
            if (!empty($data['declarations']) && is_array($data['declarations'])) {
                $stmt = $pdo->prepare('INSERT INTO buyer_declarations (buyer_id, declaration, accepted, created_at) VALUES (?, ?, 1, NOW())');
                foreach ($data['declarations'] as $declaration) {
                    $stmt->execute([$buyerId, $declaration]);
                }
            }

            // 7. Buyer documents
            $savedDocs = $this->saveBuyerUploadedFiles($buyerId);
            if (!empty($savedDocs)) {
                $stmt = $pdo->prepare('INSERT INTO buyer_documents (buyer_id, doc_type, file_path, uploaded_at) VALUES (?, ?, ?, NOW())');
                foreach ($savedDocs as $doc) {
                    $stmt->execute([$buyerId, $doc['doc_type'], $doc['file_path']]);
                }
            }

            // 8. Create buyer user account
            $this->createBuyerUserIfNotExists($pdo, $data, $buyerId);

            $pdo->commit();

            $this->buyerDebugLog('Buyer application submitted successfully: ' . $applicationNumber);

            // Redirect to success page or return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                // AJAX request - return JSON (the wizard always submits this way)
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'buyer_id' => $buyerId,
                    'application_number' => $applicationNumber,
                    'message' => 'Buyer application submitted successfully',
                ]);
            } else {
                // Regular (non-AJAX) form submission - redirect to success page.
                // Absolute so the redirect resolves the same way whether this
                // request landed via the legacy html/material/admin_buyer_processor.php
                // URL or the clean /admin/admin-buyer-processor route.
                $_SESSION['application_success'] = true;
                $_SESSION['application_number'] = $applicationNumber;
                header('Location: ' . Router::basePath() . '/html/material/buyer-success.php?app=' . $applicationNumber);
                exit;
            }
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->buyerDebugLog('ERROR: ' . $e->getMessage(), 'ERROR');

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Unable to submit the application. Please review the form and try again.',
                ]);
            } else {
                $_SESSION['form_error'] = 'Unable to submit the application. Please review the form and try again.';
                header('Location: ' . Router::basePath() . '/admin/buyer-admin-form?error=submit_failed');
                exit;
            }
        }
    }

    private function buyerDebugLog(string $msg, string $level = 'INFO'): void
    {
        if (getenv('NURU_DEBUG_LOG') !== '1'
            || strtolower((string) (getenv('NURU_APP_ENV') ?: 'production')) === 'production') {
            return;
        }
        $time = date('Y-m-d H:i:s');
        $safeMessage = substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', $msg), 0, 1000);
        @error_log("[$time] [$level] $safeMessage\n", 3, \NURU_MATERIAL . '/logs/debug.log');
    }

    private function generateBuyerApplicationNumber(\PDO $pdo): string
    {
        $prefix = 'BUY';
        $date = date('Ymd');
        $stmt = $pdo->query('SELECT COUNT(*) FROM buyers');
        $count = $stmt->fetchColumn() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    private function createBuyerUserIfNotExists(\PDO $pdo, array $data, int $buyerId): ?int
    {
        if (empty($data['email'])) {
            return null;
        }

        $check = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
        $check->execute([$data['email']]);
        $existing = $check->fetch(\PDO::FETCH_ASSOC);
        if ($existing) {
            return (int) $existing['id'];
        }

        $username = strtolower(preg_replace('/\s+/', '.', ($data['firstName'] ?? 'Buyer') . '.' . ($data['lastName'] ?? '')));
        if (empty($username)) {
            $username = 'buyer.' . uniqid();
        }

        $temporaryPassword = \generateTemporaryPassword();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $fullName = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));

        $stmt = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())');
        $stmt->execute([$username, $data['email'], $passwordHash, $fullName, 'buyer']);
        if (!\sendTemporaryCredentialEmail($data['email'], $fullName, $temporaryPassword)) {
            error_log('Buyer account provisioned but credential email could not be sent for user ' . $username);
        }

        return (int) $pdo->lastInsertId();
    }

    // basename() already strips path-traversal segments from the uploaded
    // filename below, but nothing was checking the extension - a file named
    // "shell.php" would be saved as-is into the web-servable uploads/ tree.
    // (ALLOWED_UPLOAD_EXTENSIONS ported from the original file for parity;
    // actual validation runs through SecurityIntegration::validateFileUpload().)
    private function saveBuyerUploadedFiles(int $buyerId): array
    {
        $saved = [];

        // Define allowed document types and their field names
        $docFields = [
            'id_passport' => 'id_passport',
            'proof_of_income' => 'proof_of_income',
            'bank_statements' => 'bank_statements',
            'employment_letter' => 'employment_letter',
            'marriage_certificate' => 'marriage_certificate',
        ];

        $uploadDir = \NURU_MATERIAL . '/uploads/buyers/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        foreach ($docFields as $docType => $fieldName) {
            if (!empty($_FILES[$fieldName]['name']) && $_FILES[$fieldName]['error'] === 0) {
                $file = $_FILES[$fieldName];

                $validation = \SecurityIntegration::validateFileUpload($file);
                if (!$validation['success']) {
                    $this->buyerDebugLog("Rejected $docType upload: " . implode(', ', $validation['errors']), 'ERROR');
                    continue;
                }

                $filename = $buyerId . '_' . $docType . '_' . $validation['safe_filename'];
                $target = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $saved[] = [
                        'file_path' => 'uploads/buyers/' . $filename,
                        'doc_type' => $docType,
                    ];
                }
            }
        }

        // Handle signature file upload
        if (!empty($_FILES['signatureFile']['name']) && $_FILES['signatureFile']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['signatureFile'];
            $validation = \SecurityIntegration::validateFileUpload($file);
            if ($validation['success'] && $file['size'] <= 5 * 1024 * 1024) {
                $filename = $buyerId . '_signature_' . $validation['safe_filename'];
                $target = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $saved[] = [
                        'file_path' => 'uploads/buyers/' . $filename,
                        'doc_type' => 'signature',
                    ];
                }
            }
        }

        return $saved;
    }
}

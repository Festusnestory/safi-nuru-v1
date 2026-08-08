<?php
/**
 * Agent Form Handler Class - Portal Integration with Advanced Security
 * Handles agent form data and integrates with portal authentication and security
 * Author: MiniMax Agent
 */

// Define portal access
if (!defined('PORTAL_ACCESS')) { define('PORTAL_ACCESS', true); }
if (!defined('NURU_API_ACCESS')) { define('NURU_API_ACCESS', true); }

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/agent_security_middleware.php';
// Database connection will be handled internally

class AgentFormHandler {
    private $db;
    private $sessionId;
    private $userId;
    
    public function __construct() {
        // Initialize security middleware
        AgentSecurityMiddleware::init();
        
        // Check portal authentication
        if (!PortalAuth::isAuthenticated()) {
            throw new Exception("Authentication required");
        }
        
        // Get database connection from portal's database configuration
        $this->db = $this->getDatabaseConnection();
        $user = PortalAuth::getCurrentUser();
        $this->userId = $user['id'] ?? null;
        
        if (!$this->userId) {
            throw new Exception("Invalid user session");
        }
        
        $this->initSession();
        
        // Log form handler initialization
        AgentSecurityMiddleware::logActivity('FORM_HANDLER_INIT', 'Agent form handler initialized');
    }
    
    private function initSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['agent_form_session_id'])) {
            $_SESSION['agent_form_session_id'] = bin2hex(random_bytes(16));
        }
        
        $this->sessionId = $_SESSION['agent_form_session_id'];
        
        // Initialize form session in database
        $this->initFormSession();
    }
    
    private function initFormSession() {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO agent_form_sessions (session_id, user_id, current_step, csrf_token, expires_at, ip_address, user_agent, created_at)
                 VALUES (?, ?, 1, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR), ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE last_activity = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR)"
            );
            
            $csrfToken = $this->generateCSRFToken();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([$this->sessionId, $this->userId, $csrfToken, $ipAddress, $userAgent]);
            
            // Store CSRF token in session
            $_SESSION['agent_csrf_token'] = $csrfToken;
            
        } catch (PDOException $e) {
            error_log("Error initializing agent form session: " . $e->getMessage());
            throw new Exception("Session initialization failed");
        }
    }
    
    private function generateCSRFToken() {
        return AgentSecurityMiddleware::generateCSRFToken('form_session');
    }
    
    public function validateCSRFToken($token) {
        return SecurityIntegration::validateCSRFToken($token, 'agent_form_form_session');
    }
    
    public function getSessionId() {
        return $this->sessionId;
    }
    
    public function getCurrentStep() {
        try {
            $stmt = $this->db->prepare(
                "SELECT current_step FROM agent_form_sessions WHERE session_id = ? AND user_id = ?"
            );
            $stmt->execute([$this->sessionId, $this->userId]);
            $result = $stmt->fetch();
            
            return $result ? (int)$result['current_step'] : 1;
        } catch (PDOException $e) {
            error_log("Error getting current step: " . $e->getMessage());
            return 1;
        }
    }
    
    public function updateStep($step) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE agent_form_sessions SET current_step = ?
                 WHERE session_id = ? AND user_id = ?"
            );
            $stmt->execute([$step, $this->sessionId, $this->userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating step: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get appropriate input context for sanitization
     */
    private function getInputContext($fieldName) {
        $contexts = [
            'email' => 'email',
            'res_email' => 'email',
            'kin_email' => 'email',
            'emp_email' => 'email',
            'mobile_number' => 'phone',
            'kin_contact_number' => 'phone',
            'id_number' => 'alphanumeric',
            'employment_number' => 'alphanumeric',
            'gross_income' => 'general',
            'total_deductions' => 'general'
        ];
        
        return $contexts[$fieldName] ?? 'general';
    }
    
    /**
     * Validate step data with enhanced security
     */
    public function validateStepData($stepData, $step) {
        $errors = [];
        
        try {
            switch ($step) {
                case 1: // Personal Details
                    $errors = array_merge($errors, $this->validatePersonalDetails($stepData));
                    break;
                    
                case 2: // Residential Address
                    $errors = array_merge($errors, $this->validateResidentialAddress($stepData));
                    break;
                    
                case 3: // Next of Kin
                    $errors = array_merge($errors, $this->validateNextOfKin($stepData));
                    break;
                    
                case 4: // Employment Details
                    $errors = array_merge($errors, $this->validateEmploymentDetails($stepData));
                    break;
                    
                default:
                    $errors[] = "Invalid step number: $step";
            }
            
            if (!empty($errors)) {
                AgentSecurityMiddleware::logActivity('VALIDATION_FAILED', "Step $step validation failed", [
                    'step' => $step,
                    'error_count' => count($errors),
                    'errors' => array_slice($errors, 0, 3) // Log first 3 errors only
                ]);
            }
            
            return $errors;
        } catch (Exception $e) {
            AgentSecurityMiddleware::logActivity('ERROR', "Error validating step $step", [
                'step' => $step,
                'error' => $e->getMessage()
            ]);
            return ['Validation error occurred'];
        }
    }
    
    /**
     * Validate personal details (Step 1)
     */
    private function validatePersonalDetails($data) {
        $errors = [];
        
        // Required fields
        $required = ['surname', 'first_name', 'date_of_birth', 'id_type', 'id_number', 'nationality', 'gender'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Date validation
        if (!empty($data['date_of_birth'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_of_birth']);
            if (!$date || $date->format('Y-m-d') !== $data['date_of_birth']) {
                $errors[] = "Invalid date of birth format";
            } else {
                $age = $date->diff(new DateTime())->y;
                if ($age < 18 || $age > 100) {
                    $errors[] = "Age must be between 18 and 100 years";
                }
            }
        }
        
        // ID number validation
        if (!empty($data['id_number'])) {
            if (!preg_match('/^[A-Z0-9]+$/', $data['id_number'])) {
                $errors[] = "ID number can only contain letters and numbers";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate residential address (Step 2)
     */
    private function validateResidentialAddress($data) {
        $errors = [];
        
        // Required fields
        $required = ['town', 'email', 'mobile_number'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address format";
        }
        
        // Phone validation
        if (!empty($data['mobile_number'])) {
            $phone = preg_replace('/[^0-9+]/', '', $data['mobile_number']);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $errors[] = "Invalid mobile number format";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate next of kin (Step 3)
     */
    private function validateNextOfKin($data) {
        $errors = [];
        
        // Required fields
        $required = ['kin_surname', 'kin_first_name', 'kin_contact_number'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $fieldName = str_replace(['kin_', '_'], ['', ' '], $field);
                $errors[] = "Next of kin " . ucfirst($fieldName) . " is required";
            }
        }
        
        // Email validation (optional)
        if (!empty($data['kin_email']) && !filter_var($data['kin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid next of kin email address format";
        }
        
        // Phone validation
        if (!empty($data['kin_contact_number'])) {
            $phone = preg_replace('/[^0-9+]/', '', $data['kin_contact_number']);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $errors[] = "Invalid next of kin contact number format";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate employment details (Step 4)
     */
    private function validateEmploymentDetails($data) {
        $errors = [];
        
        // Required fields
        $required = ['company_name', 'job_title', 'gross_income'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Income validation
        if (!empty($data['gross_income'])) {
            $income = (float)$data['gross_income'];
            if ($income <= 0 || $income > 10000000) {
                $errors[] = "Gross income must be a positive number less than 10,000,000";
            }
        }
        
        if (!empty($data['total_deductions'])) {
            $deductions = (float)$data['total_deductions'];
            if ($deductions < 0) {
                $errors[] = "Total deductions cannot be negative";
            }
        }
        
        // Email validation (optional)
        if (!empty($data['emp_email']) && !filter_var($data['emp_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid employment email address format";
        }
        
        return $errors;
    }
    
    public function saveFormData($stepData, $step) {
        try {
            // Get existing form data
            $stmt = $this->db->prepare(
                "SELECT form_data FROM agent_form_sessions WHERE session_id = ? AND user_id = ?"
            );
            $stmt->execute([$this->sessionId, $this->userId]);
            $result = $stmt->fetch();
            
            $existingData = $result && $result['form_data'] ? json_decode($result['form_data'], true) : [];
            
            // Merge with new step data
            $existingData["step_$step"] = $stepData;
            
            // Update in database
            $stmt = $this->db->prepare(
                "UPDATE agent_form_sessions SET form_data = ?
                 WHERE session_id = ? AND user_id = ?"
            );
            $stmt->execute([json_encode($existingData), $this->sessionId, $this->userId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error saving form data: " . $e->getMessage());
            return false;
        }
    }
    
    public function getFormData() {
        try {
            $stmt = $this->db->prepare(
                "SELECT form_data FROM agent_form_sessions WHERE session_id = ? AND user_id = ?"
            );
            $stmt->execute([$this->sessionId, $this->userId]);
            $result = $stmt->fetch();
            
            return $result && $result['form_data'] ? json_decode($result['form_data'], true) : [];
        } catch (PDOException $e) {
            error_log("Error getting form data: " . $e->getMessage());
            return [];
        }
    }
    
    public function getNamibianTowns() {
        try {
            // Try to get from existing buyer/seller data tables first
            $stmt = $this->db->prepare(
                "SELECT DISTINCT town_name, region_name FROM
                 (SELECT town as town_name, region as region_name FROM buyers
                  UNION
                  SELECT property_town as town_name, property_region as region_name FROM seller_properties) as towns
                 WHERE town_name IS NOT NULL AND town_name != ''
                 ORDER BY town_name ASC"
            );
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            if (empty($result)) {
                // Fallback to hardcoded Namibian towns if no data exists
                return $this->getDefaultNamibianTowns();
            }
            
            return array_map(function($row) {
                return [
                    'town_name' => $row['town_name'],
                    'region_name' => $row['region_name']
                ];
            }, $result);
        } catch (PDOException $e) {
            error_log("Error getting towns: " . $e->getMessage());
            return $this->getDefaultNamibianTowns();
        }
    }
    
    private function getDefaultNamibianTowns() {
        return [
            ['town_name' => 'Windhoek', 'region_name' => 'Khomas'],
            ['town_name' => 'Swakopmund', 'region_name' => 'Erongo'],
            ['town_name' => 'Walvis Bay', 'region_name' => 'Erongo'],
            ['town_name' => 'Rundu', 'region_name' => 'Kavango East'],
            ['town_name' => 'Oshakati', 'region_name' => 'Oshana'],
            ['town_name' => 'Katima Mulilo', 'region_name' => 'Zambezi'],
            ['town_name' => 'Otjiwarongo', 'region_name' => 'Otjozondjupa'],
            ['town_name' => 'Gobabis', 'region_name' => 'Omaheke'],
            ['town_name' => 'Keetmanshoop', 'region_name' => 'Karas'],
            ['town_name' => 'Tsumeb', 'region_name' => 'Oshikoto'],
            ['town_name' => 'Grootfontein', 'region_name' => 'Otjozondjupa'],
            ['town_name' => 'Rehoboth', 'region_name' => 'Hardap'],
            ['town_name' => 'Mariental', 'region_name' => 'Hardap'],
            ['town_name' => 'Okahandja', 'region_name' => 'Otjozondjupa'],
            ['town_name' => 'Ondangwa', 'region_name' => 'Oshana']
        ];
    }
    
    public function getCountries() {
        // Return list of countries/nationalities
        return [
            'Afghan', 'Albanian', 'Algerian', 'American', 'Andorran', 'Angolan', 'Antiguans', 'Argentinean', 'Armenian', 'Australian',
            'Austrian', 'Azerbaijani', 'Bahamian', 'Bahraini', 'Bangladeshi', 'Barbadian', 'Barbudans', 'Batswana', 'Belarusian',
            'Belgian', 'Belizean', 'Beninese', 'Bhutanese', 'Bolivian', 'Bosnian', 'Brazilian', 'British', 'Bruneian', 'Bulgarian',
            'Burkinabe', 'Burmese', 'Burundian', 'Cambodian', 'Cameroonian', 'Canadian', 'Cape Verdean', 'Central African',
            'Chadian', 'Chilean', 'Chinese', 'Colombian', 'Comoran', 'Congolese', 'Costa Rican', 'Croatian', 'Cuban', 'Cypriot',
            'Czech', 'Danish', 'Djibouti', 'Dominican', 'Dutch', 'East Timorese', 'Ecuadorean', 'Egyptian', 'Emirian',
            'Equatorial Guinean', 'Eritrean', 'Estonian', 'Ethiopian', 'Fijian', 'Filipino', 'Finnish', 'French', 'Gabonese',
            'Gambian', 'Georgian', 'German', 'Ghanaian', 'Greek', 'Grenadian', 'Guatemalan', 'Guinea-Bissauan', 'Guinean',
            'Guyanese', 'Haitian', 'Herzegovinian', 'Honduran', 'Hungarian', 'I-Kiribati', 'Icelander', 'Indian', 'Indonesian',
            'Iranian', 'Iraqi', 'Irish', 'Israeli', 'Italian', 'Ivorian', 'Jamaican', 'Japanese', 'Jordanian', 'Kazakhstani',
            'Kenyan', 'Kittian and Nevisian', 'Kuwaiti', 'Kyrgyz', 'Laotian', 'Latvian', 'Lebanese', 'Liberian', 'Libyan',
            'Liechtensteiner', 'Lithuanian', 'Luxembourger', 'Macedonian', 'Malagasy', 'Malawian', 'Malaysian', 'Maldivan',
            'Malian', 'Maltese', 'Marshallese', 'Mauritanian', 'Mauritian', 'Mexican', 'Micronesian', 'Moldovan', 'Monacan',
            'Mongolian', 'Moroccan', 'Mosotho', 'Motswana', 'Mozambican', 'Namibian', 'Nauruan', 'Nepalese', 'New Zealander',
            'Nicaraguan', 'Nigerian', 'Nigerien', 'North Korean', 'Northern Irish', 'Norwegian', 'Omani', 'Pakistani', 'Palauan',
            'Panamanian', 'Papua New Guinean', 'Paraguayan', 'Peruvian', 'Polish', 'Portuguese', 'Qatari', 'Romanian', 'Russian',
            'Rwandan', 'Saint Lucian', 'Salvadoran', 'Samoan', 'San Marinese', 'Sao Tomean', 'Saudi', 'Scottish', 'Senegalese',
            'Serbian', 'Seychellois', 'Sierra Leonean', 'Singaporean', 'Slovakian', 'Slovenian', 'Solomon Islander', 'Somali',
            'South African', 'South Korean', 'Spanish', 'Sri Lankan', 'Sudanese', 'Surinamer', 'Swazi', 'Swedish', 'Swiss',
            'Syrian', 'Taiwanese', 'Tajik', 'Tanzanian', 'Thai', 'Togolese', 'Tongan', 'Trinidadian or Tobagonian', 'Tunisian',
            'Turkish', 'Tuvaluan', 'Ugandan', 'Ukrainian', 'Uruguayan', 'Uzbekistani', 'Venezuelan', 'Vietnamese', 'Welsh',
            'Yemenite', 'Zambian', 'Zimbabwean'
        ];
    }
    
    public function getRegionByTown($townName) {
        try {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT region_name FROM
                 (SELECT town as town_name, region as region_name FROM buyers
                  UNION
                  SELECT property_town as town_name, property_region as region_name FROM seller_properties) as towns
                 WHERE town_name = ? LIMIT 1"
            );
            $stmt->execute([$townName]);
            $result = $stmt->fetch();
            
            if ($result) {
                return $result['region_name'];
            }
            
            // Fallback to default mapping
            $defaultTowns = $this->getDefaultNamibianTowns();
            foreach ($defaultTowns as $town) {
                if ($town['town_name'] === $townName) {
                    return $town['region_name'];
                }
            }
            
            return '';
        } catch (PDOException $e) {
            error_log("Error getting region: " . $e->getMessage());
            return '';
        }
    }
    
    public function saveUploadedFile($fileInfo) {
        try {
            // Replace any prior upload of the same document type for this session
            $this->db->prepare(
                "DELETE FROM agent_documents WHERE session_id = ? AND application_id IS NULL AND document_type = ?"
            )->execute([$this->sessionId, $fileInfo['file_type']]);

            $stmt = $this->db->prepare(
                "INSERT INTO agent_documents (session_id, application_id, document_type,
                 document_name, original_filename, stored_filename, file_path, file_size, mime_type, created_at)
                 VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $this->sessionId,
                $fileInfo['file_type'],
                $fileInfo['original_filename'],
                $fileInfo['original_filename'],
                $fileInfo['stored_filename'],
                $fileInfo['file_path'],
                $fileInfo['file_size'],
                $fileInfo['mime_type'],
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error saving uploaded file info: " . $e->getMessage());
            return false;
        }
    }

    public function getUploadedFiles() {
        try {
            $stmt = $this->db->prepare(
                "SELECT document_type as file_type, original_filename, stored_filename, file_path, file_size, mime_type
                 FROM agent_documents
                 WHERE session_id = ? AND application_id IS NULL"
            );
            $stmt->execute([$this->sessionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting uploaded files: " . $e->getMessage());
            return [];
        }
    }
    
    public function submitApplication() {
        try {
            $this->db->beginTransaction();
            
            // Get form data
            $formData = $this->getFormData();
            if (empty($formData)) {
                throw new Exception("No form data found");
            }
            
            // Extract data from form steps
            $step1 = $formData['step_1'] ?? [];
            $step2 = $formData['step_2'] ?? [];
            $step3 = $formData['step_3'] ?? [];
            $step4 = $formData['step_4'] ?? [];
            
            // Insert into agent_applications (status starts as 'submitted' pending admin review)
            $applicationNumber = $this->generateApplicationNumber();

            $stmt = $this->db->prepare(
                "INSERT INTO agent_applications (
                    application_number, status,
                    surname, first_name, middle_name, maiden_name,
                    id_type, id_number, date_of_birth, nationality, gender,
                    residential_erf_no, residential_street_name, residential_suburb, residential_location,
                    residential_town, residential_region, email, mobile_number, po_box,
                    kin_surname, kin_first_name, kin_contact_number, kin_email,
                    kin_erf_no, kin_street_name, kin_suburb, kin_location, kin_town, kin_region,
                    company_name, job_title, employment_number, gross_income, total_deductions, net_pay,
                    employment_email, employment_erf_no, employment_street_name, employment_suburb,
                    employment_location, employment_town, employment_region,
                    form_data, submitted_by, submission_date
                ) VALUES (?, 'submitted', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            // Calculate net pay
            $grossIncome = floatval($step4['gross_income'] ?? 0);
            $deductions = floatval($step4['total_deductions'] ?? 0);
            $netPay = $grossIncome - $deductions;

            $stmt->execute([
                $applicationNumber,
                $step1['surname'] ?? '', $step1['first_name'] ?? '', $step1['middle_name'] ?? '', $step1['maiden_name'] ?? '',
                $step1['id_type'] ?? '', $step1['id_number'] ?? '', $step1['date_of_birth'] ?? null,
                $step1['nationality'] ?? '', $step1['gender'] ?? '',
                $step2['erf_no'] ?? '', $step2['street_name'] ?? '', $step2['suburb'] ?? '', $step2['location'] ?? '',
                $step2['town'] ?? '', $step2['region'] ?? '', $step2['email'] ?? '', $step2['mobile_number'] ?? '', $step2['po_box'] ?? '',
                $step3['kin_surname'] ?? '', $step3['kin_first_name'] ?? '', $step3['kin_contact_number'] ?? '', $step3['kin_email'] ?? '',
                $step3['kin_erf_no'] ?? '', $step3['kin_street_name'] ?? '', $step3['kin_suburb'] ?? '',
                $step3['kin_location'] ?? '', $step3['kin_town'] ?? '', $step3['kin_region'] ?? '',
                $step4['company_name'] ?? '', $step4['job_title'] ?? '', $step4['employment_number'] ?? '',
                $grossIncome, $deductions, $netPay,
                $step4['emp_email'] ?? '', $step4['emp_erf_no'] ?? '', $step4['emp_street_name'] ?? '', $step4['emp_suburb'] ?? '',
                $step4['emp_location'] ?? '', $step4['emp_town'] ?? '', $step4['emp_region'] ?? '',
                json_encode($formData), $this->userId
            ]);

            $applicationId = (int)$this->db->lastInsertId();

            // Link the form session and uploaded documents to the new application
            $this->db->prepare(
                "UPDATE agent_form_sessions SET application_id = ? WHERE session_id = ?"
            )->execute([$applicationId, $this->sessionId]);

            $this->db->prepare(
                "UPDATE agent_documents SET application_id = ? WHERE session_id = ? AND application_id IS NULL"
            )->execute([$applicationId, $this->sessionId]);

            logActivity($this->userId, 'AGENT_APPLICATION_SUBMITTED', "Application Number: $applicationNumber", 'agent_form');

            $this->db->commit();

            return [
                'success' => true,
                'application_id' => $applicationId,
                'application_number' => $applicationNumber
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error submitting agent application: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getDatabaseConnection() {
        // Reuse the same connection the rest of the app uses
        require_once __DIR__ . '/../../config/pdo.php';
        global $pdo;
        return $pdo;
    }

    private function generateApplicationNumber(): string
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM agent_applications");
        $count = $stmt->fetchColumn() + 1;
        return sprintf('AGT-%s-%04d', date('Ymd'), $count);
    }
}

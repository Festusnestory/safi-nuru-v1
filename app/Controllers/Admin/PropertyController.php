<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Controller;
use App\Core\Router;
use App\Models\Property;

final class PropertyController extends Controller
{
    public function list(): void
    {
        \App\Core\Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        \expireOverdueProperties($this->pdo);

        $model = new Property($this->pdo);
        $words = 'Properties List';

        if (Auth::currentRole() === 'agent_coordinator') {
            $agentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0;
            $properties = $model->forAgent($agentId, (int) $_SESSION['user_id']);
            $words = 'My Properties';
        } else {
            $properties = $model->all();
        }

        $this->render('admin.properties.list', [
            'properties' => $properties,
            'words' => $words,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** Ported from html/material/properties-available.php. */
    public function available(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        $model = new Property($this->pdo);
        $isAgent = Auth::currentRole() === 'agent_coordinator';
        $agentId = $isAgent ? (\resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0) : 0;
        $properties = $model->available($isAgent, (int) $_SESSION['user_id'], $agentId);

        $this->render('admin.properties.available', [
            'properties' => $properties,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /** Ported from html/material/properties-sold.php. */
    public function sold(): void
    {
        Bootstrap::requireSession();
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);

        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        \expireOverdueProperties($this->pdo);

        $model = new Property($this->pdo);
        $isAgent = Auth::currentRole() === 'agent_coordinator';
        $agentId = $isAgent ? (\resolveAgentId($this->pdo, (int) $_SESSION['user_id']) ?? 0) : 0;
        $words = $isAgent ? 'My Sold Properties' : 'Sold Properties';
        $soldProperties = $model->sold($isAgent, (int) $_SESSION['user_id'], $agentId);

        $this->render('admin.properties.sold', [
            'soldProperties' => $soldProperties,
            'words' => $words,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /**
     * Staff "Add Property" form. Ported from html/material/property_admin_form.php,
     * including the recently added Sale Type pricing breakdown (Plot & Plan /
     * Existing House, with a server-computed Total Selling Price) - that logic
     * is preserved exactly, not simplified.
     */
    public function form(): void
    {
        $this->requireRole(['admin', 'manager', 'agent_coordinator']);
        require_once \NURU_MATERIAL . '/includes/functions.php';

        $propertyTypes = [
            'Single Residential',
            'General Residential',
            'Farm',
            'Commercial/Business',
            'Institutional',
        ];
        $landTypes = ['Vacant Land', 'Existing Property', 'Plot and Plan'];
        $regions = [
            'Erongo', 'Hardap', 'Karas', 'Kavango East', 'Kavango West', 'Khomas',
            'Kunene', 'Ohangwena', 'Omaheke', 'Omusati', 'Oshana', 'Oshikoto',
            'Otjozondjupa', 'Zambezi',
        ];
        $errors = [];
        $csrf = Auth::csrfToken('staff_property_form');

        $model = new Property($this->pdo);
        $isAgent = Auth::currentRole() === 'agent_coordinator';
        $userId = (int) $_SESSION['user_id'];
        $agentId = $isAgent ? (\resolveAgentId($this->pdo, $userId) ?? 0) : 0;

        $sellers = $model->eligibleSellersForStaffForm($isAgent, $userId, $agentId);
        $developmentSellerIds = array_map(
            static fn (array $seller): int => (int) $seller['id'],
            array_filter($sellers, static fn (array $seller): bool => $seller['sale_type'] === 'Property Development')
        );
        $sellerDevelopmentData = $model->developmentsWithHouseTypesForApplications($developmentSellerIds);

        $text = static fn (string $key): string => trim((string) ($_POST[$key] ?? ''));
        $decimal = static function (string $key) use ($text): ?float {
            $value = $text($key);
            if ($value === '') {
                return null;
            }
            $normalized = preg_replace('/[^\d.\-]/', '', $value);
            return $normalized !== '' && is_numeric($normalized) ? (float) $normalized : null;
        };
        $integer = static function (string $key) use ($text): ?int {
            $value = $text($key);
            if ($value === '') {
                return null;
            }
            return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null;
        };

        $sellingPrice = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $applicationId = filter_var(
                $_POST['seller_application_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'staff_property_form')) {
                $errors[] = 'Your session expired. Refresh the page and try again.';
            }
            if ($applicationId === false) {
                $errors[] = 'Select the seller who owns this property.';
            }

            $selectedSeller = null;
            if ($applicationId !== false) {
                $selectedSeller = $model->authorizeSellerForStaffForm((int) $applicationId, $isAgent, $userId, $agentId);
                if (!$selectedSeller) {
                    $errors[] = 'The selected seller is unavailable or outside your assigned portfolio.';
                }
            }
            // Individual sellers never see the development/house-type
            // section at all - only a Property Development seller can
            // meaningfully answer "yes" here (the form hides the question
            // otherwise, but a tampered request is treated the same as no).
            $isDevelopmentSeller = $selectedSeller && ($selectedSeller['sale_type'] ?? 'Individual') === 'Property Development';
            $belongsToDevelopment = $isDevelopmentSeller && $text('belongs_to_development') === 'yes';

            // Address fields apply to every property regardless of
            // development - an exact unit still needs its own erf/street.
            $erfNumber = $text('property_erf_no');
            $street = $text('property_street_name');
            $suburb = $text('property_suburb');
            $location = $text('property_location');
            $region = $text('property_region');
            $town = $text('property_town');
            if ($street === '' || strlen($street) > 200) {
                $errors[] = 'Provide a street name of no more than 200 characters.';
            }
            if (!in_array($region, $regions, true)) {
                $errors[] = 'Select a valid Namibian region.';
            }
            if ($town === '' || strlen($town) > 100) {
                $errors[] = 'Provide a town of no more than 100 characters.';
            }
            if (strlen($erfNumber) > 50 || strlen($suburb) > 100 || strlen($location) > 100) {
                $errors[] = 'One or more address fields are too long.';
            }

            $developmentId = null;
            $houseTypeId = null;
            // Values that end up on the seller_properties row itself - read
            // from the form (standalone property, or a brand-new house
            // type), or copied server-side from an existing house type's
            // own stored columns (never trusted from the client in that
            // case, since the template is meant to be reused verbatim).
            $propertyType = $landType = $salePricingType = null;
            $plotSellingPrice = $constructionAmount = $propertySellingPrice = $agentCommissionFees = null;
            $landSize = $sellingPrice = $houseSize = null;
            $rooms = $bathrooms = null;
            $features = '';

            $validateClassificationFields = function () use (
                $text, $decimal, $integer, $propertyTypes, $landTypes, &$errors,
                &$propertyType, &$landType, &$salePricingType,
                &$plotSellingPrice, &$constructionAmount, &$propertySellingPrice, &$agentCommissionFees,
                &$landSize, &$sellingPrice, &$houseSize, &$rooms, &$bathrooms, &$features
            ): void {
                $propertyType = $text('property_detail_type');
                $landType = $text('land_type');
                $landSize = $decimal('land_size');
                $salePricingType = $text('sale_pricing_type');
                $plotSellingPrice = $decimal('plot_selling_price');
                $constructionAmount = $decimal('construction_amount');
                $propertySellingPrice = $decimal('property_selling_price');
                $agentCommissionFees = $decimal('agent_commission_fees');
                $sellingPrice = match ($salePricingType) {
                    'plot_and_plan' => ($plotSellingPrice ?? 0) + ($constructionAmount ?? 0) + ($agentCommissionFees ?? 0),
                    'existing_house' => ($propertySellingPrice ?? 0) + ($agentCommissionFees ?? 0),
                    default => 0.0,
                };
                $sellingPrice = $sellingPrice > 0 ? $sellingPrice : null;
                $houseSize = $decimal('house_size');
                $rooms = $integer('number_of_rooms');
                $bathrooms = $integer('number_of_bathrooms');
                $features = $text('additional_features');

                if (!in_array($propertyType, $propertyTypes, true)) {
                    $errors[] = 'Select a valid property type.';
                }
                if (!in_array($landType, $landTypes, true)) {
                    $errors[] = 'Select a valid land type.';
                }
                if ($landSize === null || $landSize <= 0 || $landSize > 9999999999999.99) {
                    $errors[] = 'Land size must be greater than zero.';
                }
                if (!in_array($salePricingType, ['plot_and_plan', 'existing_house'], true)) {
                    $errors[] = 'Select a Sale Type (Plot & Plan or Existing House).';
                }
                if ($sellingPrice === null || $sellingPrice > 9999999999999.99) {
                    $errors[] = 'Total selling price must be greater than zero - check the Sale Type amounts above.';
                }
                if ($houseSize !== null && ($houseSize < 0 || $houseSize > 9999999999999.99)) {
                    $errors[] = 'House size must be a valid positive value.';
                }
                if ($rooms !== null && ($rooms < 0 || $rooms > 1000)) {
                    $errors[] = 'Number of rooms must be between 0 and 1,000.';
                }
                if ($bathrooms !== null && ($bathrooms < 0 || $bathrooms > 1000)) {
                    $errors[] = 'Number of bathrooms must be between 0 and 1,000.';
                }
                if (strlen($features) > 5000) {
                    $errors[] = 'Additional features must be no more than 5,000 characters.';
                }
            };

            $newDevelopmentFields = null;
            $newHouseTypeFields = null;

            if (!$belongsToDevelopment) {
                $validateClassificationFields();
            } else {
                $developmentChoice = $text('development_choice');
                if ($developmentChoice === 'existing') {
                    $developmentId = filter_var($_POST['development_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
                    if ($developmentId === null || !$model->authorizeDevelopmentForApplication($developmentId, (int) $applicationId)) {
                        $errors[] = 'Select a development registered under this seller.';
                        $developmentId = null;
                    }
                } elseif ($developmentChoice === 'new') {
                    $developmentName = $text('development_name');
                    $devRegion = $text('development_region');
                    $devTown = $text('development_town');
                    if ($developmentName === '' || strlen($developmentName) > 200) {
                        $errors[] = 'Provide a development name of no more than 200 characters.';
                    }
                    if (!in_array($devRegion, $regions, true)) {
                        $errors[] = 'Select a valid region for the new development.';
                    }
                    if ($devTown === '' || strlen($devTown) > 100) {
                        $errors[] = 'Provide a town for the new development.';
                    }
                    $newDevelopmentFields = [
                        'development_name' => $developmentName,
                        'region' => $devRegion,
                        'town' => $devTown,
                        'location' => $text('development_location') !== '' ? $text('development_location') : null,
                        'suburb' => $text('development_suburb') !== '' ? $text('development_suburb') : null,
                    ];
                } else {
                    $errors[] = 'Choose whether the development is already registered or new.';
                }

                $houseTypeChoice = $text('house_type_choice');
                if ($houseTypeChoice === 'existing') {
                    $houseTypeId = filter_var($_POST['development_house_type_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
                    // Only checkable once we know a real development_id - a
                    // brand-new development obviously has no house types yet.
                    if ($houseTypeId === null || $developmentId === null || !$model->authorizeHouseTypeForDevelopment($houseTypeId, $developmentId)) {
                        $errors[] = 'Select a house type registered under this development.';
                        $houseTypeId = null;
                    }
                } elseif ($houseTypeChoice === 'new') {
                    $unitsCount = $integer('number_of_units');
                    if ($unitsCount === null || $unitsCount < 1 || $unitsCount > 100000) {
                        $errors[] = 'Number of units must be at least 1.';
                    }
                    $validateClassificationFields();
                    $newHouseTypeFields = ['number_of_units' => $unitsCount];
                } else {
                    $errors[] = 'Choose whether the house type is already registered or new.';
                }
            }

            if (!$errors && $selectedSeller) {
                try {
                    $propertyStatus = in_array($selectedSeller['status'], ['approved', 'completed'], true)
                        ? 'available'
                        : 'pending_review';

                    $this->pdo->beginTransaction();

                    if ($belongsToDevelopment && $newDevelopmentFields !== null) {
                        $developmentId = $model->createDevelopment($newDevelopmentFields + ['application_id' => (int) $selectedSeller['id']]);
                    }

                    if ($belongsToDevelopment && $newHouseTypeFields !== null) {
                        $houseTypeId = $model->createHouseType([
                            'development_id' => $developmentId,
                            'property_type' => $propertyType,
                            'number_of_units' => $newHouseTypeFields['number_of_units'],
                            'house_size' => $houseSize,
                            'land_type' => $landType,
                            'sale_pricing_type' => $salePricingType,
                            'plot_selling_price' => $plotSellingPrice,
                            'construction_amount' => $constructionAmount,
                            'property_selling_price' => $propertySellingPrice,
                            'agent_commission_fees' => $agentCommissionFees,
                            'other_fees' => null,
                            'land_size' => $landSize,
                            'selling_price' => $sellingPrice,
                            'number_of_rooms' => $rooms,
                            'number_of_bathrooms' => $bathrooms,
                            'additional_features' => $features !== '' ? $features : null,
                        ]);
                    } elseif ($belongsToDevelopment && $houseTypeId !== null) {
                        // Existing house type: the template is the source of
                        // truth for classification/price, not whatever (if
                        // anything) was left in these fields client-side.
                        $houseType = $model->houseTypeById($houseTypeId);
                        $propertyType = $houseType['property_type'];
                        $landType = $houseType['land_type'];
                        $salePricingType = $houseType['sale_pricing_type'];
                        $plotSellingPrice = $houseType['plot_selling_price'];
                        $constructionAmount = $houseType['construction_amount'];
                        $propertySellingPrice = $houseType['property_selling_price'];
                        $agentCommissionFees = $houseType['agent_commission_fees'];
                        $landSize = $houseType['land_size'];
                        $sellingPrice = $houseType['selling_price'];
                        $houseSize = $houseType['house_size'];
                        $rooms = $houseType['number_of_rooms'];
                        $bathrooms = $houseType['number_of_bathrooms'];
                        $features = $houseType['additional_features'] ?? '';
                    }

                    $propertyId = $model->createFromStaffForm([
                        'application_id' => (int) $selectedSeller['id'],
                        'property_detail_type' => $propertyType,
                        'land_type' => $landType,
                        'sale_pricing_type' => $salePricingType,
                        'plot_selling_price' => $plotSellingPrice,
                        'construction_amount' => $constructionAmount,
                        'property_selling_price' => $propertySellingPrice,
                        'agent_commission_fees' => $agentCommissionFees,
                        'land_size' => $landSize,
                        'selling_price' => $sellingPrice,
                        'house_size' => $houseSize,
                        'number_of_rooms' => $rooms,
                        'number_of_bathrooms' => $bathrooms,
                        'additional_features' => $features !== '' ? $features : null,
                        'property_erf_no' => $erfNumber !== '' ? $erfNumber : null,
                        'property_street_name' => $street,
                        'property_suburb' => $suburb !== '' ? $suburb : null,
                        'property_location' => $location !== '' ? $location : null,
                        'property_region' => $region,
                        'property_town' => $town,
                        'property_status' => $propertyStatus,
                        'development_house_type_id' => $belongsToDevelopment ? $houseTypeId : null,
                    ]);

                    if (!empty($_FILES['property_images']['name'][0] ?? '')) {
                        $imageFiles = [];
                        foreach ($_FILES['property_images']['name'] as $index => $name) {
                            if ($name === '') {
                                continue;
                            }
                            $imageFiles[] = [
                                'name' => $name,
                                'type' => $_FILES['property_images']['type'][$index] ?? '',
                                'tmp_name' => $_FILES['property_images']['tmp_name'][$index] ?? '',
                                'error' => $_FILES['property_images']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                                'size' => $_FILES['property_images']['size'][$index] ?? 0,
                            ];
                        }
                        $model->addPropertyImages($propertyId, (int) $selectedSeller['id'], $imageFiles);
                    }

                    \logActivity(
                        $userId,
                        'PROPERTY_CREATED',
                        "Created property #{$propertyId} for seller {$selectedSeller['application_number']}",
                        'property_management'
                    );
                    $this->pdo->commit();

                    // Redirect back to whichever URL served this request (legacy
                    // html/material/property_admin_form.php or the clean
                    // /admin/property-admin-form route) rather than a
                    // hard-coded relative path, so both keep working.
                    $self = strtok($_SERVER['REQUEST_URI'], '?');
                    header('Location: ' . $self . '?created=' . $propertyId, true, 303);
                    exit;
                } catch (\Throwable $error) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    error_log('Staff property creation failed: ' . $error->getMessage());
                    $errors[] = 'The property could not be saved. Please verify the details and try again.';
                }
            }
        }

        $this->render('admin.properties.form', [
            'propertyTypes' => $propertyTypes,
            'landTypes' => $landTypes,
            'regions' => $regions,
            'errors' => $errors,
            'csrf' => $csrf,
            'sellers' => $sellers,
            'sellerDevelopmentData' => $sellerDevelopmentData,
            'sellingPrice' => $sellingPrice,
            'baseUrl' => Router::basePath(),
        ]);
    }

    /**
     * JSON endpoint: marks a matched (under_offer) property as sold. Ported
     * from html/material/config/mark_property_sold.php, kept as a JSON
     * response rather than a redirect since that is what the "Mark as Sold"
     * button on match-results.php expects.
     */
    public function markSold(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, private, max-age=0');

        if (!\sessionHasAuthoritativeRole(['admin', 'manager', 'agent_coordinator'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'mark_property_sold')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Your session token has expired. Refresh the page and try again.']);
            return;
        }

        $propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;
        $soldPrice = isset($_POST['sold_price']) && $_POST['sold_price'] !== '' ? (float) $_POST['sold_price'] : null;
        $saleNotes = $_POST['sale_notes'] ?? null;

        if ($propertyId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid property']);
            return;
        }

        require_once \NURU_MATERIAL . '/config/property_lifecycle.php';
        $model = new Property($this->pdo);

        if (!Auth::isFullAccess()) {
            $myAgentId = \resolveAgentId($this->pdo, (int) $_SESSION['user_id']);
            if (!$model->ownedByAgentPortfolio($propertyId, (int) $_SESSION['user_id'], $myAgentId ?? 0)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'This property is not assigned to you']);
                return;
            }
        }

        try {
            $this->pdo->beginTransaction();

            $property = $model->lockForSale($propertyId);
            if (!$property) {
                throw new \Exception('Property not found');
            }
            if ($property['property_status'] !== 'under_offer') {
                throw new \Exception('Only properties currently under offer can be marked as sold');
            }

            $finalPrice = $soldPrice ?? (float) $property['selling_price'];
            $deadline = \computeStatusDeadline('sold');

            $model->markSold($propertyId, $finalPrice, $deadline, $saleNotes);
            $model->recordMatchAudit(
                $propertyId,
                $property['buyer_id'] !== null ? (int) $property['buyer_id'] : null,
                $property['buyer_name'],
                'sold',
                (int) $_SESSION['user_id'],
                'Marked as sold'
            );

            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Property marked as sold']);
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

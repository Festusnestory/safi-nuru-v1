<?php
session_start();

require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/includes/functions.php';

requireRole(['admin', 'manager', 'agent_coordinator']);

$propertyTypes = [
    'Single Residential',
    'General Residential',
    'Farm',
    'Commercial/Business',
    'Institutional',
];
$landTypes = ['Vacant Land', 'Existing Property', 'Plot and Plan'];
$regions = [
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
    'Zambezi',
];
$eligibleStatuses = ['submitted', 'under_review', 'approved', 'completed'];
$errors = [];
$csrf = csrfToken('staff_property_form');

$sellerScope = '';
$sellerScopeParameters = [];
if (currentRole() === 'agent_coordinator') {
    $sellerScope = ' AND (
        spd.loaded_by = :scope_user_id
        OR sa.assigned_agent_id = :scope_agent_id
        OR EXISTS (
            SELECT 1
            FROM agent_task_allocations scoped_allocation
            WHERE scoped_allocation.allocation_type = \'seller\'
              AND scoped_allocation.agent_id = :scope_task_agent_id
              AND (
                  scoped_allocation.entity_reference = sa.application_number
                  OR scoped_allocation.entity_id IN (
                      SELECT scoped_property.id
                      FROM seller_properties scoped_property
                      WHERE scoped_property.application_id = sa.id
                  )
              )
        )
    )';
    $scopeAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
    $sellerScopeParameters = [
        ':scope_user_id' => (int)$_SESSION['user_id'],
        ':scope_agent_id' => $scopeAgentId,
        ':scope_task_agent_id' => $scopeAgentId,
    ];
}

$sellerSelectSql = "
    SELECT
        sa.id,
        sa.application_number,
        sa.status,
        CONCAT_WS(' ', spd.first_name, spd.surname) AS seller_name,
        sra.email,
        COUNT(existing_property.id) AS property_count
    FROM seller_applications sa
    INNER JOIN seller_personal_details spd ON spd.application_id = sa.id
    LEFT JOIN seller_residential_address sra ON sra.application_id = sa.id
    LEFT JOIN seller_properties existing_property ON existing_property.application_id = sa.id
    WHERE sa.status IN ('submitted', 'under_review', 'approved', 'completed')
    {$sellerScope}
    GROUP BY sa.id, sa.application_number, sa.status, spd.first_name, spd.surname, sra.email
    ORDER BY spd.first_name, spd.surname, sa.created_at DESC
";
$sellerStatement = $pdo->prepare($sellerSelectSql);
$sellerStatement->execute($sellerScopeParameters);
$sellers = $sellerStatement->fetchAll(PDO::FETCH_ASSOC);

function propertyFormHtml(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function propertyFormText(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function propertyFormDecimal(string $key): ?float
{
    $value = propertyFormText($key);
    if ($value === '') {
        return null;
    }
    $normalized = preg_replace('/[^\d.\-]/', '', $value);
    return $normalized !== '' && is_numeric($normalized) ? (float)$normalized : null;
}

function propertyFormInteger(string $key): ?int
{
    $value = propertyFormText($key);
    if ($value === '') {
        return null;
    }
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = filter_var(
        $_POST['seller_application_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $propertyType = propertyFormText('property_detail_type');
    $landType = propertyFormText('land_type');
    $landSize = propertyFormDecimal('land_size');
    $sellingPrice = propertyFormDecimal('selling_price');
    $houseSize = propertyFormDecimal('house_size');
    $rooms = propertyFormInteger('number_of_rooms');
    $bathrooms = propertyFormInteger('number_of_bathrooms');
    $erfNumber = propertyFormText('property_erf_no');
    $street = propertyFormText('property_street_name');
    $suburb = propertyFormText('property_suburb');
    $location = propertyFormText('property_location');
    $region = propertyFormText('property_region');
    $town = propertyFormText('property_town');
    $features = propertyFormText('additional_features');

    if (!validCsrfToken($_POST['csrf_token'] ?? null, 'staff_property_form')) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }
    if ($applicationId === false) {
        $errors[] = 'Select the seller who owns this property.';
    }
    if (!in_array($propertyType, $propertyTypes, true)) {
        $errors[] = 'Select a valid property type.';
    }
    if (!in_array($landType, $landTypes, true)) {
        $errors[] = 'Select a valid land type.';
    }
    if ($landSize === null || $landSize <= 0 || $landSize > 9999999999999.99) {
        $errors[] = 'Land size must be greater than zero.';
    }
    if ($sellingPrice === null || $sellingPrice <= 0 || $sellingPrice > 9999999999999.99) {
        $errors[] = 'Selling price must be greater than zero.';
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
    if (strlen($features) > 5000) {
        $errors[] = 'Additional features must be no more than 5,000 characters.';
    }

    $selectedSeller = null;
    if ($applicationId !== false) {
        $authorizationSql = "
            SELECT sa.id, sa.application_number, sa.status
            FROM seller_applications sa
            INNER JOIN seller_personal_details spd ON spd.application_id = sa.id
            WHERE sa.id = :application_id
              AND sa.status IN ('submitted', 'under_review', 'approved', 'completed')
              {$sellerScope}
            LIMIT 1
        ";
        $authorizationParameters = array_merge(
            [':application_id' => (int)$applicationId],
            $sellerScopeParameters
        );
        $authorization = $pdo->prepare($authorizationSql);
        $authorization->execute($authorizationParameters);
        $selectedSeller = $authorization->fetch(PDO::FETCH_ASSOC);
        if (!$selectedSeller) {
            $errors[] = 'The selected seller is unavailable or outside your assigned portfolio.';
        }
    }

    if (!$errors && $selectedSeller) {
        try {
            $propertyStatus = in_array($selectedSeller['status'], ['approved', 'completed'], true)
                ? 'available'
                : 'pending_review';

            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT INTO seller_properties (
                    application_id,
                    property_detail_type,
                    land_type,
                    land_size,
                    selling_price,
                    house_size,
                    number_of_rooms,
                    number_of_bathrooms,
                    additional_features,
                    property_erf_no,
                    property_street_name,
                    property_suburb,
                    property_location,
                    property_region,
                    property_town,
                    property_status,
                    listing_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $insert->execute([
                (int)$selectedSeller['id'],
                $propertyType,
                $landType,
                $landSize,
                $sellingPrice,
                $houseSize,
                $rooms,
                $bathrooms,
                $features !== '' ? $features : null,
                $erfNumber !== '' ? $erfNumber : null,
                $street,
                $suburb !== '' ? $suburb : null,
                $location !== '' ? $location : null,
                $region,
                $town,
                $propertyStatus,
            ]);
            $propertyId = (int)$pdo->lastInsertId();

            logActivity(
                (int)$_SESSION['user_id'],
                'PROPERTY_CREATED',
                "Created property #{$propertyId} for seller {$selectedSeller['application_number']}",
                'property_management'
            );
            $pdo->commit();

            header('Location: property_admin_form.php?created=' . $propertyId, true, 303);
            exit;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Staff property creation failed: ' . $error->getMessage());
            $errors[] = 'The property could not be saved. Please verify the details and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Add Property - Nuru</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon.png">
    <link href="../../dist/css/style.min.css" rel="stylesheet">
    <style>
        .required::after { content: " *"; color: #dc3545; }
        .form-section-title { border-bottom: 1px solid #e9ecef; padding-bottom: .75rem; margin-bottom: 1.25rem; }
    </style>
</head>
<body>
<div class="preloader"></div>
<div id="main-wrapper">
    <?php include __DIR__ . '/top-bar.php'; ?>
    <?php if (isFullAccess()): ?>
        <?php include __DIR__ . '/left-sidebar.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/agent_nemu.php'; ?>
    <?php endif; ?>

    <div class="page-wrapper">
        <div class="row page-titles">
            <div class="col-md-7 col-12 align-self-center">
                <h3 class="text-themecolor mb-0">Add Property</h3>
                <ol class="breadcrumb mb-0 p-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= propertyFormHtml(roleHomeRoute()) ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="properties-list.php">Properties</a></li>
                    <li class="breadcrumb-item active">Add Property</li>
                </ol>
            </div>
        </div>

        <div class="container-fluid">
            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Property #<?= (int)$_GET['created'] ?> was added successfully.
                    <a class="alert-link" href="properties-list.php">View properties</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>The property was not saved.</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach (array_unique($errors) as $error): ?>
                            <li><?= propertyFormHtml($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="card-title">Property details</h4>
                            <p class="text-muted mb-0">Add another property to an existing seller record. This does not create a second seller account.</p>
                        </div>
                        <a class="btn btn-outline-secondary mt-2 mt-sm-0" href="properties-list.php">Cancel</a>
                    </div>

                    <?php if (!$sellers): ?>
                        <div class="alert alert-warning" role="alert">
                            No eligible sellers are available in your portfolio. Register or assign a seller before adding a property.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="property_admin_form.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= propertyFormHtml($csrf) ?>">

                        <h5 class="form-section-title">Seller</h5>
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <label class="form-label required" for="seller-application">Property owner</label>
                                <select class="form-select" id="seller-application" name="seller_application_id" required <?= !$sellers ? 'disabled' : '' ?>>
                                    <option value="">Select an existing seller</option>
                                    <?php foreach ($sellers as $seller): ?>
                                        <option value="<?= (int)$seller['id'] ?>"
                                            <?= (string)($_POST['seller_application_id'] ?? '') === (string)$seller['id'] ? 'selected' : '' ?>>
                                            <?= propertyFormHtml(
                                                $seller['application_number']
                                                . ' — '
                                                . $seller['seller_name']
                                                . ' ('
                                                . ucwords(str_replace('_', ' ', $seller['status']))
                                                . ', '
                                                . $seller['property_count']
                                                . ' '
                                                . ((int)$seller['property_count'] === 1 ? 'property' : 'properties')
                                                . ')'
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Only active seller applications you are authorised to manage are shown.</div>
                            </div>
                        </div>

                        <h5 class="form-section-title">Classification and price</h5>
                        <div class="row">
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="property-type">Property type</label>
                                <select class="form-select" id="property-type" name="property_detail_type" required>
                                    <option value="">Select type</option>
                                    <?php foreach ($propertyTypes as $type): ?>
                                        <option value="<?= propertyFormHtml($type) ?>" <?= propertyFormText('property_detail_type') === $type ? 'selected' : '' ?>>
                                            <?= propertyFormHtml($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="land-type">Land type</label>
                                <select class="form-select" id="land-type" name="land_type" required>
                                    <option value="">Select land type</option>
                                    <?php foreach ($landTypes as $type): ?>
                                        <option value="<?= propertyFormHtml($type) ?>" <?= propertyFormText('land_type') === $type ? 'selected' : '' ?>>
                                            <?= propertyFormHtml($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="land-size">Land size (m²)</label>
                                <input class="form-control" id="land-size" name="land_size" type="number" min="0.01" max="9999999999999.99" step="0.01"
                                       value="<?= propertyFormHtml($_POST['land_size'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="selling-price">Selling price (N$)</label>
                                <input class="form-control" id="selling-price" name="selling_price" type="number" min="0.01" max="9999999999999.99" step="0.01"
                                       value="<?= propertyFormHtml($_POST['selling_price'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="house-size">House size (m²)</label>
                                <input class="form-control" id="house-size" name="house_size" type="number" min="0" max="9999999999999.99" step="0.01"
                                       value="<?= propertyFormHtml($_POST['house_size'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="rooms">Rooms</label>
                                <input class="form-control" id="rooms" name="number_of_rooms" type="number" min="0" max="1000" step="1"
                                       value="<?= propertyFormHtml($_POST['number_of_rooms'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="bathrooms">Bathrooms</label>
                                <input class="form-control" id="bathrooms" name="number_of_bathrooms" type="number" min="0" max="1000" step="1"
                                       value="<?= propertyFormHtml($_POST['number_of_bathrooms'] ?? '') ?>">
                            </div>
                        </div>

                        <h5 class="form-section-title mt-3">Location</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="erf-number">Erf number</label>
                                <input class="form-control" id="erf-number" name="property_erf_no" maxlength="50"
                                       value="<?= propertyFormHtml($_POST['property_erf_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label required" for="street">Street name or address</label>
                                <input class="form-control" id="street" name="property_street_name" maxlength="200"
                                       value="<?= propertyFormHtml($_POST['property_street_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="suburb">Suburb</label>
                                <input class="form-control" id="suburb" name="property_suburb" maxlength="100"
                                       value="<?= propertyFormHtml($_POST['property_suburb'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="location">Location</label>
                                <input class="form-control" id="location" name="property_location" maxlength="100"
                                       value="<?= propertyFormHtml($_POST['property_location'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="region">Region</label>
                                <select class="form-select" id="region" name="property_region" required>
                                    <option value="">Select region</option>
                                    <?php foreach ($regions as $regionOption): ?>
                                        <option value="<?= propertyFormHtml($regionOption) ?>" <?= propertyFormText('property_region') === $regionOption ? 'selected' : '' ?>>
                                            <?= propertyFormHtml($regionOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="town">Town</label>
                                <input class="form-control" id="town" name="property_town" maxlength="100"
                                       value="<?= propertyFormHtml($_POST['property_town'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="features">Additional features</label>
                                <textarea class="form-control" id="features" name="additional_features" rows="3" maxlength="5000"><?= propertyFormHtml($_POST['additional_features'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a class="btn btn-outline-secondary" href="properties-list.php">Cancel</a>
                            <button class="btn btn-primary" type="submit" <?= !$sellers ? 'disabled' : '' ?>>Save property</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <footer class="footer">© <?= date('Y') ?> Nuru Real Estate</footer>
    </div>
</div>

<script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../dist/js/app.min.js"></script>
<script src="../../dist/js/app.init.js"></script>
<script src="../../dist/js/app-style-switcher.js"></script>
<script src="../../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="../../dist/js/waves.js"></script>
<script src="../../dist/js/sidebarmenu.js?v=20260720"></script>
<script src="../../dist/js/feather.min.js"></script>
<script src="../../dist/js/custom.min.js"></script>
</body>
</html>

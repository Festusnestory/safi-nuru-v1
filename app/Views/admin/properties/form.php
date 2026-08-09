<?php
/** @var array $propertyTypes */
/** @var array $landTypes */
/** @var array $regions */
/** @var array $errors */
/** @var string $csrf */
/** @var array $sellers */
/** @var float|null $sellingPrice */
/** @var string $baseUrl */

$formHtml = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$propertiesListUrl = $baseUrl . '/html/material/properties-list.php';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Add Property - Nuru</title>
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/images/favicon.png">
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet">
    <style>
        .required::after { content: " *"; color: #dc3545; }
        .form-section-title { border-bottom: 1px solid #e9ecef; padding-bottom: .75rem; margin-bottom: 1.25rem; }
    </style>
</head>
<body>
<div class="preloader"></div>
<div id="main-wrapper">
    <?php require NURU_MATERIAL . '/top-bar.php'; ?>
    <?php if (\App\Core\Auth::isFullAccess()): ?>
        <?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
    <?php else: ?>
        <?php require NURU_MATERIAL . '/agent_nemu.php'; ?>
    <?php endif; ?>

    <div class="page-wrapper">
        <div class="row page-titles">
            <div class="col-md-7 col-12 align-self-center">
                <h3 class="text-themecolor mb-0">Add Property</h3>
                <ol class="breadcrumb mb-0 p-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= $formHtml(roleHomeRoute()) ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= $formHtml($propertiesListUrl) ?>">Properties</a></li>
                    <li class="breadcrumb-item active">Add Property</li>
                </ol>
            </div>
        </div>

        <div class="container-fluid">
            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Property #<?= (int)$_GET['created'] ?> was added successfully.
                    <a class="alert-link" href="<?= $formHtml($propertiesListUrl) ?>">View properties</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>The property was not saved.</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach (array_unique($errors) as $error): ?>
                            <li><?= $formHtml($error) ?></li>
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
                        <a class="btn btn-outline-secondary mt-2 mt-sm-0" href="<?= $formHtml($propertiesListUrl) ?>">Cancel</a>
                    </div>

                    <?php if (!$sellers): ?>
                        <div class="alert alert-warning" role="alert">
                            No eligible sellers are available in your portfolio. Register or assign a seller before adding a property.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $formHtml($csrf) ?>">

                        <h5 class="form-section-title">Seller</h5>
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <label class="form-label required" for="seller-application">Property owner</label>
                                <select class="form-select" id="seller-application" name="seller_application_id" required <?= !$sellers ? 'disabled' : '' ?>>
                                    <option value="">Select an existing seller</option>
                                    <?php foreach ($sellers as $seller): ?>
                                        <option value="<?= (int)$seller['id'] ?>"
                                            <?= (string)($_POST['seller_application_id'] ?? '') === (string)$seller['id'] ? 'selected' : '' ?>>
                                            <?= $formHtml(
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
                                        <option value="<?= $formHtml($type) ?>" <?= trim((string)($_POST['property_detail_type'] ?? '')) === $type ? 'selected' : '' ?>>
                                            <?= $formHtml($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="land-type">Land type</label>
                                <select class="form-select" id="land-type" name="land_type" required>
                                    <option value="">Select land type</option>
                                    <?php foreach ($landTypes as $type): ?>
                                        <option value="<?= $formHtml($type) ?>" <?= trim((string)($_POST['land_type'] ?? '')) === $type ? 'selected' : '' ?>>
                                            <?= $formHtml($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="sale-pricing-type">Sale Type</label>
                                <select class="form-select" id="sale-pricing-type" name="sale_pricing_type" required>
                                    <option value="">Select Sale Type</option>
                                    <option value="plot_and_plan" <?= trim((string)($_POST['sale_pricing_type'] ?? '')) === 'plot_and_plan' ? 'selected' : '' ?>>Plot &amp; Plan</option>
                                    <option value="existing_house" <?= trim((string)($_POST['sale_pricing_type'] ?? '')) === 'existing_house' ? 'selected' : '' ?>>Existing House</option>
                                </select>
                            </div>
                        </div>

                        <div class="row" id="plot-and-plan-pricing" style="display:none;">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="plot-selling-price">Plot selling price (N$)</label>
                                <input class="form-control sale-pricing-input" id="plot-selling-price" name="plot_selling_price" type="number" min="0.01" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['plot_selling_price'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="construction-amount">Construction amount (N$)</label>
                                <input class="form-control sale-pricing-input" id="construction-amount" name="construction_amount" type="number" min="0.01" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['construction_amount'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="agent-commission-fees-pp">Agent commission fees (N$)</label>
                                <input class="form-control sale-pricing-input" id="agent-commission-fees-pp" name="agent_commission_fees" type="number" min="0" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['agent_commission_fees'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row" id="existing-house-pricing" style="display:none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="property-selling-price">Property selling price (N$)</label>
                                <input class="form-control sale-pricing-input" id="property-selling-price" name="property_selling_price" type="number" min="0.01" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['property_selling_price'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="agent-commission-fees-eh">Agent commission fees (N$)</label>
                                <input class="form-control sale-pricing-input" id="agent-commission-fees-eh" name="agent_commission_fees" type="number" min="0" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['agent_commission_fees'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="land-size">Land size (m²)</label>
                                <input class="form-control" id="land-size" name="land_size" type="number" min="0.01" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['land_size'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-3">
                                <label class="form-label required" for="selling-price">Total selling price (N$)</label>
                                <input class="form-control" id="selling-price" name="selling_price_display" type="text" readonly
                                       value="<?= $formHtml($sellingPrice !== null ? number_format($sellingPrice, 2) : '') ?>">
                                <div class="form-text">Calculated automatically from the Sale Type fields above.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="house-size">House size (m²)</label>
                                <input class="form-control" id="house-size" name="house_size" type="number" min="0" max="9999999999999.99" step="0.01"
                                       value="<?= $formHtml($_POST['house_size'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="rooms">Rooms</label>
                                <input class="form-control" id="rooms" name="number_of_rooms" type="number" min="0" max="1000" step="1"
                                       value="<?= $formHtml($_POST['number_of_rooms'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="bathrooms">Bathrooms</label>
                                <input class="form-control" id="bathrooms" name="number_of_bathrooms" type="number" min="0" max="1000" step="1"
                                       value="<?= $formHtml($_POST['number_of_bathrooms'] ?? '') ?>">
                            </div>
                        </div>

                        <h5 class="form-section-title mt-3">Location</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="erf-number">Erf number</label>
                                <input class="form-control" id="erf-number" name="property_erf_no" maxlength="50"
                                       value="<?= $formHtml($_POST['property_erf_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label required" for="street">Street name or address</label>
                                <input class="form-control" id="street" name="property_street_name" maxlength="200"
                                       value="<?= $formHtml($_POST['property_street_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="suburb">Suburb</label>
                                <input class="form-control" id="suburb" name="property_suburb" maxlength="100"
                                       value="<?= $formHtml($_POST['property_suburb'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="location">Location</label>
                                <input class="form-control" id="location" name="property_location" maxlength="100"
                                       value="<?= $formHtml($_POST['property_location'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="region">Region</label>
                                <select class="form-select" id="region" name="property_region" required>
                                    <option value="">Select region</option>
                                    <?php foreach ($regions as $regionOption): ?>
                                        <option value="<?= $formHtml($regionOption) ?>" <?= trim((string)($_POST['property_region'] ?? '')) === $regionOption ? 'selected' : '' ?>>
                                            <?= $formHtml($regionOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="town">Town</label>
                                <input class="form-control" id="town" name="property_town" maxlength="100"
                                       value="<?= $formHtml($_POST['property_town'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="features">Additional features</label>
                                <textarea class="form-control" id="features" name="additional_features" rows="3" maxlength="5000"><?= $formHtml($_POST['additional_features'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a class="btn btn-outline-secondary" href="<?= $formHtml($propertiesListUrl) ?>">Cancel</a>
                            <button class="btn btn-primary" type="submit" <?= !$sellers ? 'disabled' : '' ?>>Save property</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <footer class="footer">© <?= date('Y') ?> Nuru Real Estate</footer>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/libs/jquery/dist/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/app.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/app.init.js"></script>
<script src="<?= $baseUrl ?>/dist/js/app-style-switcher.js"></script>
<script src="<?= $baseUrl ?>/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/waves.js"></script>
<script src="<?= $baseUrl ?>/dist/js/sidebarmenu.js?v=20260720"></script>
<script src="<?= $baseUrl ?>/dist/js/feather.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/custom.min.js"></script>
<script>
(function () {
    var typeSelect = document.getElementById('sale-pricing-type');
    var plotSection = document.getElementById('plot-and-plan-pricing');
    var houseSection = document.getElementById('existing-house-pricing');
    var totalField = document.getElementById('selling-price');
    if (!typeSelect || !totalField) return;

    function amount(id) {
        var field = document.getElementById(id);
        var value = field ? parseFloat(field.value) : NaN;
        return isNaN(value) ? 0 : value;
    }

    function toggleSection(section, show) {
        if (!section) return;
        section.style.display = show ? '' : 'none';
        section.querySelectorAll('.sale-pricing-input').forEach(function (field) {
            if (show) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
                field.value = '';
            }
        });
    }

    function recalculate() {
        var total = 0;
        if (typeSelect.value === 'plot_and_plan') {
            total = amount('plot-selling-price') + amount('construction-amount') + amount('agent-commission-fees-pp');
        } else if (typeSelect.value === 'existing_house') {
            total = amount('property-selling-price') + amount('agent-commission-fees-eh');
        }
        totalField.value = total > 0 ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
    }

    typeSelect.addEventListener('change', function () {
        toggleSection(plotSection, typeSelect.value === 'plot_and_plan');
        toggleSection(houseSection, typeSelect.value === 'existing_house');
        recalculate();
    });

    document.querySelectorAll('.sale-pricing-input').forEach(function (field) {
        field.addEventListener('input', recalculate);
    });

    // Restore the correct section/visibility on validation-failure redisplay
    // (the selected Sale Type survives via PHP's `selected` attribute above).
    if (typeSelect.value === 'plot_and_plan') {
        toggleSection(plotSection, true);
    } else if (typeSelect.value === 'existing_house') {
        toggleSection(houseSection, true);
    }
    recalculate();
})();
</script>
</body>
</html>

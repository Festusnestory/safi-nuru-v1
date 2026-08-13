<?php
/** @var array $propertyTypes */
/** @var array $landTypes */
/** @var array $regions */
/** @var array $errors */
/** @var string $csrf */
/** @var array $sellers */
/** @var array $sellerDevelopmentData Keyed by application_id -> list of developments, each with a nested house_types list. */
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
        .house-type-preview { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: .375rem; padding: .75rem 1rem; font-size: .875rem; }
        .image-preview-grid { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
        .image-preview-grid img { width: 90px; height: 90px; object-fit: cover; border-radius: .375rem; border: 1px solid #e2e8f0; }
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

                    <form method="post" action="" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $formHtml($csrf) ?>">

                        <h5 class="form-section-title">Seller</h5>
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <label class="form-label required" for="seller-application">Property owner</label>
                                <select class="form-select" id="seller-application" name="seller_application_id" required <?= !$sellers ? 'disabled' : '' ?>>
                                    <option value="">Select an existing seller</option>
                                    <?php foreach ($sellers as $seller): ?>
                                        <option value="<?= (int)$seller['id'] ?>"
                                            data-sale-type="<?= $formHtml($seller['sale_type']) ?>"
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
                                                . ($seller['sale_type'] === 'Property Development' ? ', Developer' : '')
                                                . ')'
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Only active seller applications you are authorised to manage are shown.</div>
                            </div>
                        </div>

                        <div id="development-section" style="display:none;">
                            <h5 class="form-section-title">Development</h5>
                            <div class="row mb-3">
                                <div class="col-lg-8">
                                    <label class="form-label required">Does this property belong to a development?</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="belongs_to_development" id="belongs-yes" value="yes"
                                                <?= ($_POST['belongs_to_development'] ?? '') === 'yes' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="belongs-yes">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="belongs_to_development" id="belongs-no" value="no"
                                                <?= ($_POST['belongs_to_development'] ?? 'no') === 'no' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="belongs-no">No, standalone property</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="development-picker-section" style="display:none;">
                                <div class="row mb-3">
                                    <div class="col-lg-8">
                                        <label class="form-label required">Is the development already registered?</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="development_choice" id="dev-existing" value="existing"
                                                    <?= ($_POST['development_choice'] ?? '') === 'existing' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="dev-existing">Yes, select it</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="development_choice" id="dev-new" value="new"
                                                    <?= ($_POST['development_choice'] ?? '') === 'new' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="dev-new">No, register a new one</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3" id="development-existing-picker" style="display:none;">
                                    <div class="col-lg-8">
                                        <label class="form-label required" for="development-id">Development</label>
                                        <select class="form-select" id="development-id" name="development_id">
                                            <option value="">Select a development</option>
                                        </select>
                                        <div class="form-text">Developments already registered for this seller.</div>
                                    </div>
                                </div>

                                <div class="row" id="development-new-fields" style="display:none;">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required" for="development-name">Development name</label>
                                        <input class="form-control" id="development-name" name="development_name" maxlength="200"
                                               value="<?= $formHtml($_POST['development_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label required" for="development-region">Region</label>
                                        <select class="form-select" id="development-region" name="development_region">
                                            <option value="">Select region</option>
                                            <?php foreach ($regions as $regionOption): ?>
                                                <option value="<?= $formHtml($regionOption) ?>" <?= ($_POST['development_region'] ?? '') === $regionOption ? 'selected' : '' ?>>
                                                    <?= $formHtml($regionOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label required" for="development-town">Town</label>
                                        <select class="form-select" id="development-town" name="development_town">
                                            <option value="">Select town</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="development-location">Location</label>
                                        <input class="form-control" id="development-location" name="development_location" maxlength="100"
                                               value="<?= $formHtml($_POST['development_location'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="development-suburb">Suburb</label>
                                        <input class="form-control" id="development-suburb" name="development_suburb" maxlength="100"
                                               value="<?= $formHtml($_POST['development_suburb'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row mb-3" id="house-type-section" style="display:none;">
                                    <div class="col-lg-8">
                                        <label class="form-label required">House type</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="house_type_choice" id="ht-existing" value="existing"
                                                    <?= ($_POST['house_type_choice'] ?? '') === 'existing' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ht-existing">Select an existing house type</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="house_type_choice" id="ht-new" value="new"
                                                    <?= ($_POST['house_type_choice'] ?? '') === 'new' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="ht-new">Add a new house type</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3" id="house-type-existing-picker" style="display:none;">
                                    <div class="col-lg-8">
                                        <label class="form-label required" for="development-house-type-id">Existing house type</label>
                                        <select class="form-select" id="development-house-type-id" name="development_house_type_id">
                                            <option value="">Select a house type</option>
                                        </select>
                                        <div id="house-type-preview" class="house-type-preview mt-2" style="display:none;"></div>
                                    </div>
                                </div>

                                <div class="row" id="number-of-units-field" style="display:none;">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label required" for="number-of-units">Number of units</label>
                                        <input class="form-control" id="number-of-units" name="number_of_units" type="number" min="1" max="100000" step="1"
                                               value="<?= $formHtml($_POST['number_of_units'] ?? '') ?>">
                                        <div class="form-text">How many units of this type exist in the development.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="classification-section">
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

                        <h5 class="form-section-title mt-3">Property images</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <input class="form-control" id="property-images" name="property_images[]" type="file" accept="image/png,image/jpeg,image/gif,image/webp" multiple>
                                <div class="form-text">The first image selected is used as the primary photo.</div>
                                <div class="image-preview-grid" id="image-preview-grid"></div>
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

<script src="<?= $baseUrl ?>/assets/js/nuru-regions.js"></script>
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
<script>window.NURU_SELLER_DEV_DATA = <?= json_encode($sellerDevelopmentData, JSON_UNESCAPED_SLASHES) ?>;</script>
<script>
(function () {
    // ------------------------------------------------------------
    // Sale Type pricing breakdown (existing behaviour, unchanged)
    // ------------------------------------------------------------
    var typeSelect = document.getElementById('sale-pricing-type');
    var plotSection = document.getElementById('plot-and-plan-pricing');
    var houseSection = document.getElementById('existing-house-pricing');
    var totalField = document.getElementById('selling-price');

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
                field.disabled = false;
            } else {
                field.removeAttribute('required');
                field.value = '';
                // plot-and-plan and existing-house both have an
                // "agent_commission_fees" field (same name, only one shown
                // at a time) - disabling the hidden one is what actually
                // keeps it out of the submitted form data, since a
                // display:none input with a name still posts its value and
                // silently overwrites the visible field's value otherwise.
                field.disabled = true;
            }
        });
    }

    function recalculate() {
        if (!typeSelect || !totalField) return;
        var total = 0;
        if (typeSelect.value === 'plot_and_plan') {
            total = amount('plot-selling-price') + amount('construction-amount') + amount('agent-commission-fees-pp');
        } else if (typeSelect.value === 'existing_house') {
            total = amount('property-selling-price') + amount('agent-commission-fees-eh');
        }
        totalField.value = total > 0 ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
    }

    if (typeSelect && totalField) {
        typeSelect.addEventListener('change', function () {
            toggleSection(plotSection, typeSelect.value === 'plot_and_plan');
            toggleSection(houseSection, typeSelect.value === 'existing_house');
            recalculate();
        });
        document.querySelectorAll('.sale-pricing-input').forEach(function (field) {
            field.addEventListener('input', recalculate);
        });
        // Restore the correct section on a validation-failure redisplay
        // (PHP already re-filled its values via $_POST). Disable the other
        // one without clearing it - see the disabled-toggle note in
        // toggleSection() for why disabling (not just hiding) matters here.
        if (typeSelect.value === 'plot_and_plan') {
            houseSection.querySelectorAll('.sale-pricing-input').forEach(function (field) { field.disabled = true; });
            toggleSection(plotSection, true);
        } else if (typeSelect.value === 'existing_house') {
            plotSection.querySelectorAll('.sale-pricing-input').forEach(function (field) { field.disabled = true; });
            toggleSection(houseSection, true);
        } else {
            plotSection.querySelectorAll('.sale-pricing-input').forEach(function (field) { field.disabled = true; });
            houseSection.querySelectorAll('.sale-pricing-input').forEach(function (field) { field.disabled = true; });
        }
        recalculate();
    }

    // ------------------------------------------------------------
    // Seller -> development -> house type dynamic flow
    // ------------------------------------------------------------
    var sellerSelect = document.getElementById('seller-application');
    var developmentSection = document.getElementById('development-section');
    var developmentPickerSection = document.getElementById('development-picker-section');
    var developmentExistingPicker = document.getElementById('development-existing-picker');
    var developmentNewFields = document.getElementById('development-new-fields');
    var developmentIdSelect = document.getElementById('development-id');
    var developmentRegionSelect = document.getElementById('development-region');
    var developmentTownSelect = document.getElementById('development-town');
    var houseTypeSection = document.getElementById('house-type-section');
    var houseTypeExistingPicker = document.getElementById('house-type-existing-picker');
    var houseTypeIdSelect = document.getElementById('development-house-type-id');
    var houseTypePreview = document.getElementById('house-type-preview');
    var numberOfUnitsField = document.getElementById('number-of-units-field');
    var numberOfUnitsInput = document.getElementById('number-of-units');
    var classificationSection = document.getElementById('classification-section');
    var devData = window.NURU_SELLER_DEV_DATA || {};

    function setRequired(el, required) {
        if (!el) return;
        if (required) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
        }
    }

    function show(el, visible) {
        if (el) el.style.display = visible ? '' : 'none';
    }

    function currentSellerSaleType() {
        var option = sellerSelect.options[sellerSelect.selectedIndex];
        return option ? (option.getAttribute('data-sale-type') || 'Individual') : 'Individual';
    }

    function belongsToDevelopmentValue() {
        var checked = document.querySelector('input[name="belongs_to_development"]:checked');
        return checked ? checked.value : '';
    }

    function developmentChoiceValue() {
        var checked = document.querySelector('input[name="development_choice"]:checked');
        return checked ? checked.value : '';
    }

    function houseTypeChoiceValue() {
        var checked = document.querySelector('input[name="house_type_choice"]:checked');
        return checked ? checked.value : '';
    }

    function populateDevelopmentTowns() {
        var region = developmentRegionSelect.value;
        var towns = (window.NURU_TOWNS_BY_REGION && window.NURU_TOWNS_BY_REGION[region]) || [];
        var current = developmentTownSelect.value;
        developmentTownSelect.innerHTML = '<option value="">Select town</option>';
        towns.forEach(function (town) {
            var option = document.createElement('option');
            option.value = town;
            option.textContent = town;
            if (town === current) option.selected = true;
            developmentTownSelect.appendChild(option);
        });
    }

    function developmentsForCurrentSeller() {
        var applicationId = sellerSelect.value;
        return (applicationId && devData[applicationId]) || [];
    }

    function populateDevelopmentOptions() {
        var developments = developmentsForCurrentSeller();
        var current = developmentIdSelect.value;
        developmentIdSelect.innerHTML = '<option value="">Select a development</option>';
        developments.forEach(function (development) {
            var option = document.createElement('option');
            option.value = development.id;
            option.textContent = development.development_name + ' (' + development.town + ', ' + development.region + ')';
            if (String(development.id) === String(current)) option.selected = true;
            developmentIdSelect.appendChild(option);
        });
    }

    function houseTypesForDevelopment(developmentId) {
        var developments = developmentsForCurrentSeller();
        for (var i = 0; i < developments.length; i++) {
            if (String(developments[i].id) === String(developmentId)) {
                return developments[i].house_types || [];
            }
        }
        return [];
    }

    function formatHouseTypeLabel(houseType) {
        var price = parseFloat(houseType.selling_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return houseType.property_type + ' — N$' + price + ' (' + houseType.number_of_units + ' units)';
    }

    function populateHouseTypeOptions() {
        var houseTypes = houseTypesForDevelopment(developmentIdSelect.value);
        var current = houseTypeIdSelect.value;
        houseTypeIdSelect.innerHTML = '<option value="">Select a house type</option>';
        houseTypes.forEach(function (houseType) {
            var option = document.createElement('option');
            option.value = houseType.id;
            option.textContent = formatHouseTypeLabel(houseType);
            if (String(houseType.id) === String(current)) option.selected = true;
            houseTypeIdSelect.appendChild(option);
        });
        updateHouseTypePreview();
    }

    function updateHouseTypePreview() {
        var houseTypes = houseTypesForDevelopment(developmentIdSelect.value);
        var selected = null;
        for (var i = 0; i < houseTypes.length; i++) {
            if (String(houseTypes[i].id) === String(houseTypeIdSelect.value)) {
                selected = houseTypes[i];
                break;
            }
        }
        if (!selected) {
            show(houseTypePreview, false);
            return;
        }
        houseTypePreview.innerHTML =
            '<strong>' + selected.property_type + '</strong> · ' + selected.land_type + '<br>' +
            'Land ' + selected.land_size + ' m²' + (selected.house_size ? ', house ' + selected.house_size + ' m²' : '') + '<br>' +
            'Total selling price: N$' + parseFloat(selected.selling_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
        show(houseTypePreview, true);
    }

    // The classification/price section is reused for two different
    // purposes (a standalone property's own details, or a brand-new house
    // type's template) and skipped entirely when an existing house type is
    // selected (its stored values are used instead, server-side). Hiding it
    // must also drop `required`/enable its own fields, the same way
    // toggleSection() already does for the plot-vs-existing-house
    // sub-sections - otherwise the browser still blocks submission on
    // fields the user can no longer see.
    var classificationRequiredFields = classificationSection
        ? Array.prototype.filter.call(classificationSection.querySelectorAll('[required]'), function (field) {
            return !field.classList.contains('sale-pricing-input');
        })
        : [];

    function showClassificationSection(visible) {
        show(classificationSection, visible);
        classificationRequiredFields.forEach(function (field) {
            setRequired(field, visible);
            field.disabled = !visible;
        });
        if (!visible) {
            // A hidden sale-pricing-type also means neither pricing
            // sub-section should stay enabled underneath it.
            toggleSection(plotSection, false);
            toggleSection(houseSection, false);
        }
    }

    function refreshDevelopmentFlow() {
        var isDeveloper = currentSellerSaleType() === 'Property Development';
        show(developmentSection, isDeveloper);
        if (!isDeveloper) {
            show(developmentPickerSection, false);
            showClassificationSection(true);
            return;
        }

        var belongs = belongsToDevelopmentValue();
        show(developmentPickerSection, belongs === 'yes');
        if (belongs !== 'yes') {
            showClassificationSection(true);
            setRequired(numberOfUnitsInput, false);
            show(numberOfUnitsField, false);
            return;
        }

        var devChoice = developmentChoiceValue();
        show(developmentExistingPicker, devChoice === 'existing');
        show(developmentNewFields, devChoice === 'new');
        setRequired(document.getElementById('development-name'), devChoice === 'new');
        setRequired(developmentRegionSelect, devChoice === 'new');
        setRequired(developmentTownSelect, devChoice === 'new');
        setRequired(developmentIdSelect, devChoice === 'existing');

        // A brand-new development has no house types yet - only "new" makes sense.
        var htNewRadio = document.getElementById('ht-new');
        var htExistingRadio = document.getElementById('ht-existing');
        if (devChoice === 'new') {
            htExistingRadio.checked = false;
            htExistingRadio.disabled = true;
            if (!htNewRadio.checked) htNewRadio.checked = false;
        } else {
            htExistingRadio.disabled = false;
        }

        show(houseTypeSection, devChoice === 'existing' || devChoice === 'new');

        var htChoice = houseTypeChoiceValue();
        show(houseTypeExistingPicker, htChoice === 'existing');
        setRequired(houseTypeIdSelect, htChoice === 'existing');
        show(numberOfUnitsField, htChoice === 'new');
        setRequired(numberOfUnitsInput, htChoice === 'new');
        showClassificationSection(htChoice === 'new');
    }

    if (sellerSelect) {
        sellerSelect.addEventListener('change', function () {
            populateDevelopmentOptions();
            refreshDevelopmentFlow();
        });
    }
    document.querySelectorAll('input[name="belongs_to_development"]').forEach(function (radio) {
        radio.addEventListener('change', refreshDevelopmentFlow);
    });
    document.querySelectorAll('input[name="development_choice"]').forEach(function (radio) {
        radio.addEventListener('change', refreshDevelopmentFlow);
    });
    document.querySelectorAll('input[name="house_type_choice"]').forEach(function (radio) {
        radio.addEventListener('change', refreshDevelopmentFlow);
    });
    if (developmentIdSelect) {
        developmentIdSelect.addEventListener('change', function () {
            populateHouseTypeOptions();
        });
    }
    if (houseTypeIdSelect) {
        houseTypeIdSelect.addEventListener('change', updateHouseTypePreview);
    }
    if (developmentRegionSelect) {
        developmentRegionSelect.addEventListener('change', populateDevelopmentTowns);
    }

    if (sellerSelect) {
        populateDevelopmentOptions();
        populateDevelopmentTowns();
        populateHouseTypeOptions();
        refreshDevelopmentFlow();
    }

    // ------------------------------------------------------------
    // Property image preview
    // ------------------------------------------------------------
    var imagesInput = document.getElementById('property-images');
    var previewGrid = document.getElementById('image-preview-grid');
    if (imagesInput && previewGrid) {
        imagesInput.addEventListener('change', function () {
            previewGrid.innerHTML = '';
            Array.from(imagesInput.files || []).forEach(function (file) {
                if (!file.type.startsWith('image/')) return;
                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                previewGrid.appendChild(img);
            });
        });
    }
})();
</script>
</body>
</html>

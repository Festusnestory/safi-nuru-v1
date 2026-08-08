<?php
require __DIR__ . '/public/bootstrap.php';
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'type' => in_array($_GET['type'] ?? '', propertyTypes(), true) ? $_GET['type'] : '',
    'region' => trim((string)($_GET['region'] ?? '')),
    'max_price' => filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT) ?: '',
];
$properties = publicProperties($pdo, $filters);
try {
    $regions = $pdo->query(
        "SELECT DISTINCT sp.property_region
         FROM seller_properties sp
         INNER JOIN seller_applications sa ON sa.id = sp.application_id
         WHERE sp.property_status = 'available'
           AND sa.status IN ('approved', 'completed')
         ORDER BY sp.property_region"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable) { $regions = []; }
renderHeader('Properties', 'Browse available homes, land, farms and commercial property across Namibia.', 'properties');
?>
<main id="main">
  <section class="page-hero"><p class="eyebrow">THE COLLECTION</p><h1>Find a place<br><em>to belong.</em></h1><p>Browse current opportunities or tell us what you need—we often know what is coming before it reaches the market.</p></section>
  <form class="property-filters" method="get" action="properties.php">
    <label><span>Search</span><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Town, suburb or property type"></label>
    <label><span>Property type</span><select name="type"><option value="">All types</option><?php foreach (propertyTypes() as $type): ?><option <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
    <label><span>Region</span><select name="region"><option value="">All regions</option><?php foreach ($regions as $region): ?><option <?= $filters['region'] === $region ? 'selected' : '' ?>><?= e($region) ?></option><?php endforeach; ?></select></label>
    <label><span>Maximum price</span><select name="max_price"><option value="">Any price</option><?php foreach ([1000000,2000000,3500000,5000000,10000000] as $price): ?><option value="<?= $price ?>" <?= (string)$filters['max_price'] === (string)$price ? 'selected' : '' ?>>N$ <?= number_format($price) ?></option><?php endforeach; ?></select></label>
    <button class="button button-dark" type="submit">Search</button>
  </form>
  <section class="section listing-results">
    <div class="results-bar"><p><strong><?= count($properties) ?></strong> <?= count($properties) === 1 ? 'property' : 'properties' ?> found</p><?php if (array_filter($filters)): ?><a href="properties.php">Clear filters</a><?php endif; ?></div>
    <div class="property-grid">
      <?php foreach ($properties as $property): ?>
      <article class="property-card reveal"><a href="property.php?id=<?= (int)$property['id'] ?>" class="property-image"><img loading="lazy" src="<?= e(publicPropertyImage($property['primary_image'])) ?>" alt="<?= e($property['image_alt'] ?: $property['property_detail_type'] . ' in ' . $property['property_town']) ?>"><span>View property</span></a><div class="property-meta"><p><?= e($property['property_town']) ?> · <?= e($property['property_region']) ?></p><h2><a href="property.php?id=<?= (int)$property['id'] ?>"><?= e($property['property_detail_type']) ?></a></h2><div><strong>N$ <?= number_format((float)$property['selling_price'], 0) ?></strong><span><?= (int)$property['number_of_rooms'] ?> bed · <?= (int)$property['number_of_bathrooms'] ?> bath</span></div></div></article>
      <?php endforeach; ?>
    </div>
    <?php if (!$properties): ?><div class="empty-state"><p class="eyebrow">NO EXACT MATCHES</p><h2>Let us search beyond the listings.</h2><p>Share your brief and an advisor will help identify suitable opportunities.</p><a class="button button-dark" href="contact.php">Tell us what you need</a></div><?php endif; ?>
  </section>
</main>
<?php renderFooter(); ?>

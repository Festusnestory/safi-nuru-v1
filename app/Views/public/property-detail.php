<?php
/** @var array $property */
/** @var array $images */
/** @var string $location */
use App\Models\PublicProperty;
?>
<main id="main">
  <section class="detail-head"><a href="properties.php" class="back-link">← Back to properties</a><p class="eyebrow">FOR SALE · <?= e($property['land_type']) ?></p><h1><?= e($property['property_detail_type']) ?></h1><div><p><?= e($location) ?></p><strong>N$ <?= number_format((float)$property['selling_price'], 0) ?></strong></div></section>
  <section class="detail-gallery <?= count($images) > 1 ? 'multi' : '' ?>">
    <?php $gallery = $images ?: [['file_path' => $property['primary_image'], 'alt_text' => null]]; foreach (array_slice($gallery, 0, 3) as $image): ?><img src="<?= e(PublicProperty::imagePath($image['file_path'])) ?>" alt="<?= e($image['alt_text'] ?: $property['property_detail_type'] . ' in ' . $property['property_town']) ?>"><?php endforeach; ?>
  </section>
  <section class="detail-layout section">
    <div class="detail-copy"><p class="eyebrow">THE PROPERTY</p><h2>Space for what<br>comes next.</h2><div class="features"><div><strong><?= (int)$property['number_of_rooms'] ?></strong><span>Bedrooms / rooms</span></div><div><strong><?= (int)$property['number_of_bathrooms'] ?></strong><span>Bathrooms</span></div><div><strong><?= number_format((float)$property['land_size'], 0) ?> m²</strong><span>Land size</span></div><?php if ($property['house_size']): ?><div><strong><?= number_format((float)$property['house_size'], 0) ?> m²</strong><span>House size</span></div><?php endif; ?></div><p class="property-description"><?= nl2br(e($property['additional_features'] ?: 'A considered opportunity in ' . $property['property_town'] . '. Contact Nuru for complete details, viewing availability and guidance on the next step.')) ?></p></div>
    <aside class="inquiry-card"><p class="eyebrow">ENQUIRE</p><h2>Interested in this property?</h2><p>Leave your details and a Nuru advisor will contact you.</p><form method="post" action="inquiry-handler.php" data-inquiry-form><input type="hidden" name="csrf_token" value="<?= e(csrfPublicToken()) ?>"><input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>"><input type="text" name="website" tabindex="-1" autocomplete="off" class="honeypot" aria-hidden="true"><label>Full name<input required name="full_name" autocomplete="name" maxlength="120"></label><label>Email<input required type="email" name="email" autocomplete="email" maxlength="190"></label><label>Phone<input required name="phone" autocomplete="tel" maxlength="40"></label><label>Message<textarea name="message" rows="3" maxlength="1500">I am interested in this <?= e($property['property_detail_type']) ?> in <?= e($property['property_town']) ?>.</textarea></label><label class="consent"><input required type="checkbox" name="consent" value="1"><span>I agree that Nuru may contact me about this enquiry.</span></label><button class="button button-gold" type="submit">Request a viewing →</button></form></aside>
  </section>
</main>

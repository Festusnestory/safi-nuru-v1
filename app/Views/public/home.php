<?php
/** @var array $featured */
use App\Models\PublicProperty;
?>
<main id="main">
  <section class="hero">
    <div class="hero-media" role="img" aria-label="Contemporary Namibian home overlooking the hills"></div>
    <div class="hero-shade"></div>
    <div class="hero-content reveal">
      <p class="eyebrow light">LOCAL INSIGHT · LASTING VALUE</p>
      <h1>Property, made<br><em>personal.</em></h1>
      <p class="hero-copy">Find a home, investment, or piece of land with a team that understands Namibia—and listens to what matters to you.</p>
      <div class="actions"><a class="button button-gold" href="properties.php">Explore properties <span>→</span></a><a class="text-link light" href="contact.php">Talk to an advisor <span>↗</span></a></div>
    </div>
    <div class="hero-note"><span>01</span><p>Curated opportunities<br>across Namibia</p></div>
    <a class="scroll-cue" href="#discover" aria-label="Scroll to discover"><span></span>DISCOVER</a>
  </section>

  <section class="intro section" id="discover">
    <div><p class="eyebrow">THE NURU DIFFERENCE</p><h2>A clearer way to move<br>through property.</h2></div>
    <div><p class="lead">Real estate decisions are deeply personal. We combine grounded market knowledge with attentive service, giving you the confidence to move forward.</p><a class="text-link dark" href="about.php">How we work <span>→</span></a></div>
  </section>

  <section class="stats-strip" aria-label="Nuru service highlights">
    <div><strong>Local</strong><span>Namibian market knowledge</span></div><div><strong>End-to-end</strong><span>From first conversation to close</span></div><div><strong>Human</strong><span>Guidance tailored to your goals</span></div>
  </section>

  <section class="section listings-section">
    <div class="section-heading"><div><p class="eyebrow">SELECTED PROPERTIES</p><h2>Places worth<br>discovering.</h2></div><a class="text-link dark" href="properties.php">View all properties <span>→</span></a></div>
    <div class="property-grid">
      <?php if ($featured): foreach ($featured as $property): ?>
        <article class="property-card reveal">
          <a href="property.php?id=<?= (int)$property['id'] ?>" class="property-image"><img loading="lazy" src="<?= e(PublicProperty::imagePath($property['primary_image'])) ?>" alt="<?= e($property['image_alt'] ?: $property['property_detail_type'] . ' in ' . $property['property_town']) ?>"><span>View property</span></a>
          <div class="property-meta"><p><?= e($property['property_town']) ?> · <?= e($property['property_region']) ?></p><h3><a href="property.php?id=<?= (int)$property['id'] ?>"><?= e($property['property_detail_type']) ?></a></h3><div><strong>N$ <?= number_format((float)$property['selling_price'], 0) ?></strong><span><?= (int)$property['number_of_rooms'] ?> bed · <?= (int)$property['number_of_bathrooms'] ?> bath</span></div></div>
        </article>
      <?php endforeach; else: ?>
        <div class="empty-state"><p class="eyebrow">COMING SOON</p><h3>Our next collection is being prepared.</h3><p>Tell us what you are looking for and we will connect you with suitable opportunities.</p><a class="button button-dark" href="contact.php">Share your brief</a></div>
      <?php endif; ?>
    </div>
  </section>

  <section class="journey section-dark">
    <div><p class="eyebrow light">ONE TEAM, EVERY STEP</p><h2>Wherever you are<br>on the journey.</h2></div>
    <div class="journey-grid"><a href="properties.php"><span>01</span><h3>Buy</h3><p>Explore opportunities selected around the way you want to live and invest.</p><b>Explore homes →</b></a><a href="html/material/seller/index.php"><span>02</span><h3>Sell</h3><p>Present your property with care and reach serious, qualified buyers.</p><b>List with Nuru →</b></a><a href="contact.php"><span>03</span><h3>Get guidance</h3><p>Start with a conversation, whether your plans are clear or still taking shape.</p><b>Speak to us →</b></a></div>
  </section>

  <section class="cta-panel"><p class="eyebrow light">YOUR NEXT MOVE</p><h2>Let’s find what<br>feels right.</h2><p>Share your property goals with us. We will come back with a clear, considered next step.</p><a class="button button-gold" href="contact.php">Start a conversation <span>→</span></a></section>
</main>

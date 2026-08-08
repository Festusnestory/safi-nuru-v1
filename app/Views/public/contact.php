<?php
/** @var bool $sent */
/** @var array $errors */
/** @var array $old */
/** @var string $globalError */
/** @var string $submissionToken */
?>
<main id="main">
  <section class="page-hero contact-hero">
    <p class="eyebrow">START A CONVERSATION</p>
    <h1>Tell us where you<br>want to go.</h1>
    <p>Whether you are ready to move or still exploring, share what is on your mind. We will help make the next step clear.</p>
  </section>
  <section class="contact-layout section">
    <div class="contact-details">
      <p class="eyebrow">CONTACT NURU</p>
      <h2>We’re ready<br>when you are.</h2>
      <div><span>VISIT</span><p>Windhoek, Namibia</p></div>
      <div><span>HOURS</span><p>Monday–Friday<br>08:00–17:00</p></div>
      <div><span>PORTAL</span><p><a href="html/material/authentication-login.php">Existing client sign in ↗</a></p></div>
    </div>
    <div class="contact-form-wrap">
      <?php if ($sent): ?>
        <div class="success-message" role="status">
          <span aria-hidden="true">✓</span>
          <h2>Thank you.</h2>
          <p>Your message is with our team. A Nuru advisor will be in touch shortly.</p>
          <a href="properties.php" class="text-link dark">Explore properties →</a>
        </div>
      <?php else: ?>
        <form method="post" action="inquiry-handler.php" class="contact-form" data-inquiry-form novalidate>
          <input type="hidden" name="csrf_token" value="<?= e(csrfPublicToken()) ?>">
          <input type="hidden" name="submission_token" value="<?= e($submissionToken) ?>">
          <div class="honeypot" hidden inert aria-hidden="true">
            <label for="contact-website">Leave this field blank</label>
            <input id="contact-website" type="text" name="website" tabindex="-1" autocomplete="off">
          </div>
          <?php if ($globalError !== ''): ?>
            <div class="form-alert" role="alert" tabindex="-1" data-form-error><?= e($globalError) ?></div>
          <?php endif; ?>
          <div class="form-row">
            <label for="contact-full-name">Full name
              <input required id="contact-full-name" name="full_name" autocomplete="name" maxlength="120"
                     value="<?= e($old['full_name'] ?? '') ?>"<?= isset($errors['full_name']) ? ' aria-invalid="true" aria-describedby="full-name-error"' : '' ?>>
              <span class="field-error" id="full-name-error" role="alert"><?= e($errors['full_name'] ?? '') ?></span>
            </label>
            <label for="contact-email">Email address
              <input required id="contact-email" type="email" name="email" autocomplete="email" maxlength="190"
                     value="<?= e($old['email'] ?? '') ?>"<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
              <span class="field-error" id="email-error" role="alert"><?= e($errors['email'] ?? '') ?></span>
            </label>
          </div>
          <div class="form-row">
            <label for="contact-phone">Phone number
              <input required id="contact-phone" type="tel" name="phone" autocomplete="tel" maxlength="40"
                     placeholder="+264 81 234 5678" value="<?= e($old['phone'] ?? '') ?>"
                     aria-describedby="phone-help phone-error"<?= isset($errors['phone']) ? ' aria-invalid="true"' : '' ?>>
              <span class="field-help" id="phone-help">Use 7–15 digits; spaces, brackets, hyphens and a leading + are accepted.</span>
              <span class="field-error" id="phone-error" role="alert"><?= e($errors['phone'] ?? '') ?></span>
            </label>
            <label for="contact-interest">I would like to
              <select id="contact-interest" name="interest">
                <?php foreach (['buy' => 'Buy a property', 'sell' => 'Sell a property', 'invest' => 'Discuss an investment', 'general' => 'Ask a general question'] as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['interest'] ?? 'buy') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <label for="contact-message">How can we help?
            <textarea required id="contact-message" name="message" rows="6" maxlength="1500"
                      placeholder="Tell us a little about your plans..."<?= isset($errors['message']) ? ' aria-invalid="true" aria-describedby="message-error"' : '' ?>><?= e($old['message'] ?? '') ?></textarea>
            <span class="field-error" id="message-error" role="alert"><?= e($errors['message'] ?? '') ?></span>
          </label>
          <label class="consent" for="contact-consent">
            <input required id="contact-consent" type="checkbox" name="consent" value="1"
                   <?= !empty($old['consent']) ? 'checked ' : '' ?><?= isset($errors['consent']) ? 'aria-invalid="true" aria-describedby="consent-error"' : '' ?>>
            <span>I agree that Nuru may use these details to respond to my enquiry.</span>
          </label>
          <span class="field-error" id="consent-error" role="alert"><?= e($errors['consent'] ?? '') ?></span>
          <button class="button button-dark" type="submit" data-default-label="Send enquiry →">Send enquiry →</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>

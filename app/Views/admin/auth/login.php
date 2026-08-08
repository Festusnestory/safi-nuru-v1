<?php
/** @var string $passwordResetCsrf */
/** @var string $baseUrl */
?>
<!DOCTYPE html>
<html dir="ltr">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nuru Real Estate</title>
    <?php if (TURNSTILE_READY): ?>
    <script>
      window.nuruTurnstileSuccess = function () {
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', window.nuruTurnstileSuccess, { once: true });
          return;
        }
        const button = document.querySelector('#loginForm button[type="submit"]');
        if (button) {
          button.disabled = false;
          button.removeAttribute('aria-disabled');
        }
        const message = document.getElementById('loginMessage');
        if (message && message.dataset.turnstile === 'true') {
          message.classList.add('d-none');
          message.textContent = '';
          delete message.dataset.turnstile;
        }
      };
      window.nuruTurnstileUnavailable = function () {
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', window.nuruTurnstileUnavailable, { once: true });
          return;
        }
        const button = document.querySelector('#loginForm button[type="submit"]');
        if (button) {
          button.disabled = true;
          button.setAttribute('aria-disabled', 'true');
        }
        const message = document.getElementById('loginMessage');
        if (message) {
          message.dataset.turnstile = 'true';
          message.classList.remove('d-none', 'alert-success');
          message.classList.add('alert', 'alert-danger');
          message.textContent = 'Security verification expired or is temporarily unavailable. Please wait for it to refresh.';
        }
      };
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer onerror="nuruTurnstileUnavailable()"></script>
    <?php endif; ?>

    <link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/images/favicon.png" />
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet" />

    <!-- Font Awesome (for icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
      /* Compact auth card - narrower column, right-sized controls, matched
         across the login page and the buyer/seller/agent application forms
         so none of them feel like an oversized full-width admin screen. */
      .auth-card-col {
        max-width: 460px;
      }

      #loginform .card-body,
      #recoverform .card-body {
        padding: 2rem;
      }

      #loginform h2 {
        font-size: 1.5rem;
      }

      #loginForm .form-control,
      #recoverForm .form-control {
        height: calc(2.5rem + 2px);
        font-size: 0.95rem;
      }

      #loginForm .btn,
      #recoverForm .btn {
        padding: 0.5rem 1.25rem;
        font-size: 0.95rem;
      }

      /* Registration Flex Cards */
      #roleSelection {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1.5rem;
      }

      .role-selection-card {
        transition: all 0.3s ease;
        cursor: pointer;
        flex: 1 1 220px;
        max-width: 240px;
        border: 1px solid #e0e0e0;
      }

      .role-selection-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        border-color: var(--bs-primary, #7460ee);
      }

      .role-selection-card .role-icon {
        font-size: 2rem;
        color: var(--bs-primary, #7460ee);
      }

      .role-selection-card .btn {
        padding: 0.45rem 1rem;
        font-size: 0.9rem;
      }

      /* Hide forms by default */
      #registerform,
      #recoverform {
        display: none;
      }

      #registerform .card-body {
        padding: 2rem 1.5rem;
      }

      #registerform h2 {
        font-weight: 600;
        margin-bottom: 0.75rem;
      }

      #registerform p.text-muted {
        margin-bottom: 2rem;
      }
    </style>
  </head>

  <body>
    <!-- Preloader -->
    <div class="preloader">
      <svg class="tea lds-ripple" width="37" height="48" viewBox="0 0 37 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M27.0819 17H3.02508C1.91076 17 1.01376 17.9059 1.0485 19.0197C1.15761 22.5177 1.49703 29.7374 2.5 34C4.07125 40.6778 7.18553 44.8868 8.44856 46.3845C8.79051 46.79 9.29799 47 9.82843 47H20.0218C20.639 47 21.2193 46.7159 21.5659 46.2052C22.6765 44.5687 25.2312 40.4282 27.5 34C28.9757 29.8188 29.084 22.4043 29.0441 18.9156C29.0319 17.8436 28.1539 17 27.0819 17Z"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          d="M29 23.5C29 23.5 34.5 20.5 35.5 25.4999C36.0986 28.4926 34.2033 31.5383 32 32.8713C29.4555 34.4108 28 34 28 34"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          id="teabag"
          fill="#1e88e5"
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M16 25V17H14V25H12C10.3431 25 9 26.3431 9 28V34C9 35.6569 10.3431 37 12 37H18C19.6569 37 21 35.6569 21 34V28C21 26.3431 19.6569 25 18 25H16ZM11 28C11 27.4477 11.4477 27 12 27H18C18.5523 27 19 27.4477 19 28V34C19 34.5523 18.5523 35 18 35H12C11.4477 35 11 34.5523 11 34V28Z"
        ></path>
      </svg>
    </div>

    <!-- Auth Layout -->
    <div class="row auth-wrapper gx-0">
      <!-- Left Info Section -->
      <div class="col-lg-4 col-xl-3 bg-info auth-box-2 on-sidebar">
        <div class="h-100 d-flex align-items-center justify-content-center text-center text-white">
          <div>
            <img src="<?= $baseUrl ?>/assets/images/logo-light-icon.png" alt="logo" style="width: 260px;"/>
            <h2 class="fw-light">
               <span class="font-weight-medium"></span>
            </h2>
            <p class="op-5 fs-5 mt-3"></p>
          </div>
        </div>
      </div>

      <!-- Right Form Section -->
      <div class="col-lg-8 col-xl-9 d-flex align-items-center justify-content-center">
        <div class="row justify-content-center w-100 mt-4 mt-lg-0">
          <div class="col-11 col-sm-9 col-md-7 col-lg-6 auth-card-col">

            <!-- REGISTER FORM (Flex Layout) -->
            <div class="card" id="registerform">
              <div class="card-body text-center">
                <h2 class="mb-2">Sign Up Form</h2>
                <p class="text-muted fs-5 mb-4">Choose your account type below</p>

                <div id="roleSelection">
                  <!-- Buyer -->
                  <div class="card role-selection-card" data-role="buyer">
                    <div class="card-body text-center p-4 d-flex flex-column">
                      <div class="role-icon mb-3">
                        <i class="fas fa-user-tie"></i>
                      </div>
                      <h4 class="card-title mb-3">Buyer</h4>
                      <p class="card-text text-muted flex-grow-1">Complete your buyer application to get started.</p>
                      <a href="<?= $baseUrl ?>/html/material/buyer/index.php" class="btn btn-primary w-100 mt-3">
							<i class="fas fa-arrow-right me-2"></i> Start Application
						</a>
                    </div>
                  </div>

                  <!-- Seller -->
                  <div class="card role-selection-card" data-role="seller">
                    <div class="card-body text-center p-4 d-flex flex-column">
                      <div class="role-icon mb-3">
                        <i class="fas fa-home"></i>
                      </div>
                      <h4 class="card-title mb-3">Seller</h4>
                      <p class="card-text text-muted flex-grow-1">Register as a seller to list your property.</p>
						<a href="<?= $baseUrl ?>/html/material/seller/index.php" class="btn btn-primary w-100 mt-3">
							<i class="fas fa-arrow-right me-2"></i>List Property
						</a>
                    </div>
                  </div>

                  <!-- Agent -->
                  <div class="card role-selection-card" data-role="agent">
                    <div class="card-body text-center p-4 d-flex flex-column">
                      <div class="role-icon mb-3">
                        <i class="fas fa-handshake"></i>
                      </div>
                      <h4 class="card-title mb-3">Agent</h4>
                      <p class="card-text text-muted flex-grow-1">Join our agent network to manage properties.</p>
						<a href="<?= $baseUrl ?>/html/material/agent/index.php" class="btn btn-primary w-100 mt-3">
							<i class="fas fa-arrow-right me-2"></i> Start Application
						</a>
                    </div>
                  </div>
                </div>

                <div class="mt-4">
                  <a href="javascript:void(0)" id="to-login2" class="text-decoration-none fw-semibold">
                    <i class="fas fa-arrow-left me-2"></i> Back to Login
                  </a>
                </div>
              </div>
            </div>

            <!-- LOGIN FORM -->
            <div class="card" id="loginform">
              <div class="card-body">
                <h2>Welcome to Nuru Real Estate</h2>
                <p class="text-muted fs-5">
                  New here?
                  <a href="javascript:void(0)" id="to-register">Create an account</a>
                </p>

                <form id="loginForm" class="form-horizontal mt-4 pt-3 needs-validation" novalidate>

                  <div class="form-floating mb-3">
                    <input type="email" class="form-control form-input-bg" id="tb-email" placeholder="name@example.com" autocomplete="username" maxlength="255" aria-describedby="login-email-error" required />
                    <label for="tb-email">Email</label>
                    <div class="invalid-feedback" id="login-email-error">Email is required</div>
                  </div>

                  <div class="form-floating mb-3">
                    <input type="password" class="form-control form-input-bg" id="text-password" placeholder="*****" autocomplete="current-password" aria-describedby="login-password-error" required />
                    <label for="text-password">Password</label>
                    <div class="invalid-feedback" id="login-password-error">Password is required</div>
                  </div>

                  <div class="d-flex justify-content-end mb-3">
                    <div>
                      <a href="javascript:void(0)" id="to-recover" class="fw-bold">Forgot Password?</a>
                    </div>
                  </div>

                  <?php if (TURNSTILE_READY): ?>
                  <div class="cf-turnstile mb-3"
                       data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>"
                       data-callback="nuruTurnstileSuccess"
                       data-expired-callback="nuruTurnstileUnavailable"
                       data-error-callback="nuruTurnstileUnavailable"></div>
                  <?php elseif (TURNSTILE_ENABLED): ?>
                  <div class="alert alert-warning mb-3" role="alert">
                    Security verification is temporarily unavailable. Please try again later.
                  </div>
                  <?php endif; ?>

					<div id="loginMessage" class="alert d-none"></div>

                  <div class="d-grid mt-4 pt-2">
                    <button type="submit" class="btn btn-info"<?= TURNSTILE_ENABLED ? ' disabled aria-disabled="true"' : '' ?>>Sign in</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- RECOVER FORM -->
            <div class="card" id="recoverform">
              <div class="card-body">
                <h3>Recover Password</h3>
                <p class="text-muted fs-5">Enter your email. If it matches an active account, we will send reset instructions.</p>
                <form id="recoverForm" class="mt-4 pt-3" novalidate>
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($passwordResetCsrf, ENT_QUOTES, 'UTF-8') ?>" />
                  <div class="form-floating mb-4">
                    <input id="recover-email" name="email" class="form-control form-input-bg" type="email" required autocomplete="email" placeholder="Email address" />
                    <label for="recover-email">Email</label>
                  </div>
                  <div id="recoverMessage" class="alert d-none" role="status"></div>
                  <div class="d-flex align-items-stretch button-group gap-2">
                    <button type="submit" class="btn btn-info">Send reset link</button>
                    <a href="javascript:void(0)" id="to-login" class="btn btn-light-secondary text-secondary font-weight-medium">Cancel</a>
                  </div>
                </form>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- JS -->
    <script src="<?= $baseUrl ?>/assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      $(".preloader").fadeOut();

      // Toggle forms
      $("#to-recover").on("click", function () {
        $("#loginform").hide();
        $("#recoverform").fadeIn();
      });

      $("#to-login").on("click", function () {
        $("#loginform").fadeIn();
        $("#recoverform").hide();
      });

      $("#to-register").on("click", function () {
        $("#loginform").hide();
        $("#registerform").fadeIn();
      });

      $("#to-login2").on("click", function () {
        $("#loginform").fadeIn();
        $("#registerform").hide();
      });

      // Bootstrap validation
      (function () {
        "use strict";
        var forms = document.querySelectorAll(".needs-validation");
        Array.prototype.slice.call(forms).forEach(function (form) {
          form.addEventListener(
            "submit",
            function (event) {
              if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
              }
              form.classList.add("was-validated");
            },
            false
          );
        });
      })();
    </script>
	<script>
$(document).ready(function () {
  const baseUrl = <?= json_encode($baseUrl) ?>;

  const validateLoginFields = function () {
    const emailField = document.getElementById("tb-email");
    const passwordField = document.getElementById("text-password");
    const emailValue = emailField.value.trim();
    const emailError = document.getElementById("login-email-error");
    const passwordError = document.getElementById("login-password-error");
    let firstInvalid = null;

    if (!emailValue) {
      emailError.textContent = "Email is required";
      emailField.classList.add("is-invalid");
      emailField.setAttribute("aria-invalid", "true");
      firstInvalid = emailField;
    } else if (emailField.validity.typeMismatch) {
      emailError.textContent = "Enter a valid email address";
      emailField.classList.add("is-invalid");
      emailField.setAttribute("aria-invalid", "true");
      firstInvalid = emailField;
    } else {
      emailField.classList.remove("is-invalid");
      emailField.setAttribute("aria-invalid", "false");
    }

    if (!passwordField.value) {
      passwordError.textContent = "Password is required";
      passwordField.classList.add("is-invalid");
      passwordField.setAttribute("aria-invalid", "true");
      firstInvalid ||= passwordField;
    } else {
      passwordField.classList.remove("is-invalid");
      passwordField.setAttribute("aria-invalid", "false");
    }

    return firstInvalid;
  };

  $("#tb-email, #text-password").on("input", function () {
    const shouldRevalidate = this.classList.contains("is-invalid")
      || $("#loginForm").hasClass("was-validated");
    $("#loginForm").removeClass("was-validated");
    if (shouldRevalidate) {
      validateLoginFields();
    }
    $("#loginMessage").addClass("d-none").text("");
  });

  $("#recoverForm").on("submit", function (e) {
    e.preventDefault();
    if (!this.checkValidity()) {
      this.reportValidity();
      return;
    }

    const $form = $(this);
    const $button = $form.find("button[type=submit]");
    $("#recoverMessage").addClass("d-none").removeClass("alert-danger alert-success");

    $.ajax({
      url: baseUrl + "/html/material/config/request-password-reset.php",
      type: "POST",
      dataType: "json",
      data: $form.serialize(),
      beforeSend: function () {
        $button.prop("disabled", true).text("Sending...");
      },
      success: function (response) {
        const successful = response.status === "success";
        $("#recoverMessage")
          .removeClass("d-none alert-danger alert-success")
          .addClass(successful ? "alert-success" : "alert-danger")
          .text(response.message || "Unable to process the request. Reload the page and try again.");
        if (successful) {
          $form.find("input[type=email]").val("");
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "Unable to process the request. Reload the page and try again.";
        $("#recoverMessage").removeClass("d-none alert-success").addClass("alert-danger").text(message);
      },
      complete: function () {
        $button.prop("disabled", false).text("Send reset link");
      }
    });
  });

  $("#loginForm").on("submit", function (e) {
    e.preventDefault();

    const firstInvalid = validateLoginFields();
    if (firstInvalid) {
      e.stopPropagation();
      firstInvalid.focus();
      return;
    }

    const turnstileReady = <?= TURNSTILE_READY ? 'true' : 'false' ?>;
    const turnstileRequired = <?= TURNSTILE_ENABLED ? 'true' : 'false' ?>;
    if (turnstileRequired && !turnstileReady) {
      $("#loginMessage")
        .removeClass("d-none alert-success")
        .addClass("alert alert-danger")
        .text("Security verification is temporarily unavailable. Please try again later.");
      return;
    }

    const email = $("#tb-email").val().trim();
    const password = $("#text-password").val();
    const turnstileToken = $("[name='cf-turnstile-response']").val();

    const resetTurnstile = function () {
      window.nuruTurnstileUnavailable?.();
      if ($(".cf-turnstile").length && typeof turnstile !== "undefined") {
        try { turnstile.reset(); } catch (error) { /* widget may have expired during navigation */ }
      }
    };

    $("#loginMessage")
      .removeClass("alert-danger alert-success")
      .addClass("d-none");

    if ($(".cf-turnstile").length && !turnstileToken) {
      $("#loginMessage")
        .removeClass("d-none alert-success")
        .addClass("alert alert-danger")
        .text("Please complete the CAPTCHA challenge.");
      return;
    }

    $.ajax({
      // Self-submit: this page (whether reached via /login or the legacy
      // html/material/authentication-login.php URL) handles its own POST.
      url: window.location.pathname + window.location.search,
      type: "POST",
      dataType: "json",
      data: {
        email: email,
        password: password,
        "cf-turnstile-response": turnstileToken
      },
      beforeSend: function () {
        $("#loginForm button[type=submit]").prop("disabled", true).text("Signing in...");
      },
      success: function (response) {

        if (response.status === "success") {
          $("#loginMessage")
            .removeClass("d-none alert-danger")
            .addClass("alert alert-success")
            .text("Login successful. Redirecting...");

          setTimeout(function () {
            window.location.href = response.redirect;
          }, 1200);

        } else {
          $("#loginMessage")
            .removeClass("d-none alert-success")
            .addClass("alert alert-danger")
            .text(response.message);
          // Turnstile tokens are single-use - a retry needs a fresh one.
          resetTurnstile();
        }
      },
      error: function (xhr) {
        resetTurnstile();
        const message = xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "An unexpected error occurred. Please try again.";
        $("#loginMessage")
          .removeClass("d-none alert-success")
          .addClass("alert alert-danger")
          .text(message);
      },
      complete: function () {
        const hasVerification = !turnstileRequired || Boolean($("[name='cf-turnstile-response']").val());
        $("#loginForm button[type=submit]")
          .prop("disabled", !turnstileReady || !hasVerification)
          .attr("aria-disabled", (!turnstileReady || !hasVerification) ? "true" : null)
          .text("Sign in");
      }
    });

  });

});
</script>

  </body>
</html>

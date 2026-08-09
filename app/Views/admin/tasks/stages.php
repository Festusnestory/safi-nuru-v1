<?php
/** @var string $checklistConfigCsrf */
/** @var string $baseUrl */
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="keywords"
      content="Nuru real estate administration, property management, buyers, sellers, agents, tasks, and reporting"
    />
    <meta
      name="description"
      content="Material Pro is powerful and clean admin dashboard template"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Nuru Real Estate - Checklist Stages</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="<?= $baseUrl ?>/assets/images/favicon.png"
    />
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet" />
    <!-- This Page CSS -->
    <link
      rel="stylesheet"
      type="text/css"
      href="<?= $baseUrl ?>/assets/extra-libs/prism/prism.css"
    />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <![endif]-->
  </head>

  <body>
    <!-- -------------------------------------------------------------- -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- -------------------------------------------------------------- -->
    <div class="preloader">
      <svg
        class="tea lds-ripple"
        width="37"
        height="48"
        viewbox="0 0 37 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
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
        <path
          id="steamL"
          d="M17 1C17 1 17 4.5 14 6.5C11 8.5 11 12 11 12"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke="#1e88e5"
        ></path>
        <path
          id="steamR"
          d="M21 6C21 6 21 8.22727 19 9.5C17 10.7727 17 13 17 13"
          stroke="#1e88e5"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        ></path>
      </svg>
    </div>
    <!-- -------------------------------------------------------------- -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- -------------------------------------------------------------- -->
    <div id="main-wrapper">
      <!-- -------------------------------------------------------------- -->
      <!-- Topbar header - style you can find in pages.scss -->
      <!-- -------------------------------------------------------------- -->
  <?php require NURU_MATERIAL . '/top-bar.php'; ?>
      <!-- -------------------------------------------------------------- -->
      <!-- End Topbar header -->
      <!-- -------------------------------------------------------------- -->
      <!-- -------------------------------------------------------------- -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- -------------------------------------------------------------- -->
      <?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
      <!-- -------------------------------------------------------------- -->
      <!-- End Left Sidebar - style you can find in sidebar.scss  -->
      <!-- -------------------------------------------------------------- -->
      <!-- -------------------------------------------------------------- -->
      <!-- Page wrapper  -->
      <!-- -------------------------------------------------------------- -->
      <div class="page-wrapper">
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <!-- -------------------------------------------------------------- -->
        <!-- Container fluid  -->
        <!-- -------------------------------------------------------------- -->
        <div class="container-fluid">
          <!-- -------------------------------------------------------------- -->
          <!-- Start Page Content -->
          <!-- -------------------------------------------------------------- -->
			<div class="container py-5">

			  <!-- ========================= -->
			  <!-- ADMIN: MANAGE STAGES -->
			  <!-- ========================= -->
			  <div class="row justify-content-center mb-5">
			    <div class="col-12 col-md-10 col-lg-6">
			      <div class="card">
			        <div class="card-body">

			          <h4 class="card-title">Manage Checklist Stages</h4>
			          <h5 class="card-subtitle mb-3 pb-3 border-bottom">
			            Define workflow steps
			          </h5>

			          <form id="stageForm">
			            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($checklistConfigCsrf, ENT_QUOTES, 'UTF-8') ?>">

			            <!-- Stage Name -->
			            <div class="form-floating mb-3">
			              <input
			                type="text"
			                id="stage-name"
			                name="stage_name"
			                class="form-control border border-success"
			                placeholder="Stage Name"
			                required
			              />
			              <label for="stage-name">
			                <i data-feather="layers"
			                   class="feather-sm text-success me-2"></i>
			                <span class="border-start border-success ps-3">
			                  Stage Name
			                </span>
			              </label>
			            </div>

			            <!-- Stage Order -->
			            <div class="form-floating mb-3">
			              <input
			                type="number"
			                id="stage-order"
			                name="stage_order"
			                class="form-control border border-success"
			                placeholder="Stage Order"
			                min="1"
			                required
			              />
			              <label for="stage-order">
			                <i data-feather="list"
			                   class="feather-sm text-success me-2"></i>
			                <span class="border-start border-success ps-3">
			                  Stage Order
			                </span>
			              </label>
			            </div>

			            <!-- Description -->
			            <div class="form-floating mb-3">
			              <textarea
			                id="stage-description"
			                name="description"
			                class="form-control border border-success"
			                placeholder="Description"
			                style="height: 120px"
			              ></textarea>
			              <label for="stage-description">
			                <i data-feather="file-text"
			                   class="feather-sm text-success me-2"></i>
			                <span class="border-start border-success ps-3">
			                  Description (optional)
			                </span>
			              </label>
			            </div>

			            <!-- Active -->
			            <div class="form-check mb-3">
			              <input
			                type="checkbox"
			                class="form-check-input"
			                id="stage_active"
			                name="is_active"
			                checked
			              />
			              <label class="form-check-label" for="stage_active">
			                Stage is active
			              </label>
			            </div>

			            <!-- Submit -->
			            <div class="d-flex justify-content-end">
			              <button
			                type="submit"
			                class="btn btn-success rounded-pill px-4"
			              >
			                <i data-feather="save" class="feather-sm me-2"></i>
			                Save Stage
			              </button>
			            </div>

			            <div id="stageMessage" role="status" aria-live="polite"></div>

			          </form>
			        </div>
			      </div>
			    </div>
			  </div>


			</div>

          <!-- -------------------------------------------------------------- -->
          <!-- End PAge Content -->
          <!-- -------------------------------------------------------------- -->
        </div>
        <!-- -------------------------------------------------------------- -->
        <!-- End Container fluid  -->
        <!-- -------------------------------------------------------------- -->
        <!-- -------------------------------------------------------------- -->
        <!-- footer -->
        <!-- -------------------------------------------------------------- -->

        <!-- -------------------------------------------------------------- -->
        <!-- End footer -->
        <!-- -------------------------------------------------------------- -->
      </div>
      <!-- -------------------------------------------------------------- -->
      <!-- End Page wrapper  -->
      <!-- -------------------------------------------------------------- -->
    </div>
    <!-- -------------------------------------------------------------- -->
    <!-- End Wrapper -->
    <!-- -------------------------------------------------------------- -->
    <!-- -------------------------------------------------------------- -->
    <!-- customizer Panel -->
    <!-- -------------------------------------------------------------- -->

    <div class="chat-windows"></div>
    <!-- -------------------------------------------------------------- -->
    <!-- All Jquery -->
    <!-- -------------------------------------------------------------- -->
    <script src="<?= $baseUrl ?>/assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- apps -->
    <script src="<?= $baseUrl ?>/dist/js/app.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app.init.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app-style-switcher.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="<?= $baseUrl ?>/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="<?= $baseUrl ?>/dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="<?= $baseUrl ?>/dist/js/sidebarmenu.js?v=20260720"></script>
    <!--Custom JavaScript -->
    <script src="<?= $baseUrl ?>/dist/js/feather.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/custom.min.js"></script>
    <!-- This Page JS -->
    <script src="<?= $baseUrl ?>/assets/extra-libs/prism/prism.js"></script>

	<script>
document.addEventListener('DOMContentLoaded', () => {

  const form = document.getElementById('stageForm');
  if (!form) return;

  const submitBtn = form.querySelector('button[type="submit"]');
  const message = document.getElementById('stageMessage');
  let isSubmitting = false;

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    if (isSubmitting) return; // 🔒 prevent double submit
    isSubmitting = true;

    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <span class="spinner-border spinner-border-sm me-2"></span>
      Saving...
    `;

    fetch(<?= json_encode(\portalPath('config/save_stage.php')) ?>, {
      method: 'POST',
      body: new FormData(form)
    })
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        throw new Error(data.message || 'Failed');
      }

      message.className = 'alert alert-success mt-3';
      message.textContent = data.message || 'Stage saved successfully.';
      form.reset();

    })
    .catch(err => {
      message.className = 'alert alert-danger mt-3';
      message.textContent = err.message || 'Unable to save the stage.';
    })
    .finally(() => {
      isSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.innerHTML = `
        <i data-feather="save" class="feather-sm me-2"></i>
        Save Stage
      `;
      feather.replace();
    });
  });

});
</script>

  </body>
</html>

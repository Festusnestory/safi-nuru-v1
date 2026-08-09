<?php
/** @var array $agent */
/** @var string $csrfToken */
/** @var string $baseUrl */

/**
 * Helper for documents
 */
function documentLink($url, $label)
{
    if (!empty($url)) {
        return '<a href="' . htmlspecialchars($url) . '" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">
                    <i class="fa fa-file-pdf me-1"></i>' . $label . '
                </a>';
    }
    return '<span class="text-muted me-3">' . $label . ': Not uploaded</span>';
}
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
      content="Admin Pro is powerful and clean admin dashboard template"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Profile page</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="<?= $baseUrl ?>/assets/images/favicon.png"
    />
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet" />
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
      <?php
			if (\App\Core\Auth::isFullAccess()) {
				require NURU_MATERIAL . '/left-sidebar.php';
			} else {
				require NURU_MATERIAL . '/agent_nemu.php';
			}
			?>
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
        <div class="row page-titles">
          <div class="col-md-5 col-12 align-self-center">
            <h3 class="text-themecolor mb-0">Profile Page</h3>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active">Profile Page</li>
            </ol>
          </div>
        </div>
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
          <!-- Row -->
          <div class="row">
            <!-- Column -->
            <div class="col-lg-4 col-xlg-3 col-md-5">
              <div class="card">
                <div class="card-body">
                  <center class="mt-4">
                    <img
                      src="<?= $baseUrl ?>/assets/images/users/5.jpg"
                      class="rounded-circle"
                      width="150"
                    />
                    <h4 class="card-title mt-2"><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['surname']) ?></h4>
                    <h6 class="card-subtitle"><?= htmlspecialchars($agent['company_name']) ?></h6>
                    <div class="row text-center justify-content-md-center">

                    </div>
                  </center>
                </div>
                <div>
                  <hr />
                </div>
                <div class="card-body">
                  <small class="text-muted">Email address </small>
                  <h6><?= htmlspecialchars($agent['email']) ?></h6>
                  <small class="text-muted pt-4 db">Phone</small>
                  <h6><?= htmlspecialchars($agent['mobile_number']) ?></h6>
                  <small class="text-muted pt-4 db">Application No.</small>
                  <h6><?= htmlspecialchars($agent['application_id']) ?></h6>


                </div>
              </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-8 col-xlg-9 col-md-7">
              <div class="card">
                <!-- Tabs -->
                <ul
                  class="nav nav-pills custom-pills"
                  id="pills-tab"
                  role="tablist"
                >

                  <li class="nav-item">
                    <a
                      class="nav-link active"
                      id="pills-profile-tab"
                      data-bs-toggle="pill"
                      href="#current-month"
                      role="tab"
                      aria-controls="current-month"
                      aria-selected="true"
                      >Profile</a
                    >
                  </li>
                </ul>
                <!-- Tabs -->
                <div class="tab-content" id="pills-tabContent">
                  <div
                    class="tab-pane fade show active"
                    id="current-month"
                    role="tabpanel"
                    aria-labelledby="pills-timeline-tab"
                  >
                    <div class="card-body">
                      <div class="profiletimeline mt-0">
                        <div class="sl-item d-flex align-items-start">
                          <div class="sl-left">
                            <img
                              src="<?= $baseUrl ?>/assets/images/users/1.jpg"
                              alt="user"
                              class="rounded-circle"
                            />
                          </div>
                          <div class="sl-right">
                            <div>
                              <a href="javascript:void(0)" class="link"
                                ><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['surname']) ?> | </a
                              >
                              <span class="sl-date"><?= htmlspecialchars($agent['company_name']) ?></span>
                              <div class="row">
								  <hr />
                                <div class="col-lg-3 col-md-6 mb-3">
                                  <?= documentLink($agent['id_document_url'], 'ID Document') ?>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                  <?= documentLink($agent['proof_residence_url'], 'Proof of Residence') ?>
                                </div>
								  <hr />
                                <div class="col-lg-3 col-md-6 mb-3">
                                   <?= documentLink($agent['agency_ffc_url'], 'Agency FFC') ?>
                                </div><br>
                                <div class="col-lg-3 col-md-6 mb-3">
                                  <?= documentLink($agent['agent_neab_url'], 'NEAB Certificate') ?>
                                </div>
								<div class="col-lg-3 col-md-6 mb-3">
                                  <?= documentLink($agent['agent_ffc_url'], 'Agent FFC') ?>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <hr />
                        <div class="sl-item d-flex align-items-start">
                          <div class="sl-left">
                            <img
                              src="<?= $baseUrl ?>/assets/images/users/2.jpg"
                              alt="user"
                              class="rounded-circle"
                            />
                          </div>
                          <div class="sl-right">
							  <div>
								<div class="row">
								  <!-- Gross Income -->
								  <div class="col-12 col-md-6 mb-3">
									<strong>Gross Income</strong>
									<p class="text-muted mb-0">
									  <?= htmlspecialchars($agent['gross_income']) ?>
									</p>
								  </div>

								  <!-- Net Pay -->
								  <div class="col-12 col-md-6 mb-3">
									<strong>Net Pay</strong>
									<p class="text-muted mb-0">
									  <?= htmlspecialchars($agent['net_pay']) ?>
									</p>
								  </div>
								</div>

								<!-- Status Row -->
								<div class="row mt-3 align-items-center">
								  <div class="col-md-4 col-12 mb-2 mb-md-0">
									<strong>Status</strong><br>

									<span class="badge
									  <?= $agent['status'] === 'active' ? 'bg-success' : 'bg-warning text-dark' ?>">
									  <?= ucfirst(htmlspecialchars($agent['status'])) ?>
									</span>
								  </div>

								  <?php if (isFullAccess()): ?>
								  <div class="col-md-8 col-12">
									<form id="agentStatusForm" class="d-flex flex-wrap gap-2">
									  <input type="hidden" name="agent_id" value="<?= (int)$agent['id'] ?>">
									  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

									  <select name="status" class="form-select w-auto" required style="position: absolute; margin-top: -14px;">
										<option value="">Change status</option>
										<option value="pending">Pending</option>
										<option value="approved">Approved</option>
										<option value="active">Active</option>
										<option value="suspended">Suspended</option>
										<option value="rejected">Rejected</option>
									  </select>

									  <button type="submit" class="btn btn-sm btn-primary" style="margin-top: -12px; margin-left: 168px;">
										Update
									  </button>
									</form>
									<div id="agentStatusMessage" role="status" aria-live="polite"></div>
								  </div>
								  <?php endif; ?>
								</div>

							  </div>
							</div>

                        </div>
                        <hr />



                      </div>
                    </div>
                  </div>
                  <div
                    class="tab-pane fade"
                    id="last-month"
                    role="tabpanel"
                    aria-labelledby="pills-profile-tab"
                  >


                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-3 col-xs-6 b-r">
                          <strong>Date of birth</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($agent['date_of_birth']) ?></p>
                        </div>
                        <div class="col-md-3 col-xs-6 b-r">
                          <strong><?= htmlspecialchars($agent['id_type']) ?></strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($agent['id_number']) ?></p>
                        </div>
                        <div class="col-md-3 col-xs-6 b-r">
                          <strong>Gender</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($agent['gender']) ?></p>
                        </div>
                        <div class="col-md-3 col-xs-6">
                          <strong>Location</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($agent['nationality']) ?></p>
                        </div>
                      </div>
                      <hr />
                <?= documentLink($agent['id_document_url'], 'ID Document') ?>
                <?= documentLink($agent['proof_residence_url'], 'Proof of Residence') ?>
                <?= documentLink($agent['agency_ffc_url'], 'Agency FFC') ?>
                <?= documentLink($agent['agent_neab_url'], 'NEAB Certificate') ?>
                <?= documentLink($agent['agent_ffc_url'], 'Agent FFC') ?>

                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Column -->
          </div>
          <!-- Row -->
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
        <footer class="footer text-center">
          All Rights Reserved by NURU.
        </footer>
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
	<script>
	document.getElementById('agentStatusForm')?.addEventListener('submit', function(e) {
	  e.preventDefault();

	  const form = this;
	  const btn = form.querySelector('button');
	  btn.disabled = true;

	  const payload = Object.fromEntries(new FormData(form).entries());
	  fetch(<?= json_encode($baseUrl) ?> + '/admin/update-agent-status', {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(payload)
	  })
	  .then(async res => {
		const data = await res.json();
		if (!res.ok || !data.success) throw new Error(data.message || 'Unable to update status.');
		return data;
	  })
	  .then(() => {
		location.reload();
	  })
	  .catch((error) => {
		const message = document.getElementById('agentStatusMessage');
		message.textContent = error.message;
		message.className = 'alert alert-danger mt-2';
	  })
	  .finally(() => {
		btn.disabled = false;
	  });
	});
	</script>

  </body>
</html>

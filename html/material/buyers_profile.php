<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("location: authentication-login.php");
    exit;
}

// SECURITY FIX: this page only checked for *any* logged-in session, not
// role. The "id" param is genuinely AES-256 encrypted here (unlike the
// sibling seller profile page), which prevents guessing/enumeration, but
// not a buyer/seller/agent_consultant portal account viewing another
// person's full PII if they ever obtained a valid link to it.
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/config/id_tokens.php';
requireRole(['admin', 'manager', 'agent_coordinator', 'agent_consultant']);

require './config/pdo.php';

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
      href="../../assets/images/favicon.png"
    />
    <!-- Custom CSS -->
    <link href="../../dist/css/style.min.css" rel="stylesheet" />
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
     <?php include("top-bar.php"); ?>
      <!-- ============================================================== -->
      <!-- End Topbar header -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
	  
		<?php
		require_once __DIR__ . '/config/role_helpers.php'; if (isFullAccess()) {
			include("left-sidebar.php");
		} else {
			include("agent_nemu.php");
		}
		?>
      <!-- -------------------------------------------------------------- -->
      <!-- End Left Sidebar - style you can find in sidebar.scss  -->
      <!-- -------------------------------------------------------------- -->
      <!-- -------------------------------------------------------------- -->
      <!-- Page wrapper  -->
	  		<?php
		
		
		

/* ===================== INPUT VALIDATION ===================== */
if (empty($_GET['id'])) {
    die('Buyer reference missing');
}

$buyerId = portalDecodeId($_GET['id'] ?? null);

if ($buyerId === null) {
    die('Invalid or corrupted buyer reference');
}

/* ===================== FETCH BUYER ===================== */
$sql = "
    SELECT *
    FROM vw_buyers_profile
    WHERE buyer_id = :buyer_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':buyer_id' => $buyerId]);

$buyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buyer) {
    die('Buyer not found');
}

// agent_coordinator is scoped to their own assigned buyers only - otherwise
// any agent could view every buyer's full PII, not just the ones actually
// assigned to them (same principle as checklist.php). vw_buyers_profile
// doesn't expose assigned_agent_id, so check it directly against the table.
if (currentRole() === 'agent_coordinator') {
    $myAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $ownerCheck = $pdo->prepare("SELECT 1 FROM buyers b WHERE b.id = ? AND (
        b.assigned_agent_id = ? OR b.loaded_by = ? OR EXISTS (
            SELECT 1 FROM agent_task_allocations ata
            WHERE ata.allocation_type = 'buyer' AND ata.entity_id = b.id AND ata.agent_id = ?
        )
    )");
    $ownerCheck->execute([$buyerId, $myAgentId ?? 0, (int)$_SESSION['user_id'], $myAgentId ?? 0]);
    if (!$ownerCheck->fetchColumn()) {
        http_response_code(403);
        die('You are not assigned to this buyer');
    }
}
if (currentRole() === 'agent_consultant') {
    $ownerCheck = $pdo->prepare("SELECT 1 FROM buyers WHERE id = ? AND source = 'quick_consult' AND loaded_by = ?");
    $ownerCheck->execute([$buyerId, (int)$_SESSION['user_id']]);
    if (!$ownerCheck->fetchColumn()) {
        http_response_code(403);
        die('You do not have access to this consultation');
    }
}
?>
      <!-- -------------------------------------------------------------- -->
      <div class="page-wrapper">
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
          <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active"><?= htmlspecialchars($buyer['full_name']) ?> Profile</li>
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
				<div class="card shadow-sm">
					<div class="card-body text-center">

						<img 
							src="../../assets/images/users/5.jpg"
							class="rounded-circle mb-3"
							width="150"
							alt="Buyer Profile Image"
						/>

						<div class="text-start" style="text-align: justify;">

							<p class="mb-2">
								<strong>Full Name:</strong><br>
								<?= htmlspecialchars($buyer['full_name']) ?>
							</p>

							<p class="mb-2">
								<strong>Application No:</strong><br>
								<?= htmlspecialchars($buyer['application_number']) ?>
							</p>

							<p class="mb-2">
								<strong>Marital Status:</strong><br>
								<?= htmlspecialchars($buyer['marital_status']) ?>
							</p>

							<p class="mb-2">
								<strong>Date of Birth:</strong><br>
								<?= htmlspecialchars($buyer['date_of_birth']) ?>
							</p>

							<p class="mb-3">
								<strong>
									<?= htmlspecialchars(ucwords(str_replace('_', ' ', $buyer['id_type']))) ?>:
								</strong><br>
								<?= htmlspecialchars($buyer['id_number']) ?>
							</p>


						</div>
					</div>

					<hr class="my-0">

					<div class="card-body" style="text-align: justify;">

						<p class="mb-2">
							<small class="text-muted">Email Address</small><br>
							<?= htmlspecialchars($buyer['email']) ?>
						</p>

						<p class="mb-2">
							<small class="text-muted">Phone</small><br>
							<?= htmlspecialchars($buyer['phone']) ?>
						</p>

						<p class="mb-0">
							<small class="text-muted">Address</small><br>
							<?= htmlspecialchars($buyer['address'] ?? '-') ?>
						</p>

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
                    aria-labelledby="pills-profile-tab"
                  >
                    <div class="card-body">
                      <div class="profiletimeline mt-0">
                        <div class="sl-item d-flex align-items-start">
                          <div class="sl-left">
                            <img
                              src="../../assets/images/users/1.jpg"
                              alt="user"
                              class="rounded-circle"
                            />
                          </div>
                          <div class="sl-right">
                            <div>
                              <a href="javascript:void(0)" class="link"
                                >Employment & Loan Details</a
                              >
                              <span class="sl-date"></span>
                              <p>
                                <a href="javascript:void(0)"></a
                                >
                              </p>
                              <div class="row">
								<table class="table table-striped">
									<tr>
										<th>Employer</th>
										<th>Position</th>
										<th>Monthly Income</th>
										<th>Property Value</th>
										<th>Loan Amount</th>
									</tr>
									<tr>
										<td><?= htmlspecialchars($buyer['employer_name'] ?? '-') ?></td>
										<td><?= htmlspecialchars($buyer['position'] ?? '-') ?></td>
										<td>N$ <?= number_format($buyer['monthly_income'], 2) ?></td>
										<td>N$ <?= number_format($buyer['property_value'], 2) ?></td>
										<td>N$ <?= number_format($buyer['loan_amount'], 2) ?></td>
									</tr>
								</table>

                              </div>
                              <div class="like-comm">
                             
                              </div>
                            </div>
                          </div>
                        </div>
                        <hr />
                        <div class="sl-item d-flex align-items-start">
                          <div class="sl-left">
                            <img
                              src="../../assets/images/users/2.jpg"
                              alt="user"
                              class="rounded-circle"
                            />
                          </div>
                          <div class="sl-right">
                            <div>
                              <a href="javascript:void(0)" class="link"
                                >Next of Kin</a
                              >
                              <span class="sl-date"></span>
                              <div class="mt-3 row">
                                
                                <div class="col-md-9 col-xs-12">
                                 <?php if ($buyer['nok_full_name']) : ?>
								<p>
									<strong>Name:</strong> <?= htmlspecialchars($buyer['nok_full_name']) ?><br>
									<strong>Relationship:</strong> <?= htmlspecialchars($buyer['nok_relationship']) ?><br>
									<strong>Phone:</strong> <?= htmlspecialchars($buyer['nok_phone']) ?>
								</p>
							<?php else : ?>
								<p class="text-muted">No next of kin recorded</p>
							<?php endif; ?>
                                </div>
                              </div>
                              <div class="like-comm mt-3">
                               
                              </div>
                            </div>
                          </div>
                        </div>
                        <hr />
                        <div class="sl-item d-flex align-items-start">
                          <div class="sl-left">
                            <img
                              src="../../assets/images/users/3.jpg"
                              alt="user"
                              class="rounded-circle"
                            />
                          </div>
                          <div class="sl-right">
                            <div>
                              <a href="javascript:void(0)" class="link"
                                >Spouse Details</a
                              >
                              <span class="sl-date"></span>
                             <?php if ($buyer['spouse_name']) : ?>
								<p>
									<strong>Name:</strong> <?= htmlspecialchars($buyer['spouse_name']) ?><br>
									<strong>ID:</strong> <?= htmlspecialchars($buyer['id_passport']) ?><br>
									<strong>DOB:</strong> <?= htmlspecialchars($buyer['spouse_dob']) ?>
								</p>
							<?php else : ?>
								<p class="text-muted">No spouse details</p>
							<?php endif; ?>
                            </div>
                            <div class="like-comm mt-3">
                            
                            </div>
                          </div>
                        </div>
                        <hr />
                        <div class="sl-item d-flex align-items-start">
                          <div class="sl-left">
                            <img
                              src="../../assets/images/users/4.jpg"
                              alt="user"
                              class="rounded-circle"
                            />
                          </div>
						  <?php
						  
							/* ===================== FETCH BUYER DOCUMENTS ===================== */
							$docStmt = $pdo->prepare("
								SELECT doc_type, file_path
								FROM buyer_documents
								WHERE buyer_id = :buyer_id
								ORDER BY uploaded_at DESC
							");
							$docStmt->execute([':buyer_id' => $buyerId]);

							$documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

						  
						  
						  ?>
						  
						  
												  
						<div class="sl-right">
							<div>
								<a href="javascript:void(0)" class="link">Documents</a>
								<span class="sl-date"></span>

								<?php if (!empty($documents)) : ?>
									<div class="row mt-3">
										<?php foreach ($documents as $doc) : ?>
											<div class="col-md-4 mb-3">
											<a href="view_document.php?file=<?= urlencode($doc['file_path']) ?>"
												   target="_blank"
												   class="btn btn-sm btn-primary w-100">
													View <?= htmlspecialchars(ucwords(str_replace('_', ' ', strtolower($doc['doc_type'])))) ?>
												</a>
											</div>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<p class="text-muted mt-2">No documents uploaded</p>
								<?php endif; ?>
							</div>
						</div>


                        </div>
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
                          <strong>Full Name</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($buyer['full_name'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-3 col-xs-6 b-r">
                          <strong>Mobile</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($buyer['phone'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-3 col-xs-6 b-r">
                          <strong>Email</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($buyer['email'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-3 col-xs-6">
                          <strong>Location</strong>
                          <br />
                          <p class="text-muted"><?= htmlspecialchars($buyer['address'] ?? '-') ?></p>
                        </div>
                      </div>
                      <hr />
                      <p class="mt-4">
					  <h3>Employment & Loan Details</h3>
						<table class="table table-striped">
							<thead>
								<tr>
									<th>Employer</th>
									<th>Position</th>
									<th>Monthly Income</th>
									<th>Property Value</th>
									<th>Loan Amount</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?= htmlspecialchars($buyer['employer_name'] ?? '-') ?></td>
									<td><?= htmlspecialchars($buyer['position'] ?? '-') ?></td>

									<td>
										<?= isset($buyer['monthly_income']) && $buyer['monthly_income'] !== ''
											? 'N$ ' . number_format((float)$buyer['monthly_income'], 2)
											: '-' ?>
									</td>

									<td>
										<?= isset($buyer['property_value']) && $buyer['property_value'] !== ''
											? 'N$ ' . number_format((float)$buyer['property_value'], 2)
											: '-' ?>
									</td>

									<td>
										<?= isset($buyer['loan_amount']) && $buyer['loan_amount'] !== ''
											? 'N$ ' . number_format((float)$buyer['loan_amount'], 2)
											: '-' ?>
									</td>
								</tr>
							</tbody>
						</table>

                      </p>
                      <p>
					  <h3>Next of Kin</h3>
							<?php if ($buyer['nok_full_name']) : ?>
								<p>
									<strong>Name:</strong> <?= htmlspecialchars($buyer['nok_full_name']) ?? '-' ?><br>
									<strong>Relationship:</strong> <?= htmlspecialchars($buyer['nok_relationship']) ?? '-' ?><br>
									<strong>Phone:</strong> <?= htmlspecialchars($buyer['nok_phone']) ?? '-' ?>
								</p>
							<?php else : ?>
								<p class="text-muted">No next of kin recorded</p>
							<?php endif; ?>
                      </p>
					   <hr />
                      <p>
					  <h3>Spouse Details</h3>
						<?php if ($buyer['spouse_name']) : ?>
								<p>
									<strong>Name:</strong> <?= htmlspecialchars($buyer['spouse_name']) ?><br>
									<strong>ID:</strong> <?= htmlspecialchars($buyer['id_passport']) ?><br>
									<strong>DOB:</strong> <?= htmlspecialchars($buyer['spouse_dob']) ?>
								</p>
							<?php else : ?>
								<p class="text-muted">No spouse details</p>
							<?php endif; ?>
                      </p> 
					   <hr />
					  <p>
					  <h3>Documents</h3>
						<?php if (!empty($documents)) : ?>
									<div class="row mt-3">
										<?php foreach ($documents as $doc) : ?>
											<div class="col-md-4 mb-3">
											<a href="view_document.php?file=<?= urlencode($doc['file_path']) ?>"
												   target="_blank"
												   class="btn btn-sm btn-primary w-100">
													View <?= htmlspecialchars(ucwords(str_replace('_', ' ', strtolower($doc['doc_type'])))) ?>
												</a>
											</div>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<p class="text-muted mt-2">No documents uploaded</p>
								<?php endif; ?>
                      </p>
                      <hr />
                      
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
    <!-- Template demo panels intentionally omitted from business profiles. -->
    <!-- -------------------------------------------------------------- -->
    <!-- All Jquery -->
    <!-- -------------------------------------------------------------- -->
    <script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- apps -->
    <script src="../../dist/js/app.min.js"></script>
    <script src="../../dist/js/app.init.js"></script>
    <script src="../../dist/js/app-style-switcher.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="../../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../../assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="../../dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../../dist/js/sidebarmenu.js?v=20260720"></script>
    <!--Custom JavaScript -->
    <script src="../../dist/js/feather.min.js"></script>
    <script src="../../dist/js/custom.min.js"></script>
  </body>
</html>

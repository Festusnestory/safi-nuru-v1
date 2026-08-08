<?php
session_start();
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
}
 include("./config/pdo.php");
 require_once __DIR__ . '/config/role_helpers.php';
 require_once __DIR__ . '/config/property_lifecycle.php';
 requireRole(['admin','manager','agent_coordinator']);
 $matchingCsrf = csrfToken('property_matching');
 expireOverdueProperties($pdo);
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
    <title>NURU- Admin</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="../../assets/images/favicon.png"
    />
    <!-- Custom CSS -->
    <link href="../../assets/libs/tablesaw/dist/tablesaw.css" rel="stylesheet" />
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
      <!-- -------------------------------------------------------------- -->
      <!-- End Topbar header -->
      <!-- -------------------------------------------------------------- -->
      <!-- -------------------------------------------------------------- -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- -------------------------------------------------------------- -->
<?php include(currentRole() === 'agent_coordinator' ? "agent_nemu.php" : "left-sidebar.php"); ?>
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
            <h3 class="text-themecolor mb-0"></h3>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active">Matched Sellers</li>
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
          <div class="row">
            <div class="col-12">
              <!-- Column -->
              
              <!-- Column -->
              
              <!-- Column -->
              
              <div class="card">
                <div class="border-bottom title-part-padding">
                  <h4 class="card-title mb-0">View And Match</h4>
                </div>
                <div class="card-body">
					<?php

					// -------- GET URL PARAMETERS --------
					$buyerID = filter_var($_GET['buyer'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
					$sellerIDs = $_GET['sellers'] ?? '';

					if ($buyerID === false || empty($sellerIDs)) {
						die("No match data received");
					}

					// Decode + split sellers
					$sellerIDs = urldecode($sellerIDs);
					$sellerArray = explode(',', $sellerIDs);
					$sellerArray = array_values(array_unique(array_filter(array_map('intval', $sellerArray), static fn(int $id): bool => $id > 0)));
					if (!$sellerArray || count($sellerArray) > 50) {
						die('Invalid property selection');
					}

					// Fetch buyer details
					$buyerSql = "
						SELECT 
							full_name,
							status,
							email,
							phone,
							monthly_income,
							down_payment,
							loan_amount,
							region,
							town
						FROM buyers b
						WHERE b.id = ?";
					$buyerParams = [$buyerID];
					$agentScopeId = 0;
					if (currentRole() === 'agent_coordinator') {
						$agentScopeId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
						$buyerSql .= " AND (b.assigned_agent_id = ? OR b.loaded_by = ? OR EXISTS (
							SELECT 1 FROM agent_task_allocations ata
							WHERE ata.allocation_type = 'buyer' AND ata.entity_id = b.id AND ata.agent_id = ?
						))";
						$buyerParams = [$buyerID, $agentScopeId, (int)$_SESSION['user_id'], $agentScopeId];
					}
					$stmtB = $pdo->prepare($buyerSql);
					$stmtB->execute($buyerParams);
					$buyer = $stmtB->fetch();

					if (!$buyer) die("Buyer not found");
					if ($buyer['status'] !== 'approved') die('Only approved buyers can be matched');

					// Separate amounts
					$downPayment = (float)$buyer['down_payment'];
					$loanAmount  = (float)$buyer['loan_amount'];

					// Buyer total budget (normal case)
					$buyerBudget = $downPayment + $loanAmount;

					// Fallback if BOTH are zero or missing
					if ($buyerBudget <= 0) {
						// Use annual income as estimated budget
						$buyerBudget = (float)$buyer['monthly_income'] * 12;
					}


					// -------- FETCH SELLERS (JOIN TABLES) --------
					$placeholders = implode(',', array_fill(0, count($sellerArray), '?'));

					$sql = "
						SELECT 
							sp.id,
							sp.selling_price,
							sp.property_region,
							sp.property_town,
							sp.property_location,
							sp.property_suburb,
							sp.property_status,   -- ADD THIS LINE ✔
							sp.status_deadline,

							CONCAT(spp.first_name, ' ', spp.surname) AS seller_name,
							sra.email AS seller_email,
							sra.mobile_number AS seller_phone
						FROM seller_properties sp
						JOIN seller_applications sa ON sa.id = sp.application_id
						LEFT JOIN seller_personal_details spp 
							   ON spp.application_id = sp.application_id
						LEFT JOIN seller_residential_address sra
							   ON sra.application_id = sp.application_id
						WHERE sp.id IN ($placeholders)

					";
					$sellerParams = $sellerArray;
					if (currentRole() === 'agent_coordinator') {
						$sql .= " AND (sa.assigned_agent_id = ? OR spp.loaded_by = ? OR EXISTS (
							SELECT 1 FROM agent_task_allocations ata
							WHERE ata.allocation_type = 'seller'
							  AND ata.agent_id = ?
							  AND (ata.entity_id = sp.id OR ata.entity_reference = sa.application_number)
						))";
						$sellerParams[] = $agentScopeId;
						$sellerParams[] = (int)$_SESSION['user_id'];
						$sellerParams[] = $agentScopeId;
					}

					$stmtS = $pdo->prepare($sql);
					$stmtS->execute($sellerParams);
					$sellers = $stmtS->fetchAll();
					?>

					<!-- -------- TABLE OUTPUT -------- -->
					<table class="tablesaw no-wrap table-bordered table-hover table" data-tablesaw>
					  <thead>
						<tr>
						  <th>Seller</th>
						  <th>Buyer</th>
						  <th>Selling Price (N$)</th>
						  <th>Buyer Budget (N$)</th>
						  <th>Property Location</th>
						  <th>Action</th>
						</tr>
					  </thead>

					  <tbody id="checkall-target">

					  <?php if (!empty($sellers)): ?>
						<?php foreach ($sellers as $seller): ?>
						  <tr>

							

							<!-- Seller Info (Compact) -->
							<td
							  title="<?= htmlspecialchars(($seller['seller_email'] ?? '') . ' | ' . ($seller['seller_phone'] ?? '')) ?>"
							>
							  <strong><?= htmlspecialchars($seller['seller_name'] ?? 'N/A') ?></strong><br>
							  <small class="text-muted">
								<?= htmlspecialchars($seller['seller_phone'] ?? 'N/A') ?>
							  </small>
							</td>

							<!-- Buyer Info (Compact) -->
							<td
							  title="<?= htmlspecialchars(($buyer['email'] ?? '') . ' | ' . ($buyer['phone'] ?? '')) ?>"
							>
							  <strong><?= htmlspecialchars($buyer['full_name']) ?></strong><br>
							  <small class="text-muted">
								<?= htmlspecialchars($buyer['phone'] ?? 'N/A') ?>
							  </small>
							</td>

							<!-- Selling Price -->
							<td>
							  <strong>N$ <?= number_format($seller['selling_price'], 2) ?></strong>
							</td>

							<!-- Buyer Budget (Shortened) -->
							<td>
							  <strong>N$ <?= number_format($buyerBudget, 2) ?></strong><br>
							  <small class="text-muted">Loan + Deposit</small>
							</td>

							<!-- Property Location (Merged) -->
							<td>
							  <?= htmlspecialchars($seller['property_region']) ?>,
							  <?= htmlspecialchars($seller['property_town']) ?><br>
							  <small class="text-muted">
								<?= htmlspecialchars($seller['property_suburb'] ?? '') ?>,
								<?= htmlspecialchars($seller['property_location'] ?? '') ?>
							  </small>
							</td>

							<!-- Action -->
							<td>
							  <?php if ($seller['property_status'] === 'under_offer'): ?>
								<button class="btn btn-secondary btn-sm" disabled>
								  Under Offer
								</button>
								<div class="mt-1"><?= renderCountdownBadge('under_offer', $seller['status_deadline']) ?></div>
							  <?php elseif ($seller['property_status'] === 'available'): ?>
								<button
								  class="btn btn-info btn-sm match-btn"
								  data-seller-id="<?= $seller['id'] ?>"
								  data-buyer-id="<?= $buyerID ?>"
								  data-buyer-name="<?= htmlspecialchars($buyer['full_name']) ?>"
								>
								  Match
								</button>
							  <?php else: ?>
								<button class="btn btn-secondary btn-sm" disabled><?= htmlspecialchars(ucwords(str_replace('_', ' ', $seller['property_status']))) ?></button>
							  <?php endif; ?>
							</td>

						  </tr>
						<?php endforeach; ?>

					  <?php else: ?>
						<tr>
						  <td colspan="6" class="text-center">
							No Matching  Found
						  </td>
						</tr>
					  <?php endif; ?>

					  </tbody>
					</table>



                </div>
              </div>
              
              
             
            </div>
          </div>
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
    <script src="../../assets/libs/tablesaw/dist/tablesaw.jquery.js"></script>
    <script src="../../assets/libs/tablesaw/dist/tablesaw-init.js"></script>
	<script>
	document.querySelectorAll('.match-btn').forEach(btn => {
		btn.addEventListener('click', function () {

			const sellerID = this.dataset.sellerId;
			const buyerID  = this.dataset.buyerId;
			const buyerName = this.dataset.buyerName;

			if (!sellerID || !buyerID) {
				alert("Missing buyer or seller ID");
				return;
			}

			if (this.dataset.confirmed !== 'true') {
				this.dataset.confirmed = 'true';
				this.dataset.originalText = this.textContent.trim();
				this.textContent = 'Click again to confirm';
				setTimeout(() => { if (this.dataset.confirmed === 'true') { this.dataset.confirmed = 'false'; this.textContent = this.dataset.originalText; } }, 5000);
				return;
			}

			fetch("match_property.php", {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded"
				},
				body:
					"seller_id=" + encodeURIComponent(sellerID) +
					"&buyer_id=" + encodeURIComponent(buyerID) +
					"&csrf_token=" + encodeURIComponent(<?= json_encode($matchingCsrf) ?>)
			})
			.then(async res => ({ok: res.ok, data: await res.json()}))
			.then(({ok, data}) => {
				if (!ok || !data.success) throw new Error(data.message || 'Unable to match property.');
				window.location.href = "match-results.php";
			})
			.catch(err => { this.dataset.confirmed = 'false'; this.textContent = err.message || 'Match failed'; });
		});
	});
	</script>


  </body>
</html>

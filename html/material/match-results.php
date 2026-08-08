<?PHP
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
expireOverdueProperties($pdo);
$assignTaskCsrf = csrfToken('assign_agent_task');
$markSoldCsrf = csrfToken('mark_property_sold');
?>
<?php
$params = [];
$sql = "
    SELECT
        sp.id,
        sp.application_id,
        sp.property_detail_type,
        sp.property_region,
        sp.property_town,
        sp.sold_price,
        sp.selling_price,
        sp.buyer_name,
        sp.sale_date,
        sp.status_deadline,
        (SELECT CONCAT(spn.first_name, ' ', spn.surname)
         FROM seller_personal_details spn
         WHERE spn.application_id = sp.application_id LIMIT 1) AS seller_name
";

if (currentRole() === 'agent_coordinator') {
    $myAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $sql .= ", (ata.agent_id IS NOT NULL) AS owned_by_me
        FROM seller_properties sp
        LEFT JOIN seller_personal_details spd
            ON sp.application_id = spd.application_id
        LEFT JOIN agent_task_allocations ata
            ON ata.entity_id = sp.id AND ata.agent_id = :my_agent_id
        WHERE sp.property_status = 'under_offer' AND sp.buyer_name is not null
            AND (spd.loaded_by = :loaded_by OR ata.agent_id IS NOT NULL)
    ";
    $params[':loaded_by'] = $_SESSION['user_id'];
    $params[':my_agent_id'] = $myAgentId ?? 0;
} else {
    $sql .= ", 1 AS owned_by_me
        FROM seller_properties sp
        WHERE sp.property_status = 'under_offer' AND sp.buyer_name is not null
    ";
}

$sql .= " ORDER BY sp.sale_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$soldProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

$agentStmt = $pdo->prepare("
    SELECT 
        id,
        surname,
        first_name,
        company_name
    FROM agents
    WHERE status IN ('approved','active')
    ORDER BY surname, first_name
");
$agentStmt->execute();
$agents = $agentStmt->fetchAll(PDO::FETCH_ASSOC);

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
      content="Secure Nuru Real Estate operations portal"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Nuru Admin</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="../../assets/images/favicon.png"
    />
    <link
      rel="stylesheet"
      href="../../assets/libs/apexcharts/dist/apexcharts.css"
    />
    <link
      href="../../assets/extra-libs/css-chart/css-chart.css"
      rel="stylesheet"
    />
    <!-- Vector CSS -->
    <link
      href="../../assets/libs/jvectormap/jquery-jvectormap.css"
      rel="stylesheet"
    />
    <!-- Custom CSS -->
    <link href="../../dist/css/style.min.css" rel="stylesheet" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <![endif]-->

  </head>

  <body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
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
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper">
      <!-- ============================================================== -->
      <!-- Topbar header - style you can find in pages.scss -->
      <!-- ============================================================== -->
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

      <!-- ============================================================== -->
      <!-- End Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Page wrapper  -->
      <!-- ============================================================== -->
      <div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars(roleDisplayName()) ?></li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Active Property Matches</h4>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">

							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Seller</th>
										<th>Property Type</th>
										<th>Region</th>
										<th>Town</th>
										<th>Sold Price</th>
										<th>Buyer</th>
										<th>Sale Date</th>
										<th>Countdown</th>
										<th>Add Task</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($soldProperties as $i => $p): ?>
									
									

										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= htmlspecialchars($p['seller_name'] ?: 'N/A') ?></td>
											<td><?= htmlspecialchars($p['property_detail_type']) ?></td>
											<td><?= htmlspecialchars($p['property_region']) ?></td>
											<td><?= htmlspecialchars($p['property_town']) ?></td>
											<td>N$ <?= number_format((float)($p['sold_price'] ?: $p['selling_price']), 2) ?></td>
											<td><?= htmlspecialchars($p['buyer_name']) ?></td>
											<td><?= $p['sale_date'] ? date('d M Y', strtotime($p['sale_date'])) : '—' ?></td>
											<td>
												<?= renderCountdownBadge('under_offer', $p['status_deadline']) ?: '—' ?>
												<?php if ($p['owned_by_me']): ?>
													<div class="mt-1">
														<button
															type="button"
															class="btn btn-outline-success btn-sm mark-sold-btn"
															data-property-id="<?= $p['id'] ?>"
														>
															Mark as Sold
														</button>
													</div>
												<?php endif; ?>
											</td>

											<td style="min-width:260px;">

												<!-- Due date -->
												<input
													type="date"
													class="form-control form-control-sm mb-1 task-due-date"
													data-property-id="<?= $p['id'] ?>"
												>

												<!-- Assign agent -->
												<select
													class="form-select form-select-sm assign-agent"
													data-property-id="<?= $p['id'] ?>"
												>
													<option value="">Select agent…</option>

													<?php foreach ($agents as $a): ?>
														<option value="<?= $a['id'] ?>">
															<?= htmlspecialchars($a['surname'] . ' ' . $a['first_name']) ?>
															— <?= htmlspecialchars($a['company_name']) ?>
														</option>
													<?php endforeach; ?>
												</select>

												<!-- Assigned display (hidden initially) -->
												<div
													class="assigned-agent text-success small fw-bold d-none"
													id="assigned-agent-<?= $p['id'] ?>"
												></div>

												<!-- Loader -->
												<small
													class="text-muted d-none"
													id="task-loader-<?= $p['id'] ?>"
												>
													Assigning task…
												</small>

											</td>

										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>


                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        All Rights Reserved by Nuru.
    </footer>
</div>
      <!-- ============================================================== -->
      <!-- End Page wrapper  -->
      <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- customizer Panel -->
    <!-- ============================================================== -->
    
    <div class="chat-windows"></div>
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
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
    <!--This page JavaScript -->
	
    <!--This page plugins -->
    <script src="../../assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <!-- start - This is for export functionality only -->
	<link href="../../assets/libs/select2/dist/css/select2.min.css" rel="stylesheet" />
<script src="../../assets/libs/select2/dist/js/select2.min.js"></script>

<script>
$(".table-bordered").DataTable({
  dom: "Bfrtip",
  buttons: ["copy", "csv", "excel", "pdf", "print"],
});
$(
  ".buttons-copy, .buttons-csv, .buttons-print, .buttons-pdf, .buttons-excel"
).addClass("btn btn-primary mr-1");

$(document).ready(function () {

    $('.select2-agent').select2({
        width: '100%',
        templateResult: formatAgent,
        templateSelection: formatAgent
    });

    function formatAgent(agent) {
        if (!agent.id) return agent.text;

        const parts = agent.text.split('|');

        return $(`
            <div>
                <strong>${parts[0]}</strong><br>
                <small class="text-muted">${parts[1] ?? ''}</small>
            </div>
        `);
    }

});

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.assign-agent').forEach(select => {
        select.addEventListener('change', function () {

            const agentId   = this.value;
            const propertyId = this.dataset.propertyId;
			


            if (!agentId) return;

            const dueDateInput = document.querySelector(
                `.task-due-date[data-property-id="${propertyId}"]`
            );

            const dueDate = dueDateInput?.value || null;

            if (!dueDate) {
                alert('Please select a due date first.');
                this.value = '';
                return;
            }

            const loader = document.getElementById(`task-loader-${propertyId}`);
            const assignedBox = document.getElementById(`assigned-agent-${propertyId}`);

            loader.classList.remove('d-none');

            fetch('./config/assign_agent_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    agent_id: agentId,
                    property_id: propertyId,
                    due_date: dueDate,
                    csrf_token: <?= json_encode($assignTaskCsrf) ?>
                })
            })
            .then(res => res.json())
            .then(data => {

                loader.classList.add('d-none');

                if (!data.success) {
                    alert(data.message);
                    return;
                }

                // Disable inputs
                this.disabled = true;
                dueDateInput.disabled = true;

                // Show assigned agent
                assignedBox.textContent = data.agent_label;
                assignedBox.classList.remove('d-none');

                // Hide select
                this.classList.add('d-none');
            })
            .catch(() => {
                loader.classList.add('d-none');
                alert('Failed to assign task.');
            });
        });
    });

    document.querySelectorAll('.mark-sold-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.dataset.confirmed !== '1') {
                this.dataset.confirmed = '1';
                this.dataset.originalText = this.textContent.trim();
                this.textContent = 'Click again to confirm';
                this.classList.remove('btn-outline-success');
                this.classList.add('btn-warning');
                setTimeout(() => {
                    if (this.dataset.confirmed === '1' && !this.disabled) {
                        this.dataset.confirmed = '0';
                        this.textContent = this.dataset.originalText || 'Mark as Sold';
                        this.classList.add('btn-outline-success');
                        this.classList.remove('btn-warning');
                    }
                }, 5000);
                return;
            }

            const propertyId = this.dataset.propertyId;
            this.disabled = true;

            fetch('./config/mark_property_sold.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ property_id: propertyId, csrf_token: <?= json_encode($markSoldCsrf) ?> })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                    this.disabled = false;
                    return;
                }
                location.reload();
            })
            .catch(() => {
                alert('Failed to mark property as sold.');
                this.disabled = false;
            });
        });
    });

});
</script>



  </body>
</html>

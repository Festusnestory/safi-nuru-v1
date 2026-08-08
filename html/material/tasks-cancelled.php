<?PHP
session_start();
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
}
include("./config/pdo.php");
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin','manager','agent_coordinator']);
$reassignTaskCsrf = csrfToken('reassign_task');
?>
<?php
$whereClause = "WHERE ata.status = 'cancelled'";
$params = [];

if (currentRole() === 'agent_coordinator') {
    $myAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $whereClause .= " AND ata.agent_id = :agent_id";
    $params[':agent_id'] = $myAgentId ?? 0;
}

$sql = "
    SELECT
        ata.id AS task_id,
        ata.agent_id,
        ata.entity_id AS property_id,
        ata.entity_reference,
        ata.due_date,
        ata.status,

        COALESCE(sa.application_number, b.application_number, ata.entity_reference) AS application_id,
        COALESCE(sp.property_detail_type, 'Buyer support') AS property_detail_type,
        COALESCE(sp.property_region, b.region, '—') AS property_region,
        COALESCE(sp.property_town, b.town, '—') AS property_town,
        COALESCE(sp.sold_price, b.property_value, 0) AS sold_price,
        COALESCE(sp.buyer_name, b.full_name, '—') AS buyer_name,
        sp.sale_date,

        CONCAT(a.surname, ' ', a.first_name) AS agent_name,
        a.company_name,

        CASE
            WHEN ata.due_date IS NOT NULL AND ata.due_date < CURDATE()
            THEN 1 ELSE 0
        END AS is_overdue

    FROM agent_task_allocations ata
    LEFT JOIN seller_properties sp ON ata.allocation_type = 'seller' AND sp.id = ata.entity_id
    LEFT JOIN seller_applications sa ON sp.application_id = sa.id
    LEFT JOIN buyers b ON ata.allocation_type = 'buyer' AND b.id = ata.entity_id
    JOIN agents a ON a.id = ata.agent_id

    $whereClause

    ORDER BY ata.due_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pendingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
                        <h4 class="card-title mb-0">Cancelled Tasks</h4>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">

							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Application No</th>
										<th>Property Type</th>
										<th>Region</th>
										<th>Town</th>
										<th>Sold Price</th>
										<th>Buyer</th>
										<th>Sale Date</th>
										<th>Reassign Task</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($pendingTasks as $i => $t): ?>
									<tr class="<?= $t['is_overdue'] ? 'table-warning' : '' ?>">

										<td><?= $i + 1 ?></td>
										<td><?= htmlspecialchars($t['application_id']) ?></td>
										<td><?= htmlspecialchars($t['property_detail_type']) ?></td>
										<td><?= htmlspecialchars($t['property_region']) ?></td>
										<td><?= htmlspecialchars($t['property_town']) ?></td>
										<td>N$ <?= number_format($t['sold_price'], 2) ?></td>
										<td><?= htmlspecialchars($t['buyer_name']) ?></td>
										<td><?= $t['sale_date'] ? date('d M Y', strtotime($t['sale_date'])) : '—' ?></td>

										<!-- TASK COLUMN -->
										<td style="min-width:300px;">

										<!-- Previous assignment -->
										<div class="mb-1">
											<strong class="text-danger">
												<?= htmlspecialchars($t['agent_name']) ?>
											</strong><br>
											<small class="text-muted">
												<?= htmlspecialchars($t['company_name']) ?>
											</small>
										</div>

										<!-- Task status -->
										<div class="mb-2">
											<span class="badge bg-danger">Cancelled</span>
											<?php if ($t['due_date']): ?>
												<small class="text-muted d-block">
													Was due: <?= date('d M Y', strtotime($t['due_date'])) ?>
												</small>
											<?php endif; ?>
										</div>

										<!-- Reassign agent -->
										<select
											class="form-select form-select-sm reassign-agent"
											data-task-id="<?= $t['task_id'] ?>"
										>
											<option value="">Reassign to agent…</option>

											<?php foreach ($agents as $a): ?>
												<?php if ($a['id'] != $t['agent_id']): ?>
													<option value="<?= $a['id'] ?>">
														<?= htmlspecialchars($a['surname'].' '.$a['first_name']) ?>
														— <?= htmlspecialchars($a['company_name']) ?>
													</option>
												<?php endif; ?>
											<?php endforeach; ?>
										</select>

										<small
											class="text-muted d-none"
											id="task-loader-<?= $t['task_id'] ?>"
										>
											Reassigning task…
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

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.reassign-agent').forEach(select => {
        select.addEventListener('change', function () {

            const taskId = this.dataset.taskId;
            const newAgentId = this.value;

            if (!newAgentId) return;

            const loader = document.getElementById(`task-loader-${taskId}`);
            loader.classList.remove('d-none');

            fetch('./config/reassign_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    task_id: taskId,
                    agent_id: newAgentId,
                    csrf_token: <?= json_encode($reassignTaskCsrf) ?>
                })
            })
            .then(res => res.json())
            .then(data => {
                loader.classList.add('d-none');

                if (!data.success) {
                    alert(data.message);
                    return;
                }

                location.reload();
            })
            .catch(() => {
                loader.classList.add('d-none');
                alert('Failed to reassign task.');
            });
        });
    });

});
</script>


  </body>
</html>

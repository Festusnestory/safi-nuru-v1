<?php
/** @var array $tasks */
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
      content="Secure Nuru Real Estate operations portal"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Nuru Admin</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="<?= $baseUrl ?>/assets/images/favicon.png"
    />
    <link
      rel="stylesheet"
      href="<?= $baseUrl ?>/assets/libs/apexcharts/dist/apexcharts.css"
    />
    <link
      href="<?= $baseUrl ?>/assets/extra-libs/css-chart/css-chart.css"
      rel="stylesheet"
    />
    <!-- Vector CSS -->
    <link
      href="<?= $baseUrl ?>/assets/libs/jvectormap/jquery-jvectormap.css"
      rel="stylesheet"
    />
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet" />
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
     <?php require NURU_MATERIAL . '/top-bar.php'; ?>
      <!-- ============================================================== -->
      <!-- End Topbar header -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
      <?php if (\isFullAccess()): ?>
          <?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
      <?php else: ?>
          <?php require NURU_MATERIAL . '/agent_nemu.php'; ?>
      <?php endif; ?>

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
                        <h4 class="card-title mb-0">Tasks In Progress</h4>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">

							<table class="table table-hover table-bordered align-middle">
								<thead class="table-light">
								<tr>
									<th>#</th>
									<th>Task Ref</th>
									<th>Entity</th>
									<th>Agent</th>
									<th>Status</th>
									<th>Progress</th>
									<th>Due Date</th>
									<th>SLA</th>
									<th>Last Contact</th>
									<th>Next Action</th>
									<th>Comms</th>
									<th>Actions</th>
								</tr>
								</thead>
								<tbody>
									<?php foreach ($tasks as $i => $t): ?>
									<tr class="
										<?= $t['sla_status']=='Overdue' ? 'table-danger' : '' ?>
										<?= $t['sla_status']=='Due Today' ? 'table-warning' : '' ?>
									">

									<td><?= $i + 1 ?></td>

									<td>
										<strong><?= htmlspecialchars($t['entity_reference']) ?></strong>
									</td>

									<td>
										<?= htmlspecialchars($t['property_region']) ?><br>
										<small class="text-muted"><?= htmlspecialchars($t['property_town']) ?></small>
									</td>

									<td>
										<strong><?= htmlspecialchars($t['agent_name']) ?></strong><br>
										<small class="text-muted"><?= htmlspecialchars($t['company_name']) ?></small>
									</td>

									<td>
										<span class="badge bg-info">
											<?= ucfirst(str_replace('_',' ',$t['status'])) ?>
										</span>
									</td>

									<td style="min-width:120px;">
										<div class="progress">
											<div class="progress-bar
												<?= $t['progress_percentage'] < 50 ? 'bg-warning' : 'bg-success' ?>"
												role="progressbar"
												style="width: <?= (int)$t['progress_percentage'] ?>%">
												<?= (int)$t['progress_percentage'] ?>%
											</div>
										</div>
									</td>

									<td>
										<?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—' ?>
									</td>

									<td>
										<span class="badge
											<?= $t['sla_status']=='Overdue' ? 'bg-danger' : '' ?>
											<?= $t['sla_status']=='Due Today' ? 'bg-warning text-dark' : '' ?>
											<?= $t['sla_status']=='On Track' ? 'bg-success' : '' ?>">
											<?= $t['sla_status'] ?>
										</span>
									</td>

									<td>
										<?= $t['last_contact_date']
											? date('d M Y', strtotime($t['last_contact_date']))
											: 'No contact' ?>
									</td>

									<td style="max-width:180px;">
										<?= htmlspecialchars($t['next_action_description'] ?? '—') ?><br>
										<small class="text-muted">
											<?= $t['next_action_date'] ? date('d M Y', strtotime($t['next_action_date'])) : '' ?>
										</small>
									</td>

									<td class="text-center">
										<span class="badge bg-secondary">
											<?= (int)$t['communications_count'] ?>
										</span>
									</td>

									<td>
										<a href="<?= htmlspecialchars(\portalPath('task_view.php?id=' . $t['task_id'])) ?>" class="btn btn-sm btn-primary">
											View
										</a>
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
    <!--This page JavaScript -->

    <!--This page plugins -->
    <script src="<?= $baseUrl ?>/assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>

<!-- DataTables Buttons extension (Copy/CSV/Excel/PDF/Print export) - not
     vendored locally, loaded the same way the login page's Cloudflare
     Turnstile widget already is. -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <!-- start - This is for export functionality only -->
	<link href="<?= $baseUrl ?>/assets/libs/select2/dist/css/select2.min.css" rel="stylesheet" />
<script src="<?= $baseUrl ?>/assets/libs/select2/dist/js/select2.min.js"></script>

<script>
$(".table-bordered").DataTable({
  dom: "Bfrtip",
  buttons: ["copy", "csv", "excel", "pdf", "print"],
  language: { emptyTable: "No records found." },
});
$(
  ".buttons-copy, .buttons-csv, .buttons-print, .buttons-pdf, .buttons-excel"
).addClass("btn btn-primary mr-1");
</script>



  </body>
</html>

<?php
if (!defined('NURU_SELLER_AGENT_INCLUDE')) {
    http_response_code(404);
    exit('Not found.');
}
// Make sure session exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']))
{
    header("location: authentication-login.php");
    exit;
}
require_once __DIR__ . '/config/role_helpers.php';
// Parent page performs access control before any HTML is emitted.

$params = [];
$whereClause = '';

// Agents see every seller record in their authorised portfolio.
if (isset($_SESSION['role']) && $_SESSION['role'] === 'agent_coordinator') {
    $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
    $whereClause = 'WHERE (
        sp.loaded_by = :loaded_by
        OR sa.assigned_agent_id = :agent_id
        OR EXISTS (
            SELECT 1
            FROM agent_task_allocations allocation
            INNER JOIN seller_properties scoped_property
                ON scoped_property.id = allocation.entity_id
               AND scoped_property.application_id = sa.id
            WHERE allocation.allocation_type = \'seller\'
              AND allocation.agent_id = :task_agent_id
        )
        OR EXISTS (
            SELECT 1
            FROM agent_task_allocations reference_allocation
            WHERE reference_allocation.allocation_type = \'seller\'
              AND reference_allocation.entity_reference = sa.application_number
              AND reference_allocation.agent_id = :reference_agent_id
        )
    )';
    $params[':loaded_by'] = (int)$_SESSION['user_id'];
    $params[':agent_id'] = $agentId;
    $params[':task_agent_id'] = $agentId;
    $params[':reference_agent_id'] = $agentId;
}

// Admin sees all (no WHERE clause)

$sql = "
    SELECT
        sp.id AS seller_id,
        sp.application_id AS application_number,
        CONCAT(sp.first_name, ' ', sp.surname) AS full_name,
        sra.email,
        sra.mobile_number AS phone,
        sra.region,
        sra.town,
        sp.created_at
    FROM seller_personal_details sp
    INNER JOIN seller_applications sa ON sa.id = sp.application_id
    LEFT JOIN seller_residential_address sra
        ON sra.application_id = sp.application_id
    $whereClause
    ORDER BY sp.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="page-wrapper">
        <div class="row page-titles">
          <div class="col-md-5 col-12 align-self-center">
            <h3 class="text-themecolor mb-0"></h3>
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active"><?= htmlspecialchars(roleDisplayName()) ?></li>
            </ol>
          </div>
          <!----<div class="col-md-7 col-12 align-self-center d-none d-md-block">
            <div class="d-flex mt-2 justify-content-end">
              <div class="d-flex me-3 ms-2">
                <div class="chart-text me-2">
                  <h6 class="mb-0"><small>THIS MONTH</small></h6>
                  <h4 class="mt-0 text-info">$58,356</h4>
                </div>
                <div class="spark-chart">
                  <div id="monthchart"></div>
                </div>
              </div>
              <div class="d-flex ms-2">
                <div class="chart-text me-2">
                  <h6 class="mb-0"><small>LAST MONTH</small></h6>
                  <h4 class="mt-0 text-primary">$48,356</h4>
                </div>
                <div class="spark-chart">
                  <div id="lastmonthchart"></div>
                </div>
              </div>
            </div>
          </div>------------------>
        </div>
        <!-- ============================================================== -->
        <!-- Container fluid  -->
        <!-- ============================================================== -->
        <div class="container-fluid">
          <!-- Row -->


          <!-- Row -->

          <!-- Row -->

          <!-- Row -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="border-bottom title-part-padding">
                  <h4 class="card-title mb-0">Seller Portfolio</h4>
                </div>
                <div class="card-body">
                  <h6 class="card-subtitle mb-3">
                  </h6>
					<!-- -------- TABLE OUTPUT -------- -->
					<div class="table-responsive">
						<table id="sellers_table" class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>#</th>
									<th>Full Name</th>
									<th>Email</th>
									<th>Phone</th>
									<th>Region</th>
									<th>Town</th>
									<th>Date Loaded</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($sellers)): ?>
									<?php foreach ($sellers as $index => $seller): ?>
										<tr>
											<td><?= $index + 1 ?></td>
											<td><?= htmlspecialchars($seller['full_name']) ?></td>
											<td><?= htmlspecialchars($seller['email'] ?? '-') ?></td>
											<td><?= htmlspecialchars($seller['phone'] ?? '-') ?></td>
											<td><?= htmlspecialchars($seller['region'] ?? '-') ?></td>
											<td><?= htmlspecialchars($seller['town'] ?? '-') ?></td>
											<td><?= date('d M Y', strtotime($seller['created_at'])) ?></td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="7" class="text-center text-muted">
											No authorised sellers are available.
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
          <!-- Row -->
        </div>
        <!-- ============================================================== -->
        <!-- End Container fluid  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- footer -->
        <!-- ============================================================== -->
        <footer class="footer">
          All Rights Reserved by Nuru.
        </footer>
        <!-- ============================================================== -->
        <!-- End footer -->
        <!-- ============================================================== -->
      </div>



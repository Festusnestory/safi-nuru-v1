<?php
if (!defined('NURU_BUYER_LIST_INCLUDE')) {
    http_response_code(404);
    exit('Not found.');
}
//session_start();
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
}
require_once __DIR__ . '/config/role_helpers.php';
// Parent page performs access control before any HTML is emitted.
$params = [];
$whereClause = '';
$words = 'Buyer List';

/**
 * Agent → only their buyers
 */
if (isset($_SESSION['role']) && $_SESSION['role'] === 'agent_coordinator') {
    $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']) ?? 0;
    $whereClause = 'WHERE b.loaded_by = :loaded_by OR b.assigned_agent_id = :agent_id
        OR EXISTS (
            SELECT 1 FROM agent_task_allocations allocation
            WHERE allocation.allocation_type = \'buyer\' AND allocation.entity_id = b.id AND allocation.agent_id = :task_agent_id
        )';
    $params[':loaded_by'] = (int)$_SESSION['user_id'];
    $params[':agent_id'] = $agentId;
    $params[':task_agent_id'] = $agentId;
    $words = 'My Buyers';
}

/**
 * ONE SQL statement – role controls only WHERE
 */
$sql = "
    SELECT
        b.id,
        b.application_number,
        b.full_name,
        b.email,
        b.phone,
        b.region,
        b.town,
        b.created_at,
        b.status,
        b.source
    FROM buyers b
    $whereClause
    ORDER BY b.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);



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
                  <h4 class="card-title mb-0"><?= $words ?></h4>
                </div>
                <div class="card-body">
                  <h6 class="card-subtitle mb-3">
                  </h6>
					<!-- -------- TABLE OUTPUT -------- -->
					<div class="table-responsive">
						<table id="buyers_table" class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>#</th>
									<th>Application No</th>
									<th>Buyer Name</th>
									<th>Email</th>
									<th>Phone</th>
									<th>Region</th>
									<th>Town</th>
									<th>Date Loaded</th>
									<th>Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($buyers)): ?>
									<?php foreach ($buyers as $index => $buyer): ?>
										<tr>
											<td><?= $index + 1 ?></td>
											<td><?= htmlspecialchars($buyer['application_number']) ?></td>
											<td><?= htmlspecialchars($buyer['full_name']) ?></td>
											<td><?= htmlspecialchars($buyer['email']) ?></td>
											<td><?= htmlspecialchars($buyer['phone']) ?></td>
											<td><?= htmlspecialchars($buyer['region']) ?></td>
											<td><?= htmlspecialchars($buyer['town']) ?></td>
											<td><?= date('d M Y', strtotime($buyer['created_at'])) ?></td>
											<td>
												<span class="badge bg-<?= $buyer['status'] === 'approved' ? 'success' : ($buyer['status'] === 'rejected' ? 'danger' : 'warning') ?>">
													<?= ucfirst($buyer['status']) ?>
												</span>
												<?php if ($buyer['source'] === 'quick_consult'): ?>
													<span class="badge bg-info">Quick Consult</span>
												<?php endif; ?>
											</td>
											<td>
                                                <div class="btn-group">
													<?php
											$encodedId = portalEncodeId((int)$buyer['id']);
													$url = \App\Core\Router::basePath() . "/admin/buyers-profile?id=$encodedId";

													?>
                                                    <a href="<?= $url ?>"
                                                       class="btn btn-sm btn-info">
                                                        View
                                                    </a>
											<?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'], true)): ?>
														<?php if ($buyer['status'] === 'pending'): ?>
															<button class="btn btn-sm btn-success buyer-approve" data-id="<?= $buyer['id'] ?>">Approve</button>
														<?php endif; ?>
														<button
													class="btn btn-sm btn-danger buyer-delete"
															data-id="<?= $buyer['id'] ?>">
													Reject
														</button>
													<?php endif; ?>

                                                </div>
                                            </td>
										</tr>
									<?php endforeach; ?>
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



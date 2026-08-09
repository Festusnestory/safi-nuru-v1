<?php
/**
 * Guarded partial mirroring html/material/sellerlist_table.php's
 * define+include idiom - only reachable by being require()'d from
 * list.php's NURU_SELLER_LIST_INCLUDE block, never directly (there is no
 * route to this file). $sellers/$words/$baseUrl are inherited from list.php's
 * scope, same as the original partial inherited them from sellers-list.php.
 */
if (!defined('NURU_SELLER_LIST_INCLUDE')) {
    http_response_code(404);
    exit('Not found.');
}

/** @var array $sellers */
/** @var string $words */
/** @var string $baseUrl */
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
        </div>
        <!-- ============================================================== -->
        <!-- Container fluid  -->
        <!-- ============================================================== -->
        <div class="container-fluid">
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
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>#</th>
									<th>Applicant</th>
									<th>Full Name</th>
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
							<?php if (!empty($sellers)): ?>
								<?php foreach ($sellers as $i => $s): ?>
									<tr>
										<td><?= $i + 1 ?></td>
										<td><?= htmlspecialchars($s['full_name']) ?></td>
										<td><?= htmlspecialchars($s['full_name']) ?></td>
										<td><?= htmlspecialchars($s['email']) ?></td>
										<td><?= htmlspecialchars($s['phone']) ?></td>
										<td><?= htmlspecialchars($s['region']) ?></td>
										<td><?= htmlspecialchars($s['town']) ?></td>
										<td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
										<td><span class="badge bg-<?= $s['application_status'] === 'approved' ? 'success' : ($s['application_status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $s['application_status']))) ?></span></td>
										<td>
                                                <div class="btn-group">
													<?php
													// Use application_id (the foreign key to seller_applications table)
											$encodedId = portalEncodeId((int)$s['application_id']);
											$url = $baseUrl . "/admin/sellers-profile?id=$encodedId";
													?>
                                                    <a href="<?= $url ?>"
                                                       class="btn btn-sm btn-info">
                                                        View
                                                    </a>
												<?php if (isFullAccess() && in_array($s['application_status'], ['submitted', 'under_review'], true)): ?>
													<button class="btn btn-sm btn-success seller-decision" data-id="<?= (int)$s['application_id'] ?>" data-decision="approve">Approve</button>
													<button class="btn btn-sm btn-outline-danger seller-decision" data-id="<?= (int)$s['application_id'] ?>" data-decision="reject">Reject</button>
												<?php endif; ?>
													<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
														<button
															class="btn btn-sm btn-danger delete-agent"
															data-id="<?= $s['id'] ?>">
															Delete
														</button>
													<?php } ?>

                                                </div>
                                            </td>
									</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr>
									<td colspan="10" class="text-center text-muted">
										No sellers found.
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


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
                  <h4 class="card-title mb-0">Buyer Matches</h4>
                </div>
                <div class="card-body">
                  <h6 class="card-subtitle mb-3">
                  </h6>
					<!-- -------- TABLE OUTPUT -------- -->
					<div class="table-responsive">
					<table id="file_export" class="table table-striped table-bordered display">
					  <thead>
						<tr>
						  <th>Buyer Name</th>
						  <th>Matched Properties</th>
						  <th>Top Preferred Area</th>
						  <th>Area Popularity</th>
						  <th>View Matched Sellers</th>
						</tr>
					  </thead>

					  <tbody>
					  <?php foreach ($buyerSummary as $b): ?>
					  <?php if ($b['matched_count'] === 0) continue; // skip buyers with 0 matches ?>
						  <tr>
							<td><?= htmlspecialchars($b['buyer_name']) ?></td>
							<td><?= $b['matched_count'] ?></td>
							<td><?= htmlspecialchars($b['top_area']) ?></td>
							<td><?= $b['top_area_count'] ?> times</td>
							<td>
								<?php if ($b['matched_count'] > 0): 
									  $ids = implode(',', $b['seller_ids']); ?>
									<a href="loaded_sellers.php?buyer=<?= $b['buyer_id'] ?>&sellers=<?= urlencode($ids) ?>">
										View (<?= $b['matched_count'] ?>)
									</a>
								<?php else: ?>
									No Match
								<?php endif; ?>
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

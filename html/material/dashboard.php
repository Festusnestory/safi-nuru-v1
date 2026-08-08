<?php
if (!defined('NURU_ADMIN_DASHBOARD')) {
    http_response_code(404);
    exit('Not found.');
}
// $pdo, $buyers, $sellers, $buyerSummary already available from config/matching-functions.php

$total_buyers = (int)$pdo->query("SELECT COUNT(*) FROM buyers")->fetchColumn();
$total_sellers = (int)$pdo->query("SELECT COUNT(*) FROM seller_personal_details")->fetchColumn();
$total_properties = (int)$pdo->query("SELECT COUNT(*) FROM seller_properties WHERE property_status = 'available'")->fetchColumn();
$avg_price = (float)$pdo->query("SELECT COALESCE(AVG(selling_price), 0) FROM seller_properties WHERE property_status = 'available'")->fetchColumn();

// Pending approvals needing admin attention
$pendingBuyers = (int)$pdo->query("SELECT COUNT(*) FROM buyers WHERE status = 'pending'")->fetchColumn();
$pendingAgentApps = (int)$pdo->query("SELECT COUNT(*) FROM agent_applications WHERE status IN ('submitted','under_review')")->fetchColumn();
$pendingSellerApps = (int)$pdo->query("SELECT COUNT(*) FROM seller_applications WHERE status IN ('submitted','under_review')")->fetchColumn();
$totalPendingApprovals = $pendingBuyers + $pendingAgentApps + $pendingSellerApps;

// Task workload
$openTasks = (int)$pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status IN ('assigned','in_progress')")->fetchColumn();
$overdueTasks = (int)$pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status IN ('assigned','in_progress') AND due_date IS NOT NULL AND due_date < CURDATE()")->fetchColumn();
$completedThisMonth = (int)$pdo->query("SELECT COUNT(*) FROM agent_task_allocations WHERE status = 'completed' AND completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();

// Sales pipeline this month
$pipelineValue = (float)$pdo->query("
    SELECT COALESCE(SUM(COALESCE(sold_price, selling_price)), 0)
    FROM seller_properties
    WHERE property_status IN ('under_offer','sold')
      AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
")->fetchColumn();

// Countdown: deals expiring soon (under_offer/sold approaching their deadline)
require_once __DIR__ . '/config/property_lifecycle.php';
expireOverdueProperties($pdo);
$expiringSoon = $pdo->query("
    SELECT sp.id, sp.application_id, sp.property_region, sp.property_town,
           sp.property_status, sp.status_deadline, sp.buyer_name
    FROM seller_properties sp
    WHERE sp.property_status IN ('under_offer','sold')
      AND sp.status_deadline IS NOT NULL
    ORDER BY sp.status_deadline ASC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Recent activity feed
$recentActivity = $pdo->query("
    SELECT al.action, al.description, al.category, al.created_at, au.full_name
    FROM activity_log al
    LEFT JOIN admin_users au ON au.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Region map data (real counts)
$buyers_by_region = $pdo->query("
    SELECT region, COUNT(*) AS c
    FROM buyers
    WHERE region IS NOT NULL AND region != ''
    GROUP BY region
")->fetchAll(PDO::FETCH_ASSOC);
$buyers_by_region_json = json_encode($buyers_by_region);

$sellers_by_region = $pdo->query("
    SELECT property_region AS region, COUNT(*) AS c
    FROM seller_properties
    WHERE property_region IS NOT NULL AND property_region != ''
    GROUP BY property_region
")->fetchAll(PDO::FETCH_ASSOC);
$sellers_by_region_json = json_encode($sellers_by_region);
?>
    <style>
        .property-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 5px solid #1e88e5;
            transition: transform 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1e88e5;
            margin-bottom: 5px;
        }

        .metric-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .pipeline-step {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }

        .pipeline-step.warning {
            border-left-color: #ffc107;
        }

        .pipeline-step.danger {
            border-left-color: #dc3545;
        }

        .region-heatmap {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }

        .region-item {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .region-item:hover {
            border-color: #1e88e5;
            transform: scale(1.05);
        }

        .region-count {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1e88e5;
        }

        .match-timeline {
            position: relative;
            padding-left: 30px;
        }

        .match-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #1e88e5;
        }

        .timeline-item {
            position: relative;
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 20px;
            width: 12px;
            height: 12px;
            background: #1e88e5;
            border-radius: 50%;
            border: 3px solid white;
        }

        .notification-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .property-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-matched {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .metric-value {
                font-size: 2rem;
            }

            .region-heatmap {
                grid-template-columns: repeat(2, 1fr);
            }
        }

.namibia-map-container {
    width: 100%;
    height: 100%;          /* Take full card height */
    min-height: 420px;     /* Prevent shrinking too much */
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;      /* Hide extra */
    position: relative;
    background: #f8f9fa;
    border-radius: 6px;
}

/* Ensures the full SVG is visible and centered */
.namibia-map-container svg {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;   /* KEY: shows entire map */
    display: block;
}


    </style>
<div class="page-wrapper">
        <div class="row page-titles">
          <div class="col-md-5 col-12 align-self-center">
            <h3 class="text-themecolor mb-0"></h3>
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active">System Admin</li>
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
          <?php renderAccessDeniedNotice(); ?>
          <!-- Row -->
          <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?php echo number_format($total_buyers); ?></div>
                                    <div class="metric-label">Total Buyers</div>
                                </div>
                                <div class="text-primary">
                                    <i class="mdi mdi-account-multiple" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?php echo number_format($total_sellers); ?></div>
                                    <div class="metric-label">Total Sellers</div>
                                </div>
                                <div class="text-success">
                                    <i class="mdi mdi-home-variant" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?php echo number_format($total_properties); ?></div>
                                    <div class="metric-label">Available Properties</div>
                                </div>
                                <div class="text-warning">
                                    <i class="mdi mdi-home" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value">N$ <?php echo number_format($avg_price, 0); ?></div>
                                    <div class="metric-label">Average Price</div>
                                </div>
                                <div class="text-info">
                                    <i class="mdi mdi-cash" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

          <!-- Row: needs attention -->
          <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="user-management.php" class="text-decoration-none">
                        <div class="metric-card" style="border-left-color:#dc3545;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value" style="color:#dc3545;"><?php echo number_format($totalPendingApprovals); ?></div>
                                    <div class="metric-label">Pending Approvals</div>
                                </div>
                                <div class="text-danger">
                                    <i class="mdi mdi-alert-circle-outline" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card" style="border-left-color:#ff9800;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value" style="color:#ff9800;"><?php echo number_format($openTasks); ?></div>
                                    <div class="metric-label">Open Tasks (<?php echo number_format($overdueTasks); ?> overdue)</div>
                                </div>
                                <div class="text-warning">
                                    <i class="mdi mdi-clipboard-clock-outline" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card" style="border-left-color:#28a745;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value" style="color:#28a745;"><?php echo number_format($completedThisMonth); ?></div>
                                    <div class="metric-label">Completed This Month</div>
                                </div>
                                <div class="text-success">
                                    <i class="mdi mdi-check-circle-outline" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card" style="border-left-color:#6f42c1;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value" style="color:#6f42c1;">N$ <?php echo number_format($pipelineValue, 0); ?></div>
                                    <div class="metric-label">Sales Pipeline (This Month)</div>
                                </div>
                                <div style="color:#6f42c1;">
                                    <i class="mdi mdi-trending-up" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

          <!-- Row -->

          <!-- Row -->
          <!-- Row -->
          <div class="row">
           <div class="col-lg-12 d-flex align-items-stretch" >
			  <div class="card w-100" style="height: 1000px;">
				<div class="card-body">
				  <div class="d-md-flex no-block">
					<h4 class="card-title">Property Distribution</h4>
					<div class="ms-auto">
					  <!--------<select class="form-select">
						<option selected="">January</option>
						<option value="1">February</option>
						<option value="2">March</option>
						<option value="3">April</option>
					  </select>------------>
					</div>
				  </div>

			<div class="namibia-map-container">
                    <!-- Loading indicator -->
                    <div id="loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 200;">
                        <div class="loading"></div>
                        <p class="mt-2">Loading map...</p>
                    </div>

                    <!-- Map controls -->
                    <div class="map-controls">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm active" onclick="NamibiaMap.toggleView('buyers')">
                                Buyers Only
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="NamibiaMap.toggleView('sellers')">
                                Properties Only
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="NamibiaMap.toggleView('combined')">
                                Combined
                            </button>
                        </div>
                    </div>

                    <!-- SVG Map will be inserted here -->
                    <div id="map-container">
                        <svg class="namibia-map" id="namibia-map-svg" xmlns="http://www.w3.org/2000/svg" style="height: 921px; width: 100%; margin-right: 670px;">
                            <g id="features">
                                <!-- SVG paths will be loaded dynamically -->
                            </g>
                        </svg>
                    </div>

                    <!-- Tooltip -->
                    <div id="map-tooltip" class="map-tooltip"></div>

                    <!-- Legend -->
                    <div class="map-legend">
                        <strong>Activity Level</strong>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #e9ecef;"></div>
                            <span>No data</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #c3e6cb;"></div>
                            <span>Low (1-2)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #8fd19e;"></div>
                            <span>Medium (3-5)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #52b788;"></div>
                            <span>High (6+)</span>
                        </div>
                    </div>
                </div>

				</div>
			  </div>
			</div>


          </div>
          <!-- Row -->

          <!-- Row: recent activity -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="border-bottom title-part-padding">
                  <h4 class="card-title mb-0">Recent Activity</h4>
                </div>
                <div class="card-body">
                  <div class="match-timeline">
                    <?php foreach ($recentActivity as $act): ?>
                      <div class="timeline-item">
                        <strong><?= htmlspecialchars($act['full_name'] ?? 'System') ?></strong>
                        &mdash; <?= htmlspecialchars($act['description'] ?: $act['action']) ?>
                        <div class="text-muted small"><?= htmlspecialchars($act['category']) ?> &middot; <?= date('d M Y, H:i', strtotime($act['created_at'])) ?></div>
                      </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentActivity)): ?>
                      <p class="text-muted mb-0">No recent activity recorded yet.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Row -->

          <!-- Row: deals expiring soon -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="border-bottom title-part-padding">
                  <h4 class="card-title mb-0">Deals Expiring Soon</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>Application No</th>
                          <th>Region</th>
                          <th>Town</th>
                          <th>Buyer</th>
                          <th>Status</th>
                          <th>Countdown</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($expiringSoon as $d): ?>
                          <tr>
                            <td><?= htmlspecialchars($d['application_id']) ?></td>
                            <td><?= htmlspecialchars($d['property_region']) ?></td>
                            <td><?= htmlspecialchars($d['property_town']) ?></td>
                            <td><?= htmlspecialchars($d['buyer_name'] ?? '—') ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $d['property_status'])) ?></td>
                            <td><?= renderCountdownBadge($d['property_status'], $d['status_deadline']) ?: '—' ?></td>
                          </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expiringSoon)): ?>
                          <tr><td colspan="6" class="text-center text-muted">No deals currently under offer or awaiting transfer.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Row -->

          <!-- Row -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="border-bottom title-part-padding">
                  <h4 class="card-title mb-0">Matches</h4>
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



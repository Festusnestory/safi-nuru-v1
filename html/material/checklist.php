<?php
session_start();
if (!isset($_SESSION['user_id']))
{
    header("location: authentication-login.php");
    exit;
}
include("./config/pdo.php");
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin','manager','agent_coordinator']);

$propertyId = (int)($_GET['property_id'] ?? 0);
$requestedTaskId = (int)($_GET['task_id'] ?? 0);
$checklistCsrf = csrfToken('property_checklist');
$canEditChecklist = currentRole() === 'agent_coordinator';

if ($propertyId < 1) {
    http_response_code(404);
    exit('Property not found.');
}

if ($canEditChecklist) {
    $myAgentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $ownsStmt = $pdo->prepare("SELECT 1 FROM agent_task_allocations WHERE agent_id = :agent_id AND allocation_type = 'seller' AND entity_id = :property_id AND status IN ('in_progress', 'completed') LIMIT 1");
    $ownsStmt->execute([':agent_id' => $myAgentId ?? 0, ':property_id' => $propertyId]);
    if (!$ownsStmt->fetchColumn()) {
        header("location: agent-tasks-pending.php");
        exit;
    }
}

$taskParams = [':property_id' => $propertyId];
$taskSql = "SELECT id, agent_id, status FROM agent_task_allocations
    WHERE allocation_type = 'seller' AND entity_id = :property_id AND status IN ('in_progress', 'completed')";
if ($requestedTaskId > 0) {
    $taskSql .= ' AND id = :task_id';
    $taskParams[':task_id'] = $requestedTaskId;
}
if ($canEditChecklist) {
    $taskSql .= ' AND agent_id = :agent_id';
    $taskParams[':agent_id'] = $myAgentId ?? 0;
}
$taskSql .= ' ORDER BY id DESC LIMIT 1';
$checklistTaskStmt = $pdo->prepare($taskSql);
$checklistTaskStmt->execute($taskParams);
$checklistTask = $checklistTaskStmt->fetch(PDO::FETCH_ASSOC);
if (!$checklistTask) {
    http_response_code(404);
    exit('Checklist task not found.');
}
$checklistAllocationId = (int)$checklistTask['id'];

$sql = 'SELECT
    cs.id            AS stage_id,
    cs.stage_name,
    cs.stage_order,
    cs.description,

    ci.id            AS item_id,
    ci.item_name,
    ci.item_order,
    ci.is_required,

    IFNULL(pcs.is_completed, 0) AS is_completed
FROM checklist_stages cs
JOIN checklist_items ci
    ON ci.stage_id = cs.id
LEFT JOIN property_checklist_status pcs
    ON pcs.checklist_item_id = ci.id
   AND pcs.allocation_id = :allocation_id
WHERE cs.is_active = 1
ORDER BY cs.stage_order, ci.item_order;
';

$stmt = $pdo->prepare($sql);
$stmt->execute(['allocation_id' => $checklistAllocationId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stages = [];
$stageOrder = [];

foreach ($rows as $row) {
    $sid = $row['stage_id'];

    if (!isset($stages[$sid])) {
        $stages[$sid] = [
            'stage_name' => $row['stage_name'],
            'stage_order' => $row['stage_order'],
            'description' => $row['description'],
            'items' => [],
            'completed' => true
        ];
        $stageOrder[] = $sid;
    }

    if (!$row['is_completed']) {
        $stages[$sid]['completed'] = false;
    }

    $stages[$sid]['items'][] = $row;
}

// Sort stages by stage_order
usort($stageOrder, function($a, $b) use ($stages) {
    return $stages[$a]['stage_order'] - $stages[$b]['stage_order'];
});

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
    <title>Property Checklist Wizard</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="../../assets/images/favicon.png"
    />
    <!-- This Page CSS -->
    <link
      href="../../assets/libs/bootstrap-table/dist/bootstrap-table.min.css"
      rel="stylesheet"
      type="text/css"
    />
    <!-- Custom CSS -->
    <link href="../../dist/css/style.min.css" rel="stylesheet" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <![endif]-->
    
    <style>
        /* Wizard-specific styles compatible with Material Pro */
        .wizard-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .wizard-stepper {
            position: relative;
            padding: 20px 0;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .wizard-stepper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 40px;
            right: 40px;
            height: 2px;
            background: #dee2e6;
            transform: translateY(-50%);
            z-index: 1;
        }
        
        .wizard-stepper-progress {
            position: absolute;
            top: 50%;
            left: 40px;
            height: 2px;
            background: #6c757d;
            transform: translateY(-50%);
            z-index: 2;
            transition: width 0.3s ease;
        }
        
        .wizard-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
            padding: 0 20px;
        }
        
        .wizard-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            cursor: pointer;
        }
        
        .wizard-step-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: #6c757d;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }
        
        .wizard-step.active .wizard-step-icon {
            border-color: #343a40;
            background: #343a40;
            color: #fff;
        }
        
        .wizard-step.completed .wizard-step-icon {
            border-color: #28a745;
            background: #28a745;
            color: #fff;
        }
        
        .wizard-step-label {
            font-size: 11px;
            color: #6c757d;
            text-align: center;
            max-width: 80px;
            line-height: 1.3;
        }
        
        .wizard-step.active .wizard-step-label {
            color: #343a40;
            font-weight: 600;
        }
        
        .wizard-step.completed .wizard-step-label {
            color: #28a745;
        }
        
        .wizard-content {
            padding: 25px;
        }
        
        .stage-wizard-card {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .stage-wizard-card.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stage-wizard-header {
            background: #f8f9fa;
            padding: 18px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e9ecef;
        }
        
        .stage-wizard-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stage-wizard-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            color: #495057;
        }
        
        .stage-wizard-title h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #343a40;
        }
        
        .stage-wizard-title p {
            margin: 3px 0 0 0;
            font-size: 13px;
            color: #6c757d;
        }
        
        .stage-progress-info {
            text-align: right;
        }
        
        .stage-progress-percent {
            font-size: 24px;
            font-weight: 700;
            color: #343a40;
        }
        
        .stage-progress-label {
            font-size: 12px;
            color: #6c757d;
        }
        
        .wizard-items-table {
            margin-bottom: 0;
        }
        
        .wizard-items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 15px;
        }
        
        .wizard-items-table td {
            padding: 14px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .wizard-items-table tr:hover td {
            background: #f8f9fa;
        }
        
        .wizard-items-table .item-completed {
            text-decoration: line-through;
            color: #adb5bd;
        }
        
        .wizard-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .required-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 600;
            background: #f8f9fa;
            color: #6c757d;
            border-radius: 12px;
            margin-left: 8px;
            border: 1px solid #dee2e6;
        }
        
        .completed-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
            border-radius: 12px;
            margin-left: 8px;
        }
        
        .wizard-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .wizard-btn {
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .wizard-btn-prev {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
        }
        
        .wizard-btn-prev:hover:not(:disabled) {
            background: #e9ecef;
        }
        
        .wizard-btn-prev:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .wizard-btn-next {
            background: #343a40;
            border: 1px solid #343a40;
            color: #fff;
        }
        
        .wizard-btn-next:hover {
            background: #23272b;
        }
        
        .wizard-btn-finish {
            background: #28a745;
            border: 1px solid #28a745;
            color: #fff;
        }
        
        .wizard-btn-finish:hover {
            background: #218838;
        }
        
        /* All Stages Overview Mini Cards */
        .all-stages-overview {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .all-stages-overview h5 {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .mini-stage-card {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .mini-stage-card:hover {
            background: #e9ecef;
        }
        
        .mini-stage-card.active {
            border-color: #343a40;
            background: #fff;
        }
        
        .mini-stage-card.completed {
            border-color: #28a745;
        }
        
        .mini-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            margin-right: 12px;
        }
        
        .mini-stage-card.completed .mini-icon {
            background: #28a745;
            color: #fff;
        }
        
        .mini-stage-card.active .mini-icon {
            background: #343a40;
            color: #fff;
        }
        
        .mini-info {
            flex: 1;
        }
        
        .mini-name {
            font-size: 13px;
            font-weight: 500;
            color: #343a40;
        }
        
        .mini-progress {
            font-size: 11px;
            color: #6c757d;
        }
        
        .mini-status {
            font-size: 16px;
            color: #dee2e6;
        }
        
        .mini-stage-card.completed .mini-status {
            color: #28a745;
        }
        
        .mini-stage-card.active .mini-status {
            color: #343a40;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .wizard-steps {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .wizard-step {
                flex: 0 0 25%;
            }
            
            .wizard-stepper::before {
                display: none;
            }
            
            .stage-wizard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stage-progress-info {
                text-align: center;
            }
        }
    </style>
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
              <li class="breadcrumb-item active">Checklist</li>
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

          <div class="row">
            <div class="col-12">
              
              <?php
              // Calculate which stages are accessible
              $accessibleStages = [];
              $allAccessible = true;
              foreach ($stageOrder as $index => $sid) {
                  if ($allAccessible) {
                      $accessibleStages[$sid] = true;
                      if (!$stages[$sid]['completed']) {
                          $allAccessible = false;
                      }
                  } else {
                      $accessibleStages[$sid] = false;
                  }
              }
              ?>
              
              <div class="wizard-container">
                
                <!-- Stepper -->
                <div class="wizard-stepper">
                  <div class="wizard-stepper-progress" id="wizardProgress"></div>
                  <div class="wizard-steps" id="wizardSteps">
                    <?php foreach ($stageOrder as $index => $sid): ?>
                      <?php $stage = $stages[$sid]; ?>
                      <div class="wizard-step <?= $index === 0 ? 'active' : '' ?> <?= $stage['completed'] ? 'completed' : '' ?>" 
                           data-stage="<?= $index ?>" 
                           onclick="goToStage(<?= $index ?>)"
                           <?= !$accessibleStages[$sid] ? 'style="opacity:0.6;cursor:not-allowed"' : '' ?>>
                        <div class="wizard-step-icon">
                          <?= $stage['completed'] ? '✓' : $index + 1 ?>
                        </div>
                        <div class="wizard-step-label">
                          <?= htmlspecialchars($stage['stage_name']) ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                
                <!-- Wizard Content -->
                <div class="wizard-content">
                  
                  <?php foreach ($stageOrder as $index => $sid): ?>
                    <?php $stage = $stages[$sid]; ?>
                    <?php 
                      $completedItems = count(array_filter($stage['items'], function($item) { 
                          return $item['is_completed']; 
                      }));
                      $totalItems = count($stage['items']);
                      $progressPercent = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
                      $isAccessible = $accessibleStages[$sid];
                    ?>
                    
                    <div class="stage-wizard-card <?= $index === 0 ? 'active' : '' ?>" id="stageCard<?= $index ?>">
                      
                      <!-- Stage Header -->
                      <div class="stage-wizard-header">
                        <div class="stage-wizard-title">
                          <div class="stage-wizard-number"><?= $index + 1 ?></div>
                          <div>
                            <h4><?= htmlspecialchars($stage['stage_name']) ?></h4>
                            <p><?= htmlspecialchars($stage['description']) ?></p>
                          </div>
                        </div>
                        <div class="stage-progress-info">
                          <div class="stage-progress-percent"><?= $progressPercent ?>%</div>
                          <div class="stage-progress-label"><?= $completedItems ?>/<?= $totalItems ?> items completed</div>
                        </div>
                      </div>
                      
                      <!-- Items Table -->
                      <table class="table wizard-items-table">
                        <thead>
                          <tr>
                            <th width="50" class="text-center">Status</th>
                            <th>Item Name</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($stage['items'] as $item): ?>
                            <tr>
                              <td class="text-center">
                                <input type="checkbox" 
                                       class="form-check-input wizard-checkbox"
                                       data-item-id="<?= $item['item_id'] ?>"
                                       <?= $item['is_completed'] ? 'checked disabled' : '' ?>
                                       <?= !$isAccessible ? 'disabled' : '' ?>
                                       <?= !$canEditChecklist ? 'disabled' : '' ?>
                                       onchange="toggleItem(this)">
                              </td>
                              <td class="<?= $item['is_completed'] ? 'item-completed' : '' ?>">
                                <?= htmlspecialchars($item['item_name']) ?>
                                <?php if ($item['is_required']): ?>
                                  <span class="required-badge">Required</span>
                                <?php endif; ?>
                                <?php if ($item['is_completed']): ?>
                                  <span class="completed-badge">Completed</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                      
                    </div>
                  <?php endforeach; ?>
                  
                  <!-- All Stages Overview -->
                 
                  
                  <!-- Navigation -->
                  <div class="wizard-navigation">
                    <button class="wizard-btn wizard-btn-prev" id="prevBtn" onclick="prevStage()" disabled>
                      <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button class="wizard-btn wizard-btn-next" id="nextBtn" onclick="nextStage()">
                      Next <i class="fas fa-arrow-right"></i>
                    </button>
                  </div>
                  
                </div>
              </div>
              
            </div>
          </div>

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
          All Rights Reserved by Nuru.
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
    <!-- This Page JS -->
    <script src="../../assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="../../assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>
    <script src="../../dist/js/pages/tables/bootstrap-table.init.js"></script>
    
    <!-- Wizard Functionality -->
    <script>
        var totalStages = <?= count($stageOrder) ?>;
        var currentStage = 0;
        var accessibleStages = <?php echo json_encode($accessibleStages); ?>;
        
        function updateWizardUI() {
            // Update stepper
            var steps = document.querySelectorAll('.wizard-step');
            var progress = document.getElementById('wizardProgress');
            
            steps.forEach(function(step, index) {
                step.classList.remove('active', 'completed');
                if (index < currentStage) {
                    step.classList.add('completed');
                } else if (index === currentStage) {
                    step.classList.add('active');
                }
            });
            
            // Update progress bar
            var progressWidth = (currentStage / (totalStages - 1)) * 100;
            if (totalStages === 1) {
                progressWidth = 100;
            }
            progress.style.width = 'calc(' + progressWidth + '% - ' + (currentStage * 40) + 'px)';
            
            // Update stage cards
            var cards = document.querySelectorAll('.stage-wizard-card');
            cards.forEach(function(card, index) {
                card.classList.remove('active');
                if (index === currentStage) {
                    card.classList.add('active');
                }
            });
            
            // Update mini cards
            var miniCards = document.querySelectorAll('.mini-stage-card');
            miniCards.forEach(function(card, index) {
                card.classList.remove('active', 'completed');
                if (index < currentStage) {
                    card.classList.add('completed');
                } else if (index === currentStage) {
                    card.classList.add('active');
                }
                
                var icon = card.querySelector('.mini-icon');
                if (icon) {
                    if (index < currentStage) {
                        icon.textContent = '✓';
                    } else {
                        icon.textContent = index + 1;
                    }
                }
            });
            
            // Update navigation buttons
            var prevBtn = document.getElementById('prevBtn');
            var nextBtn = document.getElementById('nextBtn');
            
            prevBtn.disabled = (currentStage === 0);
            
            if (currentStage === totalStages - 1) {
                nextBtn.className = 'wizard-btn wizard-btn-finish';
                nextBtn.innerHTML = '<i class="fas fa-check"></i> Finish';
            } else {
                nextBtn.className = 'wizard-btn wizard-btn-next';
                nextBtn.innerHTML = 'Next <i class="fas fa-arrow-right"></i>';
            }
            
            // Update stage progress
            updateStageProgress();
        }
        
        function updateStageProgress() {
            var currentCard = document.querySelector('.stage-wizard-card.active');
            if (currentCard) {
                var checkboxes = currentCard.querySelectorAll('.wizard-checkbox');
                var checkedCount = 0;
                checkboxes.forEach(function(cb) {
                    if (cb.checked) checkedCount++;
                });
                var total = checkboxes.length;
                var percent = total > 0 ? Math.round((checkedCount / total) * 100) : 0;
                
                var percentEl = currentCard.querySelector('.stage-progress-percent');
                var labelEl = currentCard.querySelector('.stage-progress-label');
                
                if (percentEl) percentEl.textContent = percent + '%';
                if (labelEl) labelEl.textContent = checkedCount + '/' + total + ' items completed';
            }
        }
        
        function goToStage(index) {
            if (index >= 0 && index < totalStages && (index <= currentStage || accessibleStages[Object.keys(accessibleStages)[index]])) {
                currentStage = index;
                updateWizardUI();
            }
        }
        
        function nextStage() {
            if (currentStage < totalStages - 1) {
                currentStage++;
                updateWizardUI();
            } else {
                // Finish button clicked
                alert('Congratulations! You have completed all stages of the property checklist!');
            }
        }
        
        function prevStage() {
            if (currentStage > 0) {
                currentStage--;
                updateWizardUI();
            }
        }
        
			function toggleItem(checkbox) {
				var itemId = checkbox.getAttribute('data-item-id');
				var isCompleted = checkbox.checked ? 1 : 0;

				var xhr = new XMLHttpRequest();
				xhr.open('POST', './config/save-checklist-item.php', true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

				xhr.onreadystatechange = function () {
					if (xhr.readyState === 4) {

						let response;

						try {
							response = JSON.parse(xhr.responseText);
						} catch (e) {
							checkbox.checked = !checkbox.checked;
							alert('Invalid server response.');
							return;
						}

						// ❌ HTTP error
						if (xhr.status !== 200) {
							checkbox.checked = !checkbox.checked;
							alert(response.message || 'Server error occurred.');
							return;
						}

						// ❌ Logical / permission / validation error
						if (!response.success) {
							checkbox.checked = !checkbox.checked;
							alert(response.message);
							return;
						}

						// ✅ SUCCESS
						var row = checkbox.closest('tr');
						var cell = row.querySelector('td:nth-child(2)');

						if (isCompleted) {
							cell.classList.add('item-completed');

							if (!cell.querySelector('.completed-badge')) {
								var completedBadge = document.createElement('span');
								completedBadge.className = 'completed-badge';
								completedBadge.textContent = 'Completed';
								cell.appendChild(completedBadge);
							}
						} else {
							cell.classList.remove('item-completed');
							var badge = cell.querySelector('.completed-badge');
							if (badge) badge.remove();
						}

						updateStageProgress();
						updateMiniCardProgress();
					}
				};

				xhr.send(
					'item_id=' + encodeURIComponent(itemId) +
					'&property_id=<?= $propertyId ?>' +
					'&task_id=<?= $checklistAllocationId ?>' +
					'&is_completed=' + encodeURIComponent(isCompleted) +
					'&csrf_token=' + encodeURIComponent(<?= json_encode($checklistCsrf) ?>)
				);
			}

        
        function updateMiniCardProgress() {
            var miniCards = document.querySelectorAll('.mini-stage-card');
            miniCards.forEach(function(card, index) {
                var stageData = <?php 
                    $stageData = [];
                    foreach ($stageOrder as $index => $sid) {
                        $stage = $stages[$sid];
                        $completedItems = count(array_filter($stage['items'], function($item) { 
                            return $item['is_completed']; 
                        }));
                        $totalItems = count($stage['items']);
                        $stageData[$sid] = [
                            'completed' => $completedItems,
                            'total' => $totalItems,
                            'isCompleted' => $stage['completed']
                        ];
                    }
                    echo json_encode($stageData);
                ?>;
                
                var sid = Object.keys(stageData)[index];
                if (stageData[sid]) {
                    var progress = card.querySelector('.mini-progress');
                    if (progress) {
                        progress.textContent = stageData[sid].completed + '/' + stageData[sid].total + ' items completed';
                    }
                    
                    if (stageData[sid].isCompleted) {
                        card.classList.add('completed');
                        var icon = card.querySelector('.mini-icon');
                        if (icon) icon.textContent = '✓';
                    }
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateWizardUI();
        });
    </script>
  </body>
</html>

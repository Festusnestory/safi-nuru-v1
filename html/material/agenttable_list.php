<?php
if (!defined('NURU_AGENT_LIST_INCLUDE')) {
    http_response_code(404);
    exit('Not found.');
}
if (!isset($_SESSION['user_id']))
{
	header("location: authentication-login.php");
    exit;
} 

// The parent page performs the role check before emitting HTML. Keep this
// fragment side-effect free so it never attempts to send headers mid-page.
require_once __DIR__ . '/config/role_helpers.php';

$sql = "
    SELECT 
        a.id,
        a.application_id,
        CONCAT(a.first_name, ' ', a.surname) AS full_name,
        a.email,
        a.mobile_number,
        a.company_name,
        a.job_title,
        a.status,
        a.created_at
    FROM agents a
    ORDER BY a.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingApps = $pdo->query("
    SELECT id, application_number, CONCAT(first_name, ' ', surname) AS full_name, email, mobile_number, company_name, submission_date
    FROM agent_applications
    WHERE status IN ('submitted','under_review')
    ORDER BY submission_date ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

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
        <?php if (!empty($pendingApps)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Pending Agent Applications <span class="badge bg-warning"><?= count($pendingApps) ?></span></h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Application No</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Company</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingApps as $app): ?>
                                        <tr id="app-row-<?= $app['id'] ?>">
                                            <td><?= htmlspecialchars($app['application_number']) ?></td>
                                            <td><?= htmlspecialchars($app['full_name']) ?></td>
                                            <td><?= htmlspecialchars($app['email']) ?></td>
                                            <td><?= htmlspecialchars($app['mobile_number']) ?></td>
                                            <td><?= htmlspecialchars($app['company_name']) ?></td>
                                            <td><?= $app['submission_date'] ? date('d M Y', strtotime($app['submission_date'])) : '' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-success application-decision" data-id="<?= $app['id'] ?>" data-decision="approve">Approve</button>
                                                <button class="btn btn-sm btn-outline-danger application-decision" data-id="<?= $app['id'] ?>" data-decision="reject">Reject</button>
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
        <?php endif; ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">All Agents</h4>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                                                        <table id="agents_table" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Application No</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Company</th>
                                        <th>Job Title</th>
                                        <th>Status</th>
                                        <th>Date Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php if (!empty($agents)): ?>
                                    <?php foreach ($agents as $index => $agent): ?>
                                        <tr>

                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($agent['application_id']) ?></td>
                                            <td><?= htmlspecialchars($agent['full_name']) ?></td>
                                            <td><?= htmlspecialchars($agent['email']) ?></td>
                                            <td><?= htmlspecialchars($agent['mobile_number']) ?></td>
                                            <td><?= htmlspecialchars($agent['company_name']) ?></td>
                                            <td><?= htmlspecialchars($agent['job_title']) ?></td>

                                            <!-- STATUS COLUMN -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">

                                                    <!-- Current status badge -->
                                                    <span
                                                        id="status-badge-<?= $agent['id'] ?>"
                                                        class="badge bg-<?=
                                                            $agent['status'] === 'approved' ? 'success' :
                                                            ($agent['status'] === 'pending' ? 'warning' :
                                                            ($agent['status'] === 'active' ? 'primary' :
                                                            ($agent['status'] === 'suspended' ? 'danger' : 'secondary')))
                                                        ?>"
                                                    >
                                                        <?= ucfirst($agent['status']) ?>
                                                    </span>

                                                    <!-- Inline loader -->
                                                    <span
                                                        id="status-loader-<?= $agent['id'] ?>"
                                                        class="spinner-border spinner-border-sm text-primary d-none"
                                                    ></span>

                                                    <!-- Status select -->
                                                    <div>
                                                        <label class="form-label small mb-0">Change Status</label>
                                                        <select
                                                            class="form-select form-select-sm agent-status"
                                                            data-id="<?= $agent['id'] ?>"
                                                        >
                                                            <?php
                                                            $statuses = ['pending','approved','active','suspended','rejected'];
                                                            foreach ($statuses as $status):
                                                            ?>
                                                                <option value="<?= $status ?>"
                                                                    <?= $agent['status'] === $status ? 'selected' : '' ?>>
                                                                    <?= ucfirst($status) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                </div>
                                            </td>

                                            <td><?= date('d M Y', strtotime($agent['created_at'])) ?></td>

                                            <!-- ACTIONS -->
                                            <td>
                                                <div class="btn-group">
													<?php 
											$encodedId = portalEncodeId((int)$agent['id']);
													$url = "agent_profile.php?id=$encodedId";

													?>
                                                    <a href="<?= $url ?>"
                                                       class="btn btn-sm btn-info">
                                                        View
                                                    </a>

                                                    <button
                                                        class="btn btn-sm btn-danger delete-agent"
                                                        data-id="<?= $agent['id'] ?>">
                                                        Delete
                                                    </button>

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
    </div>

    <footer class="footer">
        All Rights Reserved by Nuru.
    </footer>
</div>

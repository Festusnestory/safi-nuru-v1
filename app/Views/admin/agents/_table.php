<?php
/**
 * Guarded partial mirroring html/material/agenttable_list.php's define+include
 * idiom (itself mirroring app/Views/admin/sellers/_table.php) - only reachable
 * by being require()'d from list.php's NURU_AGENT_LIST_INCLUDE block, never
 * directly (there is no route to this file). $agents/$pendingApps/$baseUrl are
 * inherited from list.php's scope, same as the original partial inherited them
 * from agent_list.php.
 */
if (!defined('NURU_AGENT_LIST_INCLUDE')) {
    http_response_code(404);
    exit('Not found.');
}

/** @var array $agents */
/** @var array $pendingApps */
/** @var string $baseUrl */
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
													$url = $baseUrl . "/admin/agent-profile?id=$encodedId";

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

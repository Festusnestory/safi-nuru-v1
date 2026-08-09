<?php
/** @var string $entity */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string $dateError */
/** @var array $config */
/** @var array $rows */
$pageTitle = 'Custom Report';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Custom Report</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($dateError): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($dateError) ?></div><?php endif; ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="get" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label" for="report-entity">Entity</label>
                                <select id="report-entity" name="entity" class="form-select">
                                    <option value="buyers" <?= $entity === 'buyers' ? 'selected' : '' ?>>Buyers</option>
                                    <option value="agents" <?= $entity === 'agents' ? 'selected' : '' ?>>Agents</option>
                                    <option value="properties" <?= $entity === 'properties' ? 'selected' : '' ?>>Properties</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date-from">From</label>
                                <input id="date-from" type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date-to">To</label>
                                <input id="date-to" type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Run Report</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="border-bottom title-part-padding">
                        <h4 class="card-title mb-0">Results (<?= count($rows) ?>)</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr><?php foreach ($config['cols'] as $c): ?><th><?= ucwords(str_replace('_', ' ', $c)) ?></th><?php endforeach; ?></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr><?php foreach ($config['cols'] as $c): ?><td><?= htmlspecialchars((string)$row[$c]) ?></td><?php endforeach; ?></tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($rows)): ?>
                                        <tr><td colspan="<?= count($config['cols']) ?>" class="text-center text-muted">No results for this filter.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">All Rights Reserved by Nuru.</footer>
</div>
</div>
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>

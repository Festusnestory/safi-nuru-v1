<?php
session_start();

require_once __DIR__ . '/config/pdo.php';
require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/includes/functions.php';

requireRole(['admin', 'manager']);

$allowedStatuses = ['new', 'contacted', 'qualified', 'closed', 'spam'];
$statusLabels = [
    'new' => 'New',
    'contacted' => 'Contacted',
    'qualified' => 'Qualified',
    'closed' => 'Closed',
    'spam' => 'Spam',
];
$statusClasses = [
    'new' => 'bg-primary',
    'contacted' => 'bg-info',
    'qualified' => 'bg-success',
    'closed' => 'bg-secondary',
    'spam' => 'bg-danger',
];
$filter = isset($_GET['status']) && in_array($_GET['status'], $allowedStatuses, true)
    ? $_GET['status']
    : 'all';
$csrf = csrfToken('public_inquiry_status');
$pageError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiryId = filter_var(
        $_POST['inquiry_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $newStatus = (string)($_POST['status'] ?? '');
    $returnFilter = isset($_POST['return_status']) && in_array($_POST['return_status'], $allowedStatuses, true)
        ? $_POST['return_status']
        : 'all';

    if (!validCsrfToken($_POST['csrf_token'] ?? null, 'public_inquiry_status')) {
        $pageError = 'Your session expired. Refresh the page and try again.';
        http_response_code(400);
    } elseif ($inquiryId === false || !in_array($newStatus, $allowedStatuses, true)) {
        $pageError = 'The enquiry or status selection is invalid.';
        http_response_code(422);
    } else {
        try {
            $statement = $pdo->prepare(
                'UPDATE public_inquiries
                 SET status = ?,
                     assigned_to = CASE
                         WHEN ? = \'new\' THEN assigned_to
                         ELSE COALESCE(assigned_to, ?)
                     END
                 WHERE id = ?'
            );
            $statement->execute([
                $newStatus,
                $newStatus,
                (int)$_SESSION['user_id'],
                (int)$inquiryId,
            ]);

            if ($statement->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT 1 FROM public_inquiries WHERE id = ?');
                $exists->execute([(int)$inquiryId]);
                if (!$exists->fetchColumn()) {
                    throw new RuntimeException('The enquiry no longer exists.');
                }
            }

            logActivity(
                (int)$_SESSION['user_id'],
                'PUBLIC_INQUIRY_STATUS_UPDATED',
                "Updated website enquiry #{$inquiryId} to {$newStatus}",
                'public_inquiries'
            );

            $location = 'public-inquiries.php?updated=1';
            if ($returnFilter !== 'all') {
                $location .= '&status=' . rawurlencode($returnFilter);
            }
            header('Location: ' . $location, true, 303);
            exit;
        } catch (Throwable $error) {
            error_log('Website enquiry update failed: ' . $error->getMessage());
            $pageError = $error instanceof RuntimeException
                ? $error->getMessage()
                : 'The enquiry could not be updated. Please try again.';
            http_response_code(500);
        }
    }
}

$inquiries = [];
$statusCounts = array_fill_keys($allowedStatuses, 0);

try {
    $countRows = $pdo->query(
        'SELECT status, COUNT(*) AS total
         FROM public_inquiries
         GROUP BY status'
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($countRows as $countRow) {
        if (isset($statusCounts[$countRow['status']])) {
            $statusCounts[$countRow['status']] = (int)$countRow['total'];
        }
    }

    $sql = 'SELECT
                pi.id,
                pi.property_id,
                pi.full_name,
                pi.email,
                pi.phone,
                pi.interest,
                pi.message,
                pi.status,
                pi.source_page,
                pi.created_at,
                sp.property_detail_type,
                sp.property_town,
                au.full_name AS assigned_name
            FROM public_inquiries pi
            LEFT JOIN seller_properties sp ON sp.id = pi.property_id
            LEFT JOIN admin_users au ON au.id = pi.assigned_to';
    $parameters = [];
    if ($filter !== 'all') {
        $sql .= ' WHERE pi.status = ?';
        $parameters[] = $filter;
    }
    $sql .= " ORDER BY FIELD(pi.status, 'new', 'contacted', 'qualified', 'closed', 'spam'),
                     pi.created_at DESC
              LIMIT 500";

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    $inquiries = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    error_log('Website enquiries unavailable: ' . $error->getMessage());
    $pageError = 'Website enquiries are temporarily unavailable. Please try again.';
    http_response_code(500);
}

$totalInquiries = array_sum($statusCounts);

function inquiryHtml(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Website Enquiries - Nuru</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon.png">
    <link href="../../dist/css/style.min.css" rel="stylesheet">
    <style>
        .inquiry-message { min-width: 260px; max-width: 420px; white-space: normal; }
        .inquiry-contact { min-width: 210px; }
        .inquiry-status { min-width: 180px; }
        .filter-pills .btn { margin: 0 .35rem .5rem 0; }
    </style>
</head>
<body>
<div class="preloader"></div>
<div id="main-wrapper">
    <?php include __DIR__ . '/top-bar.php'; ?>
    <?php include __DIR__ . '/left-sidebar.php'; ?>

    <div class="page-wrapper">
        <div class="row page-titles">
            <div class="col-md-7 col-12 align-self-center">
                <h3 class="text-themecolor mb-0">Website Enquiries</h3>
                <ol class="breadcrumb mb-0 p-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= inquiryHtml(roleHomeRoute()) ?>">Home</a></li>
                    <li class="breadcrumb-item active">Website Enquiries</li>
                </ol>
            </div>
        </div>

        <div class="container-fluid">
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Enquiry status updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($pageError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= inquiryHtml($pageError) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div class="me-3">
                            <h4 class="card-title">Public website leads</h4>
                            <p class="text-muted mb-2">Review, assign, and progress contact or property enquiries submitted through the public website.</p>
                        </div>
                        <span class="badge bg-primary fs-6"><?= $totalInquiries ?> total</span>
                    </div>

                    <nav class="filter-pills mb-3" aria-label="Filter enquiries by status">
                        <a class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>"
                           href="public-inquiries.php">All (<?= $totalInquiries ?>)</a>
                        <?php foreach ($allowedStatuses as $status): ?>
                            <a class="btn btn-sm <?= $filter === $status ? 'btn-primary' : 'btn-outline-primary' ?>"
                               href="public-inquiries.php?status=<?= inquiryHtml($status) ?>">
                                <?= inquiryHtml($statusLabels[$status]) ?> (<?= $statusCounts[$status] ?>)
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Contact</th>
                                <th>Interest</th>
                                <th>Property</th>
                                <th>Message</th>
                                <th>Assigned to</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($inquiries as $row): ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <?= inquiryHtml(date('d M Y', strtotime($row['created_at']))) ?><br>
                                        <small class="text-muted"><?= inquiryHtml(date('H:i', strtotime($row['created_at']))) ?></small>
                                    </td>
                                    <td class="inquiry-contact">
                                        <strong><?= inquiryHtml($row['full_name']) ?></strong><br>
                                        <a href="mailto:<?= inquiryHtml($row['email']) ?>"><?= inquiryHtml($row['email']) ?></a><br>
                                        <a href="tel:<?= inquiryHtml($row['phone']) ?>"><?= inquiryHtml($row['phone']) ?></a>
                                    </td>
                                    <td><?= inquiryHtml(ucfirst($row['interest'])) ?></td>
                                    <td>
                                        <?php if ($row['property_id']): ?>
                                            <strong><?= inquiryHtml($row['property_detail_type'] ?: 'Property') ?></strong><br>
                                            <small class="text-muted">
                                                <?= inquiryHtml($row['property_town'] ?: 'Property #' . $row['property_id']) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">General enquiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="inquiry-message">
                                        <?= nl2br(inquiryHtml($row['message'])) ?>
                                        <?php if (!empty($row['source_page'])): ?>
                                            <div class="small text-muted mt-2">Source: <?= inquiryHtml($row['source_page']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= inquiryHtml($row['assigned_name'] ?: 'Unassigned') ?></td>
                                    <td class="inquiry-status">
                                        <span class="badge <?= inquiryHtml($statusClasses[$row['status']] ?? 'bg-secondary') ?> mb-2">
                                            <?= inquiryHtml($statusLabels[$row['status']] ?? ucfirst($row['status'])) ?>
                                        </span>
                                        <form method="post" action="public-inquiries.php" class="d-flex gap-1">
                                            <input type="hidden" name="csrf_token" value="<?= inquiryHtml($csrf) ?>">
                                            <input type="hidden" name="inquiry_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="return_status" value="<?= inquiryHtml($filter) ?>">
                                            <label class="visually-hidden" for="status-<?= (int)$row['id'] ?>">Update status</label>
                                            <select class="form-select form-select-sm" id="status-<?= (int)$row['id'] ?>" name="status">
                                                <?php foreach ($allowedStatuses as $status): ?>
                                                    <option value="<?= inquiryHtml($status) ?>" <?= $row['status'] === $status ? 'selected' : '' ?>>
                                                        <?= inquiryHtml($statusLabels[$status]) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$inquiries): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <?= $filter === 'all' ? 'No website enquiries yet.' : 'No enquiries have this status.' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">© <?= date('Y') ?> Nuru Real Estate</footer>
    </div>
</div>

<script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../dist/js/app.min.js"></script>
<script src="../../dist/js/app.init.js"></script>
<script src="../../dist/js/app-style-switcher.js"></script>
<script src="../../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="../../dist/js/waves.js"></script>
<script src="../../dist/js/sidebarmenu.js?v=20260720"></script>
    <script src="../../dist/js/feather.min.js"></script>
    <script src="../../dist/js/custom.min.js"></script>
</body>
</html>

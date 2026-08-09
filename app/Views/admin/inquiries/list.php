<?php
/** @var array $allowedStatuses */
/** @var array $statusLabels */
/** @var array $statusClasses */
/** @var string $filter */
/** @var string $csrf */
/** @var string $pageError */
/** @var array $inquiries */
/** @var array $statusCounts */
/** @var int $totalInquiries */
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Website Enquiries - Nuru</title>
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/images/favicon.png">
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet">
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
    <?php require NURU_MATERIAL . '/top-bar.php'; ?>
    <?php require NURU_MATERIAL . '/left-sidebar.php'; ?>

    <div class="page-wrapper">
        <div class="row page-titles">
            <div class="col-md-7 col-12 align-self-center">
                <h3 class="text-themecolor mb-0">Website Enquiries</h3>
                <ol class="breadcrumb mb-0 p-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars(roleHomeRoute(), ENT_QUOTES, 'UTF-8') ?>">Home</a></li>
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
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></div>
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
                               href="public-inquiries.php?status=<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($statusLabels[$status], ENT_QUOTES, 'UTF-8') ?> (<?= $statusCounts[$status] ?>)
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-bordered">
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
                                        <?= htmlspecialchars(date('d M Y', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?><br>
                                        <small class="text-muted"><?= htmlspecialchars(date('H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td class="inquiry-contact">
                                        <strong><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <a href="mailto:<?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></a><br>
                                        <a href="tel:<?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?></a>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst($row['interest']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($row['property_id']): ?>
                                            <strong><?= htmlspecialchars($row['property_detail_type'] ?: 'Property', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($row['property_town'] ?: 'Property #' . $row['property_id'], ENT_QUOTES, 'UTF-8') ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">General enquiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="inquiry-message">
                                        <?= nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8')) ?>
                                        <?php if (!empty($row['source_page'])): ?>
                                            <div class="small text-muted mt-2">Source: <?= htmlspecialchars($row['source_page'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['assigned_name'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="inquiry-status">
                                        <span class="badge <?= htmlspecialchars($statusClasses[$row['status']] ?? 'bg-secondary', ENT_QUOTES, 'UTF-8') ?> mb-2">
                                            <?= htmlspecialchars($statusLabels[$row['status']] ?? ucfirst($row['status']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <form method="post" action="public-inquiries.php" class="d-flex gap-1">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="inquiry_id" value="<?= (int) $row['id'] ?>">
                                            <input type="hidden" name="return_status" value="<?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>">
                                            <label class="visually-hidden" for="status-<?= (int) $row['id'] ?>">Update status</label>
                                            <select class="form-select form-select-sm" id="status-<?= (int) $row['id'] ?>" name="status">
                                                <?php foreach ($allowedStatuses as $status): ?>
                                                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $row['status'] === $status ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($statusLabels[$status], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">© <?= date('Y') ?> Nuru Real Estate</footer>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/libs/jquery/dist/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/app.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/app.init.js"></script>
<script src="<?= $baseUrl ?>/dist/js/app-style-switcher.js"></script>
<script src="<?= $baseUrl ?>/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="<?= $baseUrl ?>/dist/js/waves.js"></script>
<script src="<?= $baseUrl ?>/dist/js/sidebarmenu.js?v=20260720"></script>
    <script src="<?= $baseUrl ?>/dist/js/feather.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/custom.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons extension (Copy/CSV/Excel/PDF/Print export) - not
         vendored locally, loaded the same way the login page's Cloudflare
         Turnstile widget already is. -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script>
    $(".table-bordered").DataTable({
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
  language: { emptyTable: "No records found." },
    });
    $(".buttons-copy, .buttons-csv, .buttons-print, .buttons-pdf, .buttons-excel").addClass("btn btn-primary mr-1");
    </script>
</body>
</html>

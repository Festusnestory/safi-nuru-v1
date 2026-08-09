<?php require_once __DIR__ . '/../../app/autoload.php'; $__nuruBase = \App\Core\Router::basePath(); ?>
<script src="<?= $__nuruBase ?>/assets/libs/jquery/dist/jquery.min.js"></script>
<script src="<?= $__nuruBase ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $__nuruBase ?>/dist/js/app.min.js"></script>
<script src="<?= $__nuruBase ?>/dist/js/app.init.js"></script>
<script src="<?= $__nuruBase ?>/dist/js/app-style-switcher.js"></script>
<script src="<?= $__nuruBase ?>/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="<?= $__nuruBase ?>/assets/extra-libs/sparkline/sparkline.js"></script>
<script src="<?= $__nuruBase ?>/dist/js/waves.js"></script>
<script src="<?= $__nuruBase ?>/dist/js/sidebarmenu.js?v=20260720"></script>
<script src="<?= $__nuruBase ?>/dist/js/feather.min.js"></script>
<script src="<?= $__nuruBase ?>/dist/js/custom.min.js"></script>
<script src="<?= $__nuruBase ?>/assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="<?= $__nuruBase ?>/assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
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
// Blanket init for any list page that hasn't opted into its own explicit
// DataTable() call - gives every plain .table-bordered table a working
// search box, pagination, and the full export toolbar for free.
(function () {
    var exportButtonsAvailable = Boolean($.fn.dataTable && $.fn.dataTable.Buttons);
    var options = exportButtonsAvailable
        ? { dom: "Bfrtip", buttons: ["copy", "csv", "excel", "pdf", "print"] }
        : {};
    $(".table-bordered").each(function () {
        if ($.fn.DataTable.isDataTable(this)) {
            return;
        }
        $(this).DataTable(options);
    });
    if (exportButtonsAvailable) {
        $(".buttons-copy, .buttons-csv, .buttons-print, .buttons-pdf, .buttons-excel")
            .addClass("btn btn-primary mr-1");
    }
})();
</script>

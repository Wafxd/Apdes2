<?php
$pageTitle = "Dashboard";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<!-- Content Row -->

<!-- More content rows... -->
<?php
$content = ob_get_clean();
include 'template1/base.php';
?>
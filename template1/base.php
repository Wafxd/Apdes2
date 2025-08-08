<?php include 'includes/head.php'; ?>

<!-- Page Wrapper -->
<div id="wrapper">

    <?php include 'includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <?php include 'includes/navbar.php'; ?>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                    <?php if(isset($pageHeaderButton)): ?>
                        <?php echo $pageHeaderButton; ?>
                    <?php endif; ?>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <?php echo $content ?? ''; ?>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        <?php include 'includes/footer.php'; ?>

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<?php include 'includes/scripts.php'; ?>
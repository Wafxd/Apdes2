<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistem Administrasi Desa Sukolilo Timur">
    <meta name="author" content="">

    <title><?php echo $pageTitle ?? 'APDES Sukolilo Timur'; ?></title>

    <!-- Bootstrap CSS (untuk modal dan komponen) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom fonts for this template-->
    <link href="<?php echo $root_path; ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?php echo $root_path; ?>vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="<?php echo $root_path; ?>css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        /* Menyesuaikan modal dengan tema sb-admin-2 */
        .modal-content {
            border: none;
            border-radius: 0.5rem;
        }
        .modal-header {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            padding: 1rem 1.5rem;
        }
        .modal-header.bg-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        .modal-header.bg-danger {
            background: linear-gradient(135deg, #e74a3b 0%, #c82333 100%);
        }
        .modal-header.bg-success {
            background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
        }
        .modal-header.bg-info {
            background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%);
        }
        .modal-header.bg-warning {
            background: linear-gradient(135deg, #f6c23e 0%, #e0a800 100%);
        }
        .modal-footer {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            padding: 1rem 1.5rem;
        }
        .btn-close-white {
            filter: brightness(0) invert(1);
        }
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #d1d3e2;
            margin-top: 10px;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            margin-right: 5px;
            transition: 0.3s;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-delete:hover {
            background: #c82333;
        }
    </style>
</head>

<body id="page-top">
    <!-- Bootstrap JS (untuk modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JQuery (untuk sb-admin-2) -->
    <script src="<?php echo $root_path; ?>vendor/jquery/jquery.min.js"></script>
    <script src="<?php echo $root_path; ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?php echo $root_path; ?>vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?php echo $root_path; ?>js/sb-admin-2.min.js"></script>
    
    <!-- Page level plugins -->
    <script src="<?php echo $root_path; ?>vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo $root_path; ?>vendor/datatables/dataTables.bootstrap4.min.js"></script>
</body>
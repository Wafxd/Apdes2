<?php
session_start();

include "../db/funct.php";

if (isset($_SESSION['id_admin']) && isset($_SESSION['nama_admin'])) {
    $id_user = $_SESSION['id_admin'];
    $username = $_SESSION["nama_admin"];
    $nama = query("SELECT * FROM tb_admin WHERE id_admin = '$id_user'")[0];
} else {
    $id_user = null;
    $username = null;
    $nama = null;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>
        <?php
        echo "halo $username";
        ?>
    </h1>
</body>
</html>
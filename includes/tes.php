<?php
session_start();
include "../db/funct.php";

if (isset($_POST["submit"])) {
    if (!empty($_POST["nama_admin"]) && !empty($_POST["password"])) {

        $username = mysqli_real_escape_string($conn, $_POST["nama_admin"]);
        $password = $_POST["password"];

        $query = "SELECT * FROM tb_admin WHERE nama_admin = '$username'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION["id_admin"] = $user["id_admin"];
            $_SESSION["nama_admin"] = $user["nama_admin"];
            if ($password == $user['password']) {
                echo "<script>
                    alert('Login sukses'); 
                    window.location.href = 'tes2.php';
                </script>";
            } else {
                echo "<script>
                    alert('Password Salah, silahkan coba lagi.');
                    history.back();
                </script>";
            }
        }else {
            echo "<script>
                alert('Username tidak Ditemukan, silahkan daftar terlebih dahulu jika belum memiliki akun');
                history.back();
            </script>";
        }
    }
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
    <form class="user" method="post">
        <div >
            <input type="text" name="nama_admin" class="form-control form-control-user"
                id="username" placeholder="Nama Pengguna">
        </div>
        <div >
            <input type="password" 
                id="password" name="password" placeholder="Kata Sandi">
        </div>
        <button type="submit" name="submit">
            masuk
        </button>
    </form>
</body>
</html>
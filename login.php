<?php
// File: login.php
session_start();

// Konfigurasi keamanan
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60);
define('SESSION_TIMEOUT', 30 * 60);

// Proteksi Session Fixation
if (!isset($_SESSION['INITIATED'])) {
    session_regenerate_id(true);
    $_SESSION['INITIATED'] = true;
    $_SESSION['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
}

// Cek session hijacking
if (isset($_SESSION['IP_ADDRESS']) && $_SESSION['IP_ADDRESS'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_hijack");
    exit();
}

if (isset($_SESSION['USER_AGENT']) && $_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_hijack");
    exit();
}

// Cek session timeout
if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=timeout");
        exit();
    }
}

// Cek jika user sudah login, redirect ke dashboard
if (isset($_SESSION["id_admin"]) && isset($_SESSION["nama_admin"])) {
    header("Location: admin/dashboard.php");
    exit();
}

// Update last activity
$_SESSION['LAST_ACTIVITY'] = time();

include "db/koneksi.php";

// Inisialisasi array percobaan login
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [
        'count' => 0,
        'first_attempt' => time(),
        'locked' => false
    ];
}

// Cek apakah akun sedang di-lock
if ($_SESSION['login_attempts']['locked']) {
    $lockout_end = $_SESSION['login_attempts']['first_attempt'] + LOCKOUT_TIME;
    if (time() < $lockout_end) {
        $remaining_time = ceil(($lockout_end - time()) / 60);
        $error_message = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam $remaining_time menit.";
    } else {
        $_SESSION['login_attempts'] = [
            'count' => 0,
            'first_attempt' => time(),
            'locked' => false
        ];
    }
}

$error_message = '';
$success_message = '';

// CEK APAKAH TABEL log_aktivitas ADA
$log_table_exists = false;
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'log_aktivitas'");
if ($check_table && mysqli_num_rows($check_table) > 0) {
    $log_table_exists = true;
}

// Fungsi log yang aman - TIDAK PAKAI FOREIGN KEY CONSTRAINT
function safeLogActivity($conn, $id_admin, $action, $status, $details = '') {
    global $log_table_exists;
    
    if (!$log_table_exists) {
        return true; // Skip jika tabel tidak ada
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $timestamp = date('Y-m-d H:i:s');
    
    // Escape semua input
    $id_admin = (int)$id_admin; // Pastikan integer
    if ($id_admin <= 0) {
        $id_admin = 1; // Ganti dengan ID admin default jika perlu
    }
    
    $action = mysqli_real_escape_string($conn, $action);
    $status = mysqli_real_escape_string($conn, $status);
    $details = mysqli_real_escape_string($conn, $details);
    $ip_address = mysqli_real_escape_string($conn, $ip_address);
    $user_agent = mysqli_real_escape_string($conn, $user_agent);
    
    $query = "INSERT INTO log_aktivitas (id_admin, action, status, details, ip_address, user_agent, timestamp) 
              VALUES ('$id_admin', '$action', '$status', '$details', '$ip_address', '$user_agent', '$timestamp')";
    
    // Gunakan @ untuk suppress error
    @mysqli_query($conn, $query);
    return true;
}

if (isset($_POST["submit"])) {
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token!";
    }
    // Cek apakah akun sedang di-lock
    elseif ($_SESSION['login_attempts']['locked']) {
        $lockout_end = $_SESSION['login_attempts']['first_attempt'] + LOCKOUT_TIME;
        if (time() < $lockout_end) {
            $remaining_time = ceil(($lockout_end - time()) / 60);
            $error_message = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam $remaining_time menit.";
        } else {
            $_SESSION['login_attempts'] = [
                'count' => 0,
                'first_attempt' => time(),
                'locked' => false
            ];
        }
    }
    
    if (empty($error_message)) {
        if (empty($_POST["nama_admin"]) || empty($_POST["password"])) {
            $error_message = "Harap isi semua field!";
        } else {
            $username = mysqli_real_escape_string($conn, trim($_POST["nama_admin"]));
            $password = $_POST["password"];
            
            // QUERY SEDERHANA
            $query = "SELECT id_admin, nama_admin, password FROM tb_admin WHERE nama_admin = '$username'";
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                

                // VERIFIKASI PASSWORD - Support hash dan plain text
                    if (password_verify($password, $user['password']) || $password === $user['password']) {
                    // LOGIN SUKSES
                    
                    // Reset percobaan login
                    $_SESSION['login_attempts'] = [
                        'count' => 0,
                        'first_attempt' => time(),
                        'locked' => false
                    ];
                    
                    // Regenerasi session ID
                    session_regenerate_id(true);
                    
                    // Set session
                    $_SESSION["id_admin"] = $user["id_admin"];
                    $_SESSION["nama_admin"] = $user["nama_admin"];
                    $_SESSION["login_time"] = time();
                    $_SESSION["LAST_ACTIVITY"] = time();
                    
                    // Log sukses (jika tabel ada)
                    safeLogActivity($conn, $user["id_admin"], 'login', 'success', 'Login berhasil');
                    
                    // REDIRECT LANGSUNG
                    header("Location: admin/dashboard.php");
                    exit();
                    
                } else {
                    // PASSWORD SALAH
                    $_SESSION['login_attempts']['count']++;
                    
                    // Cek apakah perlu lockout
                    if ($_SESSION['login_attempts']['count'] >= MAX_LOGIN_ATTEMPTS) {
                        $_SESSION['login_attempts']['locked'] = true;
                        $_SESSION['login_attempts']['first_attempt'] = time();
                        $error_message = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam 15 menit.";
                    } else {
                        $remaining_attempts = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts']['count'];
                        $error_message = "Password salah! Sisa percobaan: $remaining_attempts";
                    }
                    
                    // Log gagal - GUNAKAN ID 1 (admin default)
                    safeLogActivity($conn, 1, 'login', 'failed', 'Password salah');
                }
            } else {
                // USERNAME TIDAK DITEMUKAN
                $_SESSION['login_attempts']['count']++;
                
                if ($_SESSION['login_attempts']['count'] >= MAX_LOGIN_ATTEMPTS) {
                    $_SESSION['login_attempts']['locked'] = true;
                    $_SESSION['login_attempts']['first_attempt'] = time();
                    $error_message = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam 15 menit.";
                } else {
                    $remaining_attempts = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts']['count'];
                    $error_message = "Username tidak ditemukan! Sisa percobaan: $remaining_attempts";
                }
                
                // Log gagal - GUNAKAN ID 1 (admin default)
                safeLogActivity($conn, 1, 'login', 'failed', 'Username tidak ditemukan: ' . $username);
            }
        }
    }
}

// Generate CSRF Token sederhana
$_SESSION['csrf_token'] = md5(uniqid(rand(), true));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistem Administrasi Desa Sukolilo Timur">
    <meta name="author" content="Desa Sukolilo Timur">
    <meta name="robots" content="noindex, nofollow">
    
    <title>Login | APDES Sukolilo Timur</title>

    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">

    <!-- Custom fonts -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom styles -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom enhancements (TETAP SAMA DENGAN TAMPILAN ANDA) -->
    <style>
        :root {
            --primary-color: #00e1ff;
            --secondary-color: #00b9fc;
            --accent-color: #f8f9fc;
            --error-color: #e74a3b;
            --success-color: #1cc88a;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-x: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
        
        .login-container {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border-radius: 20px;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .bg-login-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%), url('img/suramadu.JPG');
            background-size: cover;
            background-blend-mode: overlay;
            position: relative;
            transition: all 0.5s ease;
            min-height: 500px;
        }
        
        .bg-login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8));
            transition: all 0.5s ease;
        }
        
        .login-content {
            padding: 3rem;
            animation: fadeIn 0.8s ease-out 0.3s both;
            background: white;
        }
        
        .welcome-text {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }
        
        .welcome-text::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }
        
        .form-control-user {
            border-radius: 10px;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            font-size: 0.9rem;
        }
        
        .form-control-user:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 225, 255, 0.2);
            outline: none;
        }
        
        .btn-user {
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 15px rgba(0, 225, 255, 0.4);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 185, 252, 0.5);
        }
        
        .btn-primary:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            font-weight: 500;
            animation: slideInDown 0.5s ease-out;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #feb2b2 0%, #fc8181 100%);
            color: #742a2a;
            border-left: 4px solid #c53030;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #9ae6b4 0%, #68d391 100%);
            color: #22543d;
            border-left: 4px solid #2f855a;
        }
        
        .brand-logo {
            margin-bottom: 2rem;
            display: inline-block;
            animation: bounceIn 0.8s ease-out;
        }
        
        .brand-logo img {
            height: 90px;
            transition: transform 0.3s;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }
        
        .brand-logo:hover img {
            transform: scale(1.05) rotate(-2deg);
        }
        
        .desa-info {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            padding: 0 30px;
            color: white;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease-out 0.5s both;
            z-index: 1;
        }
        
        .desa-info h3 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
            letter-spacing: 1px;
        }
        
        .desa-info p {
            opacity: 0.95;
            font-size: 1rem;
            font-weight: 400;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        .security-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 8px 15px;
            border-radius: 50px;
            color: white;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 2;
        }
        
        .security-badge i {
            margin-right: 5px;
            color: #00ff9d;
        }
        
        .input-group {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            z-index: 10;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        /* Animations */
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-content {
                padding: 2rem;
            }
            
            .bg-login-image {
                min-height: 200px;
            }
            
            .desa-info h3 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <div class="security-badge animate__animated animate__fadeIn">
        <i class="fas fa-shield-alt"></i> Sistem Keamanan Terenkripsi
    </div>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-4">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="login-container">
                    <div class="row no-gutters">
                        <div class="col-lg-6 d-none d-lg-block bg-login-image">
                            <div class="desa-info">
                                <h3 class="floating">Desa Sukolilo Timur</h3>
                                <p>Kab. Bangkalan, Kecamatan Labang</p>
                                <div class="mt-4">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <i class="fas fa-phone-alt mr-2"></i>
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="login-content">
                                <div class="text-center mb-4">
                                    <div class="brand-logo">
                                        <img src="img/labang.png" alt="Logo APDES">
                                    </div>
                                    <h2 class="welcome-text">APDES Sukolilo Timur</h2>
                                    <p class="text-muted">Sistem Administrasi Desa Digital</p>
                                </div>
                                
                                <?php if ($error_message): ?>
                                    <div class="alert alert-danger animate__animated animate__shakeX" role="alert">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <?php echo htmlspecialchars($error_message); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($success_message): ?>
                                    <div class="alert alert-success animate__animated animate__fadeIn" role="alert">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <?php echo htmlspecialchars($success_message); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form class="user" method="post" id="loginForm" autocomplete="off">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="username" class="text-muted small">Nama Pengguna</label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   name="nama_admin" 
                                                   class="form-control form-control-user"
                                                   id="username" 
                                                   placeholder="Masukkan nama pengguna"
                                                   autocomplete="off"
                                                   required
                                                   maxlength="50"
                                                   value="<?php echo isset($_POST['nama_admin']) ? htmlspecialchars($_POST['nama_admin']) : ''; ?>">
                                            <i class="fas fa-user toggle-password" style="right: 15px; pointer-events: none;"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password" class="text-muted small">Kata Sandi</label>
                                        <div class="position-relative">
                                            <input type="password" 
                                                   class="form-control form-control-user"
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Masukkan kata sandi"
                                                   autocomplete="off"
                                                   required>
                                            <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" 
                                            name="submit" 
                                            class="btn btn-primary btn-user btn-block mt-4"
                                            id="loginButton">
                                        <i class="fas fa-sign-in-alt mr-2"></i> 
                                        <span class="button-text">Masuk</span>
                                        <span class="spinner" style="display: none;"></span>
                                    </button>
                                </form>
                                
                                <div class="text-center mt-4">
                                    <hr class="my-3">
                                    <p class="small text-muted mb-2">
                                        <i class="fas fa-lock mr-1"></i> 
                                        Koneksi Aman
                                    </p>
                                    <p class="small text-muted">
                                        &copy; <?php echo date('Y'); ?> APDES Sukolilo Timur.<br>
                                        Hak Cipta Dilindungi
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    
    <!-- Custom animations and security -->
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.fa-eye');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Simple form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Harap isi semua field!');
                return false;
            }
        });
    </script>
</body>
</html>
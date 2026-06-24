<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../koneksi.php';

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '⚠️ Username dan password wajib diisi.';
    } else {
        $statement = $pdo->prepare('SELECT * FROM admin WHERE username = ? LIMIT 1');
        $statement->execute([$username]);
        $admin = $statement->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id' => (int) $admin['id_admin'],
                'username' => $admin['username'],
                'nama' => $admin['nama_admin'],
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = '❌ Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Perpus Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0a0f2c 0%, #1a1a3e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        
        /* ==================== PARALLAX BACKGROUND SLASH ==================== */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 40px,
                rgba(214, 168, 79, 0.05) 40px,
                rgba(214, 168, 79, 0.08) 80px
            );
            animation: slashMoveRight 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 60px,
                rgba(214, 168, 79, 0.03) 60px,
                rgba(214, 168, 79, 0.05) 120px
            );
            animation: slashMoveLeft 25s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes slashMoveRight {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(80px) translateY(80px); }
        }
        
        @keyframes slashMoveLeft {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-80px) translateY(-80px); }
        }
        
        /* ==================== CARD LOGIN ==================== */
        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 32px;
            padding: 40px 32px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(20px);
            animation: cardFloatIn 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            overflow: hidden;
        }
        
        @keyframes cardFloatIn {
            0% {
                opacity: 0;
                transform: translateY(50px) scale(0.96);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3);
        }
        
        /* ==================== SLASH DI PINGGIR CARD ==================== */
        .slash-top {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #d6a84f, #f4d991, transparent);
            animation: slashTop 4s ease-in-out infinite;
            pointer-events: none;
        }
        
        .slash-bottom {
            position: absolute;
            bottom: 0;
            right: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(270deg, transparent, #d6a84f, #f4d991, transparent);
            animation: slashBottom 4s ease-in-out infinite;
            pointer-events: none;
        }
        
        .slash-left {
            position: absolute;
            top: -100%;
            left: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, transparent, #d6a84f, #f4d991, transparent);
            animation: slashLeft 5s ease-in-out infinite;
            pointer-events: none;
        }
        
        .slash-right {
            position: absolute;
            top: -100%;
            right: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, transparent, #d6a84f, #f4d991, transparent);
            animation: slashRight 5s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes slashTop {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }
        
        @keyframes slashBottom {
            0% { right: -100%; }
            50% { right: 100%; }
            100% { right: 100%; }
        }
        
        @keyframes slashLeft {
            0% { top: -100%; }
            50% { top: 100%; }
            100% { top: 100%; }
        }
        
        @keyframes slashRight {
            0% { top: -100%; }
            50% { top: 100%; }
            100% { top: 100%; }
        }
        
        /* ==================== BRAND ==================== */
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
            animation: brandReveal 0.5s ease-out;
        }
        
        @keyframes brandReveal {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .brand-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #f4d991, #d6a84f);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            animation: iconFloat 3s ease-in-out infinite;
        }
        
        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); box-shadow: 0 10px 20px rgba(214, 168, 79, 0.3); }
            50% { transform: translateY(-5px); box-shadow: 0 20px 35px rgba(214, 168, 79, 0.4); }
        }
        
        .brand-icon i {
            font-size: 24px;
            color: #071a35;
        }
        
        .brand strong {
            font-size: 20px;
            display: block;
        }
        
        .brand small {
            font-size: 12px;
            color: #666;
        }
        
        /* ==================== TYPOGRAPHY ==================== */
        h1 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #d6a84f, #f4d991);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: titleGradient 3s ease infinite;
        }
        
        @keyframes titleGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 24px;
            font-size: 14px;
            animation: fadeInUp 0.5s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ==================== ALERT ERROR ==================== */
        .error-alert {
            background: #fee2e2 !important;
            border: 2px solid #dc2626 !important;
            color: #991b1b !important;
            padding: 14px 18px !important;
            border-radius: 14px !important;
            margin-bottom: 24px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            font-weight: 600 !important;
            animation: shakeError 0.5s ease !important;
        }
        
        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
        
        .error-alert i {
            font-size: 20px;
            color: #dc2626;
        }
        
        /* ==================== FORM ==================== */
        .form-group {
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-out 0.15s both;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .form-group:hover label {
            color: #d6a84f;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #d6a84f;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        
        .form-group input:hover {
            transform: translateY(-2px);
            border-color: #d6a84f;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #d6a84f;
            box-shadow: 0 0 0 4px rgba(214, 168, 79, 0.2);
            transform: translateY(-2px);
        }
        
        /* ==================== BUTTON DENGAN EFEK LOADING ==================== */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d6a84f, #f4d991);
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            color: #071a35;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            position: relative;
            overflow: hidden;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -8px rgba(214, 168, 79, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        /* Efek shimmer pada tombol */
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        /* Efek loading */
        .login-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .login-btn.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ==================== DEMO INFO ==================== */
        .demo-info {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #888;
            padding-top: 16px;
            border-top: 1px solid #eee;
            animation: fadeInUp 0.5s ease-out 0.2s both;
        }
        
        /* ==================== DARK MODE ==================== */
        .dark .demo-info {
            border-top-color: #333;
        }
        
        .dark .form-group input {
            background: rgba(255, 255, 255, 0.1);
            border-color: #444;
            color: white;
        }
        
        .dark .subtitle {
            color: #aaa;
        }
        
        .dark .brand small {
            color: #aaa;
        }
        
        .dark .login-card {
            background: rgba(10, 31, 59, 0.95);
            color: white;
        }
        
        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 480px) {
            .login-card {
                margin: 0 16px;
                padding: 32px 24px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .brand-icon {
                width: 44px;
                height: 44px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <!-- Slash dekoratif di pinggir card -->
        <div class="slash-top"></div>
        <div class="slash-bottom"></div>
        <div class="slash-left"></div>
        <div class="slash-right"></div>
        
        <div class="brand">
            <div class="brand-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <strong>Perpus Digital</strong>
                <small>Admin Panel</small>
            </div>
        </div>
        
        <h1>Masuk Dashboard</h1>
        <p class="subtitle">Gunakan akun admin untuk mengelola buku, anggota, dan transaksi peminjaman.</p>

        <?php if ($error !== ''): ?>
            <div class="error-alert" id="errorAlert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" placeholder="Masukkan username" required>
            </div>
            
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" placeholder="password" required>
            </div>
            
            <button type="submit" class="login-btn" id="loginBtn">
                Login <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="demo-info">
            <i class="fa-solid fa-info-circle"></i> Demo: username <strong>admin</strong> | password <strong>admin123</strong>
        </div>
    </div>

    <script>
        // Efek loading saat submit form
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                // Tambahkan class loading ke tombol
                loginBtn.classList.add('loading');
                loginBtn.innerHTML = 'Memproses... <i class="fa-solid fa-spinner"></i>';
                
                // Biarkan form submit tetap berjalan
                // Tombol akan kembali normal setelah halaman reload/redirect
            });
        }
        
        // Auto hide error alert setelah 5 detik
        setTimeout(function() {
            var errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                errorAlert.style.transition = 'opacity 0.5s ease';
                errorAlert.style.opacity = '0';
                setTimeout(function() {
                    if (errorAlert.parentNode) {
                        errorAlert.style.display = 'none';
                    }
                }, 500);
            }
        }, 5000);
        
        // Efek parallax sederhana untuk background (opsional)
        window.addEventListener('mousemove', function(e) {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            const moveX = (x - 0.5) * 20;
            const moveY = (y - 0.5) * 20;
            
            document.body.style.backgroundPosition = `${50 + moveX}% ${50 + moveY}%`;
        });
    </script>
</body>
</html>
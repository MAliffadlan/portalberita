<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = db();
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password!';
    } else {
        // Cek user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login sukses
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];

            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#ec4899',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4 overflow-hidden relative">

    <!-- Ambient Background Animation -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <div class="w-full max-w-md">
        
        <!-- Glass Card -->
        <div class="glass-card rounded-3xl shadow-2xl p-8 md:p-10 relative overflow-hidden">
            
            <!-- Decor Text -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gradient-to-br from-primary to-secondary opacity-10 rounded-full blur-2xl"></div>

            <div class="text-center mb-8">
                <!-- Icon Logo -->
                <div class="w-16 h-16 bg-gradient-to-br from-primary to-secondary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg text-white transform rotate-3 hover:rotate-6 transition-transform">
                    <i class="fas fa-bolt text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Welcome Back!</h1>
                <p class="text-sm text-gray-500 mt-2">Masuk untuk mengelola portal berita Anda</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm flex items-center mb-6 animate-pulse">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div class="space-y-1">
                    <label class="text-sm font-semibold text-gray-700 ml-1">Username / Email</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-gray-400 group-focus-within:text-primary transition-colors"><i class="fas fa-envelope"></i></span>
                        <input type="text" name="username" required 
                               class="w-full pl-11 pr-4 py-3 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium text-gray-700 placeholder-gray-400"
                               placeholder="Masukkan username anda">
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between items-center ml-1">
                        <label class="text-sm font-semibold text-gray-700">Password</label>
                        <a href="#" class="text-xs font-semibold text-primary hover:text-secondary transition-colors">Lupa Password?</a>
                    </div>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-gray-400 group-focus-within:text-primary transition-colors"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" required 
                               class="w-full pl-11 pr-4 py-3 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium text-gray-700 placeholder-gray-400"
                               placeholder="••••••••">
                        <!-- Eye icon toggle could be added here -->
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 bg-gray-900 text-white rounded-xl font-bold text-sm hover:bg-gray-800 hover:shadow-lg hover:shadow-gray-900/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    Masuk Sekarang
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    Belum punya akun? 
                    <a href="register.php" class="font-bold text-gray-900 hover:text-primary transition-colors">Buat Akun Baru</a>
                </p>
            </div>

            <div class="mt-4 text-center">
                <a href="index.php" class="inline-flex items-center space-x-2 text-sm text-gray-500 hover:text-primary transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Beranda</span>
                </a>
            </div>
        </div>

        <!-- Credentials Hint -->
        <div class="mt-6 text-center">
            <div class="inline-flex items-center space-x-4 bg-white/40 backdrop-blur-sm px-4 py-2 rounded-full border border-white/50 shadow-sm">
                <div class="flex items-center space-x-1.5 text-xs text-gray-600">
                    <i class="fas fa-user-shield text-primary"></i>
                    <span>Admin: <strong>admin</strong></span>
                </div>
                <div class="w-px h-3 bg-gray-300"></div>
                <div class="flex items-center space-x-1.5 text-xs text-gray-600">
                    <i class="fas fa-user text-secondary"></i>
                    <span>User: <strong>user</strong></span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = db();
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Cek username/email sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah terdaftar!';
        } else {
            // Insert user baru
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $email, $hashedPassword]);
                
                $success = 'Akun berhasil dibuat! Silakan login.';
            } catch (PDOException $e) {
                $error = 'Gagal mendaftarkan akun: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?= SITE_NAME ?></title>
    
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
        <div class="absolute top-0 -left-4 w-72 h-72 bg-green-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <div class="w-full max-w-md">
        
        <!-- Glass Card -->
        <div class="glass-card rounded-3xl shadow-2xl p-8 md:p-10 relative overflow-hidden">
            
            <!-- Decor -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gradient-to-br from-green-400 to-blue-500 opacity-10 rounded-full blur-2xl"></div>

            <div class="text-center mb-8">
                <!-- Icon Logo -->
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg text-white transform -rotate-3 hover:rotate-3 transition-transform">
                    <i class="fas fa-user-plus text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Buat Akun Baru</h1>
                <p class="text-sm text-gray-500 mt-2">Daftar untuk mulai berkomentar</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm flex items-center mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-50 border border-green-100 text-green-600 px-4 py-3 rounded-xl text-sm flex items-center mb-6">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?>
                <a href="login.php" class="ml-auto font-bold hover:underline">Login →</a>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="space-y-1">
                    <label class="text-sm font-semibold text-gray-700 ml-1">Username</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-gray-400 group-focus-within:text-primary transition-colors"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               class="w-full pl-11 pr-4 py-3 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium text-gray-700 placeholder-gray-400"
                               placeholder="username_anda">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-semibold text-gray-700 ml-1">Email</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-gray-400 group-focus-within:text-primary transition-colors"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="w-full pl-11 pr-4 py-3 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium text-gray-700 placeholder-gray-400"
                               placeholder="email@example.com">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-semibold text-gray-700 ml-1">Password</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-gray-400 group-focus-within:text-primary transition-colors"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" required minlength="6"
                               class="w-full pl-11 pr-4 py-3 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium text-gray-700 placeholder-gray-400"
                               placeholder="Minimal 6 karakter">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-semibold text-gray-700 ml-1">Konfirmasi Password</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-gray-400 group-focus-within:text-primary transition-colors"><i class="fas fa-lock"></i></span>
                        <input type="password" name="confirm_password" required
                               class="w-full pl-11 pr-4 py-3 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium text-gray-700 placeholder-gray-400"
                               placeholder="Ulangi password">
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 bg-gray-900 text-white rounded-xl font-bold text-sm hover:bg-gray-800 hover:shadow-lg hover:shadow-gray-900/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 mt-2">
                    Daftar Sekarang
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    Sudah punya akun? 
                    <a href="login.php" class="font-bold text-gray-900 hover:text-primary transition-colors">Masuk disini</a>
                </p>
            </div>
        </div>

    </div>

</body>
</html>

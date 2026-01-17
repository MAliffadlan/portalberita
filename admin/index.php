<?php
session_start();
// Cek Login & Role Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

/**
 * Admin Panel - Dashboard
 * Halaman utama admin dengan statistik dan berita terbaru
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

// Get stats
$totalNews = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
$totalPublished = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalDownloads = $pdo->query("SELECT COALESCE(SUM(downloads), 0) FROM attachments")->fetchColumn();

// Latest news
$latestNews = $pdo->query("
    SELECT n.*, c.name as category_name 
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    ORDER BY n.created_at DESC 
    LIMIT 5
")->fetchAll();

// Flash message
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb', // blue-600
                        secondary: '#1d4ed8', // blue-700
                    }
                }
            }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 antialiased">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white fixed h-full z-40">
            <div class="p-6">
                <a href="../index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-newspaper text-white"></i>
                    </div>
                    <span class="text-xl font-bold">Admin Panel</span>
                </a>
            </div>
            
            <nav class="mt-6">
                <a href="index.php" class="flex items-center space-x-3 px-6 py-3 bg-white/10 border-r-4 border-blue-500">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="news/index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-newspaper w-5"></i>
                    <span>Kelola Berita</span>
                </a>
                <a href="news/create.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-plus-circle w-5"></i>
                    <span>Tambah Berita</span>
                </a>
                <a href="categories/index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-folder w-5"></i>
                    <span>Kategori</span>
                </a>
                
                <div class="border-t border-gray-700 my-4"></div>
                
                <a href="../index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-external-link-alt w-5"></i>
                    <span>Lihat Website</span>
                </a>
                
                <a href="../logout.php" class="flex items-center space-x-3 px-6 py-3 text-red-400 hover:text-red-300 hover:bg-white/5 transition-colors mt-8">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-500">Selamat datang di Admin Panel</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600"><?= date('d M Y') ?></span>
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>

            <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Berita -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Berita</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($totalNews) ?></p>
                        </div>
                        <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-newspaper text-blue-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-green-500"><i class="fas fa-check-circle mr-1"></i><?= $totalPublished ?> published</span>
                    </div>
                </div>

                <!-- Kategori -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Kategori</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($totalCategories) ?></p>
                        </div>
                        <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-folder text-purple-500 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Downloads -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Downloads</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($totalDownloads) ?></p>
                        </div>
                        <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-download text-green-500 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Quick Action -->
                <div class="bg-blue-600 rounded-2xl p-6 text-white">
                    <p class="text-white/80 text-sm mb-2">Quick Action</p>
                    <p class="text-xl font-bold mb-4">Tambah Berita Baru</p>
                    <a href="news/create.php" class="inline-flex items-center space-x-2 px-4 py-2 bg-white text-blue-600 rounded-xl font-medium hover:bg-white/90 transition-colors">
                        <i class="fas fa-plus"></i>
                        <span>Tambah</span>
                    </a>
                </div>
            </div>

            <!-- Latest News Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-800">Berita Terbaru</h2>
                        <a href="news/index.php" class="text-primary hover:text-secondary font-medium text-sm">
                            Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($latestNews)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p>Belum ada berita</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($latestNews as $news): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                                <?php if ($news['thumbnail']): ?>
                                                <img src="../uploads/thumbnails/<?= $news['thumbnail'] ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-800 truncate max-w-[250px]"><?= htmlspecialchars($news['title']) ?></p>
                                                <p class="text-xs text-gray-400"><?= $news['views'] ?> views</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full">
                                            <?= htmlspecialchars($news['category_name'] ?? 'Tanpa Kategori') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $news['status'] === 'published' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' ?>">
                                            <?= ucfirst($news['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= formatDate($news['created_at'], 'short') ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="news/edit.php?id=<?= $news['id'] ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../detail.php?slug=<?= $news['slug'] ?>" target="_blank" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?= $news['id'] ?>, '<?= addslashes($news['title']) ?>')" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(id, title) {
            Swal.fire({
                title: 'Hapus Berita?',
                html: `Anda yakin ingin menghapus berita:<br><strong>${title}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `news/delete.php?id=${id}`;
                }
            });
        }
    </script>
</body>
</html>

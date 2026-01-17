<?php
/**
 * Portal Berita - Arsip Berita (Grid Style ala WinPoin)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

// Pagination config
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Grid 3x3
$offset = ($page - 1) * $limit;

// Count total news
$total = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn();
$totalPages = ceil($total / $limit);

// Get news data
$stmt = $pdo->prepare("
    SELECT n.*, c.name as category_name, c.slug as category_slug
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.status = 'published'
    ORDER BY n.published_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$newsList = $stmt->fetchAll();

// Categories for sidebar (jika diperlukan layout sidebar, tapi referensi full width grid)
// Kita buat full width grid saja agar mirip referensi gambar
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Berita - <?= SITE_NAME ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0078d4', // WinPoin Blue
                        darkblue: '#001e3c', // WinPoin Dark Navy
                        secondary: '#2b88d8',
                        accent: '#ff4500', // WinPoin Orange
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-white text-gray-800 font-sans">

    <!-- Navbar -->
    <nav class="bg-primary text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="text-2xl font-bold tracking-tight">PortalBerita</a>
                    <div class="hidden md:flex items-center space-x-6 text-sm font-medium">
                        <a href="index.php" class="hover:text-gray-200 transition-colors">Home</a>
                        <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                        <a href="category.php?slug=<?= $cat['slug'] ?>" class="hover:text-gray-200 transition-colors"><?= htmlspecialchars($cat['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                     <form action="search.php" method="GET" class="hidden md:block relative">
                        <input type="text" name="q" placeholder="Search..." 
                               class="pl-3 pr-8 py-1.5 text-sm text-gray-800 rounded-sm focus:outline-none focus:ring-2 focus:ring-accent w-64">
                         <button type="submit" class="absolute right-2 top-1.5 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <a href="admin/index.php" class="text-white hover:text-accent transition-colors" title="Admin Panel"><i class="fas fa-user-circle text-xl"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Header Arsip (Hidden di gambar referensi, tapi bagus untuk konteks) -->
        <!-- <div class="mb-8 pb-4 border-b border-gray-100">
            <h1 class="text-3xl font-bold text-gray-800">Arsip Berita</h1>
            <span class="text-gray-500 text-sm">Halaman category <?= $page ?> dari <?= $totalPages ?></span>
        </div> -->

        <?php if (empty($newsList)): ?>
            <div class="text-center py-20">
                <i class="fas fa-newspaper text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl text-gray-500 font-medium">Belum ada berita.</h3>
            </div>
        <?php else: ?>
            <!-- Grid 3 Kolom -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                <?php foreach ($newsList as $news): ?>
                <article class="flex flex-col group h-full">
                    <!-- Thumb -->
                    <a href="detail.php?slug=<?= $news['slug'] ?>" class="block aspect-video overflow-hidden bg-gray-100 shrink-0 mb-4">
                        <img src="<?= $news['thumbnail'] ? 'uploads/thumbnails/' . $news['thumbnail'] : 'https://source.unsplash.com/random/400x300?tech' ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                             loading="lazy">
                    </a>
                    
                    <!-- Content -->
                    <div class="flex flex-col flex-1">
                        <!-- Meta -->
                        <div class="text-xs text-gray-500 mb-2 italic">
                            in <span class="text-gray-700 not-italic font-semibold"><?= htmlspecialchars($news['category_name'] ?? 'General') ?></span>, PC
                        </div>

                        <!-- Title -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3 leading-snug group-hover:text-primary transition-colors line-clamp-3">
                            <a href="detail.php?slug=<?= $news['slug'] ?>">
                                <?= htmlspecialchars($news['title']) ?>
                            </a>
                        </h3>
                        
                        <!-- Date -->
                        <div class="text-xs text-gray-400 mt-auto">
                            <?= timeAgo($news['published_at']) ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination (Simple Previous/Next) -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-16 space-x-4">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-6 py-2 bg-primary text-white font-semibold rounded hover:bg-secondary transition-colors">
                &laquo; Previous Page
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-6 py-2 bg-primary text-white font-semibold rounded hover:bg-secondary transition-colors">
                Next Page &raquo;
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer class="bg-darkblue text-white py-12 px-4 text-center mt-12 border-t-4 border-primary">
        <p class="text-sm text-gray-400">&copy; <?= date('Y') ?> PortalBerita. All rights reserved.</p>
    </footer>

</body>
</html>

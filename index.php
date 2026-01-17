<?php
session_start();
/**
 * Portal Berita - Homepage (WinPoin Style)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

// 1. Ambil 2 Berita Unggulan (Featured) untuk Hero Section
$stmtFeatured = $pdo->query("
    SELECT n.*, c.name as category_name 
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.status = 'published' AND n.is_featured = 1 
    ORDER BY n.published_at DESC 
    LIMIT 2
");
$featuredNews = $stmtFeatured->fetchAll();

// Jika kurang dari 2, ambil dari berita terbaru biasa
if (count($featuredNews) < 2) {
    $needed = 2 - count($featuredNews);
    $ids = array_column($featuredNews, 'id');
    $idsStr = $ids ? implode(',', $ids) : '0';
    
    $stmtExtra = $pdo->query("
        SELECT n.*, c.name as category_name 
        FROM news n 
        LEFT JOIN categories c ON n.category_id = c.id 
        WHERE n.status = 'published' AND n.id NOT IN ($idsStr)
        ORDER BY n.published_at DESC 
        LIMIT $needed
    ");
    $featuredNews = array_merge($featuredNews, $stmtExtra->fetchAll());
}

// ID yang sudah ditampilkan di featured agar tidak muncul lagi
$displayedIds = array_column($featuredNews, 'id');
$displayedIdsStr = $displayedIds ? implode(',', $displayedIds) : '0';

// 2. Berita Terbaru (What's New)
$stmtLatest = $pdo->query("
    SELECT n.*, c.name as category_name, c.slug as category_slug 
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.status = 'published' AND n.id NOT IN ($displayedIdsStr)
    ORDER BY n.published_at DESC 
    LIMIT 6
");
$latestNews = $stmtLatest->fetchAll();

// 3. Categories
$stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmtCategories->fetchAll();

// 4. Trending News (Sidebar)
$stmtPopular = $pdo->query("
    SELECT n.* 
    FROM news n 
    WHERE n.status = 'published' 
    ORDER BY n.views DESC 
    LIMIT 5
");
$popularNews = $stmtPopular->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Berita Terkini</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0078d4', // WinPoin Blue
                        darkblue: '#001e3c', // WinPoin Dark Navy
                        secondary: '#2b88d8',
                        accent: '#ffb900', // Warning/Number color
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
        body { font-family: 'Segoe UI', 'Inter', sans-serif; }
        .hero-gradient {
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.8) 100%);
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-white text-gray-800">

    <!-- Navbar -->
    <nav class="bg-primary text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <!-- Logo & Menu -->
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="text-2xl font-bold tracking-tight">PortalBerita</a>
                    
                    <div class="hidden md:flex items-center space-x-6 text-sm font-medium">
                        <a href="index.php" class="hover:text-gray-200 transition-colors">Home</a>
                        <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                        <a href="category.php?slug=<?= $cat['slug'] ?>" class="hover:text-gray-200 transition-colors">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Search & Admin -->
                <div class="flex items-center space-x-4">
                    <form action="search.php" method="GET" class="hidden md:block relative">
                        <input type="text" name="q" placeholder="Search..." 
                               class="pl-3 pr-8 py-1.5 text-sm text-gray-800 rounded-sm focus:outline-none focus:ring-2 focus:ring-accent w-64">
                        <button type="submit" class="absolute right-2 top-1.5 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center space-x-3">
                            <!-- User Info -->
                            <div class="flex items-center space-x-2 text-white">
                                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">
                                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                </div>
                                <span class="text-sm font-medium hidden md:inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            </div>
                            
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/index.php" class="text-white/70 hover:text-white text-sm hidden md:inline" title="Dashboard Admin">
                                <i class="fas fa-cog"></i>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Logout Button (Always Visible) -->
                            <a href="logout.php" class="flex items-center space-x-1 px-3 py-1.5 bg-red-500/80 hover:bg-red-500 text-white text-sm rounded transition-colors" title="Logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="hidden md:inline">Logout</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-white hover:text-accent transition-colors font-medium text-sm border border-white/30 px-4 py-1.5 rounded hover:bg-white/10">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section (Dark Blue Background) -->
    <div class="bg-darkblue py-8 border-b-4 border-primary">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($featuredNews as $i => $news): ?>
                <div class="relative aspect-video group cursor-pointer overflow-hidden bg-gray-900" 
                     onclick="window.location='detail.php?slug=<?= $news['slug'] ?>'">
                    <!-- Thumbnail -->
                    <img src="<?= $news['thumbnail'] ? 'uploads/thumbnails/' . $news['thumbnail'] : 'https://source.unsplash.com/random/800x600?tech' ?>" 
                         alt="<?= htmlspecialchars($news['title']) ?>"
                         class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
                    
                    <!-- Number Badge -->
                    <div class="absolute top-0 right-0 bg-[#ff4500] text-white font-bold w-10 h-10 flex items-center justify-center text-lg shadow-lg z-10">
                        <?= $i + 1 ?>
                    </div>
                    
                    <!-- Content Overlay -->
                    <div class="absolute bottom-0 left-0 right-0 p-6 hero-gradient pt-20">
                        <h2 class="text-2xl font-bold text-white leading-tight mb-2 group-hover:text-accent transition-colors">
                            <?= htmlspecialchars($news['title']) ?>
                        </h2>
                        <div class="text-gray-300 text-xs flex items-center space-x-3">
                            <span><?= htmlspecialchars($news['author']) ?></span>
                            <span>&bull;</span>
                            <span><?= timeAgo($news['published_at']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <!-- Left Column: What's New -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-bold text-gray-800">What's New</h2>
                    <a href="news.php" class="text-primary font-semibold text-sm hover:underline hover:text-[#ff4500]">View More ></a>
                </div>

                <div class="space-y-8" id="news-list">
                    <?php foreach ($latestNews as $news): ?>
                    <article class="flex flex-col sm:flex-row gap-6 group">
                        <!-- Thumb -->
                        <a href="detail.php?slug=<?= $news['slug'] ?>" class="sm:w-5/12 aspect-video overflow-hidden bg-gray-100 shrink-0">
                            <img src="<?= $news['thumbnail'] ? 'uploads/thumbnails/' . $news['thumbnail'] : 'https://source.unsplash.com/random/400x300?tech' ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 loading="lazy">
                        </a>
                        
                        <!-- Text -->
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-2 uppercase tracking-wide font-semibold">
                                <span class="text-primary hover:underline cursor-pointer">
                                    <?= htmlspecialchars($news['category_name'] ?? 'General') ?>
                                </span>
                                <?php if($news['category_name']): ?><span>, PC</span><?php endif; ?>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-800 mb-2 leading-snug group-hover:text-primary transition-colors">
                                <a href="detail.php?slug=<?= $news['slug'] ?>">
                                    <?= htmlspecialchars($news['title']) ?>
                                </a>
                            </h3>
                            
                            <p class="text-sm text-gray-600 mb-3 line-clamp-3">
                                <?= htmlspecialchars($news['excerpt'] ?? truncateText(strip_tags($news['content']), 120)) ?>
                            </p>
                            
                            <div class="text-xs text-gray-400">
                                by <span class="font-semibold text-gray-500"><?= htmlspecialchars($news['author']) ?></span> &bull; <?= timeAgo($news['published_at']) ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Load More -->
                <div class="text-center mt-10">
                    <button id="load-more" class="px-8 py-2 border-2 border-primary text-primary font-semibold rounded-full hover:bg-primary hover:text-white transition-all text-sm">
                        Load More News
                    </button>
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <aside class="lg:col-span-1 space-y-8">
                <!-- Trending Widget -->
                <div class="bg-darkblue text-white p-6">
                    <h3 class="text-xl font-bold text-[#ff4500] mb-6">Trending</h3>
                    
                    <div class="space-y-6">
                        <?php foreach ($popularNews as $i => $news): ?>
                        <a href="detail.php?slug=<?= $news['slug'] ?>" class="flex gap-4 group">
                            <div class="text-3xl font-bold text-[#ff4500] leading-none shrink-0 w-8">
                                <?= $i + 1 ?>
                            </div>
                            <div>
                                <h4 class="font-bold leading-snug group-hover:text-[#ff4500] transition-colors">
                                    <?= htmlspecialchars($news['title']) ?>
                                </h4>
                                <div class="text-xs text-gray-400 mt-1">
                                    <?= number_format($news['views']) ?> views
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tags / Categories Widget (Pills) -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b-2 border-gray-100 pb-2">Topik Populer</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($categories as $cat): ?>
                        <a href="category.php?slug=<?= $cat['slug'] ?>" 
                           class="px-3 py-1 border border-blue-200 text-primary hover:bg-primary hover:text-white rounded-full text-sm transition-colors">
                             <i class="fas fa-hashtag mr-1 text-xs"></i><?= htmlspecialchars($cat['name']) ?>
                        </a>
                        <?php endforeach; ?>
                        <a href="#" class="px-3 py-1 border border-blue-200 text-primary hover:bg-primary hover:text-white rounded-full text-sm transition-colors">
                            Windows 11
                        </a>
                        <a href="#" class="px-3 py-1 border border-blue-200 text-primary hover:bg-primary hover:text-white rounded-full text-sm transition-colors">
                            Tutorial
                        </a>
                    </div>
                </div>

            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-darkblue text-white py-12 border-t-4 border-primary mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-4">PortalBerita</h2>
                <div class="flex justify-center space-x-6 mb-8">
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white text-xl"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="text-sm text-gray-400 space-x-4">
                    <a href="#" class="hover:text-white">Tentang Kami</a>
                    <a href="#" class="hover:text-white">Kontak</a>
                    <a href="#" class="hover:text-white">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-white">Disclaimer</a>
                </div>
                <p class="mt-8 text-xs text-gray-500">&copy; <?= date('Y') ?> PortalBerita Indonesia. Design inspired by WinPoin.</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts for Load More (Reuse Logic) -->
    <script>
    let currentOffset = 6;
    
    document.getElementById('load-more').addEventListener('click', async function() {
        const btn = this;
        const list = document.getElementById('news-list');
        
        btn.innerText = 'Loading...';
        btn.disabled = true;
        
        try {
            const response = await fetch(`api/load_more_news.php?offset=${currentOffset}&limit=6`);
            const data = await response.json();
            
            if (data.success && data.html) {
                // Perlu menyesuaikan struktur HTML dari API karena layoutnya beda
                // Opsi cepat: Refresh page atau sesuaikan API. 
                // Karena API return HTML yg Hardcoded card style lama, kita harus bikin API baru 
                // atau parsing data JSON raw kalau API support.
                // UNTUK SEKARANG: Kita redirect ke news.php aja (View More) atau biarkan placeholder.
                
                // Oops, API mengembalikan HTML string dengan class lama.
                // Sebaiknya tombol ini redirect ke halaman arsip saja untuk konsistensi UI
                window.location.href = 'news.php';
            }
        } catch (e) {
            console.error(e);
            window.location.href = 'news.php';
        }
    });

    // Karena struktur HTML di API 'load_more_news.php' masih pakai style lama card grid,
    // tombol "Load More" di sini sebaiknya redirect ke halaman News Archive saja.
    document.getElementById('load-more').onclick = function() {
        window.location.href = 'news.php';
    }
    document.getElementById('load-more').innerText = 'View All News Archive';
    </script>
</body>
</html>

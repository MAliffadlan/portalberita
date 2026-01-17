<?php
/**
 * Portal Berita - Category Page
 * Menampilkan berita berdasarkan kategori
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$pdo = db();

// Ambil kategori
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Total berita
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM news WHERE category_id = ? AND status = 'published'");
$stmtCount->execute([$category['id']]);
$totalNews = $stmtCount->fetchColumn();
$totalPages = ceil($totalNews / $perPage);

// Ambil berita
$stmtNews = $pdo->prepare("
    SELECT n.*, c.name as category_name 
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.category_id = ? AND n.status = 'published'
    ORDER BY n.published_at DESC
    LIMIT ? OFFSET ?
");
$stmtNews->execute([$category['id'], $perPage, $offset]);
$newsList = $stmtNews->fetchAll();

// All categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name']) ?> - <?= SITE_NAME ?></title>
    
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
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 antialiased">

    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-newspaper text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold text-gradient">PortalBerita</span>
                </a>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-700 hover:text-primary font-medium">Beranda</a>
                    <div class="relative group">
                        <button class="text-primary font-medium flex items-center space-x-1">
                            <span>Kategori</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            <div class="py-2">
                                <?php foreach ($categories as $cat): ?>
                                <a href="category.php?slug=<?= $cat['slug'] ?>" 
                                   class="block px-4 py-2 text-gray-700 hover:bg-primary/10 hover:text-primary <?= $cat['id'] == $category['id'] ? 'bg-primary/10 text-primary' : '' ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="admin/index.php" class="px-4 py-2 bg-blue-600 text-white rounded-full font-medium hover:bg-blue-700 hover:shadow-lg transition-all">
                    <i class="fas fa-user-shield mr-2"></i>Admin
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="pt-24 pb-8 bg-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-block px-4 py-1.5 bg-white/20 text-white text-sm font-semibold rounded-full mb-4">
                <i class="fas fa-folder mr-2"></i>Kategori
            </span>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?= htmlspecialchars($category['name']) ?></h1>
            <p class="text-white/80"><?= htmlspecialchars($category['description'] ?? "Berita terbaru kategori " . $category['name']) ?></p>
            <p class="text-white/60 mt-2"><?= $totalNews ?> artikel</p>
        </div>
    </section>

    <!-- Category Pills -->
    <section class="py-6 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center space-x-3 overflow-x-auto pb-2">
                <a href="index.php" class="flex-shrink-0 px-5 py-2.5 bg-gray-100 text-gray-700 rounded-full font-medium hover:bg-gray-200 transition-all">
                    <i class="fas fa-th-large mr-2"></i>Semua
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="category.php?slug=<?= $cat['slug'] ?>" 
                   class="flex-shrink-0 px-5 py-2.5 rounded-full font-medium transition-all <?= $cat['id'] == $category['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- News Grid -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (!empty($newsList)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($newsList as $index => $news): ?>
                <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover reveal" data-delay="<?= $index * 100 ?>">
                    <a href="detail.php?slug=<?= $news['slug'] ?>" class="block img-zoom">
                        <div class="aspect-video relative">
                            <img src="<?= $news['thumbnail'] ? 'uploads/thumbnails/' . $news['thumbnail'] : 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=600' ?>" 
                                 alt="<?= htmlspecialchars($news['title']) ?>"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </div>
                    </a>
                    <div class="p-5">
                        <a href="detail.php?slug=<?= $news['slug'] ?>">
                            <h3 class="font-bold text-gray-800 mb-2 line-clamp-2 hover:text-primary transition-colors">
                                <?= htmlspecialchars($news['title']) ?>
                            </h3>
                        </a>
                        <p class="text-gray-500 text-sm mb-4 line-clamp-2">
                            <?= htmlspecialchars($news['excerpt'] ?? truncateText(strip_tags($news['content']), 100)) ?>
                        </p>
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span><i class="far fa-clock mr-1"></i><?= timeAgo($news['published_at']) ?></span>
                            <span><i class="far fa-eye mr-1"></i><?= number_format($news['views']) ?></span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center space-x-2 mt-12">
                <?php if ($page > 1): ?>
                <a href="?slug=<?= $slug ?>&page=<?= $page - 1 ?>" 
                   class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?slug=<?= $slug ?>&page=<?= $i ?>" 
                   class="px-4 py-2 rounded-lg transition-colors <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?slug=<?= $slug ?>&page=<?= $page + 1 ?>" 
                   class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-newspaper text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-700">Belum ada berita</h3>
                <p class="text-gray-500 mt-2">Tidak ada berita di kategori ini</p>
                <a href="index.php" class="inline-block mt-4 px-6 py-3 bg-blue-600 text-white rounded-full font-medium hover:bg-blue-700 hover:shadow-lg transition-all">
                    Kembali ke Beranda
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; <?= date('Y') ?> PortalBerita. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Scroll Reveal
        const revealElements = document.querySelectorAll('.reveal');
        const reveal = () => {
            revealElements.forEach((element, index) => {
                const windowHeight = window.innerHeight;
                const elementTop = element.getBoundingClientRect().top;
                if (elementTop < windowHeight - 150) {
                    setTimeout(() => element.classList.add('active'), element.dataset.delay || 0);
                }
            });
        };
        window.addEventListener('scroll', reveal);
        reveal();
    </script>
</body>
</html>

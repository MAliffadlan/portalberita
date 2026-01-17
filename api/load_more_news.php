<?php
/**
 * API Endpoint - Load More News
 * Mengembalikan berita tambahan dalam format HTML
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Get offset from request (default 6 karena sudah 6 berita ditampilkan)
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 6;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;

// Validasi
$offset = max(0, $offset);
$limit = min(12, max(1, $limit)); // Max 12 berita per request

try {
    $pdo = db();
    
    // Hitung total berita
    $stmtCount = $pdo->query("SELECT COUNT(*) as total FROM news WHERE status = 'published'");
    $totalNews = $stmtCount->fetch()['total'];
    
    // Ambil berita
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
    $news = $stmt->fetchAll();
    
    // Cek apakah masih ada berita lagi
    $hasMore = ($offset + $limit) < $totalNews;
    
    // Generate HTML untuk setiap berita
    $html = '';
    foreach ($news as $index => $item) {
        $thumbnail = $item['thumbnail'] 
            ? 'uploads/thumbnails/' . $item['thumbnail'] 
            : 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=600';
        $category = htmlspecialchars($item['category_name'] ?? 'Berita');
        $title = htmlspecialchars($item['title']);
        $excerpt = htmlspecialchars($item['excerpt'] ?? truncateText(strip_tags($item['content']), 100));
        $timeAgo = timeAgo($item['published_at']);
        $views = number_format($item['views']);
        $slug = $item['slug'];
        
        $html .= <<<HTML
<article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover reveal active animate__animated animate__fadeInUp" style="animation-delay: {$index}00ms">
    <a href="detail.php?slug={$slug}" class="block img-zoom">
        <div class="aspect-video relative">
            <img src="{$thumbnail}" 
                 alt="{$title}"
                 class="w-full h-full object-cover"
                 loading="lazy">
            <div class="absolute top-3 left-3">
                <span class="px-3 py-1 bg-white/90 backdrop-blur-sm text-primary text-xs font-semibold rounded-full">
                    {$category}
                </span>
            </div>
        </div>
    </a>
    <div class="p-5">
        <a href="detail.php?slug={$slug}">
            <h3 class="font-bold text-gray-800 mb-2 line-clamp-2 hover:text-primary transition-colors">
                {$title}
            </h3>
        </a>
        <p class="text-gray-500 text-sm mb-4 line-clamp-2">
            {$excerpt}
        </p>
        <div class="flex items-center justify-between text-xs text-gray-400">
            <span><i class="far fa-clock mr-1"></i> {$timeAgo}</span>
            <span><i class="far fa-eye mr-1"></i> {$views}</span>
        </div>
    </div>
</article>
HTML;
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'hasMore' => $hasMore,
        'loaded' => count($news),
        'nextOffset' => $offset + count($news)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Gagal memuat berita: ' . $e->getMessage()
    ]);
}

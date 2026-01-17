<?php
session_start();
/**
 * Portal Berita - Detail Berita (WinPoin Style)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Ambil slug dari URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$pdo = db();

// Ambil detail berita
$stmt = $pdo->prepare("
    SELECT n.*, c.name as category_name, c.slug as category_slug 
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.slug = ? AND n.status = 'published'
");
$stmt->execute([$slug]);
$news = $stmt->fetch();

if (!$news) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Update view count
$pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?")->execute([$news['id']]);

// Ambil attachments
$stmtAttach = $pdo->prepare("SELECT * FROM attachments WHERE news_id = ?");
$stmtAttach->execute([$news['id']]);
$attachments = $stmtAttach->fetchAll();

// Categories for sidebar
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Latest News (What's New Sidebar) - Limit 5
$stmtLatest = $pdo->query("
    SELECT id, title, slug, published_at 
    FROM news 
    WHERE status = 'published' 
    ORDER BY published_at DESC 
    LIMIT 5
");
$latestNews = $stmtLatest->fetchAll();

// Trending News (Sidebar)
$stmtPopular = $pdo->query("
    SELECT id, title, slug, views 
    FROM news 
    WHERE status = 'published' 
    ORDER BY views DESC 
    LIMIT 5
");
$popularNews = $stmtPopular->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($news['title']) ?> - <?= SITE_NAME ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0078d4', // WinPoin Blue
                        darkblue: '#001e3c', // WinPoin Dark Navy
                        secondary: '#2b88d8',
                        accent: '#ff4500', // WinPoin Orange/Red
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Inter', 'system-ui', 'sans-serif'],
                        serif: ['Georgia', 'Cambria', 'Times New Roman', 'serif'],
                    }
                }
            }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
        body { font-family: 'Segoe UI', 'Inter', sans-serif; }
        .prose p { margin-bottom: 1.5rem; line-height: 1.8; color: #374151; font-size: 1.05rem; }
        .prose h2 { font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1rem; color: #111827; }
        .prose h3 { font-size: 1.25rem; font-weight: 600; margin: 1.5rem 0 0.75rem; color: #1f2937; }
        .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1.5rem; }
        .prose li { margin-bottom: 0.5rem; }
        .prose a { color: #0078d4; text-decoration: underline; }
        .prose a:hover { color: #005a9e; }
        .prose img { width: 100%; height: auto; border-radius: 0.5rem; margin: 2rem 0; }
        .prose blockquote { border-left: 4px solid #0078d4; padding-left: 1rem; font-style: italic; color: #4b5563; background: #f9fafb; padding: 1rem; }
    </style>
</head>
<body class="bg-white text-gray-800">

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
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            <!-- Left Column: Article -->
            <article class="lg:col-span-2">
                <!-- Meta Category -->
                <div class="text-sm text-gray-400 italic mb-4">
                    in <a href="category.php?slug=<?= $news['category_slug'] ?>" class="text-gray-600 hover:text-primary not-italic font-semibold"><?= htmlspecialchars($news['category_name']) ?></a>, Featured
                </div>

                <!-- Title -->
                <h1 class="text-3xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight tracking-tight">
                    <?= htmlspecialchars($news['title']) ?>
                </h1>

                <!-- Author Meta -->
                <div class="flex items-center space-x-3 mb-8 pb-8 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden">
                        <!-- Placeholder Avatar -->
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($news['author']) ?>&background=random" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <div class="text-sm">
                        <div class="font-bold text-gray-700">by <?= htmlspecialchars($news['author']) ?></div>
                        <div class="text-gray-400"><?= timeAgo($news['published_at']) ?> &bull; <?= number_format($news['views']) ?> views</div>
                    </div>
                </div>

                <!-- Content -->
                <div class="prose max-w-none">
                    <!-- Featured Image (Optional, comment out if not needed inside content) -->
                    <?php if ($news['thumbnail']): ?>
                    <img src="uploads/thumbnails/<?= $news['thumbnail'] ?>" alt="<?= htmlspecialchars($news['title']) ?>" class="mb-8">
                    <?php endif; ?>

                    <?= $news['content'] ?>
                </div>

                <!-- Attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="mt-8 mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Attachments</h3>
                    <div class="space-y-3">
                        <?php foreach ($attachments as $attach): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center space-x-3 text-sm">
                                <i class="fas <?= str_contains($attach['file_type'], 'pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-word text-blue-500' ?> text-xl"></i>
                                <span class="font-medium text-gray-700"><?= htmlspecialchars($attach['original_name']) ?></span>
                            </div>
                            <a href="download.php?id=<?= $attach['id'] ?>" class="px-4 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Download
                            </a>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($attachments) > 1): ?>
                        <div class="mt-4">
                             <a href="download.php?news_id=<?= $news['id'] ?>&type=zip" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-secondary transition-colors">
                                <i class="fas fa-file-archive mr-2"></i> Download All (ZIP)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tags / Pills -->
                <div class="flex flex-wrap gap-2 mt-8 pt-6 border-t border-gray-100">
                    <span class="text-sm text-gray-500 py-1">Temukan lebih banyak:</span>
                    <a href="#" class="px-3 py-1 rounded-full border border-blue-200 text-blue-600 text-sm hover:bg-blue-50 transition-colors">
                        <i class="fas fa-laptop mr-1 text-xs"></i> laptop
                    </a>
                    <a href="#" class="px-3 py-1 rounded-full border border-blue-200 text-blue-600 text-sm hover:bg-blue-50 transition-colors">
                        <i class="fab fa-windows mr-1 text-xs"></i> Windows 11
                    </a>
                    <a href="#" class="px-3 py-1 rounded-full border border-blue-200 text-blue-600 text-sm hover:bg-blue-50 transition-colors">
                        <i class="fas fa-microchip mr-1 text-xs"></i> RAM
                    </a>
                </div>

                <!-- ========== COMMENT SECTION ========== -->
                <div class="mt-12 pt-8 border-t-2 border-gray-100" id="comments-section">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-comments text-primary mr-3"></i>
                        Komentar <span class="text-lg text-gray-400 font-normal ml-2" id="comment-count">(0)</span>
                    </h3>

                    <!-- Comment Form (Only for logged in users) -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mb-8 bg-gray-50 rounded-xl p-6" id="main-comment-form">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm shrink-0">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                            <div class="flex-1">
                                <textarea id="comment-input" rows="3" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary resize-none text-gray-700"
                                    placeholder="Tulis komentar Anda..."></textarea>
                                <div class="flex justify-end mt-3">
                                    <button onclick="submitComment()" 
                                        class="px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-secondary transition-colors">
                                        <i class="fas fa-paper-plane mr-2"></i> Kirim
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Login Prompt for Guests -->
                    <div class="mb-8 bg-gray-50 rounded-xl p-6 text-center">
                        <i class="fas fa-user-lock text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-600 mb-4">Silakan login untuk berkomentar</p>
                        <a href="login.php" class="inline-block px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-secondary transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login Sekarang
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <div id="comments-list" class="space-y-6">
                        <div class="text-center text-gray-400 py-8" id="loading-comments">
                            <i class="fas fa-spinner fa-spin text-2xl"></i>
                            <p class="mt-2">Memuat komentar...</p>
                        </div>
                    </div>
                </div>
                <!-- ========== END COMMENT SECTION ========== -->

            </article>

            <!-- Right Column: Sidebar -->
            <aside class="lg:col-span-1 space-y-10 border-l border-gray-100 pl-8 hidden lg:block">
                
                <!-- What's New Widget -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6">What's New</h3>
                    <div class="space-y-6">
                        <?php foreach ($latestNews as $i => $item): ?>
                        <div class="flex gap-4 group">
                            <div class="text-xl font-bold text-accent leading-none shrink-0 w-4 pt-1">
                                <?= $i + 1 ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 leading-snug group-hover:text-primary transition-colors text-base mb-1">
                                    <a href="detail.php?slug=<?= $item['slug'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                                </h4>
                                <div class="text-xs text-gray-400">
                                    <?= timeAgo($item['published_at']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Trending Widget -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Trending</h3>
                    <div class="space-y-6">
                        <?php foreach ($popularNews as $i => $item): ?>
                        <div class="flex gap-4 group">
                            <div class="text-xl font-bold text-accent leading-none shrink-0 w-4 pt-1">
                                <?= $i + 1 ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 leading-snug group-hover:text-primary transition-colors text-base mb-1">
                                    <a href="detail.php?slug=<?= $item['slug'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                                </h4>
                                <div class="text-xs text-gray-400">
                                    <?= number_format($item['views']) ?> views
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-darkblue text-white py-12 border-t-4 border-primary mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> PortalBerita - WinPoin Style.</p>
        </div>
    </footer>

    <!-- Comment System JavaScript -->
    <script>
        const NEWS_ID = <?= $news['id'] ?>;
        const IS_LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        const CURRENT_USER = <?= isset($_SESSION['username']) ? '"' . htmlspecialchars($_SESSION['username']) . '"' : 'null' ?>;
        
        // Fetch comments on page load
        document.addEventListener('DOMContentLoaded', fetchComments);
        
        async function fetchComments() {
            try {
                const res = await fetch(`api/comments.php?news_id=${NEWS_ID}`);
                const data = await res.json();
                
                document.getElementById('comment-count').textContent = `(${data.count})`;
                
                const container = document.getElementById('comments-list');
                container.innerHTML = '';
                
                if (data.comments.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-gray-400 py-8">
                            <i class="fas fa-comment-slash text-3xl mb-2"></i>
                            <p>Belum ada komentar. Jadilah yang pertama!</p>
                        </div>
                    `;
                    return;
                }
                
                data.comments.forEach(comment => {
                    container.appendChild(renderComment(comment, 0));
                });
            } catch (err) {
                console.error('Error fetching comments:', err);
                document.getElementById('comments-list').innerHTML = `
                    <div class="text-center text-red-400 py-8">
                        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                        <p>Gagal memuat komentar.</p>
                    </div>
                `;
            }
        }
        
        function renderComment(comment, depth) {
            const div = document.createElement('div');
            div.className = `comment-item ${depth > 0 ? 'ml-10 mt-4 pl-4 border-l-2 border-gray-100' : ''}`;
            div.id = `comment-${comment.id}`;
            
            const avatar = comment.avatar 
                ? `<img src="uploads/avatars/${comment.avatar}" class="w-10 h-10 rounded-full object-cover" alt="${comment.username}">` 
                : `<div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-sm">${comment.username.charAt(0).toUpperCase()}</div>`;
            
            const timeAgo = formatTimeAgo(comment.created_at);
            
            div.innerHTML = `
                <div class="flex items-start gap-3">
                    ${avatar}
                    <div class="flex-1 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-gray-800">${comment.username}</span>
                            <span class="text-xs text-gray-400">${timeAgo}</span>
                        </div>
                        <p class="text-gray-600 text-sm leading-relaxed">${escapeHtml(comment.content)}</p>
                        ${IS_LOGGED_IN ? `
                        <button onclick="showReplyForm(${comment.id})" class="text-xs text-primary hover:underline mt-3 font-medium">
                            <i class="fas fa-reply mr-1"></i> Balas
                        </button>
                        ` : ''}
                        
                        <!-- Reply Form (Hidden by default) -->
                        <div id="reply-form-${comment.id}" class="hidden mt-4">
                            <textarea id="reply-input-${comment.id}" rows="2" 
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                                placeholder="Tulis balasan..."></textarea>
                            <div class="flex justify-end gap-2 mt-2">
                                <button onclick="hideReplyForm(${comment.id})" class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700">Batal</button>
                                <button onclick="submitReply(${comment.id})" class="px-4 py-1 bg-primary text-white text-sm rounded-md hover:bg-secondary">Balas</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Render nested replies
            if (comment.replies && comment.replies.length > 0) {
                const repliesContainer = document.createElement('div');
                repliesContainer.className = 'replies-container';
                comment.replies.forEach(reply => {
                    repliesContainer.appendChild(renderComment(reply, depth + 1));
                });
                div.appendChild(repliesContainer);
            }
            
            return div;
        }
        
        function showReplyForm(commentId) {
            document.getElementById(`reply-form-${commentId}`).classList.remove('hidden');
        }
        
        function hideReplyForm(commentId) {
            document.getElementById(`reply-form-${commentId}`).classList.add('hidden');
            document.getElementById(`reply-input-${commentId}`).value = '';
        }
        
        async function submitComment() {
            const input = document.getElementById('comment-input');
            const content = input.value.trim();
            
            if (!content) {
                Swal.fire('Oops!', 'Komentar tidak boleh kosong', 'warning');
                return;
            }
            
            try {
                const res = await fetch('api/comments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ news_id: NEWS_ID, content: content })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    input.value = '';
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Komentar Anda telah terkirim', timer: 1500, showConfirmButton: false });
                    fetchComments();
                } else {
                    Swal.fire('Gagal', data.error || 'Terjadi kesalahan', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal mengirim komentar', 'error');
            }
        }
        
        async function submitReply(parentId) {
            const input = document.getElementById(`reply-input-${parentId}`);
            const content = input.value.trim();
            
            if (!content) {
                Swal.fire('Oops!', 'Balasan tidak boleh kosong', 'warning');
                return;
            }
            
            try {
                const res = await fetch('api/comments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ news_id: NEWS_ID, parent_id: parentId, content: content })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    hideReplyForm(parentId);
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Balasan Anda telah terkirim', timer: 1500, showConfirmButton: false });
                    fetchComments();
                } else {
                    Swal.fire('Gagal', data.error || 'Terjadi kesalahan', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal mengirim balasan', 'error');
            }
        }
        
        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'Baru saja';
            if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
            if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
            if (diff < 2592000) return Math.floor(diff / 86400) + ' hari lalu';
            return Math.floor(diff / 2592000) + ' bulan lalu';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

</body>
</html>

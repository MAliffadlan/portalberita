<?php
/**
 * Admin - News List
 * Daftar berita dengan filter dan pagination
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$pdo = db();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filter
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where = [];
$params = [];

if ($categoryFilter) {
    $where[] = "n.category_id = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $where[] = "n.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = "(n.title LIKE ? OR n.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM news n $whereClause");
$stmtCount->execute($params);
$totalNews = $stmtCount->fetchColumn();
$totalPages = ceil($totalNews / $perPage);

// Get news
$params[] = $perPage;
$params[] = $offset;
$stmtNews = $pdo->prepare("
    SELECT n.*, c.name as category_name,
           (SELECT COUNT(*) FROM attachments WHERE news_id = n.id) as attachment_count
    FROM news n 
    LEFT JOIN categories c ON n.category_id = c.id 
    $whereClause
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$stmtNews->execute($params);
$newsList = $stmtNews->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - Admin Panel</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#667eea', secondary: '#764ba2' } } }
        }
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 antialiased">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white fixed h-full z-40">
            <div class="p-6">
                <a href="../../index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center">
                        <i class="fas fa-newspaper text-white"></i>
                    </div>
                    <span class="text-xl font-bold">Admin Panel</span>
                </a>
            </div>
            
            <nav class="mt-6">
                <a href="../index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="index.php" class="flex items-center space-x-3 px-6 py-3 bg-white/10 border-r-4 border-primary">
                    <i class="fas fa-newspaper w-5"></i>
                    <span>Kelola Berita</span>
                </a>
                <a href="create.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-plus-circle w-5"></i>
                    <span>Tambah Berita</span>
                </a>
                <a href="../categories/index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i class="fas fa-folder w-5"></i>
                    <span>Kategori</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Kelola Berita</h1>
                    <p class="text-gray-500"><?= $totalNews ?> total berita</p>
                </div>
                <a href="create.php" class="flex items-center space-x-2 px-5 py-2.5 bg-gradient-to-r from-primary to-secondary text-white rounded-xl font-medium hover:shadow-lg transition-all">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Berita</span>
                </a>
            </div>

            <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-2xl p-4 mb-6 shadow-sm border border-gray-100">
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Cari berita..." 
                                   class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    <select name="category" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-primary bg-white">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-primary bg-white">
                        <option value="">Semua Status</option>
                        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl font-medium hover:bg-primary/90 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="index.php" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                        Reset
                    </a>
                </form>
            </div>

            <!-- News Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Berita</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Views</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Lampiran</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($newsList)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">Tidak ada berita ditemukan</p>
                                    <a href="create.php" class="inline-block mt-4 px-6 py-2 bg-primary text-white rounded-xl">
                                        Tambah Berita Baru
                                    </a>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($newsList as $news): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-16 h-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                                <?php if ($news['thumbnail']): ?>
                                                <img src="../../uploads/thumbnails/<?= $news['thumbnail'] ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-image text-xl"></i>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0 max-w-[300px]">
                                                <p class="font-medium text-gray-800 truncate"><?= htmlspecialchars($news['title']) ?></p>
                                                <p class="text-xs text-gray-400 truncate"><?= truncateText(strip_tags($news['content']), 60) ?></p>
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
                                    <td class="px-6 py-4 text-gray-600">
                                        <i class="far fa-eye mr-1 text-gray-400"></i><?= number_format($news['views']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?php if ($news['attachment_count'] > 0): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-600 text-xs rounded-full">
                                            <i class="fas fa-paperclip mr-1"></i><?= $news['attachment_count'] ?> file
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= formatDate($news['created_at'], 'short') ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-1">
                                            <a href="edit.php?id=<?= $news['id'] ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../../detail.php?slug=<?= $news['slug'] ?>" target="_blank" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors" title="View">
                                                <i class="fas fa-external-link-alt"></i>
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Menampilkan <?= $offset + 1 ?> - <?= min($offset + $perPage, $totalNews) ?> dari <?= $totalNews ?> berita
                    </p>
                    <div class="flex items-center space-x-2">
                        <?php 
                        $queryParams = $_GET;
                        unset($queryParams['page']);
                        $queryString = http_build_query($queryParams);
                        ?>
                        
                        <?php if ($page > 1): ?>
                        <a href="?<?= $queryString ?>&page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?<?= $queryString ?>&page=<?= $i ?>" 
                           class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?<?= $queryString ?>&page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(id, title) {
            Swal.fire({
                title: 'Hapus Berita?',
                html: `Anda yakin ingin menghapus:<br><strong>${title}</strong><br><small class="text-gray-500">Semua lampiran juga akan dihapus</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete.php?id=${id}`;
                }
            });
        }
    </script>
</body>
</html>

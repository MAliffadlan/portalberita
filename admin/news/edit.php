<?php
/**
 * Admin - Edit News
 * Form untuk mengedit berita
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';

$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get news
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    redirect('index.php', 'error', 'Berita tidak ditemukan');
}

// Get attachments
$stmtAttach = $pdo->prepare("SELECT * FROM attachments WHERE news_id = ?");
$stmtAttach->execute([$id]);
$attachments = $stmtAttach->fetchAll();

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $excerpt = sanitize($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $author = sanitize($_POST['author'] ?? 'Admin');
        
        if (empty($title)) $errors[] = 'Judul berita wajib diisi';
        if (empty($content)) $errors[] = 'Konten berita wajib diisi';
        if ($category_id <= 0) $errors[] = 'Pilih kategori';
        
        // Update slug if title changed
        $slug = $news['slug'];
        if ($title !== $news['title']) {
            $slug = createSlug($title);
            $slugCheck = $pdo->prepare("SELECT id FROM news WHERE slug = ? AND id != ?");
            $slugCheck->execute([$slug, $id]);
            if ($slugCheck->fetch()) {
                $slug .= '-' . time();
            }
        }
        
        // Handle thumbnail
        $thumbnail = $news['thumbnail'];
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadThumbnail($_FILES['thumbnail']);
            if ($uploadResult['success']) {
                // Delete old thumbnail
                if ($news['thumbnail']) {
                    deleteThumbnail($news['thumbnail']);
                }
                $thumbnail = $uploadResult['filename'];
            } else {
                $errors[] = 'Thumbnail: ' . $uploadResult['error'];
            }
        }
        
        // Handle remove thumbnail
        if (isset($_POST['remove_thumbnail']) && $_POST['remove_thumbnail'] === '1') {
            if ($news['thumbnail']) {
                deleteThumbnail($news['thumbnail']);
            }
            $thumbnail = null;
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Update news
                $publishedAt = $news['published_at'];
                if ($status === 'published' && !$publishedAt) {
                    $publishedAt = date('Y-m-d H:i:s');
                }
                
                $stmt = $pdo->prepare("
                    UPDATE news SET 
                        category_id = ?, title = ?, slug = ?, excerpt = ?, content = ?,
                        thumbnail = ?, author = ?, status = ?, is_featured = ?, published_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([$category_id, $title, $slug, $excerpt, $content, $thumbnail, $author, $status, $is_featured, $publishedAt, $id]);
                
                // Handle delete attachments
                if (isset($_POST['delete_attachments']) && is_array($_POST['delete_attachments'])) {
                    foreach ($_POST['delete_attachments'] as $attachId) {
                        $stmtGetAttach = $pdo->prepare("SELECT filename FROM attachments WHERE id = ? AND news_id = ?");
                        $stmtGetAttach->execute([$attachId, $id]);
                        $attach = $stmtGetAttach->fetch();
                        if ($attach) {
                            deleteAttachment($attach['filename']);
                            $pdo->prepare("DELETE FROM attachments WHERE id = ?")->execute([$attachId]);
                        }
                    }
                }
                
                // Upload new attachments
                if (isset($_FILES['attachments'])) {
                    $attachResults = uploadMultipleAttachments($_FILES['attachments']);
                    foreach ($attachResults['success'] as $attach) {
                        $stmtAttach = $pdo->prepare("
                            INSERT INTO attachments (news_id, filename, original_name, file_type, file_size)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmtAttach->execute([$id, $attach['filename'], $attach['original_name'], $attach['file_type'], $attach['file_size']]);
                    }
                }
                
                $pdo->commit();
                redirect('index.php', 'success', 'Berita berhasil diperbarui!');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Gagal memperbarui berita: ' . $e->getMessage();
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita - Admin Panel</title>
    
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
                <a href="../index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
                </a>
                <a href="index.php" class="flex items-center space-x-3 px-6 py-3 bg-white/10 border-r-4 border-primary">
                    <i class="fas fa-newspaper w-5"></i><span>Kelola Berita</span>
                </a>
                <a href="create.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-plus-circle w-5"></i><span>Tambah Berita</span>
                </a>
                <a href="../categories/index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-folder w-5"></i><span>Kategori</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <div class="mb-8">
                <div class="flex items-center space-x-3 text-gray-500 text-sm mb-2">
                    <a href="index.php" class="hover:text-primary">Berita</a>
                    <span>/</span>
                    <span class="text-gray-700">Edit Berita</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Berita</h1>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-200 rounded-xl text-red-700">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Berita</h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Berita *</label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($news['title']) ?>" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ringkasan</label>
                                    <textarea name="excerpt" rows="2"
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary"><?= htmlspecialchars($news['excerpt']) ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Konten Berita *</label>
                                    <textarea name="content" rows="12" required
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary"><?= htmlspecialchars($news['content']) ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Attachments -->
                        <?php if (!empty($attachments)): ?>
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Lampiran Saat Ini</h2>
                            <div class="space-y-3">
                                <?php foreach ($attachments as $attach): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= str_contains($attach['file_type'], 'pdf') ? 'bg-red-100 text-red-500' : 'bg-blue-100 text-blue-500' ?>">
                                            <i class="fas <?= str_contains($attach['file_type'], 'pdf') ? 'fa-file-pdf' : 'fa-file-word' ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?= htmlspecialchars($attach['original_name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= formatFileSize($attach['file_size']) ?> • <?= $attach['downloads'] ?> downloads</p>
                                        </div>
                                    </div>
                                    <label class="flex items-center space-x-2 text-red-500 cursor-pointer">
                                        <input type="checkbox" name="delete_attachments[]" value="<?= $attach['id'] ?>" class="rounded">
                                        <span class="text-sm">Hapus</span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Add New Attachments -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Tambah Lampiran Baru</h2>
                            <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.zip"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                            <p class="text-xs text-gray-500 mt-2">Format: PDF, DOC, DOCX, ZIP (Maks. 10MB per file)</p>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Publish Options -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Publikasi</h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-white">
                                        <option value="draft" <?= $news['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="published" <?= $news['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                                    <select name="category_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-white">
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $news['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Penulis</label>
                                    <input type="text" name="author" value="<?= htmlspecialchars($news['author']) ?>"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_featured" id="is_featured" class="w-4 h-4 text-primary rounded"
                                           <?= $news['is_featured'] ? 'checked' : '' ?>>
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Berita Unggulan</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thumbnail -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Thumbnail</h2>
                            
                            <?php if ($news['thumbnail']): ?>
                            <div class="mb-4">
                                <img src="../../uploads/thumbnails/<?= $news['thumbnail'] ?>" class="w-full rounded-xl mb-2">
                                <label class="flex items-center space-x-2 text-red-500 cursor-pointer">
                                    <input type="checkbox" name="remove_thumbnail" value="1" class="rounded">
                                    <span class="text-sm">Hapus thumbnail</span>
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <input type="file" name="thumbnail" accept="image/jpeg,image/png"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                            <p class="text-xs text-gray-500 mt-2">JPG, PNG (Maks. 2MB)</p>
                        </div>
                        
                        <!-- Submit -->
                        <div class="flex space-x-3">
                            <button type="submit" class="flex-1 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                            <a href="index.php" class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl font-semibold hover:bg-gray-200">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</body>
</html>

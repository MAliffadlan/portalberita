<?php
/**
 * Admin - Create News
 * Form untuk menambah berita baru
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';

$pdo = db();

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$errors = [];
$old = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } else {
        // Get form data
        $title = sanitize($_POST['title'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $excerpt = sanitize($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? ''; // Allow HTML
        $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $author = sanitize($_POST['author'] ?? 'Admin');
        
        $old = compact('title', 'category_id', 'excerpt', 'content', 'status', 'is_featured', 'author');
        
        // Validate
        if (empty($title)) $errors[] = 'Judul berita wajib diisi';
        if (empty($content)) $errors[] = 'Konten berita wajib diisi';
        if ($category_id <= 0) $errors[] = 'Pilih kategori';
        
        // Generate slug
        $slug = createSlug($title);
        $slugCheck = $pdo->prepare("SELECT id FROM news WHERE slug = ?");
        $slugCheck->execute([$slug]);
        if ($slugCheck->fetch()) {
            $slug .= '-' . time();
        }
        
        // Upload thumbnail
        $thumbnail = null;
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadThumbnail($_FILES['thumbnail']);
            if ($uploadResult['success']) {
                $thumbnail = $uploadResult['filename'];
            } else {
                $errors[] = 'Thumbnail: ' . $uploadResult['error'];
            }
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Insert news
                $stmt = $pdo->prepare("
                    INSERT INTO news (category_id, title, slug, excerpt, content, thumbnail, author, status, is_featured, published_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$category_id, $title, $slug, $excerpt, $content, $thumbnail, $author, $status, $is_featured, $publishedAt]);
                
                $newsId = $pdo->lastInsertId();
                
                // Upload attachments
                if (isset($_FILES['attachments'])) {
                    $attachResults = uploadMultipleAttachments($_FILES['attachments']);
                    
                    foreach ($attachResults['success'] as $attach) {
                        $stmtAttach = $pdo->prepare("
                            INSERT INTO attachments (news_id, filename, original_name, file_type, file_size)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmtAttach->execute([
                            $newsId,
                            $attach['filename'],
                            $attach['original_name'],
                            $attach['file_type'],
                            $attach['file_size']
                        ]);
                    }
                    
                    foreach ($attachResults['errors'] as $err) {
                        $errors[] = "Lampiran {$err['file']}: {$err['error']}";
                    }
                }
                
                $pdo->commit();
                
                redirect('index.php', 'success', 'Berita berhasil ditambahkan!');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                if ($thumbnail) {
                    deleteThumbnail($thumbnail);
                }
                $errors[] = 'Gagal menyimpan berita: ' . $e->getMessage();
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
    <title>Tambah Berita - Admin Panel</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#2563eb', secondary: '#1d4ed8' } } }
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
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-newspaper text-white"></i>
                    </div>
                    <span class="text-xl font-bold">Admin Panel</span>
                </a>
            </div>
            
            <nav class="mt-6">
                <a href="../index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
                </a>
                <a href="index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-newspaper w-5"></i><span>Kelola Berita</span>
                </a>
                <a href="create.php" class="flex items-center space-x-3 px-6 py-3 bg-white/10 border-r-4 border-blue-500">
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
                    <span class="text-gray-700">Tambah Berita</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Tambah Berita Baru</h1>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-200 rounded-xl text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>
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
                                    <input type="text" name="title" value="<?= htmlspecialchars($old['title'] ?? '') ?>" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                           placeholder="Masukkan judul berita">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ringkasan</label>
                                    <textarea name="excerpt" rows="2"
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                              placeholder="Ringkasan singkat berita (opsional)"><?= htmlspecialchars($old['excerpt'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Konten Berita *</label>
                                    <textarea name="content" rows="12" required
                                              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                              placeholder="Tulis konten berita (HTML diperbolehkan)"><?= htmlspecialchars($old['content'] ?? '') ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Mendukung format HTML seperti &lt;p&gt;, &lt;h2&gt;, &lt;ul&gt;, dll.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attachments -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-paperclip mr-2 text-primary"></i>Lampiran
                            </h2>
                            
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:border-primary transition-colors" id="attachment-dropzone">
                                <input type="file" name="attachments[]" id="attachments" multiple accept=".pdf,.doc,.docx,.zip" class="hidden">
                                <div class="text-gray-400 mb-3">
                                    <i class="fas fa-cloud-upload-alt text-4xl"></i>
                                </div>
                                <p class="text-gray-600 mb-2">Drag & drop file atau <label for="attachments" class="text-primary cursor-pointer hover:underline">browse</label></p>
                                <p class="text-xs text-gray-400">Format: PDF, DOC, DOCX, ZIP (Maks. 10MB per file)</p>
                                <div id="attachment-list" class="mt-4 space-y-2"></div>
                            </div>
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
                                    <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary bg-white">
                                        <option value="draft" <?= ($old['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="published" <?= ($old['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                                    <select name="category_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary bg-white">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($old['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Penulis</label>
                                    <input type="text" name="author" value="<?= htmlspecialchars($old['author'] ?? 'Admin') ?>"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary">
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_featured" id="is_featured" class="w-4 h-4 text-primary rounded"
                                           <?= ($old['is_featured'] ?? false) ? 'checked' : '' ?>>
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Jadikan Berita Unggulan</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thumbnail -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Thumbnail</h2>
                            
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-primary transition-colors cursor-pointer" id="thumbnail-dropzone">
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/jpeg,image/png" class="hidden">
                                <div id="thumbnail-preview" class="hidden mb-3">
                                    <img src="" alt="Preview" class="max-h-40 mx-auto rounded-lg">
                                </div>
                                <div id="thumbnail-placeholder">
                                    <i class="fas fa-image text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600">Klik untuk upload</p>
                                    <p class="text-xs text-gray-400 mt-1">JPG, PNG (Maks. 2MB)</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit -->
                        <div class="flex space-x-3">
                            <button type="submit" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 hover:shadow-lg transition-all">
                                <i class="fas fa-save mr-2"></i>Simpan Berita
                            </button>
                            <a href="index.php" class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Thumbnail preview
        const thumbnailInput = document.getElementById('thumbnail');
        const thumbnailDropzone = document.getElementById('thumbnail-dropzone');
        const thumbnailPreview = document.getElementById('thumbnail-preview');
        const thumbnailPlaceholder = document.getElementById('thumbnail-placeholder');
        
        thumbnailDropzone.addEventListener('click', () => thumbnailInput.click());
        
        thumbnailInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    thumbnailPreview.querySelector('img').src = e.target.result;
                    thumbnailPreview.classList.remove('hidden');
                    thumbnailPlaceholder.classList.add('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Attachment list
        const attachmentInput = document.getElementById('attachments');
        const attachmentList = document.getElementById('attachment-list');
        
        attachmentInput.addEventListener('change', function() {
            attachmentList.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                const icon = file.type.includes('pdf') ? 'fa-file-pdf text-red-500' : 'fa-file-word text-blue-500';
                div.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <i class="fas ${icon}"></i>
                        <span class="text-sm text-gray-700">${file.name}</span>
                    </div>
                    <span class="text-xs text-gray-400">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                `;
                attachmentList.appendChild(div);
            });
        });
    </script>
</body>
</html>

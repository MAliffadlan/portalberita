<?php
/**
 * Admin - Categories Management
 * CRUD kategori berita
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$pdo = db();

$errors = [];
$editCategory = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Check if category has news
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM news WHERE category_id = ?");
    $stmtCheck->execute([$deleteId]);
    $newsCount = $stmtCheck->fetchColumn();
    
    if ($newsCount > 0) {
        setFlash('error', "Tidak dapat menghapus kategori. Ada $newsCount berita yang menggunakan kategori ini.");
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$deleteId]);
        setFlash('success', 'Kategori berhasil dihapus!');
    }
    redirect('index.php');
}

// Handle Edit (load data)
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    if (empty($name)) {
        $errors[] = 'Nama kategori wajib diisi';
    }
    
    $slug = createSlug($name);
    
    // Check slug unique
    $slugCheck = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $slugCheck->execute([$slug, $categoryId]);
    if ($slugCheck->fetch()) {
        $errors[] = 'Nama kategori sudah ada';
    }
    
    if (empty($errors)) {
        if ($categoryId > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $categoryId]);
            setFlash('success', 'Kategori berhasil diperbarui!');
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            setFlash('success', 'Kategori berhasil ditambahkan!');
        }
        redirect('index.php');
    }
}

// Get all categories with news count
$categories = $pdo->query("
    SELECT c.*, COUNT(n.id) as news_count 
    FROM categories c 
    LEFT JOIN news n ON c.id = n.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();

$flash = getFlash();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin Panel</title>
    
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
                <a href="../news/index.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-newspaper w-5"></i><span>Kelola Berita</span>
                </a>
                <a href="../news/create.php" class="flex items-center space-x-3 px-6 py-3 text-gray-400 hover:text-white hover:bg-white/5">
                    <i class="fas fa-plus-circle w-5"></i><span>Tambah Berita</span>
                </a>
                <a href="index.php" class="flex items-center space-x-3 px-6 py-3 bg-white/10 border-r-4 border-primary">
                    <i class="fas fa-folder w-5"></i><span>Kategori</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Kelola Kategori</h1>
                    <p class="text-gray-500"><?= count($categories) ?> kategori</p>
                </div>
            </div>

            <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            <?= $editCategory ? 'Edit Kategori' : 'Tambah Kategori' ?>
                        </h2>
                        
                        <?php if (!empty($errors)): ?>
                        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                            <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <?php if ($editCategory): ?>
                            <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
                            <?php endif; ?>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori *</label>
                                <input type="text" name="name" required
                                       value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary"
                                       placeholder="Contoh: Teknologi">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea name="description" rows="3"
                                          class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-primary"
                                          placeholder="Deskripsi singkat kategori"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="submit" class="flex-1 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                                    <i class="fas fa-save mr-2"></i><?= $editCategory ? 'Update' : 'Simpan' ?>
                                </button>
                                <?php if ($editCategory): ?>
                                <a href="index.php" class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl font-semibold hover:bg-gray-200">
                                    Batal
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Category List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Slug</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Berita</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                                            <p>Belum ada kategori</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $cat): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                                                        <i class="fas fa-folder text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($cat['name']) ?></p>
                                                        <?php if ($cat['description']): ?>
                                                        <p class="text-xs text-gray-500 truncate max-w-[200px]"><?= htmlspecialchars($cat['description']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                                    <?= htmlspecialchars($cat['slug']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 bg-blue-100 text-blue-600 text-xs font-medium rounded-full">
                                                    <?= $cat['news_count'] ?> berita
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-2">
                                                    <a href="?edit=<?= $cat['id'] ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../../category.php?slug=<?= $cat['slug'] ?>" target="_blank" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors" title="View">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(<?= $cat['id'] ?>, '<?= addslashes($cat['name']) ?>', <?= $cat['news_count'] ?>)" 
                                                            class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
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
                </div>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(id, name, newsCount) {
            if (newsCount > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Menghapus',
                    html: `Kategori <strong>${name}</strong> memiliki <strong>${newsCount} berita</strong>.<br>Pindahkan atau hapus berita terlebih dahulu.`,
                    confirmButtonColor: '#667eea'
                });
            } else {
                Swal.fire({
                    title: 'Hapus Kategori?',
                    html: `Anda yakin ingin menghapus kategori <strong>${name}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `?delete=${id}`;
                    }
                });
            }
        }
    </script>
</body>
</html>

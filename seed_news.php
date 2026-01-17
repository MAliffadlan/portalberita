<?php
/**
 * Seeder Berita Dummy
 * Jalankan file ini sekali untuk mengisi database dengan 10 berita contoh.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

echo "Memulai seeding berita...\n";

// 1. Pastikan Kategori Ada
$categories = ['Windows', 'MacPoin', 'Android', 'Review', 'Tutorial'];
$catIds = [];

foreach ($categories as $cat) {
    try {
        $slug = createSlug($cat);
        // Cek dulu
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $existing = $stmt->fetch();

        if ($existing) {
            $catIds[] = $existing['id'];
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmtInsert->execute([$cat, $slug]);
            $catIds[] = $pdo->lastInsertId();
            echo "Kategori dibuat: $cat\n";
        }
    } catch (Exception $e) { /* Ignore duplicate errors */ }
}

// 2. Data Berita Dummy (10 Item)
$dummyNews = [
    [
        'title' => 'Windows 12 Dikabarkan Rilis Tahun Depan dengan Fokus AI',
        'content' => '<p>Microsoft dikabarkan sedang mempersiapkan penerus Windows 11 yang disebut-sebut sebagai Windows 12. Fokus utama sistem operasi baru ini adalah integrasi kecerdasan buatan (AI) yang lebih dalam ke dalam sistem.</p><p>Menurut bocoran, UI akan dirombak total dengan floating taskbar dan top bar ala macOS.</p>',
        'cat_idx' => 0 // Windows
    ],
    [
        'title' => 'Review MacBook Air M3: Laptop Terbaik untuk Kebanyakan Orang?',
        'content' => '<p>Apple baru saja merilis MacBook Air dengan chip M3 yang lebih kencang. Kami telah mengujinya selama seminggu penuh.</p><h2>Performa</h2><p>Chip M3 memberikan peningkatan performa grafis yang signifikan berkat fitur Dynamic Caching.</p>',
        'cat_idx' => 1 // MacPoin
    ],
    [
        'title' => 'Cara Install Windows 11 di Laptop Jadul Tanpa TPM 2.0',
        'content' => '<p>Banyak pengguna yang kecewa karena laptop mereka tidak support Windows 11. Namun, ada cara mudah untuk mengakalinya menggunakan Rufus.</p><ol><li>Download ISO Windows 11</li><li>Buka Rufus</li><li>Pilih opsi "No TPM / No Secure Boot"</li></ol>',
        'cat_idx' => 4 // Tutorial
    ],
    [
        'title' => 'Samsung Galaxy S24 Ultra Hadir dengan Frame Titanium',
        'content' => '<p>Samsung akhirnya mengikuti jejak Apple dengan menyematkan frame Titanium pada flagship terbaru mereka, Galaxy S24 Ultra.</p><p>Selain itu, fitur Galaxy AI menjadi nilai jual utama tahun ini.</p>',
        'cat_idx' => 2 // Android
    ],
    [
        'title' => 'Microsoft Edge Kini Punya Fitur Split Screen Bawaan',
        'content' => '<p>Fitur produktivitas baru hadir di Microsoft Edge. Kini pengguna bisa membuka dua tab berdampingan dalam satu jendela browser tanpa ekstensi tambahan.</p>',
        'cat_idx' => 0
    ],
    [
        'title' => 'NVIDIA Luncurkan RTX 5090, Harganya Bikin Dompet Menjerit',
        'content' => '<p>Kartu grafis monster terbaru dari NVIDIA telah tiba. RTX 5090 menjanjikan performa 2x lipat dari 4090, namun dengan konsumsi daya yang juga mengerikan.</p>',
        'cat_idx' => 3 // Review
    ],
    [
        'title' => 'Update WhatsApp Terbaru Bikin Tampilan Android Mirip iOS',
        'content' => '<p>Meta merilis update antarmuka untuk WhatsApp Android. Navigasi bar kini pindah ke bawah, membuatnya sangat mirip dengan versi iOS.</p>',
        'cat_idx' => 2
    ],
    [
        'title' => 'Cara Mematikan Iklan di Windows 11 Start Menu',
        'content' => '<p>Microsoft mulai menyisipkan rekomendasi aplikasi di Start Menu yang terasa seperti iklan. Berikut adalah langkah-langkah untuk mematikannya secara permanen.</p>',
        'cat_idx' => 4
    ],
    [
        'title' => 'Linux Mint 22 "Wilma" Resmi Dirilis, Apa yang Baru?',
        'content' => '<p>Distro Linux favorit pemula, Linux Mint, merilis versi terbaru berbasis Ubuntu 24.04 LTS. Simak fitur-fitur barunya di sini.</p>',
        'cat_idx' => 3
    ],
    [
        'title' => 'Google Chrome Mulai Blokir Cookie Pihak Ketiga Secara Default',
        'content' => '<p>Langkah besar Google untuk privasi pengguna dimulai hari ini dengan memblokir third-party cookies untuk 1% pengguna Chrome.</p>',
        'cat_idx' => 0
    ]
];

// 3. Masukkan ke Database
foreach ($dummyNews as $i => $news) {
    $title = $news['title'];
    $slug = createSlug($title);
    
    // Cek duplikat slug
    $check = $pdo->prepare("SELECT id FROM news WHERE slug = ?");
    $check->execute([$slug]);
    if ($check->fetch()) {
        echo "Skip (Sudah ada): $title\n";
        continue;
    }

    $categoryId = $catIds[$news['cat_idx']] ?? $catIds[0];
    $author = ['Joko', 'Budi', 'Siti', 'Admin'][rand(0, 3)];
    $views = rand(100, 5000);
    $status = 'published';
    $is_featured = ($i < 2) ? 1 : 0; // 2 berita pertama jadi featured

    $stmt = $pdo->prepare("
        INSERT INTO news (category_id, title, slug, excerpt, content, author, status, is_featured, views, created_at, published_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $excerpt = substr(strip_tags($news['content']), 0, 150) . '...';

    $stmt->execute([
        $categoryId,
        $title,
        $slug,
        $excerpt,
        $news['content'],
        $author,
        $status,
        $is_featured,
        $views
    ]);

    echo "Berhasil tambah: $title\n";
}

echo "\nSelesai! 10 Berita telah ditambahkan.\n";
echo "Silakan refresh halaman website Anda.\n";

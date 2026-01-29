<?php
/**
 * Setup Foreign Keys - Menambahkan relasi antar tabel
 * Jalankan sekali: http://localhost/portalberita/setup_foreign_keys.php
 */

require_once __DIR__ . '/config/database.php';
$pdo = db();

echo "<h2>🔗 Setup Foreign Keys untuk Portal Berita</h2>";
echo "<pre>";

// Pastikan InnoDB engine untuk semua tabel
$tables = ['news', 'attachments', 'categories', 'users', 'comments'];

echo "=== STEP 1: Mengecek dan mengubah engine ke InnoDB ===\n";
foreach ($tables as $table) {
    try {
        $pdo->exec("ALTER TABLE `$table` ENGINE = InnoDB");
        echo "✅ Tabel '$table' sudah menggunakan InnoDB\n";
    } catch (PDOException $e) {
        echo "⚠️ Tabel '$table': " . $e->getMessage() . "\n";
    }
}

echo "\n=== STEP 2: Menghapus Foreign Keys lama (jika ada) ===\n";

// Hapus FK lama untuk menghindari duplikat
$dropFKs = [
    "ALTER TABLE `news` DROP FOREIGN KEY IF EXISTS `fk_news_category`",
    "ALTER TABLE `attachments` DROP FOREIGN KEY IF EXISTS `fk_attachments_news`",
];

foreach ($dropFKs as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Drop FK berhasil\n";
    } catch (PDOException $e) {
        // Ignore error jika FK tidak ada
        echo "ℹ️ Skip: " . $e->getMessage() . "\n";
    }
}

echo "\n=== STEP 3: Menambahkan Foreign Keys ===\n";

// FK untuk tabel NEWS -> CATEGORIES
try {
    $pdo->exec("
        ALTER TABLE `news` 
        ADD CONSTRAINT `fk_news_category` 
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
    ");
    echo "✅ FK news.category_id -> categories.id berhasil ditambahkan\n";
} catch (PDOException $e) {
    echo "❌ FK news->categories: " . $e->getMessage() . "\n";
}

// FK untuk tabel ATTACHMENTS -> NEWS  
try {
    $pdo->exec("
        ALTER TABLE `attachments` 
        ADD CONSTRAINT `fk_attachments_news` 
        FOREIGN KEY (`news_id`) REFERENCES `news`(`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
    ");
    echo "✅ FK attachments.news_id -> news.id berhasil ditambahkan\n";
} catch (PDOException $e) {
    echo "❌ FK attachments->news: " . $e->getMessage() . "\n";
}

echo "\n=== STEP 4: Verifikasi Foreign Keys ===\n";

// Tampilkan semua FK yang ada
$stmt = $pdo->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME
");

$fks = $stmt->fetchAll();

if (empty($fks)) {
    echo "⚠️ Tidak ada Foreign Key yang terdeteksi!\n";
} else {
    echo "\n📋 Daftar Foreign Keys:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-15s %-15s %-25s %-15s\n", "TABEL", "KOLOM", "CONSTRAINT", "REFERENSI");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($fks as $fk) {
        printf("%-15s %-15s %-25s %-15s\n", 
            $fk['TABLE_NAME'],
            $fk['COLUMN_NAME'],
            $fk['CONSTRAINT_NAME'],
            $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME']
        );
    }
    echo str_repeat("-", 80) . "\n";
}

echo "\n✅ SELESAI! Sekarang buka phpMyAdmin Designer dan refresh.\n";
echo "   Garis relasi seharusnya sudah muncul otomatis.\n";
echo "</pre>";

echo "<br><a href='index.php' style='padding:10px 20px; background:#0078d4; color:white; text-decoration:none; border-radius:5px;'>← Kembali ke Beranda</a>";
?>

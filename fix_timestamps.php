<?php
require_once __DIR__ . '/config/database.php';
$pdo = db();

// Ambil semua berita
$news = $pdo->query("SELECT id FROM news ORDER BY id DESC")->fetchAll();

$currentTime = time();

foreach ($news as $index => $item) {
    // Mundurkan waktu 1 jam untuk setiap berita sebelumnya agar urutan jelas
    // Berita ID terbesar (terbaru) akan punya waktu paling baru
    $timestamp = $currentTime - ($index * 3600); 
    $date = date('Y-m-d H:i:s', $timestamp);
    
    $stmt = $pdo->prepare("UPDATE news SET published_at = ? WHERE id = ?");
    $stmt->execute([$date, $item['id']]);
    
    echo "Updated News ID {$item['id']} to $date\n";
}

echo "Selesai update timestamp.\n";

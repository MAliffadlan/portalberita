<?php
/**
 * Admin - Delete News
 * Hapus berita beserta file terkait
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect('index.php', 'error', 'ID berita tidak valid');
}

$pdo = db();

try {
    // Get news data
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    
    if (!$news) {
        redirect('index.php', 'error', 'Berita tidak ditemukan');
    }
    
    // Get attachments
    $stmtAttach = $pdo->prepare("SELECT filename FROM attachments WHERE news_id = ?");
    $stmtAttach->execute([$id]);
    $attachments = $stmtAttach->fetchAll();
    
    $pdo->beginTransaction();
    
    // Delete attachments from database (CASCADE will handle this, but we need to delete files)
    foreach ($attachments as $attach) {
        deleteAttachment($attach['filename']);
    }
    
    // Delete thumbnail
    if ($news['thumbnail']) {
        deleteThumbnail($news['thumbnail']);
    }
    
    // Delete news (attachments will be deleted automatically due to CASCADE)
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    
    $pdo->commit();
    
    redirect('index.php', 'success', 'Berita berhasil dihapus!');
    
} catch (Exception $e) {
    $pdo->rollBack();
    redirect('index.php', 'error', 'Gagal menghapus berita: ' . $e->getMessage());
}

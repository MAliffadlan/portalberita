<?php
/**
 * Portal Berita - Download Handler
 * Secure download untuk file attachment dan ZIP
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/zip.php';

$pdo = db();

// Download individual file
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Ambil data attachment
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$id]);
    $attachment = $stmt->fetch();
    
    if (!$attachment) {
        http_response_code(404);
        die('File tidak ditemukan');
    }
    
    $filePath = ATTACHMENT_PATH . $attachment['filename'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File tidak ditemukan di server');
    }
    
    // Update download count
    $pdo->prepare("UPDATE attachments SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);
    
    // Set headers
    header('Content-Type: ' . $attachment['file_type']);
    header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($filePath);
    exit;
}

// Download ZIP of all attachments from a news
if (isset($_GET['news_id']) && isset($_GET['type']) && $_GET['type'] === 'zip') {
    $newsId = (int)$_GET['news_id'];
    
    $result = createAttachmentZip($newsId);
    
    if (!$result['success']) {
        // Redirect back dengan error
        if (isset($_SERVER['HTTP_REFERER'])) {
            setFlash('error', $result['error']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            http_response_code(400);
            die($result['error']);
        }
        exit;
    }
    
    // Download dan hapus file temporary
    downloadAndCleanupZip($result['path'], $result['filename']);
}

// Invalid request
http_response_code(400);
die('Request tidak valid');

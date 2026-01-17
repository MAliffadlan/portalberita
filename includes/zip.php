<?php
/**
 * ZIP Archive Handler
 * Fungsi untuk mengompres lampiran berita menjadi file ZIP
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Buat ZIP dari semua lampiran berita
 * @param int $newsId - ID berita
 * @return array - ['success' => bool, 'filename' => string, 'error' => string]
 */
function createAttachmentZip($newsId) {
    $result = ['success' => false, 'filename' => '', 'path' => '', 'error' => ''];
    
    // Validasi ZipArchive tersedia
    if (!class_exists('ZipArchive')) {
        $result['error'] = 'Extension ZipArchive tidak tersedia di server';
        return $result;
    }
    
    // Ambil data berita
    $pdo = db();
    $stmt = $pdo->prepare("SELECT title, slug FROM news WHERE id = ?");
    $stmt->execute([$newsId]);
    $news = $stmt->fetch();
    
    if (!$news) {
        $result['error'] = 'Berita tidak ditemukan';
        return $result;
    }
    
    // Ambil semua lampiran
    $stmt = $pdo->prepare("SELECT filename, original_name FROM attachments WHERE news_id = ?");
    $stmt->execute([$newsId]);
    $attachments = $stmt->fetchAll();
    
    if (empty($attachments)) {
        $result['error'] = 'Tidak ada lampiran untuk berita ini';
        return $result;
    }
    
    // Buat nama file ZIP
    $zipFilename = 'lampiran_' . $news['slug'] . '_' . time() . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipFilename;
    
    // Buat ZIP archive
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        $result['error'] = 'Gagal membuat file ZIP';
        return $result;
    }
    
    $addedFiles = 0;
    
    foreach ($attachments as $attachment) {
        $filePath = ATTACHMENT_PATH . $attachment['filename'];
        
        if (file_exists($filePath)) {
            // Gunakan nama asli file dalam ZIP
            $zip->addFile($filePath, $attachment['original_name']);
            $addedFiles++;
        }
    }
    
    $zip->close();
    
    if ($addedFiles === 0) {
        // Hapus ZIP kosong
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        $result['error'] = 'Tidak ada file yang valid untuk dikompres';
        return $result;
    }
    
    $result['success'] = true;
    $result['filename'] = $zipFilename;
    $result['path'] = $zipPath;
    $result['file_count'] = $addedFiles;
    
    return $result;
}

/**
 * Download ZIP file dan hapus setelah selesai
 * @param string $zipPath - Path ke file ZIP
 * @param string $filename - Nama file untuk download
 */
function downloadAndCleanupZip($zipPath, $filename) {
    if (!file_exists($zipPath)) {
        http_response_code(404);
        die('File tidak ditemukan');
    }
    
    // Set headers untuk download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($zipPath);
    
    // Hapus file temporary
    unlink($zipPath);
    
    exit;
}

/**
 * Proses lengkap: buat ZIP dan download
 * @param int $newsId - ID berita
 */
function downloadNewsAttachments($newsId) {
    $result = createAttachmentZip($newsId);
    
    if (!$result['success']) {
        return $result;
    }
    
    downloadAndCleanupZip($result['path'], $result['filename']);
}

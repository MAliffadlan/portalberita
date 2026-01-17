<?php
/**
 * File Upload Handler
 * Fungsi untuk menangani upload file dengan validasi lengkap
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Upload thumbnail berita
 * @param array $file - $_FILES['thumbnail']
 * @return array - ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadThumbnail($file) {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Validasi file ada
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $result['error'] = 'Tidak ada file yang diupload';
        return $result;
    }
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = getUploadErrorMessage($file['error']);
        return $result;
    }
    
    // Validasi tipe file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        $result['error'] = 'Tipe file tidak diizinkan. Gunakan JPG atau PNG';
        return $result;
    }
    
    // Validasi ukuran file
    if ($file['size'] > MAX_THUMBNAIL_SIZE) {
        $result['error'] = 'Ukuran file terlalu besar. Maksimal 2MB';
        return $result;
    }
    
    // Validasi dimensi gambar (optional)
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $result['error'] = 'File bukan gambar yang valid';
        return $result;
    }
    
    // Generate unique filename
    $extension = getFileExtension($file['name']);
    $filename = 'thumb_' . uniqid() . '_' . time() . '.' . $extension;
    $destination = THUMBNAIL_PATH . $filename;
    
    // Buat direktori jika belum ada
    if (!is_dir(THUMBNAIL_PATH)) {
        mkdir(THUMBNAIL_PATH, 0755, true);
    }
    
    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = 'Gagal menyimpan file. Periksa permission folder';
    }
    
    return $result;
}

/**
 * Upload attachment (PDF/DOCX)
 * @param array $file - $_FILES['attachment']
 * @return array - ['success' => bool, 'data' => array, 'error' => string]
 */
function uploadAttachment($file) {
    $result = ['success' => false, 'data' => [], 'error' => ''];
    
    // Validasi file ada
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $result['error'] = 'Tidak ada file yang diupload';
        return $result;
    }
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = getUploadErrorMessage($file['error']);
        return $result;
    }
    
    // Validasi tipe file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_ATTACHMENT_TYPES)) {
        $result['error'] = 'Tipe file tidak diizinkan. Gunakan PDF atau DOCX';
        return $result;
    }
    
    // Validasi ukuran file
    if ($file['size'] > MAX_ATTACHMENT_SIZE) {
        $result['error'] = 'Ukuran file terlalu besar. Maksimal 10MB';
        return $result;
    }
    
    // Generate unique filename
    $extension = getFileExtension($file['name']);
    $filename = 'attach_' . uniqid() . '_' . time() . '.' . $extension;
    $destination = ATTACHMENT_PATH . $filename;
    
    // Buat direktori jika belum ada
    if (!is_dir(ATTACHMENT_PATH)) {
        mkdir(ATTACHMENT_PATH, 0755, true);
    }
    
    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $result['success'] = true;
        $result['data'] = [
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_type' => $mimeType,
            'file_size' => $file['size']
        ];
    } else {
        $result['error'] = 'Gagal menyimpan file. Periksa permission folder';
    }
    
    return $result;
}

/**
 * Upload multiple attachments
 * @param array $files - $_FILES['attachments']
 * @return array - ['success' => array, 'errors' => array]
 */
function uploadMultipleAttachments($files) {
    $results = ['success' => [], 'errors' => []];
    
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $results;
    }
    
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        $singleFile = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        $result = uploadAttachment($singleFile);
        
        if ($result['success']) {
            $results['success'][] = $result['data'];
        } else {
            $results['errors'][] = [
                'file' => $files['name'][$i],
                'error' => $result['error']
            ];
        }
    }
    
    return $results;
}

/**
 * Hapus file thumbnail
 */
function deleteThumbnail($filename) {
    $path = THUMBNAIL_PATH . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Hapus file attachment
 */
function deleteAttachment($filename) {
    $path = ATTACHMENT_PATH . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Get upload error message
 */
function getUploadErrorMessage($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi limit server)',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi limit form)',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
    ];
    
    return $errors[$errorCode] ?? 'Error upload tidak diketahui';
}



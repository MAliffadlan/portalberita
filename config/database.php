<?php
/**
 * Database Configuration
 * Koneksi database menggunakan PDO dengan error handling
 */

// Konfigurasi Database
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'portal_berita');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Konfigurasi Situs
define('SITE_NAME', 'Portal Berita Indonesia');
define('SITE_URL', 'http://localhost/portalberita');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('THUMBNAIL_PATH', UPLOAD_PATH . 'thumbnails/');
define('ATTACHMENT_PATH', UPLOAD_PATH . 'attachments/');

// Konfigurasi Upload
define('MAX_THUMBNAIL_SIZE', 2 * 1024 * 1024); // 2MB
define('MAX_ATTACHMENT_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_ATTACHMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed']);

/**
 * Class Database
 * Singleton pattern untuk koneksi database PDO
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function untuk mendapatkan koneksi database
 */
function db() {
    return Database::getInstance()->getConnection();
}

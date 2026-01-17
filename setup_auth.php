<?php
require_once __DIR__ . '/config/database.php';
$pdo = db();

try {
    // 1. Create table users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            avatar VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabel users berhasil dibuat.\n";

    // 2. Seed Admin & User
    $password = password_hash('password123', PASSWORD_DEFAULT);
    
    // Cek admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
            ->execute(['admin', 'admin@example.com', $password, 'admin']);
        echo "Akun Admin dibuat: admin / password123\n";
    }

    // Cek user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'user'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
            ->execute(['user', 'user@example.com', $password, 'user']);
        echo "Akun User dibuat: user / password123\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

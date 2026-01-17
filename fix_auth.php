<?php
require_once __DIR__ . '/config/database.php';
$pdo = db();

echo "Memperbaiki data user...\n";

// 1. Hapus user lama biar bersih
$pdo->exec("TRUNCATE TABLE users");

// 2. Buat password hash baru yang fresh
$password = password_hash('password123', PASSWORD_BCRYPT); // Eksplisit BCRYPT

// 3. Masukkan Admin
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@example.com', $password, 'admin']);
    echo "Admin created: admin / password123 (Hash OK)\n";
} catch (PDOException $e) {
    echo "Error create admin: " . $e->getMessage() . "\n";
}

// 4. Masukkan User
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['user', 'user@example.com', $password, 'user']);
    echo "User created: user / password123 (Hash OK)\n";
} catch (PDOException $e) {
    echo "Error create user: " . $e->getMessage() . "\n";
}

echo "\nSelesai! Silakan coba login lagi.\n";

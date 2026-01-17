<?php
require_once __DIR__ . '/config/database.php';
$pdo = db();

echo "Memperbaiki struktur tabel user...\n";

try {
    // 1. Ubah kolom role jadi VARCHAR biar aman
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
    echo "Kolom role diubah ke VARCHAR.\n";

    // 2. Bersihkan ulang
    $pdo->exec("TRUNCATE TABLE users");

    // 3. Insert ulang
    $password = password_hash('password123', PASSWORD_BCRYPT);

    // Admin
    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute(['admin', 'admin@example.com', $password, 'admin']);
    echo "✅ Admin berhasil direpair: admin / password123\n";

    // User
    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute(['user', 'user@example.com', $password, 'user']);
    echo "✅ User berhasil direpair: user / password123\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nPERBAIKAN SELESAI.\n";

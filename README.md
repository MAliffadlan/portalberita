# 📰 Portal Berita Indonesia

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white" alt="XAMPP">
</p>

<p align="center">
  <b>Portal berita modern dengan PHP Native dan Tailwind CSS</b><br>
  Design terinspirasi dari WinPoin 🎨
</p>

---

## ✨ Features

### 📱 Frontend
- **Homepage** dengan Hero Section dan Featured News
- **Trending Sidebar** menampilkan berita populer
- **Category Filter** untuk browsing berdasarkan kategori
- **Detail Berita** dengan sistem komentar nested
- **Responsive Design** untuk semua perangkat
- **Load More** dengan AJAX

### 🔐 Authentication
- Login & Register untuk user
- Role-based access (Admin & User)
- Session management

### 💬 Comment System
- Komentar nested (reply to reply)
- Hanya user login yang bisa komentar
- AJAX-based submission

### 🛠️ Admin Panel
- **Dashboard** dengan statistik
- **CRUD Berita** (Create, Read, Update, Delete)
- **Kategori Management**
- **File Upload** (Thumbnail & Attachments)
- Format attachment: PDF, DOC, DOCX, ZIP

### 📦 File Management
- Upload thumbnail berita
- Multiple attachment upload
- Download lampiran
- ZIP download untuk multiple files

---

## 🗂️ Project Structure

```
portalberita/
├── admin/                  # Admin panel
│   ├── index.php          # Dashboard
│   ├── news/              # CRUD Berita
│   │   ├── index.php      # List berita
│   │   ├── create.php     # Tambah berita
│   │   └── edit.php       # Edit berita
│   └── categories/        # Kategori management
├── api/                   # API endpoints
│   ├── comments.php       # API komentar
│   └── load_more_news.php # Load more AJAX
├── assets/
│   └── css/
│       └── custom.css     # Custom styling
├── config/
│   └── database.php       # Konfigurasi database & site
├── includes/
│   ├── functions.php      # Helper functions
│   ├── upload.php         # Upload handler
│   └── zip.php            # ZIP generator
├── uploads/
│   ├── thumbnails/        # Gambar thumbnail
│   └── attachments/       # File lampiran
├── index.php              # Homepage
├── detail.php             # Detail berita
├── category.php           # Filter kategori
├── news.php               # Arsip berita
├── login.php              # Halaman login
├── register.php           # Halaman register
├── logout.php             # Logout handler
├── download.php           # Download handler
└── database.sql           # Database schema
```

---


    
    attachments {
        int id PK
        int news_id FK
        varchar filename
        varchar original_name
        int file_size
    }
    
    comments {
        int id PK
        int news_id FK
        int user_id FK
        int parent_id FK
        text content
        timestamp created_at
    }
    
    users ||--o{ comments : writes
    categories ||--o{ news : contains
    news ||--o{ attachments : has
    news ||--o{ comments : receives
    comments ||--o{ comments : replies_to
```

---

## 🚀 Installation

### Prerequisites
- XAMPP / LAMPP / MAMP dengan PHP 7.4+
- MySQL 5.7+

### Steps

1. **Clone repository**
   ```bash
   git clone https://github.com/username/portalberita.git
   cd portalberita
   ```

2. **Copy ke htdocs**
   ```bash
   # Linux/Mac
   sudo cp -r . /opt/lampp/htdocs/portalberita
   
   # Windows
   xcopy . C:\xampp\htdocs\portalberita /E /I
   ```

3. **Set folder permissions (Linux/Mac)**
   ```bash
   sudo chmod -R 777 /opt/lampp/htdocs/portalberita/uploads
   ```

4. **Import database**
   - Buka phpMyAdmin: `http://localhost/phpmyadmin`
   - Buat database baru: `portal_berita`
   - Import file `database.sql`

5. **Konfigurasi**
   
   Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'portal_berita');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('SITE_URL', 'http://localhost/portalberita');
   ```

6. **Jalankan**
   - Buka browser: `http://localhost/portalberita`

---

## 👤 Default Accounts

| Role  | Username | Password   |
|-------|----------|------------|
| Admin | admin    | admin123   |
| User  | user     | user123    |

> ⚠️ **Penting:** Ganti password default setelah instalasi!

---

## 📸 Screenshots

### Homepage
<img width="1366" height="768" alt="Screenshot_20260117_135043" src="https://github.com/user-attachments/assets/3cc5689e-c46b-4b98-963a-7a02b90c1de5" />


### Admin Dashboard
![Admin](https://via.placeholder.com/800x400?text=Admin+Dashboard)

---

## 🛠️ Tech Stack

| Technology | Purpose |
|------------|---------|
| **PHP 7.4+** | Backend |
| **MySQL** | Database |
| **Tailwind CSS** | Styling |
| **Font Awesome** | Icons |
| **SweetAlert2** | Alerts |
| **PDO** | Database connection |

---

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

<p align="center">
  Made with ❤️ by <b>Portal Berita Team</b>
</p>

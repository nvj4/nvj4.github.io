# FaktaKu - Platform Deteksi Hoax

## Instalasi Database

### 1. Persiapan
- Pastikan XAMPP/WAMP/MAMP sudah terinstall
- Aktifkan Apache dan MySQL

### 2. Import Database
1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Klik tab "SQL"
3. Copy isi file `database.sql`
4. Paste dan klik "Go"

Atau via command line:
```bash
mysql -u root -p < database.sql
```

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
private $host = "localhost";
private $db_name = "faktaku_db";
private $username = "root"; // Sesuaikan username Anda
private $password = ""; // Sesuaikan password Anda
```

### 4. Struktur Folder
```
project/
├── index.html
├── config/
│   └── database.php
├── api/
│   ├── auth.php
│   ├── detection.php
│   ├── news.php
│   └── statistics.php
├── icons/
├── database.sql
└── .htaccess
```

### 5. Testing
1. Buka http://localhost/[folder-project]/index.html
2. Coba registrasi user baru
3. Login dan test fitur deteksi
4. Cek history pengecekan

## API Endpoints

### Authentication
- POST `/api/auth.php?action=register` - Registrasi
- POST `/api/auth.php?action=login` - Login
- POST `/api/auth.php?action=logout` - Logout
- GET `/api/auth.php?action=verify&token={token}` - Verify session

### Detection
- POST `/api/detection.php?action=check` - Check berita
- GET `/api/detection.php?action=history&user_id={id}` - Get history
- DELETE `/api/detection.php?action=delete&id={id}&user_id={user_id}` - Delete item
- DELETE `/api/detection.php?action=clear&user_id={user_id}` - Clear all

### News
- GET `/api/news.php?action=list` - List berita
- GET `/api/news.php?action=detail&id={id}` - Detail berita

### Statistics
- GET `/api/statistics.php` - Get statistik

## Troubleshooting

### Error: Database connection failed
- Pastikan MySQL sudah berjalan
- Cek username dan password di `config/database.php`
- Pastikan database `faktaku_db` sudah dibuat

### Error: 404 Not Found
- Pastikan file .htaccess ada dan Apache mod_rewrite enabled
- Cek struktur folder sudah benar

### CORS Error
- Pastikan header CORS sudah di-set di semua file API
- Atau gunakan extension CORS untuk development

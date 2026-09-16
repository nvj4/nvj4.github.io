-- Create database
CREATE DATABASE IF NOT EXISTS faktaku_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE faktaku_db;

-- Table for users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for news articles
CREATE TABLE IF NOT EXISTS news_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    excerpt TEXT,
    content TEXT,
    image_url VARCHAR(500),
    source VARCHAR(255),
    source_url VARCHAR(500),
    published_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published_date (published_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for detection history
CREATE TABLE IF NOT EXISTS detection_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    content_type ENUM('text', 'link') DEFAULT 'text',
    detection_result ENUM('hoax', 'safe', 'unknown') NOT NULL,
    confidence_level DECIMAL(5,2),
    source_link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for statistics
CREATE TABLE IF NOT EXISTS statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_news_analyzed INT DEFAULT 0,
    total_users INT DEFAULT 0,
    accuracy_rate DECIMAL(5,2) DEFAULT 95.00,
    avg_detection_time DECIMAL(5,2) DEFAULT 4.50,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial statistics
INSERT INTO statistics (total_news_analyzed, total_users, accuracy_rate, avg_detection_time) 
VALUES (10000, 5000, 95.00, 4.50);

-- Table for user sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample news articles
INSERT INTO news_articles (title, excerpt, content, image_url, source, source_url, published_date) VALUES
('Menkominfo Blokir 812 Akun Penyebar Hoax Pemilu 2024', 
 'Kementerian Komunikasi dan Informatika terus berupaya menekan penyebaran hoax terkait Pemilu 2024 dengan memblokir ratusan akun media sosial.',
 'Kementerian Komunikasi dan Informatika (Kominfo) terus berupaya menekan penyebaran hoaks terkait Pemilu 2024. Hingga saat ini, Kominfo telah memblokir 812 akun media sosial yang terindikasi menyebarkan informasi palsu yang dapat mengganggu proses demokrasi.',
 'https://images.unsplash.com/photo-1504711434969-1761ba6beeb2?w=400&h=200&fit=crop',
 'Kompas',
 'https://www.kompas.com/tren/read/2024/12/01/150500365/menkominfo-blokir-812-akun-penyebar-hoax-pemilu-2024',
 '2024-12-01'),

('Kemenpora: Hoax Tiket Gratis Mundial FIFA Klub Friendlies',
 'Kemenpora memastikan informasi tentang tiket gratis pertandingan persahabatan FIFA adalah hoax dan mengimbau masyarakat waspada terhadap penipuan.',
 'Kementerian Pemuda dan Olahraga (Kemenpora) memastikan bahwa informasi tentang tiket gratis untuk pertandingan persahabatan FIFA Klub Friendlies yang beredar di media sosial adalah hoaks.',
 'https://images.unsplash.com/photo-1560472354-b33ff5ce5042?w=400&h=200&fit=crop',
 'CNN Indonesia',
 'https://www.cnnindonesia.com/teknologi/20241201143508-338982/kemenpora-hoax-tiket-gratis-mundial-fifa-klub-friendlies',
 '2024-12-01'),

('Cara Embed Video Google Drive di Website',
 'Panduan lengkap untuk menyematkan video dari Google Drive ke website dengan mudah dan cepat, cocok untuk keperluan pembelajaran online.',
 'Menyematkan video dari Google Drive ke website dapat menjadi solusi praktis untuk berbagi konten video tanpa harus mengunggah ulang ke platform hosting video.',
 'https://images.unsplash.com/photo-1611224942175-767c19e38302?w=400&h=200&fit=crop',
 'DetikEdu',
 'https://www.detik.com/edu/detik-edu/detik-6988223/cara-membed-video-google-drive-di-website',
 '2024-11-30');

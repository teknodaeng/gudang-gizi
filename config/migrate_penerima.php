<?php
/**
 * Database Migration - Penerima (Recipients) Table
 * Run this file once to create the necessary tables
 */

require_once __DIR__ . '/../config/database.php';

// Create penerima table
$conn->query("CREATE TABLE IF NOT EXISTS penerima (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kategori ENUM('balita', 'anak', 'dewasa', 'lansia') DEFAULT 'anak',
    lokasi VARCHAR(255),
    jumlah_orang INT DEFAULT 1,
    faktor_porsi DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Multiplier porsi (balita=0.5, anak=0.75, dewasa=1, lansia=0.8)',
    catatan TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create jadwal_makan table for meal schedule
$conn->query("CREATE TABLE IF NOT EXISTS jadwal_makan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    waktu_makan ENUM('pagi', 'siang', 'sore') DEFAULT 'siang',
    menu_id INT NOT NULL,
    total_penerima INT DEFAULT 0,
    total_porsi DECIMAL(10,2) DEFAULT 0,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    catatan TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menu(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create jadwal_makan_penerima for linking schedule with recipients
$conn->query("CREATE TABLE IF NOT EXISTS jadwal_makan_penerima (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jadwal_id INT NOT NULL,
    penerima_id INT NOT NULL,
    jumlah_orang INT DEFAULT 0,
    faktor_porsi DECIMAL(3,2) DEFAULT 1.00,
    total_porsi DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal_makan(id) ON DELETE CASCADE,
    FOREIGN KEY (penerima_id) REFERENCES penerima(id),
    UNIQUE KEY unique_jadwal_penerima (jadwal_id, penerima_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Add default portion factors
echo "✅ Tables created successfully!\n";
echo "- penerima\n";
echo "- jadwal_makan\n";
echo "- jadwal_makan_penerima\n";
echo "\n📌 Faktor Porsi Default:\n";
echo "- Balita: 0.50 (setengah porsi dewasa)\n";
echo "- Anak: 0.75 (3/4 porsi dewasa)\n";
echo "- Dewasa: 1.00 (porsi penuh)\n";
echo "- Lansia: 0.80 (80% porsi dewasa)\n";
?>
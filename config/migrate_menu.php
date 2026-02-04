<?php
/**
 * Database Migration - Menu & Resep Tables
 * Run this file once to create the necessary tables
 */

require_once __DIR__ . '/../config/database.php';

// Create menu table
$conn->query("CREATE TABLE IF NOT EXISTS menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    kategori ENUM('makanan_utama', 'lauk', 'sayur', 'minuman', 'snack') DEFAULT 'makanan_utama',
    porsi_standar INT DEFAULT 1,
    gambar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create menu_resep table (recipe ingredients)
$conn->query("CREATE TABLE IF NOT EXISTS menu_resep (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    bahan_id INT NOT NULL,
    jumlah DECIMAL(10,3) NOT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE,
    FOREIGN KEY (bahan_id) REFERENCES bahan_makanan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_menu_bahan (menu_id, bahan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create produksi table (cooking/production records)
$conn->query("CREATE TABLE IF NOT EXISTS produksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_produksi VARCHAR(30) NOT NULL UNIQUE,
    tanggal DATE NOT NULL,
    menu_id INT NOT NULL,
    jumlah_porsi INT NOT NULL,
    catatan TEXT,
    total_biaya DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft', 'completed', 'cancelled') DEFAULT 'draft',
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menu(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create produksi_detail table (ingredients used in production)
$conn->query("CREATE TABLE IF NOT EXISTS produksi_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produksi_id INT NOT NULL,
    bahan_id INT NOT NULL,
    jumlah_dibutuhkan DECIMAL(10,3) NOT NULL,
    jumlah_terpakai DECIMAL(10,3) NOT NULL,
    harga_satuan DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produksi_id) REFERENCES produksi(id) ON DELETE CASCADE,
    FOREIGN KEY (bahan_id) REFERENCES bahan_makanan(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "✅ Tables created successfully!\n";
echo "- menu\n";
echo "- menu_resep\n";
echo "- produksi\n";
echo "- produksi_detail\n";
?>
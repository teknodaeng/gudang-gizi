<?php
/**
 * Initialize Database Tables
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/database.php';

// Create users table
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('owner', 'admin', 'gudang') NOT NULL DEFAULT 'gudang',
        email VARCHAR(100),
        phone VARCHAR(20),
        avatar VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Create kategori table
$conn->query("
    CREATE TABLE IF NOT EXISTS kategori (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Create satuan table
$conn->query("
    CREATE TABLE IF NOT EXISTS satuan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(50) NOT NULL,
        singkatan VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Create bahan_makanan table
$conn->query("
    CREATE TABLE IF NOT EXISTS bahan_makanan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(20) UNIQUE NOT NULL,
        nama VARCHAR(100) NOT NULL,
        kategori_id INT,
        satuan_id INT,
        stok_minimum INT DEFAULT 10,
        stok_saat_ini INT DEFAULT 0,
        harga_satuan DECIMAL(15,2) DEFAULT 0,
        lokasi_rak VARCHAR(50),
        foto VARCHAR(255),
        keterangan TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
        FOREIGN KEY (satuan_id) REFERENCES satuan(id) ON DELETE SET NULL
    ) ENGINE=InnoDB
");

// Create supplier table
$conn->query("
    CREATE TABLE IF NOT EXISTS supplier (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        alamat TEXT,
        telepon VARCHAR(20),
        email VARCHAR(100),
        kontak_person VARCHAR(100),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Create stok_masuk table
$conn->query("
    CREATE TABLE IF NOT EXISTS stok_masuk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        no_transaksi VARCHAR(30) UNIQUE NOT NULL,
        tanggal DATE NOT NULL,
        supplier_id INT,
        user_id INT NOT NULL,
        total_nilai DECIMAL(15,2) DEFAULT 0,
        catatan TEXT,
        status ENUM('draft', 'completed', 'cancelled') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Create stok_masuk_detail table
$conn->query("
    CREATE TABLE IF NOT EXISTS stok_masuk_detail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stok_masuk_id INT NOT NULL,
        bahan_id INT NOT NULL,
        jumlah INT NOT NULL,
        harga_satuan DECIMAL(15,2) DEFAULT 0,
        subtotal DECIMAL(15,2) DEFAULT 0,
        tanggal_kadaluarsa DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (stok_masuk_id) REFERENCES stok_masuk(id) ON DELETE CASCADE,
        FOREIGN KEY (bahan_id) REFERENCES bahan_makanan(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Create stok_keluar table
$conn->query("
    CREATE TABLE IF NOT EXISTS stok_keluar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        no_transaksi VARCHAR(30) UNIQUE NOT NULL,
        tanggal DATE NOT NULL,
        tujuan VARCHAR(100),
        user_id INT NOT NULL,
        total_nilai DECIMAL(15,2) DEFAULT 0,
        catatan TEXT,
        status ENUM('draft', 'completed', 'cancelled') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Create stok_keluar_detail table
$conn->query("
    CREATE TABLE IF NOT EXISTS stok_keluar_detail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stok_keluar_id INT NOT NULL,
        bahan_id INT NOT NULL,
        jumlah INT NOT NULL,
        harga_satuan DECIMAL(15,2) DEFAULT 0,
        subtotal DECIMAL(15,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (stok_keluar_id) REFERENCES stok_keluar(id) ON DELETE CASCADE,
        FOREIGN KEY (bahan_id) REFERENCES bahan_makanan(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Create notifikasi table
$conn->query("
    CREATE TABLE IF NOT EXISTS notifikasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        tipe ENUM('low_stock', 'expired', 'info', 'warning') NOT NULL,
        judul VARCHAR(100) NOT NULL,
        pesan TEXT NOT NULL,
        bahan_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (bahan_id) REFERENCES bahan_makanan(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Create activity_log table
$conn->query("
    CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB
");

// Insert default data
// Check if data already exists
$check = fetchOne("SELECT id FROM users WHERE username = 'owner'");
if (!$check) {
    // Insert default users
    $password = password_hash('owner123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, nama_lengkap, role, email) VALUES 
        ('owner', '$password', 'Pemilik Dapur Gizi', 'owner', 'owner@dapurgizi.com')");
    
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, nama_lengkap, role, email) VALUES 
        ('admin', '$password', 'Administrator', 'admin', 'admin@dapurgizi.com')");
    
    $password = password_hash('gudang123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, nama_lengkap, role, email) VALUES 
        ('gudang', '$password', 'Staff Gudang', 'gudang', 'gudang@dapurgizi.com')");
}

// Insert default kategori
$check = fetchOne("SELECT id FROM kategori LIMIT 1");
if (!$check) {
    $conn->query("INSERT INTO kategori (nama, deskripsi) VALUES 
        ('Bahan Pokok', 'Beras, tepung, gula, minyak, dll'),
        ('Protein', 'Daging, ikan, telur, tahu, tempe'),
        ('Sayuran', 'Sayuran segar dan beku'),
        ('Bumbu Dapur', 'Bumbu-bumbu masakan'),
        ('Minuman', 'Susu, teh, kopi, sirup'),
        ('Lainnya', 'Bahan makanan lainnya')");
}

// Insert default satuan
$check = fetchOne("SELECT id FROM satuan LIMIT 1");
if (!$check) {
    $conn->query("INSERT INTO satuan (nama, singkatan) VALUES 
        ('Kilogram', 'kg'),
        ('Gram', 'gr'),
        ('Liter', 'L'),
        ('Mililiter', 'ml'),
        ('Buah', 'bh'),
        ('Bungkus', 'bks'),
        ('Dus', 'dus'),
        ('Karung', 'krg'),
        ('Ikat', 'ikt'),
        ('Botol', 'btl')");
}

// Insert sample supplier
$check = fetchOne("SELECT id FROM supplier LIMIT 1");
if (!$check) {
    $conn->query("INSERT INTO supplier (nama, alamat, telepon, kontak_person) VALUES 
        ('PT Sembako Makmur', 'Jl. Pasar Induk No. 123', '021-12345678', 'Budi Santoso'),
        ('UD Sayur Segar', 'Jl. Pasar Sayur No. 45', '0812-3456-7890', 'Ani Lestari'),
        ('CV Protein Sehat', 'Jl. Industri Makanan No. 78', '021-87654321', 'Dedi Prasetyo')");
}

// Insert sample bahan makanan
$check = fetchOne("SELECT id FROM bahan_makanan LIMIT 1");
if (!$check) {
    $conn->query("INSERT INTO bahan_makanan (kode, nama, kategori_id, satuan_id, stok_minimum, stok_saat_ini, harga_satuan, lokasi_rak) VALUES 
        ('BRS001', 'Beras Premium', 1, 1, 50, 150, 12000, 'A-01'),
        ('MNY001', 'Minyak Goreng', 1, 3, 20, 45, 15000, 'A-02'),
        ('GUL001', 'Gula Pasir', 1, 1, 30, 80, 14000, 'A-03'),
        ('TLR001', 'Telur Ayam', 2, 1, 50, 100, 28000, 'B-01'),
        ('DGN001', 'Daging Ayam', 2, 1, 30, 8, 35000, 'B-02'),
        ('TMT001', 'Tempe', 2, 5, 50, 25, 3000, 'B-03'),
        ('WRT001', 'Wortel', 3, 1, 20, 35, 8000, 'C-01'),
        ('KNT001', 'Kentang', 3, 1, 20, 5, 12000, 'C-02'),
        ('BWM001', 'Bawang Merah', 4, 1, 10, 25, 35000, 'D-01'),
        ('BWP001', 'Bawang Putih', 4, 1, 10, 3, 30000, 'D-02')");
}

echo "Database initialized successfully!";
?>

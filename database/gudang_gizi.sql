-- Adminer 5.4.1 MariaDB 10.11.14-MariaDB-0ubuntu0.24.04.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1,	1,	'login',	'User logged in',	'::1',	'2026-02-03 11:11:37'),
(2,	1,	'logout',	'User logged out',	'::1',	'2026-02-03 11:14:36'),
(3,	2,	'login',	'User logged in',	'::1',	'2026-02-03 11:14:46'),
(4,	2,	'logout',	'User logged out',	'::1',	'2026-02-03 11:15:10'),
(5,	3,	'login',	'User logged in',	'::1',	'2026-02-03 11:15:19'),
(6,	3,	'login',	'User logged in',	'::1',	'2026-02-03 15:47:15'),
(7,	2,	'login',	'User logged in',	'::1',	'2026-02-03 17:29:06'),
(8,	2,	'logout',	'User logged out',	'::1',	'2026-02-03 17:29:19'),
(9,	1,	'login',	'User logged in',	'::1',	'2026-02-03 17:29:26'),
(10,	1,	'create_user',	'Menambah user: aliefrahman',	'::1',	'2026-02-03 17:30:15'),
(11,	1,	'logout',	'User logged out',	'::1',	'2026-02-03 17:30:41'),
(12,	4,	'login',	'User logged in',	'::1',	'2026-02-03 17:30:44'),
(13,	4,	'logout',	'User logged out',	'::1',	'2026-02-03 17:44:50'),
(14,	2,	'login',	'User logged in',	'::1',	'2026-02-03 17:45:00'),
(15,	2,	'update_bahan',	'Mengupdate bahan: Bawang Merah',	'::1',	'2026-02-03 17:48:50'),
(16,	2,	'login',	'User logged in',	'::1',	'2026-02-03 18:07:11'),
(17,	2,	'login',	'User logged in',	'::1',	'2026-02-03 18:13:46'),
(18,	2,	'logout',	'User logged out',	'::1',	'2026-02-03 18:27:30'),
(19,	2,	'login',	'User logged in from ::1',	'::1',	'2026-02-03 18:27:35'),
(20,	2,	'login',	'User logged in from ::1',	'::1',	'2026-02-04 05:06:52');

DROP TABLE IF EXISTS `bahan_makanan`;
CREATE TABLE `bahan_makanan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `satuan_id` int(11) DEFAULT NULL,
  `stok_minimum` int(11) DEFAULT 10,
  `stok_saat_ini` int(11) DEFAULT 0,
  `harga_satuan` decimal(15,2) DEFAULT 0.00,
  `lokasi_rak` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `kategori_id` (`kategori_id`),
  KEY `satuan_id` (`satuan_id`),
  CONSTRAINT `bahan_makanan_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bahan_makanan_ibfk_2` FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bahan_makanan` (`id`, `kode`, `nama`, `kategori_id`, `satuan_id`, `stok_minimum`, `stok_saat_ini`, `harga_satuan`, `lokasi_rak`, `foto`, `keterangan`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'BRS001',	'Beras Premium',	1,	1,	50,	150,	12000.00,	'A-01',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(2,	'MNY001',	'Minyak Goreng',	1,	3,	20,	45,	15000.00,	'A-02',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(3,	'GUL001',	'Gula Pasir',	1,	1,	30,	80,	14000.00,	'A-03',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(4,	'TLR001',	'Telur Ayam',	2,	1,	50,	100,	28000.00,	'B-01',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(5,	'DGN001',	'Daging Ayam',	2,	1,	30,	8,	35000.00,	'B-02',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(6,	'TMT001',	'Tempe',	2,	5,	50,	25,	3000.00,	'B-03',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(7,	'WRT001',	'Wortel',	3,	1,	20,	35,	8000.00,	'C-01',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(8,	'KNT001',	'Kentang',	3,	1,	20,	5,	12000.00,	'C-02',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(9,	'BWM001',	'Bawang Merah',	4,	1,	10,	25,	35000.00,	'Gudang Basah',	NULL,	'',	1,	'2026-02-03 11:10:39',	'2026-02-03 17:48:50'),
(10,	'BWP001',	'Bawang Putih',	4,	1,	10,	3,	30000.00,	'D-02',	NULL,	NULL,	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39');

DROP TABLE IF EXISTS `jadwal_makan`;
CREATE TABLE `jadwal_makan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `waktu_makan` enum('pagi','siang','sore') DEFAULT 'siang',
  `menu_id` int(11) NOT NULL,
  `total_penerima` int(11) DEFAULT 0,
  `total_porsi` decimal(10,2) DEFAULT 0.00,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `catatan` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `menu_id` (`menu_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `jadwal_makan_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  CONSTRAINT `jadwal_makan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `jadwal_makan_penerima`;
CREATE TABLE `jadwal_makan_penerima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jadwal_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `jumlah_orang` int(11) DEFAULT 0,
  `faktor_porsi` decimal(3,2) DEFAULT 1.00,
  `total_porsi` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_jadwal_penerima` (`jadwal_id`,`penerima_id`),
  KEY `penerima_id` (`penerima_id`),
  CONSTRAINT `jadwal_makan_penerima_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal_makan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jadwal_makan_penerima_ibfk_2` FOREIGN KEY (`penerima_id`) REFERENCES `penerima` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `kategori`;
CREATE TABLE `kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kategori` (`id`, `nama`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1,	'Bahan Pokok',	'Beras, tepung, gula, minyak, dll',	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(2,	'Protein',	'Daging, ikan, telur, tahu, tempe',	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(3,	'Sayuran',	'Sayuran segar dan beku',	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(4,	'Bumbu Dapur',	'Bumbu-bumbu masakan',	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(5,	'Minuman',	'Susu, teh, kopi, sirup',	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(6,	'Lainnya',	'Bahan makanan lainnya',	'2026-02-03 11:10:39',	'2026-02-03 11:10:39');

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` enum('makanan_utama','lauk','sayur','minuman','snack') DEFAULT 'makanan_utama',
  `porsi_standar` int(11) DEFAULT 1,
  `gambar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `menu_resep`;
CREATE TABLE `menu_resep` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `bahan_id` int(11) NOT NULL,
  `jumlah` decimal(10,3) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_menu_bahan` (`menu_id`,`bahan_id`),
  KEY `bahan_id` (`bahan_id`),
  CONSTRAINT `menu_resep_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_resep_ibfk_2` FOREIGN KEY (`bahan_id`) REFERENCES `bahan_makanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `tipe` enum('low_stock','expired','info','warning') NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `bahan_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `bahan_id` (`bahan_id`),
  CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifikasi_ibfk_2` FOREIGN KEY (`bahan_id`) REFERENCES `bahan_makanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifikasi` (`id`, `user_id`, `tipe`, `judul`, `pesan`, `bahan_id`, `is_read`, `created_at`) VALUES
(1,	NULL,	'low_stock',	'Stok Menipis!',	'Stok Daging Ayam (DGN001) tinggal 8 unit',	5,	0,	'2026-02-03 11:11:37'),
(2,	NULL,	'low_stock',	'Stok Menipis!',	'Stok Tempe (TMT001) tinggal 25 unit',	6,	0,	'2026-02-03 11:11:37'),
(3,	NULL,	'low_stock',	'Stok Menipis!',	'Stok Kentang (KNT001) tinggal 5 unit',	8,	0,	'2026-02-03 11:11:37'),
(4,	NULL,	'low_stock',	'Stok Menipis!',	'Stok Bawang Putih (BWP001) tinggal 3 unit',	10,	0,	'2026-02-03 11:11:37');

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `penerima`;
CREATE TABLE `penerima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kategori` enum('balita','anak','dewasa','lansia') DEFAULT 'anak',
  `lokasi` varchar(255) DEFAULT NULL,
  `jumlah_orang` int(11) DEFAULT 1,
  `faktor_porsi` decimal(3,2) DEFAULT 1.00 COMMENT 'Multiplier porsi (balita=0.5, anak=0.75, dewasa=1, lansia=0.8)',
  `catatan` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `produksi`;
CREATE TABLE `produksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_produksi` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `menu_id` int(11) NOT NULL,
  `jumlah_porsi` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `total_biaya` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','completed','cancelled') DEFAULT 'draft',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_produksi` (`no_produksi`),
  KEY `menu_id` (`menu_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `produksi_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  CONSTRAINT `produksi_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `produksi_detail`;
CREATE TABLE `produksi_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produksi_id` int(11) NOT NULL,
  `bahan_id` int(11) NOT NULL,
  `jumlah_dibutuhkan` decimal(10,3) NOT NULL,
  `jumlah_terpakai` decimal(10,3) NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `produksi_id` (`produksi_id`),
  KEY `bahan_id` (`bahan_id`),
  CONSTRAINT `produksi_detail_ibfk_1` FOREIGN KEY (`produksi_id`) REFERENCES `produksi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `produksi_detail_ibfk_2` FOREIGN KEY (`bahan_id`) REFERENCES `bahan_makanan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `satuan`;
CREATE TABLE `satuan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(50) NOT NULL,
  `singkatan` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `satuan` (`id`, `nama`, `singkatan`, `created_at`) VALUES
(1,	'Kilogram',	'kg',	'2026-02-03 11:10:39'),
(2,	'Gram',	'gr',	'2026-02-03 11:10:39'),
(3,	'Liter',	'L',	'2026-02-03 11:10:39'),
(4,	'Mililiter',	'ml',	'2026-02-03 11:10:39'),
(5,	'Buah',	'bh',	'2026-02-03 11:10:39'),
(6,	'Bungkus',	'bks',	'2026-02-03 11:10:39'),
(7,	'Dus',	'dus',	'2026-02-03 11:10:39'),
(8,	'Karung',	'krg',	'2026-02-03 11:10:39'),
(9,	'Ikat',	'ikt',	'2026-02-03 11:10:39'),
(10,	'Botol',	'btl',	'2026-02-03 11:10:39');

DROP TABLE IF EXISTS `security_log`;
CREATE TABLE `security_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `security_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `security_log` (`id`, `event_type`, `details`, `ip_address`, `user_agent`, `user_id`, `created_at`) VALUES
(1,	'login_success',	'User logged in',	'::1',	'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	2,	'2026-02-03 18:27:35'),
(2,	'login_success',	'User logged in',	'::1',	'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	2,	'2026-02-04 05:06:53');

DROP TABLE IF EXISTS `stok_keluar`;
CREATE TABLE `stok_keluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `tujuan` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `total_nilai` decimal(15,2) DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `status` enum('draft','completed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stok_keluar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `stok_keluar_detail`;
CREATE TABLE `stok_keluar_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stok_keluar_id` int(11) NOT NULL,
  `bahan_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `stok_keluar_id` (`stok_keluar_id`),
  KEY `bahan_id` (`bahan_id`),
  CONSTRAINT `stok_keluar_detail_ibfk_1` FOREIGN KEY (`stok_keluar_id`) REFERENCES `stok_keluar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stok_keluar_detail_ibfk_2` FOREIGN KEY (`bahan_id`) REFERENCES `bahan_makanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `stok_masuk`;
CREATE TABLE `stok_masuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `total_nilai` decimal(15,2) DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `nota_file` varchar(255) DEFAULT NULL,
  `status` enum('draft','completed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stok_masuk_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stok_masuk_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `stok_masuk_detail`;
CREATE TABLE `stok_masuk_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stok_masuk_id` int(11) NOT NULL,
  `bahan_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tanggal_kadaluarsa` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `stok_masuk_id` (`stok_masuk_id`),
  KEY `bahan_id` (`bahan_id`),
  CONSTRAINT `stok_masuk_detail_ibfk_1` FOREIGN KEY (`stok_masuk_id`) REFERENCES `stok_masuk` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stok_masuk_detail_ibfk_2` FOREIGN KEY (`bahan_id`) REFERENCES `bahan_makanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `supplier`;
CREATE TABLE `supplier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `kontak_person` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `supplier` (`id`, `nama`, `alamat`, `telepon`, `email`, `kontak_person`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'PT Sembako Makmur',	'Jl. Pasar Induk No. 123',	'021-12345678',	NULL,	'Budi Santoso',	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(2,	'UD Sayur Segar',	'Jl. Pasar Sayur No. 45',	'0812-3456-7890',	NULL,	'Ani Lestari',	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39'),
(3,	'CV Protein Sehat',	'Jl. Industri Makanan No. 78',	'021-87654321',	NULL,	'Dedi Prasetyo',	1,	'2026-02-03 11:10:39',	'2026-02-03 11:10:39');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('owner','admin','gudang') NOT NULL DEFAULT 'gudang',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `email`, `phone`, `avatar`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1,	'owner',	'$2y$10$oh8G0AkZs.JufvyMXihRk.EuoOwYfeJN4rIbic.YbCKitf4O3F2My',	'Pemilik Dapur Gizi',	'owner',	'owner@dapurgizi.com',	NULL,	NULL,	1,	'2026-02-04 01:29:26',	'2026-02-03 11:10:39',	'2026-02-03 17:29:26'),
(2,	'admin',	'$2y$12$HrsIIXlmh9LpxScmjpfzgONTeIQ8DxPdQBL59r55zgvYL0RSZDLQy',	'Administrator',	'admin',	'admin@dapurgizi.com',	NULL,	NULL,	1,	'2026-02-04 13:06:52',	'2026-02-03 11:10:39',	'2026-02-04 05:06:52'),
(3,	'gudang',	'$2y$10$fI1lPl8SckpmQQGiLDkABuoTSHA33k4nzDvdT6hhW7UeoaBuV8EiS',	'Staff Gudang',	'gudang',	'gudang@dapurgizi.com',	NULL,	NULL,	1,	'2026-02-03 23:47:15',	'2026-02-03 11:10:39',	'2026-02-03 15:47:15'),
(4,	'aliefrahman',	'$2y$10$elXVtoenm8ZATNmDCEPRR.CLrfR0fOkjOfeh59sMOHU68tAs48Oyi',	'Andi Muhammad Aliefrahman',	'gudang',	'aliefrahman@gmail.com',	'082394571505',	NULL,	1,	'2026-02-04 01:30:44',	'2026-02-03 17:30:15',	'2026-02-03 17:30:44');

-- 2026-02-04 05:31:04 UTC

<?php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
session_start();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['session_created'])) {
    $_SESSION['session_created'] = time();
} elseif (time() - $_SESSION['session_created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['session_created'] = time();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';

// Set secure HTTP headers
setSecureHeaders();

// Check if user is logged in
if (!isset($_SESSION['user']) && !str_contains($_SERVER['PHP_SELF'], 'login.php')) {
    header('Location: /gudang-gizi/modules/auth/login.php');
    exit;
}

$currentUser = $_SESSION['user'] ?? null;
$unreadNotifications = getUnreadNotificationsCount();
$notifications = getLatestNotifications(5);

// Check low stock on every page load
checkLowStock();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentModule = '';
if (preg_match('/modules\/([^\/]+)/', $_SERVER['PHP_SELF'], $matches)) {
    $currentModule = $matches[1];
}

// Generate CSRF token for forms
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gudang Gizi - Sistem Manajemen Stok</title>
    <meta name="description" content="Sistem Manajemen Stok dan Inventory untuk Dapur Makan Gizi Gratis">

    <!-- Tailwind CSS 4 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        accent: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #1e293b;
        }

        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-dark {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Gradient backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .gradient-accent {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }

        .gradient-dark {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        /* Card hover effect */
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        /* Sidebar active state */
        .nav-item {
            transition: all 0.2s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.2) 0%, transparent 100%);
            border-left: 3px solid #22c55e;
        }

        /* Mobile menu backdrop */
        .mobile-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Table styles */
        .table-modern th {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background: rgba(34, 197, 94, 0.1);
        }

        /* Input focus */
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3);
        }

        /* Button hover */
        .btn-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(34, 197, 94, 0.3);
        }

        /* Badge pulse */
        .badge-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Loading spinner */
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #22c55e;
            width: 24px;
            height: 24px;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="bg-slate-900 text-gray-100 font-sans min-h-screen">
    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn"
        class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-slate-800 text-white shadow-lg">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <!-- Mobile Backdrop -->
    <div id="mobileBackdrop" class="mobile-backdrop fixed inset-0 z-40 hidden lg:hidden" onclick="closeMobileMenu()">
    </div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-full w-64 bg-slate-800/95 backdrop-blur-xl z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 shadow-2xl">
        <!-- Logo Section -->
        <div class="p-6 border-b border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center shadow-lg">
                    <i class="fas fa-warehouse text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-white">Gudang Gizi</h1>
                    <p class="text-xs text-gray-400">Manajemen Stok</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="py-4 px-3 space-y-1 overflow-y-auto h-[calc(100%-200px)]">
            <a href="/gudang-gizi/index.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <?php if (hasPermission(['owner', 'admin'])): ?>
                <div class="pt-4 pb-2 px-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Master Data</span>
                </div>
                <a href="/gudang-gizi/modules/master/bahan.php"
                    class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'master' && $currentPage === 'bahan' ? 'active' : '' ?>">
                    <i class="fas fa-carrot w-5 text-center"></i>
                    <span>Bahan Makanan</span>
                </a>
                <a href="/gudang-gizi/modules/master/kategori.php"
                    class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'master' && $currentPage === 'kategori' ? 'active' : '' ?>">
                    <i class="fas fa-tags w-5 text-center"></i>
                    <span>Kategori</span>
                </a>
                <a href="/gudang-gizi/modules/master/supplier.php"
                    class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'master' && $currentPage === 'supplier' ? 'active' : '' ?>">
                    <i class="fas fa-truck w-5 text-center"></i>
                    <span>Supplier</span>
                </a>
            <?php endif; ?>

            <div class="pt-4 pb-2 px-4">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Transaksi</span>
            </div>
            <a href="/gudang-gizi/modules/stok_masuk/index.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'stok_masuk' ? 'active' : '' ?>">
                <i class="fas fa-arrow-down w-5 text-center text-green-400"></i>
                <span>Stok Masuk</span>
            </a>
            <a href="/gudang-gizi/modules/stok_keluar/index.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'stok_keluar' ? 'active' : '' ?>">
                <i class="fas fa-arrow-up w-5 text-center text-red-400"></i>
                <span>Stok Keluar</span>
            </a>

            <div class="pt-4 pb-2 px-4">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu & Produksi</span>
            </div>
            <a href="/gudang-gizi/modules/menu/index.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'menu' ? 'active' : '' ?>">
                <i class="fas fa-utensils w-5 text-center text-orange-400"></i>
                <span>Menu & Resep</span>
            </a>
            <a href="/gudang-gizi/modules/produksi/index.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'produksi' ? 'active' : '' ?>">
                <i class="fas fa-fire-burner w-5 text-center text-rose-400"></i>
                <span>Produksi / Masak</span>
            </a>
            <a href="/gudang-gizi/modules/penerima/index.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'penerima' ? 'active' : '' ?>">
                <i class="fas fa-users w-5 text-center text-cyan-400"></i>
                <span>Penerima</span>
            </a>
            <a href="/gudang-gizi/modules/penerima/calculator.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'penerima' && $currentPage === 'calculator' ? 'active' : '' ?>">
                <i class="fas fa-calculator w-5 text-center text-teal-400"></i>
                <span>Kalkulator Porsi</span>
            </a>

            <div class="pt-4 pb-2 px-4">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Laporan</span>
            </div>
            <a href="/gudang-gizi/modules/laporan/stok.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'laporan' && $currentPage === 'stok' ? 'active' : '' ?>">
                <i class="fas fa-boxes-stacked w-5 text-center"></i>
                <span>Laporan Stok</span>
            </a>
            <a href="/gudang-gizi/modules/laporan/transaksi.php"
                class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'laporan' && $currentPage === 'transaksi' ? 'active' : '' ?>">
                <i class="fas fa-receipt w-5 text-center"></i>
                <span>Laporan Transaksi</span>
            </a>

            <?php if (hasPermission(['owner'])): ?>
                <div class="pt-4 pb-2 px-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengaturan</span>
                </div>
                <a href="/gudang-gizi/modules/master/users.php"
                    class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:text-white <?= $currentModule === 'master' && $currentPage === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Manajemen User</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- User Profile Section -->
        <?php if ($currentUser): ?>
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700 bg-slate-800">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full gradient-accent flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr($currentUser['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-white truncate">
                            <?= htmlspecialchars($currentUser['nama_lengkap']) ?>
                        </p>
                        <p class="text-xs text-gray-400 capitalize">
                            <?= $currentUser['role'] ?>
                        </p>
                    </div>
                    <a href="/gudang-gizi/modules/auth/logout.php"
                        class="text-gray-400 hover:text-red-400 transition-colors" title="Logout">
                        <i class="fas fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <header class="sticky top-0 z-30 glass-dark px-4 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="hidden lg:block">
                        <h2 class="text-xl font-semibold text-white" id="pageTitle">Dashboard</h2>
                        <p class="text-sm text-gray-400" id="pageSubtitle">Selamat datang di Gudang Gizi</p>
                    </div>
                    <div class="lg:hidden ml-12">
                        <h2 class="text-lg font-semibold text-white">Gudang Gizi</h2>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Search (Desktop) -->
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Cari bahan makanan..."
                            class="w-64 bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-2 pl-10 text-sm text-white placeholder-gray-400 focus:border-primary-500 transition-all"
                            id="globalSearch">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>

                    <!-- Notifications -->
                    <div class="relative" id="notificationDropdown">
                        <button onclick="toggleNotifications()"
                            class="relative p-2 rounded-xl bg-slate-700/50 hover:bg-slate-700 transition-colors">
                            <i class="fas fa-bell text-gray-300"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span
                                    class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center badge-pulse">
                                    <?= $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <!-- Notifications Panel -->
                        <div id="notificationPanel"
                            class="hidden absolute right-0 top-full mt-2 w-80 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-700 flex justify-between items-center">
                                <h3 class="font-semibold text-white">Notifikasi</h3>
                                <?php if ($unreadNotifications > 0): ?>
                                    <a href="/gudang-gizi/modules/notifikasi/mark_all_read.php"
                                        class="text-xs text-primary-400 hover:text-primary-300">
                                        Tandai semua dibaca
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <?php if (empty($notifications)): ?>
                                    <div class="p-8 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                        <p>Tidak ada notifikasi</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <div
                                            class="p-4 border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors <?= $notif['is_read'] ? 'opacity-60' : '' ?>">
                                            <div class="flex gap-3">
                                                <div
                                                    class="w-10 h-10 rounded-full <?= $notif['tipe'] === 'low_stock' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-blue-500/20 text-blue-400' ?> flex items-center justify-center flex-shrink-0">
                                                    <i
                                                        class="fas <?= $notif['tipe'] === 'low_stock' ? 'fa-triangle-exclamation' : 'fa-info-circle' ?>"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-white">
                                                        <?= htmlspecialchars($notif['judul']) ?>
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        <?= htmlspecialchars($notif['pesan']) ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-2">
                                                        <?= formatTanggal($notif['created_at'], true) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 border-t border-slate-700 text-center">
                                <a href="/gudang-gizi/modules/notifikasi/index.php"
                                    class="text-sm text-primary-400 hover:text-primary-300">
                                    Lihat semua notifikasi
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Profile (Desktop) -->
                    <?php if ($currentUser): ?>
                        <div class="hidden md:flex items-center gap-3 pl-4 border-l border-slate-700">
                            <div
                                class="w-10 h-10 rounded-full gradient-primary flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr($currentUser['nama_lengkap'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">
                                    <?= htmlspecialchars($currentUser['nama_lengkap']) ?>
                                </p>
                                <p class="text-xs text-gray-400 capitalize">
                                    <?= $currentUser['role'] ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 lg:p-8">
            <?= showFlashMessage() ?>
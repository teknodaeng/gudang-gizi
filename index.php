<?php
/**
 * Dashboard - Main Page
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/includes/header.php';

// Get dashboard statistics
$stats = getDashboardStats();

// Get low stock items
$lowStockItems = fetchAll("SELECT b.*, k.nama as kategori_nama, s.singkatan as satuan_singkatan 
                           FROM bahan_makanan b 
                           LEFT JOIN kategori k ON b.kategori_id = k.id 
                           LEFT JOIN satuan s ON b.satuan_id = s.id 
                           WHERE b.stok_saat_ini <= b.stok_minimum AND b.is_active = 1 
                           ORDER BY (b.stok_saat_ini / b.stok_minimum) ASC 
                           LIMIT 5");

// Get recent transactions
$recentMasuk = fetchAll("SELECT sm.*, u.nama_lengkap, sp.nama as supplier_nama 
                         FROM stok_masuk sm 
                         LEFT JOIN users u ON sm.user_id = u.id 
                         LEFT JOIN supplier sp ON sm.supplier_id = sp.id 
                         ORDER BY sm.created_at DESC 
                         LIMIT 5");

$recentKeluar = fetchAll("SELECT sk.*, u.nama_lengkap 
                          FROM stok_keluar sk 
                          LEFT JOIN users u ON sk.user_id = u.id 
                          ORDER BY sk.created_at DESC 
                          LIMIT 5");

// Get chart data - Stock by category
$chartData = fetchAll("SELECT k.nama, SUM(b.stok_saat_ini) as total_stok 
                       FROM bahan_makanan b 
                       LEFT JOIN kategori k ON b.kategori_id = k.id 
                       WHERE b.is_active = 1 
                       GROUP BY k.id, k.nama 
                       ORDER BY total_stok DESC");

// Get monthly transaction data for chart
$monthlyData = fetchAll("SELECT 
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    SUM(total_nilai) as total 
    FROM stok_masuk 
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND status = 'completed'
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC");

$monthlyKeluarData = fetchAll("SELECT 
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    SUM(total_nilai) as total 
    FROM stok_keluar 
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND status = 'completed'
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC");
?>

<script>setPageTitle('Dashboard', 'Selamat datang, <?= htmlspecialchars($currentUser['nama_lengkap']) ?>!');</script>

<!-- Quick Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
    <!-- Total Bahan -->
    <div class="glass rounded-2xl p-5 card-hover">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">Total Bahan</p>
                <h3 class="text-2xl lg:text-3xl font-bold text-white">
                    <?= number_format($stats['total_bahan']) ?>
                </h3>
                <p class="text-xs text-gray-500 mt-2">Jenis bahan aktif</p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                <i class="fas fa-cubes text-white text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Nilai Stok -->
    <div class="glass rounded-2xl p-5 card-hover">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">Nilai Stok</p>
                <h3 class="text-xl lg:text-2xl font-bold text-white">
                    <?= formatRupiah($stats['total_nilai_stok']) ?>
                </h3>
                <p class="text-xs text-gray-500 mt-2">Total nilai inventory</p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center">
                <i class="fas fa-coins text-white text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Stok Menipis -->
    <div
        class="glass rounded-2xl p-5 card-hover <?= $stats['stok_menipis'] > 0 ? 'border border-yellow-500/50' : '' ?>">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">Stok Menipis</p>
                <h3
                    class="text-2xl lg:text-3xl font-bold <?= $stats['stok_menipis'] > 0 ? 'text-yellow-400' : 'text-white' ?>">
                    <?= number_format($stats['stok_menipis']) ?>
                </h3>
                <p class="text-xs text-gray-500 mt-2">Perlu restok</p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center <?= $stats['stok_menipis'] > 0 ? 'animate-pulse' : '' ?>">
                <i class="fas fa-triangle-exclamation text-white text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Transaksi Hari Ini -->
    <div class="glass rounded-2xl p-5 card-hover">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">Transaksi Hari Ini</p>
                <h3 class="text-2xl lg:text-3xl font-bold text-white">
                    <?= $stats['stok_masuk_hari_ini'] + $stats['stok_keluar_hari_ini'] ?>
                </h3>
                <p class="text-xs text-gray-500 mt-2">
                    <span class="text-green-400">
                        <?= $stats['stok_masuk_hari_ini'] ?> masuk
                    </span> •
                    <span class="text-red-400">
                        <?= $stats['stok_keluar_hari_ini'] ?> keluar
                    </span>
                </p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                <i class="fas fa-exchange-alt text-white text-lg"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="/gudang-gizi/modules/stok_masuk/create.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-green-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Stok Masuk</h4>
            <p class="text-xs text-gray-400">Tambah stok baru</p>
        </div>
    </a>

    <a href="/gudang-gizi/modules/stok_keluar/create.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-red-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-red-500/20 text-red-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-minus text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Stok Keluar</h4>
            <p class="text-xs text-gray-400">Keluarkan stok</p>
        </div>
    </a>

    <?php if (hasPermission(['owner', 'admin'])): ?>
        <a href="/gudang-gizi/modules/master/bahan.php"
            class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-blue-500/10 transition-all group">
            <div
                class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-carrot text-xl"></i>
            </div>
            <div>
                <h4 class="font-medium text-white">Bahan Makanan</h4>
                <p class="text-xs text-gray-400">Kelola master data</p>
            </div>
        </a>
    <?php endif; ?>

    <a href="/gudang-gizi/modules/laporan/stok.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-purple-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-chart-bar text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Laporan Stok</h4>
            <p class="text-xs text-gray-400">Lihat laporan</p>
        </div>
    </a>
</div>

<!-- Quick Actions Row 2 -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="/gudang-gizi/modules/produksi/create.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-rose-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-fire-burner text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Produksi/Masak</h4>
            <p class="text-xs text-gray-400">Proses masak baru</p>
        </div>
    </a>

    <a href="/gudang-gizi/modules/menu/index.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-orange-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-orange-500/20 text-orange-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-utensils text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Menu & Resep</h4>
            <p class="text-xs text-gray-400">Kelola resep</p>
        </div>
    </a>

    <a href="/gudang-gizi/modules/produksi/index.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-pink-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-pink-500/20 text-pink-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-list-check text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Riwayat Produksi</h4>
            <p class="text-xs text-gray-400">Lihat semua</p>
        </div>
    </a>

    <a href="/gudang-gizi/modules/penerima/calculator.php"
        class="glass rounded-xl p-4 flex items-center gap-4 hover:bg-teal-500/10 transition-all group">
        <div
            class="w-12 h-12 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center group-hover:scale-110 transition-transform">
            <i class="fas fa-calculator text-xl"></i>
        </div>
        <div>
            <h4 class="font-medium text-white">Kalkulator Porsi</h4>
            <p class="text-xs text-gray-400">Hitung kebutuhan</p>
        </div>
    </a>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <!-- Chart - Stock by Category -->
    <div class="glass rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-primary-400"></i>
            Stok per Kategori
        </h3>
        <div class="h-64">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

    <!-- Chart - Monthly Transactions -->
    <div class="glass rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-line text-primary-400"></i>
            Transaksi 6 Bulan Terakhir
        </h3>
        <div class="h-64">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Low Stock Alert -->
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="fas fa-triangle-exclamation text-yellow-400"></i>
                Stok Menipis
            </h3>
            <a href="/gudang-gizi/modules/laporan/stok.php?filter=low"
                class="text-sm text-primary-400 hover:text-primary-300">
                Lihat Semua
            </a>
        </div>

        <?php if (empty($lowStockItems)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                <p>Semua stok aman!</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-700/50 transition-colors">
                        <div
                            class="w-10 h-10 rounded-lg bg-yellow-500/20 text-yellow-400 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-white truncate">
                                <?= htmlspecialchars($item['nama']) ?>
                            </p>
                            <p class="text-xs text-gray-400">
                                <?= htmlspecialchars($item['kategori_nama'] ?? 'Tanpa Kategori') ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-yellow-400">
                                <?= $item['stok_saat_ini'] ?>
                            </p>
                            <p class="text-xs text-gray-500">min:
                                <?= $item['stok_minimum'] ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Stock In -->
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="fas fa-arrow-down text-green-400"></i>
                Stok Masuk Terbaru
            </h3>
            <a href="/gudang-gizi/modules/stok_masuk/index.php" class="text-sm text-primary-400 hover:text-primary-300">
                Lihat Semua
            </a>
        </div>

        <?php if (empty($recentMasuk)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>Belum ada transaksi</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentMasuk as $item): ?>
                    <a href="/gudang-gizi/modules/stok_masuk/detail.php?id=<?= $item['id'] ?>"
                        class="block p-3 rounded-xl bg-slate-800/50 hover:bg-slate-700/50 transition-colors">
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-medium text-white text-sm">
                                <?= htmlspecialchars($item['no_transaksi']) ?>
                            </span>
                            <span class="text-green-400 text-sm font-medium">
                                <?= formatRupiah($item['total_nilai']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400">
                                <?= htmlspecialchars($item['supplier_nama'] ?? 'Tanpa Supplier') ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <?= formatTanggal($item['tanggal']) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Stock Out -->
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="fas fa-arrow-up text-red-400"></i>
                Stok Keluar Terbaru
            </h3>
            <a href="/gudang-gizi/modules/stok_keluar/index.php"
                class="text-sm text-primary-400 hover:text-primary-300">
                Lihat Semua
            </a>
        </div>

        <?php if (empty($recentKeluar)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>Belum ada transaksi</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentKeluar as $item): ?>
                    <a href="/gudang-gizi/modules/stok_keluar/detail.php?id=<?= $item['id'] ?>"
                        class="block p-3 rounded-xl bg-slate-800/50 hover:bg-slate-700/50 transition-colors">
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-medium text-white text-sm">
                                <?= htmlspecialchars($item['no_transaksi']) ?>
                            </span>
                            <span class="text-red-400 text-sm font-medium">
                                <?= formatRupiah($item['total_nilai']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400">
                                <?= htmlspecialchars($item['tujuan'] ?? 'Dapur') ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <?= formatTanggal($item['tanggal']) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Summary -->
<div class="mt-8 glass rounded-2xl p-6">
    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
        <i class="fas fa-calendar-alt text-primary-400"></i>
        Ringkasan Bulan Ini
    </h3>
    <div class="grid md:grid-cols-2 gap-6">
        <div class="flex items-center gap-4 p-4 rounded-xl bg-green-500/10 border border-green-500/20">
            <div class="w-14 h-14 rounded-xl bg-green-500 flex items-center justify-center">
                <i class="fas fa-arrow-trend-up text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-400">Total Nilai Stok Masuk</p>
                <p class="text-2xl font-bold text-green-400">
                    <?= formatRupiah($stats['nilai_masuk_bulan']) ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
            <div class="w-14 h-14 rounded-xl bg-red-500 flex items-center justify-center">
                <i class="fas fa-arrow-trend-down text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-400">Total Nilai Stok Keluar</p>
                <p class="text-2xl font-bold text-red-400">
                    <?= formatRupiah($stats['nilai_keluar_bulan']) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($chartData, 'nama')) ?>,
            datasets: [{
                data: <?= json_encode(array_map('intval', array_column($chartData, 'total_stok'))) ?>,
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(20, 184, 166, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#94a3b8',
                        padding: 15,
                        usePointStyle: true
                    }
                }
            },
            cutout: '60%'
        }
    });

    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyLabels = <?= json_encode(array_column($monthlyData, 'bulan')) ?>;
    const monthlyMasuk = <?= json_encode(array_map('floatval', array_column($monthlyData, 'total'))) ?>;
    const monthlyKeluar = <?= json_encode(array_map('floatval', array_column($monthlyKeluarData, 'total'))) ?>;

    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthlyLabels.map(m => {
                const [year, month] = m.split('-');
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                return months[parseInt(month) - 1] + ' ' + year.slice(2);
            }),
            datasets: [{
                label: 'Stok Masuk',
                data: monthlyMasuk,
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderRadius: 8
            }, {
                label: 'Stok Keluar',
                data: monthlyKeluar,
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#94a3b8',
                        padding: 15,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                },
                y: {
                    grid: { color: 'rgba(100, 116, 139, 0.2)' },
                    ticks: {
                        color: '#64748b',
                        callback: function (value) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
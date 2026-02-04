<?php
/**
 * Laporan Transaksi
 */

require_once __DIR__ . '/../../includes/header.php';

$tipe = $_GET['tipe'] ?? 'all';
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');

// Get stok masuk
$masukData = fetchAll("SELECT sm.*, s.nama as supplier_nama, u.nama_lengkap as user_nama,
                       (SELECT COUNT(*) FROM stok_masuk_detail WHERE stok_masuk_id = sm.id) as jumlah_item
                       FROM stok_masuk sm
                       LEFT JOIN supplier s ON sm.supplier_id = s.id
                       LEFT JOIN users u ON sm.user_id = u.id
                       WHERE sm.tanggal BETWEEN '$mulai' AND '$akhir' AND sm.status = 'completed'
                       ORDER BY sm.tanggal DESC, sm.id DESC");

// Get stok keluar
$keluarData = fetchAll("SELECT sk.*, u.nama_lengkap as user_nama,
                        (SELECT COUNT(*) FROM stok_keluar_detail WHERE stok_keluar_id = sk.id) as jumlah_item
                        FROM stok_keluar sk
                        LEFT JOIN users u ON sk.user_id = u.id
                        WHERE sk.tanggal BETWEEN '$mulai' AND '$akhir' AND sk.status = 'completed'
                        ORDER BY sk.tanggal DESC, sk.id DESC");

$totalMasuk = array_sum(array_column($masukData, 'total_nilai'));
$totalKeluar = array_sum(array_column($keluarData, 'total_nilai'));
?>

<script>setPageTitle('Laporan Transaksi', 'Rekap transaksi stok');</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center">
            <i class="fas fa-receipt"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Laporan Transaksi</h1>
            <p class="text-sm text-gray-400">
                <?= formatTanggal($mulai) ?> -
                <?= formatTanggal($akhir) ?>
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <!-- Export Dropdown -->
        <div class="relative" id="exportDropdown">
            <button onclick="toggleExportDropdown()"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white flex items-center gap-2 shadow-lg shadow-blue-500/20 transition-all">
                <i class="fas fa-download"></i> Export
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="exportMenu"
                class="hidden absolute right-0 top-full mt-2 w-48 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden z-50">
                <a href="/gudang-gizi/modules/laporan/export_transaksi.php?format=pdf&tipe=<?= urlencode($tipe) ?>&mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>"
                    target="_blank"
                    class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-slate-700 hover:text-white transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm">Export PDF</p>
                        <p class="text-xs text-gray-500">Untuk cetak</p>
                    </div>
                </a>
                <a href="/gudang-gizi/modules/laporan/export_transaksi.php?format=excel&tipe=<?= urlencode($tipe) ?>&mulai=<?= urlencode($mulai) ?>&akhir=<?= urlencode($akhir) ?>"
                    class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-slate-700 hover:text-white transition-colors border-t border-slate-700/50">
                    <div class="w-8 h-8 rounded-lg bg-green-500/20 text-green-400 flex items-center justify-center">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm">Export Excel</p>
                        <p class="text-xs text-gray-500">Format CSV</p>
                    </div>
                </a>
            </div>
        </div>
        <button onclick="window.print()"
            class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white flex items-center gap-2">
            <i class="fas fa-print"></i> Cetak
        </button>
    </div>
</div>

<script>
    function toggleExportDropdown() {
        const menu = document.getElementById('exportMenu');
        menu.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('exportDropdown');
        const menu = document.getElementById('exportMenu');
        if (dropdown && !dropdown.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>

<!-- Summary -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="glass rounded-xl p-4 text-center border-l-4 border-green-500">
        <p class="text-sm text-gray-400">Total Stok Masuk</p>
        <p class="text-xl font-bold text-green-400">
            <?= formatRupiah($totalMasuk) ?>
        </p>
        <p class="text-xs text-gray-500">
            <?= count($masukData) ?> transaksi
        </p>
    </div>
    <div class="glass rounded-xl p-4 text-center border-l-4 border-red-500">
        <p class="text-sm text-gray-400">Total Stok Keluar</p>
        <p class="text-xl font-bold text-red-400">
            <?= formatRupiah($totalKeluar) ?>
        </p>
        <p class="text-xs text-gray-500">
            <?= count($keluarData) ?> transaksi
        </p>
    </div>
    <div class="glass rounded-xl p-4 text-center border-l-4 border-blue-500">
        <p class="text-sm text-gray-400">Selisih</p>
        <p class="text-xl font-bold <?= $totalMasuk >= $totalKeluar ? 'text-blue-400' : 'text-yellow-400' ?>">
            <?= formatRupiah($totalMasuk - $totalKeluar) ?>
        </p>
    </div>
</div>

<!-- Filters -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Dari Tanggal</label>
            <input type="date" name="mulai" value="<?= $mulai ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Sampai</label>
            <input type="date" name="akhir" value="<?= $akhir ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Tipe</label>
            <select name="tipe"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="all" <?= $tipe === 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="in" <?= $tipe === 'in' ? 'selected' : '' ?>>Stok Masuk</option>
                <option value="out" <?= $tipe === 'out' ? 'selected' : '' ?>>Stok Keluar</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit"
                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm"><i
                    class="fas fa-search mr-1"></i> Filter</button>
        </div>
    </form>
</div>

<?php if ($tipe !== 'out'): ?>
    <!-- Stok Masuk Table -->
    <div class="glass rounded-xl overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-700 bg-green-500/10">
            <h3 class="font-semibold text-green-400"><i class="fas fa-arrow-down mr-2"></i>Stok Masuk</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-modern">
                <thead>
                    <tr class="text-left text-sm text-gray-400">
                        <th class="px-4 py-3">No. Transaksi</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 hidden md:table-cell">Supplier</th>
                        <th class="px-4 py-3 text-center">Item</th>
                        <th class="px-4 py-3 text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($masukData)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data</td>
                        </tr>
                    <?php else:
                        foreach ($masukData as $item): ?>
                            <tr class="hover:bg-slate-700/30">
                                <td class="px-4 py-3 font-mono text-sm text-green-400">
                                    <?= $item['no_transaksi'] ?>
                                </td>
                                <td class="px-4 py-3 text-gray-300">
                                    <?= formatTanggal($item['tanggal']) ?>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-gray-400">
                                    <?= htmlspecialchars($item['supplier_nama'] ?? '-') ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?= $item['jumlah_item'] ?>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-green-400">
                                    <?= formatRupiah($item['total_nilai']) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($tipe !== 'in'): ?>
    <!-- Stok Keluar Table -->
    <div class="glass rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-700 bg-red-500/10">
            <h3 class="font-semibold text-red-400"><i class="fas fa-arrow-up mr-2"></i>Stok Keluar</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-modern">
                <thead>
                    <tr class="text-left text-sm text-gray-400">
                        <th class="px-4 py-3">No. Transaksi</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 hidden md:table-cell">Tujuan</th>
                        <th class="px-4 py-3 text-center">Item</th>
                        <th class="px-4 py-3 text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($keluarData)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data</td>
                        </tr>
                    <?php else:
                        foreach ($keluarData as $item): ?>
                            <tr class="hover:bg-slate-700/30">
                                <td class="px-4 py-3 font-mono text-sm text-red-400">
                                    <?= $item['no_transaksi'] ?>
                                </td>
                                <td class="px-4 py-3 text-gray-300">
                                    <?= formatTanggal($item['tanggal']) ?>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-gray-400">
                                    <?= htmlspecialchars($item['tujuan'] ?? 'Dapur') ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?= $item['jumlah_item'] ?>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-red-400">
                                    <?= formatRupiah($item['total_nilai']) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
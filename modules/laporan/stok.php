<?php
/**
 * Laporan Stok
 */

require_once __DIR__ . '/../../includes/header.php';

$filter = $_GET['filter'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE b.is_active = 1";
if ($filter === 'low') {
    $where .= " AND b.stok_saat_ini <= b.stok_minimum";
}
if ($kategori) {
    $where .= " AND b.kategori_id = " . (int) $kategori;
}
if ($search) {
    $search = escape($search);
    $where .= " AND (b.nama LIKE '%$search%' OR b.kode LIKE '%$search%')";
}

$items = fetchAll("SELECT b.*, k.nama as kategori_nama, s.singkatan as satuan_singkatan,
                   (b.stok_saat_ini * b.harga_satuan) as nilai_stok
                   FROM bahan_makanan b
                   LEFT JOIN kategori k ON b.kategori_id = k.id
                   LEFT JOIN satuan s ON b.satuan_id = s.id
                   $where ORDER BY b.nama ASC");

$summary = fetchOne("SELECT COUNT(*) as total_item, SUM(stok_saat_ini) as total_stok, 
                     SUM(stok_saat_ini * harga_satuan) as total_nilai FROM bahan_makanan b $where");

$categories = fetchAll("SELECT * FROM kategori ORDER BY nama ASC");
?>

<script>setPageTitle('Laporan Stok', 'Rekap stok bahan makanan');</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Laporan Stok</h1>
            <p class="text-sm text-gray-400">
                <?= count($items) ?> item
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <!-- Export Dropdown -->
        <div class="relative" id="exportDropdown">
            <button onclick="toggleExportDropdown()"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white flex items-center gap-2 shadow-lg shadow-green-500/20 transition-all">
                <i class="fas fa-download"></i> Export
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="exportMenu"
                class="hidden absolute right-0 top-full mt-2 w-48 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden z-50">
                <a href="/gudang-gizi/modules/laporan/export_stok.php?format=pdf&filter=<?= urlencode($filter) ?>&kategori=<?= urlencode($kategori) ?>&search=<?= urlencode($search) ?>"
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
                <a href="/gudang-gizi/modules/laporan/export_stok.php?format=excel&filter=<?= urlencode($filter) ?>&kategori=<?= urlencode($kategori) ?>&search=<?= urlencode($search) ?>"
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
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-sm text-gray-400">Total Item</p>
        <p class="text-2xl font-bold text-white">
            <?= number_format($summary['total_item']) ?>
        </p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-sm text-gray-400">Total Stok</p>
        <p class="text-2xl font-bold text-blue-400">
            <?= number_format($summary['total_stok']) ?>
        </p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-sm text-gray-400">Nilai Stok</p>
        <p class="text-xl font-bold text-green-400">
            <?= formatRupiah($summary['total_nilai']) ?>
        </p>
    </div>
</div>

<!-- Filters -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Filter</label>
            <select name="filter"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="">Semua</option>
                <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>Stok Menipis</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Kategori</label>
            <select name="kategori"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $kategori == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Cari</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                placeholder="Nama/kode...">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm"><i
                    class="fas fa-search mr-1"></i> Filter</button>
            <a href="/gudang-gizi/modules/laporan/stok.php"
                class="bg-slate-800 text-gray-400 px-4 py-2 rounded-lg text-sm"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="glass rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full table-modern">
            <thead>
                <tr class="text-left text-sm text-gray-400">
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Bahan</th>
                    <th class="px-4 py-3 hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-center">Stok</th>
                    <th class="px-4 py-3 text-center hidden lg:table-cell">Min</th>
                    <th class="px-4 py-3 text-right hidden lg:table-cell">Harga</th>
                    <th class="px-4 py-3 text-right">Nilai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500"><i
                                class="fas fa-box-open text-4xl mb-3"></i>
                            <p>Tidak ada data</p>
                        </td>
                    </tr>
                <?php else:
                    foreach ($items as $item):
                        $isLow = $item['stok_saat_ini'] <= $item['stok_minimum']; ?>
                        <tr class="hover:bg-slate-700/30 <?= $isLow ? 'bg-yellow-500/10' : '' ?>">
                            <td class="px-4 py-3 font-mono text-sm text-primary-400">
                                <?= $item['kode'] ?>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">
                                    <?= htmlspecialchars($item['nama']) ?>
                                </p>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-gray-400">
                                <?= htmlspecialchars($item['kategori_nama'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-center"><span
                                    class="px-2 py-1 rounded-lg text-sm font-medium <?= $isLow ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400' ?>">
                                    <?= number_format($item['stok_saat_ini']) ?>
                                    <?= $item['satuan_singkatan'] ?>
                                </span></td>
                            <td class="px-4 py-3 text-center hidden lg:table-cell text-gray-400">
                                <?= number_format($item['stok_minimum']) ?>
                            </td>
                            <td class="px-4 py-3 text-right hidden lg:table-cell text-gray-300">
                                <?= formatRupiah($item['harga_satuan']) ?>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-green-400">
                                <?= formatRupiah($item['nilai_stok']) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
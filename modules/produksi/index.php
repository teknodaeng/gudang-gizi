<?php
/**
 * Daftar Produksi/Masak
 */

require_once __DIR__ . '/../../includes/header.php';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');

$where = "WHERE p.tanggal BETWEEN '$mulai' AND '$akhir'";
if ($search) {
    $search = escape($search);
    $where .= " AND (p.no_produksi LIKE '%$search%' OR m.nama LIKE '%$search%')";
}
if ($status) {
    $where .= " AND p.status = '$status'";
}

$produksiList = fetchAll("SELECT p.*, m.nama as menu_nama, m.kode as menu_kode, u.nama_lengkap as user_nama
                          FROM produksi p
                          JOIN menu m ON p.menu_id = m.id
                          LEFT JOIN users u ON p.user_id = u.id
                          $where
                          ORDER BY p.tanggal DESC, p.id DESC");

// Stats
$todayCount = fetchOne("SELECT COUNT(*) as count FROM produksi WHERE tanggal = CURDATE()")['count'];
$thisMonthCost = fetchOne("SELECT SUM(total_biaya) as total FROM produksi WHERE MONTH(tanggal) = MONTH(CURDATE()) AND status = 'completed'")['total'] ?? 0;
?>

<script>setPageTitle('Produksi / Masak', 'Riwayat produksi makanan');</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-500 flex items-center justify-center shadow-lg">
            <i class="fas fa-fire-burner text-white"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Produksi / Masak</h1>
            <p class="text-sm text-gray-400">
                <?= count($produksiList) ?> produksi
            </p>
        </div>
    </div>
    <a href="/gudang-gizi/modules/produksi/create.php"
        class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-400 hover:to-pink-400 text-white flex items-center gap-2 shadow-lg shadow-rose-500/20 transition-all w-fit">
        <i class="fas fa-plus"></i> Masak Baru
    </a>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-4">
        <p class="text-sm text-gray-400">Produksi Hari Ini</p>
        <p class="text-2xl font-bold text-white">
            <?= $todayCount ?>
        </p>
    </div>
    <div class="glass rounded-xl p-4">
        <p class="text-sm text-gray-400">Biaya Bulan Ini</p>
        <p class="text-lg font-bold text-rose-400">
            <?= formatRupiah($thisMonthCost) ?>
        </p>
    </div>
    <div class="glass rounded-xl p-4 col-span-2">
        <p class="text-sm text-gray-400">Periode</p>
        <p class="text-lg font-bold text-white">
            <?= formatTanggal($mulai) ?> -
            <?= formatTanggal($akhir) ?>
        </p>
    </div>
</div>

<!-- Filter -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Dari</label>
            <input type="date" name="mulai" value="<?= $mulai ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Sampai</label>
            <input type="date" name="akhir" value="<?= $akhir ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Status</label>
            <select name="status"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="">Semua</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Batal</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Cari</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                placeholder="No/Menu...">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="bg-slate-800 text-gray-400 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Produksi List -->
<div class="glass rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full table-modern">
            <thead>
                <tr class="text-left text-sm text-gray-400">
                    <th class="px-4 py-3">No. Produksi</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Menu</th>
                    <th class="px-4 py-3 text-center">Porsi</th>
                    <th class="px-4 py-3 text-right">Biaya</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <?php if (empty($produksiList)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-fire-burner text-4xl mb-3"></i>
                            <p>Tidak ada data produksi</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produksiList as $item): ?>
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-3 font-mono text-sm text-rose-400">
                                <?= $item['no_produksi'] ?>
                            </td>
                            <td class="px-4 py-3 text-gray-300">
                                <?= formatTanggal($item['tanggal']) ?>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">
                                    <?= htmlspecialchars($item['menu_nama']) ?>
                                </p>
                                <p class="text-xs text-gray-500 font-mono">
                                    <?= $item['menu_kode'] ?>
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-lg bg-slate-700 text-white font-medium">
                                    <?= $item['jumlah_porsi'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-rose-400">
                                <?= formatRupiah($item['total_biaya']) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $statusClass = match ($item['status']) {
                                    'completed' => 'bg-green-500/20 text-green-400',
                                    'cancelled' => 'bg-red-500/20 text-red-400',
                                    default => 'bg-yellow-500/20 text-yellow-400'
                                };
                                $statusLabel = match ($item['status']) {
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Batal',
                                    default => 'Draft'
                                };
                                ?>
                                <span class="px-2 py-1 rounded-lg text-xs font-medium <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="/gudang-gizi/modules/produksi/detail.php?id=<?= $item['id'] ?>"
                                    class="px-3 py-1 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 text-sm inline-flex items-center gap-1">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
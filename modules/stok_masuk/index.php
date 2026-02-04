<?php
/**
 * Stok Masuk - List
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/../../includes/header.php';

// Get filters
$search = $_GET['search'] ?? '';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$supplier_id = $_GET['supplier'] ?? '';

// Build query
$where = "WHERE sm.tanggal BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";

if ($search) {
    $search = escape($search);
    $where .= " AND (sm.no_transaksi LIKE '%$search%' OR s.nama LIKE '%$search%')";
}

if ($supplier_id) {
    $where .= " AND sm.supplier_id = " . (int) $supplier_id;
}

// Get data with pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$countResult = fetchOne("SELECT COUNT(*) as total FROM stok_masuk sm LEFT JOIN supplier s ON sm.supplier_id = s.id $where");
$totalItems = $countResult['total'];
$totalPages = ceil($totalItems / $limit);

$items = fetchAll("SELECT sm.*, s.nama as supplier_nama, u.nama_lengkap as user_nama,
                   (SELECT COUNT(*) FROM stok_masuk_detail WHERE stok_masuk_id = sm.id) as jumlah_item
                   FROM stok_masuk sm
                   LEFT JOIN supplier s ON sm.supplier_id = s.id
                   LEFT JOIN users u ON sm.user_id = u.id
                   $where
                   ORDER BY sm.tanggal DESC, sm.created_at DESC
                   LIMIT $limit OFFSET $offset");

// Get suppliers for filter
$suppliers = fetchAll("SELECT * FROM supplier WHERE is_active = 1 ORDER BY nama ASC");

// Calculate summary
$summary = fetchOne("SELECT COUNT(*) as total_trx, SUM(total_nilai) as total_nilai FROM stok_masuk sm $where");
?>

<script>setPageTitle('Stok Masuk', 'Riwayat penerimaan stok');</script>

<!-- Header Actions -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Stok Masuk</h1>
            <p class="text-sm text-gray-400">
                <?= number_format($totalItems) ?> transaksi
            </p>
        </div>
    </div>

    <a href="/gudang-gizi/modules/stok_masuk/create.php"
        class="btn-primary px-4 py-2 rounded-xl text-white font-medium flex items-center gap-2 w-fit">
        <i class="fas fa-plus"></i>
        Tambah Stok Masuk
    </a>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="glass rounded-xl p-4 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center">
            <i class="fas fa-receipt"></i>
        </div>
        <div>
            <p class="text-sm text-gray-400">Total Transaksi</p>
            <p class="text-xl font-bold text-white">
                <?= number_format($summary['total_trx']) ?>
            </p>
        </div>
    </div>
    <div class="glass rounded-xl p-4 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div>
            <p class="text-sm text-gray-400">Total Nilai</p>
            <p class="text-xl font-bold text-green-400">
                <?= formatRupiah($summary['total_nilai'] ?? 0) ?>
            </p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Supplier</label>
            <select name="supplier"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="">Semua Supplier</option>
                <?php foreach ($suppliers as $sup): ?>
                    <option value="<?= $sup['id'] ?>" <?= $supplier_id == $sup['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sup['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Cari</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                placeholder="No. transaksi...">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="/gudang-gizi/modules/stok_masuk/index.php"
                class="bg-slate-800 hover:bg-slate-700 text-gray-400 px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="glass rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full table-modern">
            <thead>
                <tr class="text-left text-sm text-gray-400">
                    <th class="px-4 py-3">No. Transaksi</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3 hidden md:table-cell">Supplier</th>
                    <th class="px-4 py-3 text-center hidden lg:table-cell">Item</th>
                    <th class="px-4 py-3 text-right">Nilai</th>
                    <th class="px-4 py-3 text-center hidden lg:table-cell">Petugas</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Tidak ada data transaksi</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm text-primary-400">
                                    <?= htmlspecialchars($item['no_transaksi']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-300">
                                <?= formatTanggal($item['tanggal']) ?>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-gray-400">
                                <?= htmlspecialchars($item['supplier_nama'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-center hidden lg:table-cell">
                                <span class="px-2 py-1 rounded-lg bg-slate-700 text-gray-300 text-sm">
                                    <?= $item['jumlah_item'] ?> item
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-green-400">
                                <?= formatRupiah($item['total_nilai']) ?>
                            </td>
                            <td class="px-4 py-3 text-center hidden lg:table-cell text-gray-400 text-sm">
                                <?= htmlspecialchars($item['user_nama']) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $statusClass = match ($item['status']) {
                                    'completed' => 'bg-green-500/20 text-green-400',
                                    'draft' => 'bg-yellow-500/20 text-yellow-400',
                                    'cancelled' => 'bg-red-500/20 text-red-400',
                                    default => 'bg-gray-500/20 text-gray-400'
                                };
                                ?>
                                <span class="inline-block px-2 py-1 rounded-lg text-xs font-medium <?= $statusClass ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="/gudang-gizi/modules/stok_masuk/detail.php?id=<?= $item['id'] ?>"
                                        class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors"
                                        title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($item['status'] === 'completed'): ?>
                                        <a href="/gudang-gizi/modules/stok_masuk/print.php?id=<?= $item['id'] ?>" target="_blank"
                                            class="p-2 text-gray-400 hover:text-purple-400 hover:bg-purple-500/20 rounded-lg transition-colors"
                                            title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-4 border-t border-slate-700 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-500">
                Menampilkan
                <?= ($offset + 1) ?>-
                <?= min($offset + $limit, $totalItems) ?> dari
                <?= $totalItems ?> transaksi
            </p>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&supplier=<?= $supplier_id ?>"
                        class="px-3 py-2 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 text-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&supplier=<?= $supplier_id ?>"
                        class="px-3 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-primary-500 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&supplier=<?= $supplier_id ?>"
                        class="px-3 py-2 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 text-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
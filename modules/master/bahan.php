<?php
/**
 * Master Bahan Makanan
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/../../includes/header.php';

// Check permission
if (!hasPermission(['owner', 'admin'])) {
    redirectWith('/gudang-gizi/index.php', 'Anda tidak memiliki akses ke halaman ini', 'error');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    query("UPDATE bahan_makanan SET is_active = 0 WHERE id = ?", [$id], 'i');
    logActivity($currentUser['id'], 'delete_bahan', "Menghapus bahan ID: $id");
    redirectWith('/gudang-gizi/modules/master/bahan.php', 'Bahan makanan berhasil dihapus', 'success');
}

// Get filters
$search = $_GET['search'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$status = $_GET['status'] ?? 'active';

// Build query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($status === 'active') {
    $where .= " AND b.is_active = 1";
} elseif ($status === 'inactive') {
    $where .= " AND b.is_active = 0";
}

if ($search) {
    $where .= " AND (b.nama LIKE ? OR b.kode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($kategori) {
    $where .= " AND b.kategori_id = ?";
    $params[] = (int) $kategori;
    $types .= "i";
}

// Get data with pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$countResult = fetchOne("SELECT COUNT(*) as total FROM bahan_makanan b $where", $params, $types);
$totalItems = $countResult['total'];
$totalPages = ceil($totalItems / $limit);

$sql = "SELECT b.*, k.nama as kategori_nama, s.nama as satuan_nama, s.singkatan as satuan_singkatan
        FROM bahan_makanan b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN satuan s ON b.satuan_id = s.id
        $where
        ORDER BY b.nama ASC
        LIMIT $limit OFFSET $offset";

$items = empty($params) ? fetchAll($sql) : fetchAll($sql, $params, $types);

// Get categories for filter
$categories = fetchAll("SELECT * FROM kategori ORDER BY nama ASC");
?>

<script>setPageTitle('Bahan Makanan', 'Kelola data bahan makanan');</script>

<!-- Header Actions -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl gradient-primary flex items-center justify-center">
            <i class="fas fa-carrot text-white"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Master Bahan Makanan</h1>
            <p class="text-sm text-gray-400">
                <?= number_format($totalItems) ?> item ditemukan
            </p>
        </div>
    </div>

    <a href="/gudang-gizi/modules/master/bahan_form.php"
        class="btn-primary px-4 py-2 rounded-xl text-white font-medium flex items-center gap-2 w-fit">
        <i class="fas fa-plus"></i>
        Tambah Bahan
    </a>
</div>

<!-- Filters -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Cari</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                placeholder="Nama atau kode...">
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
            <label class="block text-sm text-gray-400 mb-1">Status</label>
            <select name="status"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                <i class="fas fa-search mr-1"></i> Cari
            </button>
            <a href="/gudang-gizi/modules/master/bahan.php"
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
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Bahan</th>
                    <th class="px-4 py-3 hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-center">Stok</th>
                    <th class="px-4 py-3 hidden lg:table-cell text-right">Harga</th>
                    <th class="px-4 py-3 hidden lg:table-cell">Lokasi</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-3"></i>
                            <p>Tidak ada data bahan makanan</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm text-primary-400">
                                    <?= htmlspecialchars($item['kode']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-gray-400">
                                        <i class="fas fa-carrot"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-white">
                                            <?= htmlspecialchars($item['nama']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 md:hidden">
                                            <?= htmlspecialchars($item['kategori_nama'] ?? '-') ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-gray-400">
                                <?= htmlspecialchars($item['kategori_nama'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $isLow = $item['stok_saat_ini'] <= $item['stok_minimum'];
                                $stockClass = $isLow ? 'text-yellow-400 bg-yellow-500/20' : 'text-green-400 bg-green-500/20';
                                ?>
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-sm font-medium <?= $stockClass ?>">
                                    <?php if ($isLow): ?><i class="fas fa-exclamation-triangle text-xs"></i>
                                    <?php endif; ?>
                                    <?= number_format($item['stok_saat_ini']) ?>
                                    <span class="text-xs opacity-75">
                                        <?= $item['satuan_singkatan'] ?? '' ?>
                                    </span>
                                </span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-right text-gray-300">
                                <?= formatRupiah($item['harga_satuan']) ?>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-gray-400">
                                <?= htmlspecialchars($item['lokasi_rak'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($item['is_active']): ?>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-green-500/20 text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="/gudang-gizi/modules/master/bahan_form.php?id=<?= $item['id'] ?>"
                                        class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        onclick="confirmDelete(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nama']) ?>')"
                                        class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded-lg transition-colors"
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                <?= $totalItems ?> item
            </p>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori ?>&status=<?= $status ?>"
                        class="px-3 py-2 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 text-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori ?>&status=<?= $status ?>"
                        class="px-3 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-primary-500 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori ?>&status=<?= $status ?>"
                        class="px-3 py-2 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 text-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
        <div class="bg-slate-800 rounded-2xl shadow-2xl p-6 border border-slate-700">
            <div class="text-center mb-6">
                <div
                    class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Hapus Bahan Makanan?</h3>
                <p class="text-gray-400">Apakah Anda yakin ingin menghapus <span id="deleteItemName"
                        class="text-white font-medium"></span>?</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()"
                    class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-colors">
                    Batal
                </button>
                <a id="deleteConfirmBtn" href="#"
                    class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-center transition-colors">
                    Ya, Hapus
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('deleteItemName').textContent = name;
        document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
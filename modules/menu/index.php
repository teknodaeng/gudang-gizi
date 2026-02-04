<?php
/**
 * Manajemen Menu & Resep
 */

require_once __DIR__ . '/../../includes/header.php';

// Check permission
if (!hasPermission(['owner', 'admin'])) {
    redirectWith('/gudang-gizi/index.php', 'Anda tidak memiliki akses ke halaman ini', 'error');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $kode = sanitize($_POST['kode']);
        $nama = sanitize($_POST['nama']);
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');
        $kategori = $_POST['kategori'];
        $porsi_standar = (int) $_POST['porsi_standar'];

        if ($action === 'add') {
            $result = query(
                "INSERT INTO menu (kode, nama, deskripsi, kategori, porsi_standar) VALUES (?, ?, ?, ?, ?)",
                [$kode, $nama, $deskripsi, $kategori, $porsi_standar],
                "ssssi"
            );
            if ($result) {
                redirectWith($_SERVER['PHP_SELF'], 'Menu berhasil ditambahkan!', 'success');
            } else {
                redirectWith($_SERVER['PHP_SELF'], 'Gagal menambahkan menu. Kode mungkin sudah ada.', 'error');
            }
        } else {
            $result = query(
                "UPDATE menu SET nama = ?, deskripsi = ?, kategori = ?, porsi_standar = ? WHERE id = ?",
                [$nama, $deskripsi, $kategori, $porsi_standar, $id],
                "sssii"
            );
            if ($result) {
                redirectWith($_SERVER['PHP_SELF'], 'Menu berhasil diperbarui!', 'success');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        // Check if menu is used in production
        $check = fetchOne("SELECT COUNT(*) as count FROM produksi WHERE menu_id = ?", [$id], "i");
        if ($check['count'] > 0) {
            redirectWith($_SERVER['PHP_SELF'], 'Menu tidak bisa dihapus karena sudah digunakan dalam produksi', 'error');
        } else {
            query("DELETE FROM menu WHERE id = ?", [$id], "i");
            redirectWith($_SERVER['PHP_SELF'], 'Menu berhasil dihapus!', 'success');
        }
    }

    if ($action === 'toggle') {
        $id = (int) $_POST['id'];
        query("UPDATE menu SET is_active = NOT is_active WHERE id = ?", [$id], "i");
        redirectWith($_SERVER['PHP_SELF'], 'Status menu berhasil diubah!', 'success');
    }
}

// Get all menus
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';

$where = "WHERE 1=1";
if ($search) {
    $search = escape($search);
    $where .= " AND (m.nama LIKE '%$search%' OR m.kode LIKE '%$search%')";
}
if ($kategori_filter) {
    $where .= " AND m.kategori = '$kategori_filter'";
}

$menus = fetchAll("SELECT m.*, 
                   (SELECT COUNT(*) FROM menu_resep WHERE menu_id = m.id) as total_bahan,
                   (SELECT SUM(mr.jumlah * b.harga_satuan) FROM menu_resep mr 
                    JOIN bahan_makanan b ON mr.bahan_id = b.id WHERE mr.menu_id = m.id) as estimasi_biaya
                   FROM menu m $where ORDER BY m.nama ASC");

$kategoriList = [
    'makanan_utama' => 'Makanan Utama',
    'lauk' => 'Lauk',
    'sayur' => 'Sayur',
    'minuman' => 'Minuman',
    'snack' => 'Snack'
];
?>

<script>setPageTitle('Menu & Resep', 'Kelola menu dan komposisi bahan');</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center shadow-lg">
            <i class="fas fa-utensils text-white"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Menu & Resep</h1>
            <p class="text-sm text-gray-400">
                <?= count($menus) ?> menu tersedia
            </p>
        </div>
    </div>
    <button onclick="openModal('add')"
        class="px-4 py-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-400 hover:to-amber-400 text-white flex items-center gap-2 shadow-lg shadow-orange-500/20 transition-all w-fit">
        <i class="fas fa-plus"></i> Tambah Menu
    </button>
</div>

<!-- Search & Filter -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Cari</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                placeholder="Nama/kode menu...">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Kategori</label>
            <select name="kategori"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $kategori_filter === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
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

<!-- Menu Grid -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php if (empty($menus)): ?>
        <div class="col-span-full text-center py-12 text-gray-500">
            <i class="fas fa-utensils text-5xl mb-4"></i>
            <p>Belum ada menu. Klik tombol "Tambah Menu" untuk memulai.</p>
        </div>
    <?php else: ?>
        <?php foreach ($menus as $menu):
            $isActive = $menu['is_active'];
            ?>
            <div class="glass rounded-xl overflow-hidden <?= !$isActive ? 'opacity-60' : '' ?>">
                <!-- Header -->
                <div class="p-4 border-b border-slate-700/50">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500/20 to-amber-500/20 flex items-center justify-center">
                                <?php
                                $icon = match ($menu['kategori']) {
                                    'makanan_utama' => 'fa-bowl-rice',
                                    'lauk' => 'fa-drumstick-bite',
                                    'sayur' => 'fa-leaf',
                                    'minuman' => 'fa-mug-hot',
                                    'snack' => 'fa-cookie',
                                    default => 'fa-utensils'
                                };
                                ?>
                                <i class="fas <?= $icon ?> text-orange-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">
                                    <?= htmlspecialchars($menu['nama']) ?>
                                </h3>
                                <p class="text-xs text-gray-400 font-mono">
                                    <?= $menu['kode'] ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-lg text-xs font-medium bg-slate-700 text-gray-300">
                            <?= $kategoriList[$menu['kategori']] ?? $menu['kategori'] ?>
                        </span>
                    </div>
                    <?php if ($menu['deskripsi']): ?>
                        <p class="text-sm text-gray-400 mt-3 line-clamp-2">
                            <?= htmlspecialchars($menu['deskripsi']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="px-4 py-3 bg-slate-800/30 grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-lg font-bold text-white">
                            <?= $menu['porsi_standar'] ?>
                        </p>
                        <p class="text-xs text-gray-500">Porsi</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-blue-400">
                            <?= $menu['total_bahan'] ?>
                        </p>
                        <p class="text-xs text-gray-500">Bahan</p>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-green-400">
                            <?= formatRupiah($menu['estimasi_biaya'] ?? 0) ?>
                        </p>
                        <p class="text-xs text-gray-500">Est. Biaya</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-3 border-t border-slate-700/50 flex gap-2">
                    <a href="/gudang-gizi/modules/menu/resep.php?id=<?= $menu['id'] ?>"
                        class="flex-1 text-center py-2 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 text-sm font-medium transition-colors">
                        <i class="fas fa-list-check mr-1"></i> Resep
                    </a>
                    <button onclick="openModal('edit', <?= htmlspecialchars(json_encode($menu)) ?>)"
                        class="px-3 py-2 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus menu ini?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $menu['id'] ?>">
                        <button type="submit"
                            class="px-3 py-2 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Add/Edit -->
<div id="menuModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-slate-800 rounded-2xl w-full max-w-md shadow-2xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h3 id="modalTitle" class="text-xl font-bold text-white">Tambah Menu</h3>
        </div>
        <form method="POST" id="menuForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId">

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Kode Menu *</label>
                    <input type="text" name="kode" id="formKode" required
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                        placeholder="Contoh: MNU001">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nama Menu *</label>
                    <input type="text" name="nama" id="formNama" required
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                        placeholder="Contoh: Nasi Goreng Spesial">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Kategori</label>
                    <select name="kategori" id="formKategori"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                        <?php foreach ($kategoriList as $key => $label): ?>
                            <option value="<?= $key ?>">
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Porsi Standar</label>
                    <input type="number" name="porsi_standar" id="formPorsi" value="1" min="1"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" id="formDeskripsi" rows="3"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                        placeholder="Deskripsi singkat menu..."></textarea>
                </div>
            </div>

            <div class="p-6 border-t border-slate-700 flex gap-3">
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-4 py-2 rounded-xl bg-slate-700 text-gray-300 hover:bg-slate-600 transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-medium hover:from-orange-400 hover:to-amber-400 transition-all">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(action, data = null) {
        const modal = document.getElementById('menuModal');
        const title = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const formId = document.getElementById('formId');
        const formKode = document.getElementById('formKode');
        const formNama = document.getElementById('formNama');
        const formKategori = document.getElementById('formKategori');
        const formPorsi = document.getElementById('formPorsi');
        const formDeskripsi = document.getElementById('formDeskripsi');

        if (action === 'edit' && data) {
            title.textContent = 'Edit Menu';
            formAction.value = 'edit';
            formId.value = data.id;
            formKode.value = data.kode;
            formKode.readOnly = true;
            formNama.value = data.nama;
            formKategori.value = data.kategori;
            formPorsi.value = data.porsi_standar;
            formDeskripsi.value = data.deskripsi || '';
        } else {
            title.textContent = 'Tambah Menu';
            formAction.value = 'add';
            formId.value = '';
            formKode.value = '';
            formKode.readOnly = false;
            formNama.value = '';
            formKategori.value = 'makanan_utama';
            formPorsi.value = 1;
            formDeskripsi.value = '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('menuModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on backdrop click
    document.getElementById('menuModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
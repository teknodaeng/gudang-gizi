<?php
/**
 * Master Kategori
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

    // Check if category is in use
    $inUse = fetchOne("SELECT COUNT(*) as count FROM bahan_makanan WHERE kategori_id = ?", [$id], 'i');
    if ($inUse['count'] > 0) {
        redirectWith('/gudang-gizi/modules/master/kategori.php', 'Kategori tidak dapat dihapus karena masih digunakan', 'error');
    }

    query("DELETE FROM kategori WHERE id = ?", [$id], 'i');
    logActivity($currentUser['id'], 'delete_kategori', "Menghapus kategori ID: $id");
    redirectWith('/gudang-gizi/modules/master/kategori.php', 'Kategori berhasil dihapus', 'success');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nama = sanitize($_POST['nama']);
    $deskripsi = sanitize($_POST['deskripsi']);

    if (empty($nama)) {
        $_SESSION['flash_message'] = 'Nama kategori harus diisi';
        $_SESSION['flash_type'] = 'error';
    } else {
        if ($id > 0) {
            query("UPDATE kategori SET nama = ?, deskripsi = ? WHERE id = ?", [$nama, $deskripsi, $id], 'ssi');
            logActivity($currentUser['id'], 'update_kategori', "Mengupdate kategori: $nama");
            redirectWith('/gudang-gizi/modules/master/kategori.php', 'Kategori berhasil diupdate', 'success');
        } else {
            query("INSERT INTO kategori (nama, deskripsi) VALUES (?, ?)", [$nama, $deskripsi], 'ss');
            logActivity($currentUser['id'], 'create_kategori', "Menambah kategori: $nama");
            redirectWith('/gudang-gizi/modules/master/kategori.php', 'Kategori berhasil ditambahkan', 'success');
        }
    }
}

// Get data
$categories = fetchAll("SELECT k.*, (SELECT COUNT(*) FROM bahan_makanan WHERE kategori_id = k.id AND is_active = 1) as jumlah_bahan 
                        FROM kategori k ORDER BY k.nama ASC");

$editItem = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editItem = fetchOne("SELECT * FROM kategori WHERE id = ?", [(int) $_GET['edit']], 'i');
}
?>

<script>setPageTitle('Kategori Bahan', 'Kelola kategori bahan makanan');</script>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-6 sticky top-24">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-<?= $editItem ? 'edit' : 'plus' ?> text-primary-400"></i>
                <?= $editItem ? 'Edit Kategori' : 'Tambah Kategori' ?>
            </h3>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama Kategori *</label>
                    <input type="text" name="nama" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['nama'] ?? '') ?>" placeholder="Contoh: Bahan Pokok">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                    <textarea name="deskripsi" rows="3"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        placeholder="Deskripsi kategori..."><?= htmlspecialchars($editItem['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="flex gap-2">
                    <?php if ($editItem): ?>
                        <a href="/gudang-gizi/modules/master/kategori.php"
                            class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-center transition-colors">
                            Batal
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="flex-1 btn-primary px-4 py-3 rounded-xl text-white font-medium">
                        <i class="fas fa-save mr-2"></i>
                        <?= $editItem ? 'Update' : 'Simpan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="lg:col-span-2">
        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-tags text-primary-400"></i>
                    Daftar Kategori
                </h3>
                <span class="text-sm text-gray-400">
                    <?= count($categories) ?> kategori
                </span>
            </div>

            <div class="divide-y divide-slate-700/50">
                <?php if (empty($categories)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                        <p>Belum ada kategori</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="p-4 hover:bg-slate-700/30 transition-colors flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-lg bg-primary-500/20 text-primary-400 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-white truncate">
                                        <?= htmlspecialchars($cat['nama']) ?>
                                    </p>
                                    <?php if ($cat['deskripsi']): ?>
                                        <p class="text-xs text-gray-400 truncate">
                                            <?= htmlspecialchars($cat['deskripsi']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 rounded-lg bg-slate-700 text-gray-300 text-sm">
                                    <?= $cat['jumlah_bahan'] ?> bahan
                                </span>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?= $cat['id'] ?>"
                                        class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        onclick="confirmDeleteKategori(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nama']) ?>', <?= $cat['jumlah_bahan'] ?>)"
                                        class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
        <div class="bg-slate-800 rounded-2xl shadow-2xl p-6 border border-slate-700">
            <div class="text-center mb-6">
                <div
                    class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Hapus Kategori?</h3>
                <p class="text-gray-400" id="deleteMessage"></p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal()"
                    class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-colors">
                    Batal
                </button>
                <a id="deleteBtn" href="#"
                    class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-center transition-colors">
                    Ya, Hapus
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDeleteKategori(id, name, count) {
        if (count > 0) {
            alert('Kategori ini tidak dapat dihapus karena masih digunakan oleh ' + count + ' bahan makanan');
            return;
        }
        document.getElementById('deleteMessage').textContent = 'Apakah Anda yakin ingin menghapus kategori "' + name + '"?';
        document.getElementById('deleteBtn').href = '?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
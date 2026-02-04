<?php
/**
 * Master Supplier
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
    query("UPDATE supplier SET is_active = 0 WHERE id = ?", [$id], 'i');
    logActivity($currentUser['id'], 'delete_supplier', "Menghapus supplier ID: $id");
    redirectWith('/gudang-gizi/modules/master/supplier.php', 'Supplier berhasil dihapus', 'success');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nama = sanitize($_POST['nama']);
    $alamat = sanitize($_POST['alamat']);
    $telepon = sanitize($_POST['telepon']);
    $email = sanitize($_POST['email']);
    $kontak_person = sanitize($_POST['kontak_person']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($nama)) {
        $_SESSION['flash_message'] = 'Nama supplier harus diisi';
        $_SESSION['flash_type'] = 'error';
    } else {
        if ($id > 0) {
            query(
                "UPDATE supplier SET nama = ?, alamat = ?, telepon = ?, email = ?, kontak_person = ?, is_active = ? WHERE id = ?",
                [$nama, $alamat, $telepon, $email, $kontak_person, $is_active, $id],
                'sssssii'
            );
            logActivity($currentUser['id'], 'update_supplier', "Mengupdate supplier: $nama");
            redirectWith('/gudang-gizi/modules/master/supplier.php', 'Supplier berhasil diupdate', 'success');
        } else {
            query(
                "INSERT INTO supplier (nama, alamat, telepon, email, kontak_person, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                [$nama, $alamat, $telepon, $email, $kontak_person, $is_active],
                'sssssi'
            );
            logActivity($currentUser['id'], 'create_supplier', "Menambah supplier: $nama");
            redirectWith('/gudang-gizi/modules/master/supplier.php', 'Supplier berhasil ditambahkan', 'success');
        }
    }
}

// Get data
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'active';

$where = "WHERE 1=1";
if ($status === 'active')
    $where .= " AND is_active = 1";
elseif ($status === 'inactive')
    $where .= " AND is_active = 0";

if ($search) {
    $search = escape($search);
    $where .= " AND (nama LIKE '%$search%' OR telepon LIKE '%$search%' OR email LIKE '%$search%')";
}

$suppliers = fetchAll("SELECT s.*, 
                       (SELECT COUNT(*) FROM stok_masuk WHERE supplier_id = s.id) as total_transaksi
                       FROM supplier s $where ORDER BY s.nama ASC");

$editItem = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editItem = fetchOne("SELECT * FROM supplier WHERE id = ?", [(int) $_GET['edit']], 'i');
}
?>

<script>setPageTitle('Supplier', 'Kelola data supplier');</script>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-6 sticky top-24">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-<?= $editItem ? 'edit' : 'plus' ?> text-primary-400"></i>
                <?= $editItem ? 'Edit Supplier' : 'Tambah Supplier' ?>
            </h3>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama Supplier *</label>
                    <input type="text" name="nama" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['nama'] ?? '') ?>" placeholder="Nama perusahaan/toko">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Alamat</label>
                    <textarea name="alamat" rows="2"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        placeholder="Alamat lengkap..."><?= htmlspecialchars($editItem['alamat'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Telepon</label>
                    <input type="text" name="telepon"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['telepon'] ?? '') ?>" placeholder="Nomor telepon">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" name="email"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['email'] ?? '') ?>" placeholder="email@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Kontak Person</label>
                    <input type="text" name="kontak_person"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['kontak_person'] ?? '') ?>" placeholder="Nama kontak">
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" class="sr-only peer" <?= ($editItem['is_active'] ?? true) ? 'checked' : '' ?>>
                        <div
                            class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-500">
                        </div>
                    </label>
                    <span class="text-sm text-gray-300">Status Aktif</span>
                </div>

                <div class="flex gap-2">
                    <?php if ($editItem): ?>
                        <a href="/gudang-gizi/modules/master/supplier.php"
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
        <!-- Filter -->
        <div class="glass rounded-xl p-4 mb-4">
            <form method="GET" class="flex flex-wrap gap-3">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    class="flex-1 min-w-[200px] bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                    placeholder="Cari supplier...">
                <select name="status"
                    class="bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option>
                </select>
                <button type="submit"
                    class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-truck text-primary-400"></i>
                    Daftar Supplier
                </h3>
                <span class="text-sm text-gray-400">
                    <?= count($suppliers) ?> supplier
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-modern">
                    <thead>
                        <tr class="text-left text-sm text-gray-400">
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 hidden md:table-cell">Kontak</th>
                            <th class="px-4 py-3 text-center hidden lg:table-cell">Transaksi</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                                    <i class="fas fa-truck text-4xl mb-3"></i>
                                    <p>Belum ada supplier</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $sup): ?>
                                <tr class="hover:bg-slate-700/30">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-white">
                                                    <?= htmlspecialchars($sup['nama']) ?>
                                                </p>
                                                <?php if ($sup['alamat']): ?>
                                                    <p class="text-xs text-gray-400 truncate max-w-[200px]">
                                                        <?= htmlspecialchars($sup['alamat']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <div class="text-sm">
                                            <?php if ($sup['telepon']): ?>
                                                <p class="text-gray-300"><i class="fas fa-phone text-xs mr-1 text-gray-500"></i>
                                                    <?= htmlspecialchars($sup['telepon']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($sup['kontak_person']): ?>
                                                <p class="text-gray-400 text-xs"><i class="fas fa-user text-xs mr-1"></i>
                                                    <?= htmlspecialchars($sup['kontak_person']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center hidden lg:table-cell">
                                        <span class="text-gray-300">
                                            <?= $sup['total_transaksi'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($sup['is_active']): ?>
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
                                            <a href="?edit=<?= $sup['id'] ?>"
                                                class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button
                                                onclick="confirmDelete(<?= $sup['id'] ?>, '<?= htmlspecialchars($sup['nama']) ?>')"
                                                class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">
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
                <h3 class="text-xl font-bold text-white mb-2">Hapus Supplier?</h3>
                <p class="text-gray-400">Apakah Anda yakin ingin menghapus supplier <span id="deleteName"
                        class="text-white font-medium"></span>?</p>
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
    function confirmDelete(id, name) {
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteBtn').href = '?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
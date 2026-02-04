<?php
/**
 * Master Satuan
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

    // Check if satuan is in use
    $inUse = fetchOne("SELECT COUNT(*) as count FROM bahan_makanan WHERE satuan_id = ?", [$id], 'i');
    if ($inUse['count'] > 0) {
        redirectWith('/gudang-gizi/modules/master/satuan.php', 'Satuan tidak dapat dihapus karena masih digunakan', 'error');
    }

    query("DELETE FROM satuan WHERE id = ?", [$id], 'i');
    logActivity($currentUser['id'], 'delete_satuan', "Menghapus satuan ID: $id");
    redirectWith('/gudang-gizi/modules/master/satuan.php', 'Satuan berhasil dihapus', 'success');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nama = sanitize($_POST['nama']);
    $singkatan = sanitize($_POST['singkatan']);

    if (empty($nama)) {
        $_SESSION['flash_message'] = 'Nama satuan harus diisi';
        $_SESSION['flash_type'] = 'error';
    } elseif (empty($singkatan)) {
        $_SESSION['flash_message'] = 'Singkatan satuan harus diisi';
        $_SESSION['flash_type'] = 'error';
    } else {
        // Check for duplicate singkatan
        $checkQuery = "SELECT id FROM satuan WHERE singkatan = ?";
        $checkParams = [$singkatan];
        $checkTypes = 's';
        
        if ($id > 0) {
            $checkQuery .= " AND id != ?";
            $checkParams[] = $id;
            $checkTypes .= 'i';
        }
        
        $existing = fetchOne($checkQuery, $checkParams, $checkTypes);
        
        if ($existing) {
            $_SESSION['flash_message'] = 'Singkatan satuan sudah digunakan';
            $_SESSION['flash_type'] = 'error';
        } else {
            if ($id > 0) {
                query("UPDATE satuan SET nama = ?, singkatan = ? WHERE id = ?", [$nama, $singkatan, $id], 'ssi');
                logActivity($currentUser['id'], 'update_satuan', "Mengupdate satuan: $nama");
                redirectWith('/gudang-gizi/modules/master/satuan.php', 'Satuan berhasil diupdate', 'success');
            } else {
                query("INSERT INTO satuan (nama, singkatan) VALUES (?, ?)", [$nama, $singkatan], 'ss');
                logActivity($currentUser['id'], 'create_satuan', "Menambah satuan: $nama");
                redirectWith('/gudang-gizi/modules/master/satuan.php', 'Satuan berhasil ditambahkan', 'success');
            }
        }
    }
}

// Get data
$units = fetchAll("SELECT s.*, (SELECT COUNT(*) FROM bahan_makanan WHERE satuan_id = s.id AND is_active = 1) as jumlah_bahan 
                    FROM satuan s ORDER BY s.nama ASC");

$editItem = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editItem = fetchOne("SELECT * FROM satuan WHERE id = ?", [(int) $_GET['edit']], 'i');
}
?>

<script>setPageTitle('Satuan', 'Kelola satuan bahan makanan');</script>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-6 sticky top-24">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-<?= $editItem ? 'edit' : 'plus' ?> text-primary-400"></i>
                <?= $editItem ? 'Edit Satuan' : 'Tambah Satuan' ?>
            </h3>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama Satuan *</label>
                    <input type="text" name="nama" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['nama'] ?? '') ?>" placeholder="Contoh: Kilogram">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Singkatan *</label>
                    <input type="text" name="singkatan" required maxlength="10"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['singkatan'] ?? '') ?>" placeholder="Contoh: kg">
                    <p class="text-xs text-gray-500 mt-1">Maksimal 10 karakter</p>
                </div>

                <div class="flex gap-2">
                    <?php if ($editItem): ?>
                        <a href="/gudang-gizi/modules/master/satuan.php"
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

            <!-- Quick Reference -->
            <div class="mt-6 p-4 bg-slate-700/50 rounded-xl">
                <h4 class="text-sm font-medium text-gray-300 mb-3 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-yellow-400"></i>
                    Contoh Satuan Umum
                </h4>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="flex justify-between text-gray-400">
                        <span>Kilogram</span>
                        <span class="text-primary-400">kg</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Gram</span>
                        <span class="text-primary-400">gr</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Liter</span>
                        <span class="text-primary-400">L</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Mililiter</span>
                        <span class="text-primary-400">ml</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Buah</span>
                        <span class="text-primary-400">bh</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Bungkus</span>
                        <span class="text-primary-400">bks</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="lg:col-span-2">
        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-ruler text-primary-400"></i>
                    Daftar Satuan
                </h3>
                <span class="text-sm text-gray-400">
                    <?= count($units) ?> satuan
                </span>
            </div>

            <div class="divide-y divide-slate-700/50">
                <?php if (empty($units)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-ruler-horizontal text-4xl mb-3"></i>
                        <p>Belum ada satuan</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($units as $unit): ?>
                        <div class="p-4 hover:bg-slate-700/30 transition-colors flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div
                                    class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary-500/20 to-primary-600/20 text-primary-400 flex items-center justify-center flex-shrink-0">
                                    <span class="font-bold text-sm"><?= htmlspecialchars($unit['singkatan']) ?></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-white truncate">
                                        <?= htmlspecialchars($unit['nama']) ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Singkatan: <?= htmlspecialchars($unit['singkatan']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1.5 rounded-lg bg-slate-700 text-gray-300 text-sm whitespace-nowrap">
                                    <i class="fas fa-box text-xs mr-1"></i>
                                    <?= $unit['jumlah_bahan'] ?> bahan
                                </span>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?= $unit['id'] ?>"
                                        class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        onclick="confirmDeleteSatuan(<?= $unit['id'] ?>, '<?= htmlspecialchars($unit['nama']) ?>', <?= $unit['jumlah_bahan'] ?>)"
                                        class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded-lg transition-colors"
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Card -->
        <div class="mt-6 glass rounded-2xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-2">Tentang Satuan</h4>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        Satuan digunakan untuk mengukur jumlah bahan makanan. Setiap bahan makanan harus memiliki satuan yang sesuai
                        agar perhitungan stok menjadi akurat. Pastikan singkatan satuan mudah dipahami dan konsisten.
                    </p>
                </div>
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
                <h3 class="text-xl font-bold text-white mb-2">Hapus Satuan?</h3>
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
    function confirmDeleteSatuan(id, name, count) {
        if (count > 0) {
            alert('Satuan ini tidak dapat dihapus karena masih digunakan oleh ' + count + ' bahan makanan');
            return;
        }
        document.getElementById('deleteMessage').textContent = 'Apakah Anda yakin ingin menghapus satuan "' + name + '"?';
        document.getElementById('deleteBtn').href = '?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

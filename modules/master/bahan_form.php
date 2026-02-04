<?php
/**
 * Bahan Makanan Form (Add/Edit)
 * Gudang Gizi - Sistem Manajemen Stok
 */

// Start session and include only necessary files for processing BEFORE header
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /gudang-gizi/modules/auth/login.php');
    exit;
}

$currentUser = $_SESSION['user'];

// Check permission
if (!hasPermission(['owner', 'admin'])) {
    $_SESSION['flash_message'] = 'Anda tidak memiliki akses ke halaman ini';
    $_SESSION['flash_type'] = 'error';
    header('Location: /gudang-gizi/index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$item = null;
$errors = [];

if ($isEdit) {
    $item = fetchOne("SELECT * FROM bahan_makanan WHERE id = ?", [$id], 'i');
    if (!$item) {
        $_SESSION['flash_message'] = 'Data tidak ditemukan';
        $_SESSION['flash_type'] = 'error';
        header('Location: /gudang-gizi/modules/master/bahan.php');
        exit;
    }
}

// Handle form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $_SESSION['flash_message'] = 'Invalid security token. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: /gudang-gizi/modules/master/bahan.php');
        exit;
    }

    $kode = sanitize($_POST['kode']);
    $nama = sanitize($_POST['nama']);
    $kategori_id = !empty($_POST['kategori_id']) ? (int) $_POST['kategori_id'] : null;
    $satuan_id = !empty($_POST['satuan_id']) ? (int) $_POST['satuan_id'] : null;
    $stok_minimum = (int) $_POST['stok_minimum'];
    $harga_satuan = (float) $_POST['harga_satuan'];
    $lokasi_rak = sanitize($_POST['lokasi_rak']);
    $keterangan = sanitize($_POST['keterangan']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($kode))
        $errors[] = 'Kode bahan harus diisi';
    if (empty($nama))
        $errors[] = 'Nama bahan harus diisi';

    // Check duplicate kode
    $checkKode = fetchOne("SELECT id FROM bahan_makanan WHERE kode = ? AND id != ?", [$kode, $id], 'si');
    if ($checkKode) {
        $errors[] = 'Kode bahan sudah digunakan';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $sql = "UPDATE bahan_makanan SET 
                    kode = ?, nama = ?, kategori_id = ?, satuan_id = ?, 
                    stok_minimum = ?, harga_satuan = ?, lokasi_rak = ?, 
                    keterangan = ?, is_active = ?
                    WHERE id = ?";
            $params = [$kode, $nama, $kategori_id, $satuan_id, $stok_minimum, $harga_satuan, $lokasi_rak, $keterangan, $is_active, $id];
            $types = "ssiidsssii";

            query($sql, $params, $types);
            logActivity($currentUser['id'], 'update_bahan', "Mengupdate bahan: $nama");
            
            $_SESSION['flash_message'] = 'Bahan makanan berhasil diupdate';
            $_SESSION['flash_type'] = 'success';
            header('Location: /gudang-gizi/modules/master/bahan.php');
            exit;
        } else {
            $sql = "INSERT INTO bahan_makanan (kode, nama, kategori_id, satuan_id, stok_minimum, harga_satuan, lokasi_rak, keterangan, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$kode, $nama, $kategori_id, $satuan_id, $stok_minimum, $harga_satuan, $lokasi_rak, $keterangan, $is_active];
            $types = "ssiidsssi";

            $newId = insertGetId($sql, $params, $types);
            logActivity($currentUser['id'], 'create_bahan', "Menambah bahan baru: $nama");
            
            $_SESSION['flash_message'] = 'Bahan makanan berhasil ditambahkan';
            $_SESSION['flash_type'] = 'success';
            header('Location: /gudang-gizi/modules/master/bahan.php');
            exit;
        }
    }
}

// Get categories and units for dropdown
$categories = fetchAll("SELECT * FROM kategori ORDER BY nama ASC");
$units = fetchAll("SELECT * FROM satuan ORDER BY nama ASC");

// Generate code suggestion for new item
$suggestedCode = '';
if (!$isEdit) {
    $lastItem = fetchOne("SELECT kode FROM bahan_makanan ORDER BY id DESC LIMIT 1");
    if ($lastItem) {
        preg_match('/(\D+)(\d+)/', $lastItem['kode'], $matches);
        if ($matches) {
            $prefix = $matches[1];
            $num = (int) $matches[2] + 1;
            $suggestedCode = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
        }
    } else {
        $suggestedCode = 'BHN001';
    }
}

// NOW include header (only for displaying the page)
require_once __DIR__ . '/../../includes/header.php';
?>

<script>setPageTitle('<?= $isEdit ? 'Edit' : 'Tambah' ?> Bahan Makanan', '<?= $isEdit ? 'Ubah data bahan makanan' : 'Tambah data bahan baru' ?>');</script>

<!-- Back Button -->
<a href="/gudang-gizi/modules/master/bahan.php"
    class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors">
    <i class="fas fa-arrow-left"></i>
    Kembali ke Daftar Bahan
</a>

<div class="max-w-2xl">
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div
                class="w-12 h-12 rounded-xl <?= $isEdit ? 'bg-blue-500/20 text-blue-400' : 'gradient-primary text-white' ?> flex items-center justify-center">
                <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">
                    <?= $isEdit ? 'Edit Bahan Makanan' : 'Tambah Bahan Makanan' ?>
                </h2>
                <p class="text-sm text-gray-400">
                    <?= $isEdit ? 'Ubah data bahan: ' . htmlspecialchars($item['nama']) : 'Isi form untuk menambah bahan baru' ?>
                </p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6">
                <ul class="list-disc list-inside text-sm">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <?= csrfField() ?>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Kode Bahan *</label>
                    <input type="text" name="kode" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($item['kode'] ?? $_POST['kode'] ?? $suggestedCode) ?>"
                        placeholder="Contoh: BRS001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama Bahan *</label>
                    <input type="text" name="nama" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($item['nama'] ?? $_POST['nama'] ?? '') ?>"
                        placeholder="Contoh: Beras Premium">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Kategori</label>
                    <select name="kategori_id"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($item['kategori_id'] ?? $_POST['kategori_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Satuan</label>
                    <select name="satuan_id"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500">
                        <option value="">Pilih Satuan</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= $unit['id'] ?>" <?= ($item['satuan_id'] ?? $_POST['satuan_id'] ?? '') == $unit['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($unit['nama']) ?> (
                                <?= $unit['singkatan'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Stok Minimum</label>
                    <input type="number" name="stok_minimum" min="0"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= $item['stok_minimum'] ?? $_POST['stok_minimum'] ?? 10 ?>">
                    <p class="text-xs text-gray-500 mt-1">Batas minimum stok</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Harga Satuan</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">Rp</span>
                        <input type="number" name="harga_satuan" min="0" step="100"
                            class="w-full bg-slate-800 border border-slate-600 rounded-xl pl-12 pr-4 py-3 text-white focus:border-primary-500"
                            value="<?= $item['harga_satuan'] ?? $_POST['harga_satuan'] ?? 0 ?>">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Lokasi Rak</label>
                    <input type="text" name="lokasi_rak"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($item['lokasi_rak'] ?? $_POST['lokasi_rak'] ?? '') ?>"
                        placeholder="Contoh: A-01">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Keterangan</label>
                <textarea name="keterangan" rows="3"
                    class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                    placeholder="Tambahkan keterangan jika perlu..."><?= htmlspecialchars($item['keterangan'] ?? $_POST['keterangan'] ?? '') ?></textarea>
            </div>

            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" class="sr-only peer" <?= ($item['is_active'] ?? true) ? 'checked' : '' ?>>
                    <div
                        class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-500">
                    </div>
                </label>
                <span class="text-sm text-gray-300">Status Aktif</span>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-700">
                <a href="/gudang-gizi/modules/master/bahan.php"
                    class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-center transition-colors">
                    Batal
                </a>
                <button type="submit" class="flex-1 btn-primary px-4 py-3 rounded-xl text-white font-medium">
                    <i class="fas fa-save mr-2"></i>
                    <?= $isEdit ? 'Update' : 'Simpan' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
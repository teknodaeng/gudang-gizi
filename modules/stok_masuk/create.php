<?php
/**
 * Stok Masuk - Create Transaction
 * Gudang Gizi - Sistem Manajemen Stok
 * With Nota/Receipt Upload Support
 */

// Start session and include necessary files BEFORE any output
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

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../../uploads/nota/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get suppliers and bahan for dropdowns
$suppliers = fetchAll("SELECT * FROM supplier WHERE is_active = 1 ORDER BY nama ASC");
$bahanList = fetchAll("SELECT b.*, s.singkatan as satuan_singkatan 
                       FROM bahan_makanan b 
                       LEFT JOIN satuan s ON b.satuan_id = s.id 
                       WHERE b.is_active = 1 
                       ORDER BY b.nama ASC");

// Handle form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate CSRF token first
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }

    $tanggal = $_POST['tanggal'];
    $supplier_id = !empty($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : null;
    $supplier_manual = sanitize($_POST['supplier_manual'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $items = $_POST['items'] ?? [];

    if (empty($tanggal))
        $errors[] = 'Tanggal harus diisi';
    if (empty($items))
        $errors[] = 'Minimal harus ada 1 item';

    // Handle file upload
    $nota_file = null;
    if (!empty($_FILES['nota_file']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file = $_FILES['nota_file'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, WEBP, atau PDF';
            } elseif ($file['size'] > $max_size) {
                $errors[] = 'Ukuran file maksimal 5MB';
            } else {
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'nota_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                $upload_path = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $nota_file = 'uploads/nota/' . $filename;
                } else {
                    $errors[] = 'Gagal mengupload file';
                }
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Error upload file: ' . $file['error'];
        }
    }

    if (empty($errors)) {
        // Generate transaction number
        $no_transaksi = generateNoTransaksi('SM');

        // Calculate total
        $total_nilai = 0;
        foreach ($items as $item) {
            $total_nilai += (float) $item['jumlah'] * (float) $item['harga'];
        }

        // Insert header with nota_file
        $sql = "INSERT INTO stok_masuk (no_transaksi, tanggal, supplier_id, supplier, user_id, total_nilai, catatan, nota_file, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')";
        $stok_masuk_id = insertGetId($sql, [$no_transaksi, $tanggal, $supplier_id, $supplier_manual, $currentUser['id'], $total_nilai, $catatan, $nota_file], 'ssissdss');

        if ($stok_masuk_id) {
            // Insert details and update stock
            foreach ($items as $item) {
                $bahan_id = (int) $item['bahan_id'];
                $jumlah = (int) $item['jumlah'];
                $harga = (float) $item['harga'];
                $subtotal = $jumlah * $harga;
                $kadaluarsa = !empty($item['kadaluarsa']) ? $item['kadaluarsa'] : null;

                // Insert detail
                query("INSERT INTO stok_masuk_detail (stok_masuk_id, bahan_id, jumlah, harga_satuan, subtotal, tanggal_kadaluarsa) 
                       VALUES (?, ?, ?, ?, ?, ?)",
                    [$stok_masuk_id, $bahan_id, $jumlah, $harga, $subtotal, $kadaluarsa],
                    'iiidds'
                );

                // Update stock
                updateStock($bahan_id, $jumlah, 'in');

                // Update harga satuan di master bahan
                query("UPDATE bahan_makanan SET harga_satuan = ? WHERE id = ?", [$harga, $bahan_id], 'di');
            }

            logActivity($currentUser['id'], 'create_stok_masuk', "Menambah stok masuk: $no_transaksi");

            // Redirect BEFORE any HTML output
            $_SESSION['flash_message'] = 'Stok masuk berhasil disimpan';
            $_SESSION['flash_type'] = 'success';
            header('Location: /gudang-gizi/modules/stok_masuk/detail.php?id=' . $stok_masuk_id);
            exit;
        } else {
            $errors[] = 'Gagal menyimpan transaksi';
        }
    }

    if (!empty($errors)) {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
    }
}

// NOW include header (only for displaying the page)
require_once __DIR__ . '/../../includes/header.php';
?>

<script>setPageTitle('Tambah Stok Masuk', 'Input penerimaan stok baru');</script>

<!-- Back Button -->
<a href="/gudang-gizi/modules/stok_masuk/index.php"
    class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors">
    <i class="fas fa-arrow-left"></i>
    Kembali ke Daftar
</a>

<form method="POST" id="stokMasukForm" enctype="multipart/form-data">
    <!-- CSRF Token -->
    <?= csrfField() ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Form Header -->
        <div class="lg:col-span-1">
            <div class="glass rounded-2xl p-6 sticky top-24">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-file-invoice text-green-400"></i>
                    Informasi Transaksi
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal *</label>
                        <input type="date" name="tanggal" required
                            class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                            value="<?= date('Y-m-d') ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Supplier</label>
                        <select name="supplier_id" id="supplierSelect" onchange="toggleManualSupplier()"
                            class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500">
                            <option value="">Pilih Supplier (Opsional)</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= $sup['id'] ?>">
                                    <?= htmlspecialchars($sup['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="manual">-- Input Manual --</option>
                        </select>
                    </div>

                    <div id="manualSupplierDiv" class="hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nama Toko/Supplier</label>
                        <input type="text" name="supplier_manual"
                            class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                            placeholder="Masukkan nama toko/supplier">
                    </div>

                    <!-- Nota Upload Section -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-receipt text-yellow-400 mr-1"></i>
                            Upload Nota/Bukti Belanja
                        </label>
                        <div id="notaDropzone"
                            class="border-2 border-dashed border-slate-600 rounded-xl p-4 text-center hover:border-green-500/50 transition-colors cursor-pointer"
                            onclick="document.getElementById('notaInput').click()">
                            <div id="notaPreview" class="hidden">
                                <img id="notaImage" src="" alt="Preview" class="max-h-32 mx-auto rounded-lg mb-2">
                                <p id="notaFileName" class="text-sm text-gray-400"></p>
                            </div>
                            <div id="notaPlaceholder">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-500 mb-2"></i>
                                <p class="text-sm text-gray-400">Klik atau drag file ke sini</p>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP, PDF (Maks. 5MB)</p>
                            </div>
                        </div>
                        <input type="file" id="notaInput" name="nota_file" accept="image/*,.pdf" class="hidden"
                            onchange="handleNotaUpload(this)">
                        <button type="button" id="removeNota"
                            class="hidden mt-2 text-xs text-red-400 hover:text-red-300" onclick="removeNotaPreview()">
                            <i class="fas fa-times mr-1"></i> Hapus file
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Catatan</label>
                        <textarea name="catatan" rows="3"
                            class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                            placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-6 pt-6 border-t border-slate-700">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-400">Total Item:</span>
                        <span id="totalItems" class="font-bold text-white">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Total Nilai:</span>
                        <span id="totalNilai" class="text-xl font-bold text-green-400">Rp 0</span>
                    </div>
                </div>

                <button type="submit" class="w-full mt-6 btn-primary px-4 py-3 rounded-xl text-white font-medium">
                    <i class="fas fa-save mr-2"></i>
                    Simpan Transaksi
                </button>
            </div>
        </div>

        <!-- Items -->
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-boxes text-green-400"></i>
                        Daftar Bahan
                    </h3>
                    <button type="button" onclick="addItem()"
                        class="px-4 py-2 rounded-xl bg-green-500/20 text-green-400 hover:bg-green-500/30 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Item
                    </button>
                </div>

                <!-- Item Container -->
                <div id="itemsContainer" class="space-y-4">
                    <!-- Items will be added here via JavaScript -->
                </div>

                <div id="emptyState" class="text-center py-12 text-gray-500">
                    <i class="fas fa-box-open text-4xl mb-3"></i>
                    <p>Klik "Tambah Item" untuk menambahkan bahan</p>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Item Template (Hidden) -->
<template id="itemTemplate">
    <div class="item-row bg-slate-800/50 rounded-xl p-4 animate-fade-in" data-index="__INDEX__">
        <div class="grid md:grid-cols-12 gap-4 items-start">
            <div class="md:col-span-4">
                <label class="block text-xs text-gray-400 mb-1">Bahan Makanan *</label>
                <select name="items[__INDEX__][bahan_id]" required onchange="updateHarga(this, __INDEX__)"
                    class="item-bahan w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-primary-500">
                    <option value="">Pilih Bahan</option>
                    <?php foreach ($bahanList as $bahan): ?>
                        <option value="<?= $bahan['id'] ?>" data-harga="<?= $bahan['harga_satuan'] ?>"
                            data-satuan="<?= htmlspecialchars($bahan['satuan_singkatan'] ?? '') ?>">
                            <?= htmlspecialchars($bahan['nama']) ?> (
                            <?= $bahan['kode'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-400 mb-1">Jumlah *</label>
                <div class="relative">
                    <input type="number" name="items[__INDEX__][jumlah]" required min="1"
                        class="item-jumlah w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-primary-500"
                        onchange="calculateSubtotal(__INDEX__)" oninput="calculateSubtotal(__INDEX__)">
                    <span class="item-satuan absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500"></span>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-400 mb-1">Harga Satuan *</label>
                <input type="number" name="items[__INDEX__][harga]" required min="0" step="100"
                    class="item-harga w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-primary-500"
                    onchange="calculateSubtotal(__INDEX__)" oninput="calculateSubtotal(__INDEX__)">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-400 mb-1">Kadaluarsa</label>
                <input type="date" name="items[__INDEX__][kadaluarsa]"
                    class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-primary-500">
            </div>
            <div class="md:col-span-2 flex items-end justify-between">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Subtotal</label>
                    <span class="item-subtotal text-lg font-bold text-green-400">Rp 0</span>
                </div>
                <button type="button" onclick="removeItem(__INDEX__)"
                    class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    let itemIndex = 0;
    const items = [];

    function addItem() {
        const template = document.getElementById('itemTemplate');
        const container = document.getElementById('itemsContainer');
        const emptyState = document.getElementById('emptyState');

        let html = template.innerHTML.replace(/__INDEX__/g, itemIndex);
        container.insertAdjacentHTML('beforeend', html);

        items.push(itemIndex);
        itemIndex++;

        emptyState.classList.add('hidden');
        updateTotals();
    }

    function removeItem(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (row) {
            row.remove();
            const idx = items.indexOf(index);
            if (idx > -1) items.splice(idx, 1);
        }

        if (items.length === 0) {
            document.getElementById('emptyState').classList.remove('hidden');
        }

        updateTotals();
    }

    function updateHarga(select, index) {
        const option = select.options[select.selectedIndex];
        const harga = option.dataset.harga || 0;
        const satuan = option.dataset.satuan || '';

        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (row) {
            row.querySelector('.item-harga').value = harga;
            row.querySelector('.item-satuan').textContent = satuan;
            calculateSubtotal(index);
        }
    }

    function calculateSubtotal(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (!row) return;

        const jumlah = parseFloat(row.querySelector('.item-jumlah').value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
        const subtotal = jumlah * harga;

        row.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);
        updateTotals();
    }

    function updateTotals() {
        let totalItems = 0;
        let totalNilai = 0;

        items.forEach(index => {
            const row = document.querySelector(`.item-row[data-index="${index}"]`);
            if (row) {
                const jumlah = parseFloat(row.querySelector('.item-jumlah').value) || 0;
                const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
                totalItems += jumlah;
                totalNilai += jumlah * harga;
            }
        });

        document.getElementById('totalItems').textContent = totalItems.toLocaleString('id-ID');
        document.getElementById('totalNilai').textContent = formatRupiah(totalNilai);
    }

    function formatRupiah(value) {
        return 'Rp ' + value.toLocaleString('id-ID');
    }

    // Toggle manual supplier input
    function toggleManualSupplier() {
        const select = document.getElementById('supplierSelect');
        const manualDiv = document.getElementById('manualSupplierDiv');

        if (select.value === 'manual') {
            manualDiv.classList.remove('hidden');
            select.name = ''; // Don't submit this field
        } else {
            manualDiv.classList.add('hidden');
            select.name = 'supplier_id';
        }
    }

    // Handle nota file upload
    function handleNotaUpload(input) {
        const file = input.files[0];
        if (!file) return;

        const preview = document.getElementById('notaPreview');
        const placeholder = document.getElementById('notaPlaceholder');
        const image = document.getElementById('notaImage');
        const fileName = document.getElementById('notaFileName');
        const removeBtn = document.getElementById('removeNota');

        // Check file size
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB');
            input.value = '';
            return;
        }

        fileName.textContent = file.name;

        // Show preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                image.src = e.target.result;
                image.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            // PDF icon
            image.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="%23ef4444" d="M320 464c8.8 0 16-7.2 16-16V160H256c-17.7 0-32-14.3-32-32V48H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320zM0 64C0 28.7 28.7 0 64 0H229.5c17 0 33.3 6.7 45.3 18.7l90.5 90.5c12 12 18.7 28.3 18.7 45.3V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64z"/></svg>';
            image.classList.remove('hidden');
        }

        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
    }

    function removeNotaPreview() {
        const input = document.getElementById('notaInput');
        const preview = document.getElementById('notaPreview');
        const placeholder = document.getElementById('notaPlaceholder');
        const image = document.getElementById('notaImage');
        const removeBtn = document.getElementById('removeNota');

        input.value = '';
        image.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
    }

    // Drag and drop support
    const dropzone = document.getElementById('notaDropzone');

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-green-500', 'bg-green-500/10');
    });

    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-green-500', 'bg-green-500/10');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-green-500', 'bg-green-500/10');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const input = document.getElementById('notaInput');
            input.files = files;
            handleNotaUpload(input);
        }
    });

    // Add first item on load
    addItem();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
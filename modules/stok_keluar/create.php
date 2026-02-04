<?php
/**
 * Stok Keluar - Create Transaction
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/../../includes/header.php';

// Get bahan for dropdown
$bahanList = fetchAll("SELECT b.*, s.singkatan as satuan_singkatan 
                       FROM bahan_makanan b 
                       LEFT JOIN satuan s ON b.satuan_id = s.id 
                       WHERE b.is_active = 1 AND b.stok_saat_ini > 0
                       ORDER BY b.nama ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $tanggal = $_POST['tanggal'];
    $tujuan = sanitize($_POST['tujuan'] ?? 'Dapur Utama');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $items = $_POST['items'] ?? [];

    $errors = [];

    if (empty($tanggal))
        $errors[] = 'Tanggal harus diisi';
    if (empty($items))
        $errors[] = 'Minimal harus ada 1 item';

    // Check stock availability
    foreach ($items as $item) {
        $bahan = fetchOne("SELECT nama, stok_saat_ini FROM bahan_makanan WHERE id = ?", [(int) $item['bahan_id']], 'i');
        if ($bahan && (int) $item['jumlah'] > $bahan['stok_saat_ini']) {
            $errors[] = "Stok {$bahan['nama']} tidak mencukupi (tersedia: {$bahan['stok_saat_ini']})";
        }
    }

    if (empty($errors)) {
        // Generate transaction number
        $no_transaksi = generateNoTransaksi('SK');

        // Calculate total
        $total_nilai = 0;
        foreach ($items as $item) {
            $bahan = fetchOne("SELECT harga_satuan FROM bahan_makanan WHERE id = ?", [(int) $item['bahan_id']], 'i');
            $total_nilai += (float) $item['jumlah'] * ($bahan['harga_satuan'] ?? 0);
        }

        // Insert header
        $sql = "INSERT INTO stok_keluar (no_transaksi, tanggal, tujuan, user_id, total_nilai, catatan, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'completed')";
        $stok_keluar_id = insertGetId($sql, [$no_transaksi, $tanggal, $tujuan, $currentUser['id'], $total_nilai, $catatan], 'sssids');

        if ($stok_keluar_id) {
            // Insert details and update stock
            foreach ($items as $item) {
                $bahan_id = (int) $item['bahan_id'];
                $jumlah = (int) $item['jumlah'];

                // Get current price
                $bahan = fetchOne("SELECT harga_satuan FROM bahan_makanan WHERE id = ?", [$bahan_id], 'i');
                $harga = $bahan['harga_satuan'] ?? 0;
                $subtotal = $jumlah * $harga;

                // Insert detail
                query("INSERT INTO stok_keluar_detail (stok_keluar_id, bahan_id, jumlah, harga_satuan, subtotal) 
                       VALUES (?, ?, ?, ?, ?)",
                    [$stok_keluar_id, $bahan_id, $jumlah, $harga, $subtotal],
                    'iiidd'
                );

                // Update stock
                updateStock($bahan_id, $jumlah, 'out');
            }

            logActivity($currentUser['id'], 'create_stok_keluar', "Menambah stok keluar: $no_transaksi");
            redirectWith('/gudang-gizi/modules/stok_keluar/detail.php?id=' . $stok_keluar_id, 'Stok keluar berhasil disimpan', 'success');
        } else {
            $errors[] = 'Gagal menyimpan transaksi';
        }
    }

    if (!empty($errors)) {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
    }
}
?>

<script>setPageTitle('Buat Stok Keluar', 'Pengeluaran stok dari gudang');</script>

<!-- Back Button -->
<a href="/gudang-gizi/modules/stok_keluar/index.php"
    class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors">
    <i class="fas fa-arrow-left"></i>
    Kembali ke Daftar
</a>

<form method="POST" id="stokKeluarForm">
    <?= csrfField() ?>
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Form Header -->
        <div class="lg:col-span-1">
            <div class="glass rounded-2xl p-6 sticky top-24">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-file-invoice text-red-400"></i>
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
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tujuan</label>
                        <input type="text" name="tujuan"
                            class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                            value="Dapur Utama" placeholder="Tujuan penggunaan">
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
                        <span class="text-gray-400">Estimasi Nilai:</span>
                        <span id="totalNilai" class="text-xl font-bold text-red-400">Rp 0</span>
                    </div>
                </div>

                <button type="submit"
                    class="w-full mt-6 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 px-4 py-3 rounded-xl text-white font-medium transition-all">
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
                        <i class="fas fa-boxes text-red-400"></i>
                        Daftar Bahan Keluar
                    </h3>
                    <button type="button" onclick="addItem()"
                        class="px-4 py-2 rounded-xl bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors">
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
            <div class="md:col-span-5">
                <label class="block text-xs text-gray-400 mb-1">Bahan Makanan *</label>
                <select name="items[__INDEX__][bahan_id]" required onchange="updateStokInfo(this, __INDEX__)"
                    class="item-bahan w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-primary-500">
                    <option value="">Pilih Bahan</option>
                    <?php foreach ($bahanList as $bahan): ?>
                        <option value="<?= $bahan['id'] ?>" data-stok="<?= $bahan['stok_saat_ini'] ?>"
                            data-harga="<?= $bahan['harga_satuan'] ?>"
                            data-satuan="<?= htmlspecialchars($bahan['satuan_singkatan'] ?? '') ?>">
                            <?= htmlspecialchars($bahan['nama']) ?> (Stok:
                            <?= $bahan['stok_saat_ini'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs text-gray-400 mb-1">Jumlah *</label>
                <div class="relative">
                    <input type="number" name="items[__INDEX__][jumlah]" required min="1"
                        class="item-jumlah w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:border-primary-500"
                        onchange="calculateSubtotal(__INDEX__)" oninput="calculateSubtotal(__INDEX__)">
                    <span class="item-satuan absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500"></span>
                </div>
                <p class="item-stok-info text-xs text-gray-500 mt-1">Stok tersedia: -</p>
            </div>
            <div class="md:col-span-4 flex items-end justify-between">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Estimasi Nilai</label>
                    <span class="item-subtotal text-lg font-bold text-red-400">Rp 0</span>
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
    const bahanData = {};

    <?php foreach ($bahanList as $bahan): ?>
        bahanData[<?= $bahan['id'] ?>] = {
            stok: <?= $bahan['stok_saat_ini'] ?>,
            harga: <?= $bahan['harga_satuan'] ?>,
            satuan: '<?= $bahan['satuan_singkatan'] ?? '' ?>'
        };
    <?php endforeach; ?>

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

    function updateStokInfo(select, index) {
        const option = select.options[select.selectedIndex];
        const bahan_id = select.value;
        const row = document.querySelector(`.item-row[data-index="${index}"]`);

        if (!row) return;

        if (bahan_id && bahanData[bahan_id]) {
            const data = bahanData[bahan_id];
            row.querySelector('.item-satuan').textContent = data.satuan;
            row.querySelector('.item-stok-info').textContent = `Stok tersedia: ${data.stok} ${data.satuan}`;
            row.querySelector('.item-jumlah').max = data.stok;
        } else {
            row.querySelector('.item-satuan').textContent = '';
            row.querySelector('.item-stok-info').textContent = 'Stok tersedia: -';
            row.querySelector('.item-jumlah').max = '';
        }

        calculateSubtotal(index);
    }

    function calculateSubtotal(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (!row) return;

        const select = row.querySelector('.item-bahan');
        const bahan_id = select.value;
        const jumlah = parseFloat(row.querySelector('.item-jumlah').value) || 0;

        let subtotal = 0;
        if (bahan_id && bahanData[bahan_id]) {
            subtotal = jumlah * bahanData[bahan_id].harga;
        }

        row.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);
        updateTotals();
    }

    function updateTotals() {
        let totalItems = 0;
        let totalNilai = 0;

        items.forEach(index => {
            const row = document.querySelector(`.item-row[data-index="${index}"]`);
            if (row) {
                const select = row.querySelector('.item-bahan');
                const bahan_id = select.value;
                const jumlah = parseFloat(row.querySelector('.item-jumlah').value) || 0;

                totalItems += jumlah;
                if (bahan_id && bahanData[bahan_id]) {
                    totalNilai += jumlah * bahanData[bahan_id].harga;
                }
            }
        });

        document.getElementById('totalItems').textContent = totalItems.toLocaleString('id-ID');
        document.getElementById('totalNilai').textContent = formatRupiah(totalNilai);
    }

    function formatRupiah(value) {
        return 'Rp ' + value.toLocaleString('id-ID');
    }

    // Add first item on load
    addItem();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
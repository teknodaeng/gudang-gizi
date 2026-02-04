<?php
/**
 * Create Produksi - Proses Masak
 * Otomatis mengurangi stok sesuai resep
 * Dengan perhitungan porsi berdasarkan penerima
 */

require_once __DIR__ . '/../../includes/header.php';

$menu_id = (int) ($_GET['menu_id'] ?? 0);

// Get available menus with recipes
$menus = fetchAll("SELECT m.*, 
                   (SELECT COUNT(*) FROM menu_resep WHERE menu_id = m.id) as total_bahan
                   FROM menu m 
                   WHERE m.is_active = 1 
                   AND EXISTS (SELECT 1 FROM menu_resep WHERE menu_id = m.id)
                   ORDER BY m.nama ASC");

// Get active penerima for selection
$penerimaList = fetchAll("SELECT * FROM penerima WHERE is_active = 1 ORDER BY nama ASC");

// If menu_id is provided, get recipe details
$selectedMenu = null;
$resepItems = [];

if ($menu_id) {
    $selectedMenu = fetchOne("SELECT * FROM menu WHERE id = ? AND is_active = 1", [$menu_id], "i");
    if ($selectedMenu) {
        $resepItems = fetchAll("SELECT mr.*, b.nama as bahan_nama, b.kode as bahan_kode, 
                                b.stok_saat_ini, b.harga_satuan, b.stok_minimum,
                                s.singkatan as satuan_singkatan
                                FROM menu_resep mr
                                JOIN bahan_makanan b ON mr.bahan_id = b.id
                                LEFT JOIN satuan s ON b.satuan_id = s.id
                                WHERE mr.menu_id = ?
                                ORDER BY b.nama ASC", [$menu_id], "i");
    }
}

// Category info for display
$kategoriInfo = [
    'balita' => ['icon' => 'fa-baby', 'color' => 'pink', 'label' => 'Balita'],
    'anak' => ['icon' => 'fa-child', 'color' => 'blue', 'label' => 'Anak'],
    'dewasa' => ['icon' => 'fa-user', 'color' => 'green', 'label' => 'Dewasa'],
    'lansia' => ['icon' => 'fa-person-cane', 'color' => 'purple', 'label' => 'Lansia']
];


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF'] . '?menu_id=' . ($_POST['menu_id'] ?? ''));

    $menu_id = (int) $_POST['menu_id'];
    $tanggal = $_POST['tanggal'];
    $jumlah_porsi = (float) $_POST['jumlah_porsi'];
    $catatan = sanitize($_POST['catatan'] ?? '');
    $action = $_POST['submit_action'] ?? 'draft';

    // Get menu and recipe
    $menu = fetchOne("SELECT * FROM menu WHERE id = ?", [$menu_id], "i");
    $resep = fetchAll("SELECT mr.*, b.stok_saat_ini, b.harga_satuan, b.nama as bahan_nama
                       FROM menu_resep mr
                       JOIN bahan_makanan b ON mr.bahan_id = b.id
                       WHERE mr.menu_id = ?", [$menu_id], "i");

    if (!$menu || empty($resep)) {
        redirectWith($_SERVER['PHP_SELF'], 'Menu atau resep tidak valid', 'error');
    }

    // Calculate multiplier based on porsi
    $multiplier = $jumlah_porsi / $menu['porsi_standar'];

    // Check stock availability
    $stockError = false;
    $insufficientItems = [];

    foreach ($resep as $item) {
        $needed = $item['jumlah'] * $multiplier;
        if ($item['stok_saat_ini'] < $needed) {
            $stockError = true;
            $insufficientItems[] = $item['bahan_nama'] . " (butuh: " . number_format($needed, 2) . ", tersedia: " . number_format($item['stok_saat_ini'], 2) . ")";
        }
    }

    if ($stockError && $action === 'complete') {
        redirectWith(
            $_SERVER['PHP_SELF'] . "?menu_id=$menu_id",
            'Stok tidak mencukupi untuk: ' . implode(', ', $insufficientItems),
            'error'
        );
    }

    // Generate no produksi
    $noProduksi = 'PRD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Calculate total cost
    $totalBiaya = 0;
    foreach ($resep as $item) {
        $totalBiaya += ($item['jumlah'] * $multiplier) * $item['harga_satuan'];
    }

    $status = ($action === 'complete') ? 'completed' : 'draft';

    // Insert produksi
    global $conn;
    $conn->begin_transaction();

    try {
        // Insert header
        $produksiId = insertGetId(
            "INSERT INTO produksi (no_produksi, tanggal, menu_id, jumlah_porsi, catatan, total_biaya, status, user_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$noProduksi, $tanggal, $menu_id, $jumlah_porsi, $catatan, $totalBiaya, $status, $currentUser['id']],
            "ssiiidsi"
        );

        // Insert details and reduce stock if completing
        foreach ($resep as $item) {
            $jumlahDibutuhkan = $item['jumlah'] * $multiplier;
            $subtotal = $jumlahDibutuhkan * $item['harga_satuan'];

            query(
                "INSERT INTO produksi_detail (produksi_id, bahan_id, jumlah_dibutuhkan, jumlah_terpakai, harga_satuan, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$produksiId, $item['bahan_id'], $jumlahDibutuhkan, $jumlahDibutuhkan, $item['harga_satuan'], $subtotal],
                "iidddd"
            );

            // Reduce stock if completing
            if ($status === 'completed') {
                updateStock($item['bahan_id'], $jumlahDibutuhkan, 'out');
            }
        }

        $conn->commit();

        $message = ($status === 'completed')
            ? "Produksi selesai! Stok bahan telah dikurangi otomatis."
            : "Draft produksi berhasil disimpan.";

        redirectWith('/gudang-gizi/modules/produksi/detail.php?id=' . $produksiId, $message, 'success');

    } catch (Exception $e) {
        $conn->rollback();
        redirectWith($_SERVER['PHP_SELF'] . "?menu_id=$menu_id", 'Terjadi kesalahan: ' . $e->getMessage(), 'error');
    }
}
?>

<script>setPageTitle('Masak Baru', 'Proses produksi makanan');</script>

<!-- Breadcrumb -->
<div class="mb-6">
    <a href="/gudang-gizi/modules/produksi/index.php" class="text-gray-400 hover:text-white text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Produksi
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Form -->
    <div class="lg:col-span-2">
        <form method="POST" id="produksiForm">
            <?= csrfField() ?>
            <div class="glass rounded-xl overflow-hidden">
                <div class="p-6 border-b border-slate-700 bg-rose-500/10">
                    <h2 class="text-xl font-bold text-rose-400 flex items-center gap-2">
                        <i class="fas fa-fire-burner"></i>
                        Form Produksi / Masak
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Menu Selection -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Pilih Menu *</label>
                        <select name="menu_id" id="menuSelect" required onchange="loadRecipe(this.value)"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white">
                            <option value="">-- Pilih Menu yang akan dimasak --</option>
                            <?php foreach ($menus as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $menu_id == $m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nama']) ?> (<?= $m['total_bahan'] ?> bahan)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($menus)): ?>
                            <p class="text-yellow-400 text-sm mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Tidak ada menu dengan resep. <a href="/gudang-gizi/modules/menu/index.php"
                                    class="underline">Buat menu & resep dulu</a>.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Penerima Selection (Anak/Kelompok) -->
                    <?php if (!empty($penerimaList)): ?>
                        <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm text-gray-400 flex items-center gap-2">
                                    <i class="fas fa-users text-cyan-400"></i>
                                    Pilih Penerima (Opsional)
                                </label>
                                <button type="button" onclick="togglePenerimaSection()"
                                    class="text-xs text-cyan-400 hover:underline">
                                    <span id="penerimaToggleText">Tampilkan</span>
                                </button>
                            </div>

                            <div id="penerimaSection" class="hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto mb-3">
                                    <?php foreach ($penerimaList as $p):
                                        $info = $kategoriInfo[$p['kategori']] ?? ['icon' => 'fa-user', 'color' => 'gray', 'label' => $p['kategori']];
                                        ?>
                                        <label
                                            class="flex items-center gap-2 p-2 rounded-lg bg-slate-700/50 hover:bg-slate-700 cursor-pointer transition-colors">
                                            <input type="checkbox" name="penerima_ids[]" value="<?= $p['id'] ?>"
                                                data-jumlah="<?= $p['jumlah_orang'] ?>" data-faktor="<?= $p['faktor_porsi'] ?>"
                                                onchange="calculateFromPenerima()"
                                                class="w-4 h-4 rounded border-slate-600 text-cyan-500 focus:ring-cyan-500">
                                            <div
                                                class="w-7 h-7 rounded-lg bg-<?= $info['color'] ?>-500/20 text-<?= $info['color'] ?>-400 flex items-center justify-center flex-shrink-0">
                                                <i class="fas <?= $info['icon'] ?> text-xs"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-white truncate"><?= htmlspecialchars($p['nama']) ?></p>
                                                <p class="text-xs text-gray-500">
                                                    <?= $p['jumlah_orang'] ?> org × <?= $p['faktor_porsi'] ?> =
                                                    <span
                                                        class="text-cyan-400"><?= number_format($p['jumlah_orang'] * $p['faktor_porsi'], 1) ?></span>
                                                    porsi
                                                </p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div
                                    class="flex items-center gap-4 p-3 rounded-lg bg-teal-500/10 border border-teal-500/20">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-400">Total dari penerima terpilih:</p>
                                        <p class="text-lg font-bold text-teal-400">
                                            <span id="totalPenerimaOrang">0</span> orang =
                                            <span id="totalPenerimaPorsi">0</span> porsi
                                        </p>
                                    </div>
                                    <button type="button" onclick="applyPenerimaPorsi()"
                                        class="px-3 py-2 rounded-lg bg-teal-500/20 text-teal-400 hover:bg-teal-500/30 text-sm font-medium transition-colors">
                                        <i class="fas fa-arrow-right mr-1"></i> Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-slate-800/30 rounded-lg p-3 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            <a href="/gudang-gizi/modules/penerima/index.php" class="text-cyan-400 hover:underline">Tambah
                                data penerima</a>
                            untuk kalkulasi porsi otomatis
                        </div>
                    <?php endif; ?>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Tanggal Produksi *</label>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Jumlah Porsi *</label>
                            <input type="number" name="jumlah_porsi" id="jumlahPorsi" value="1" min="1" step="0.5"
                                required onchange="updateCalculation()"
                                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white">
                            <?php if ($selectedMenu): ?>
                                <p class="text-xs text-gray-500 mt-1">Resep standar untuk
                                    <?= $selectedMenu['porsi_standar'] ?> porsi
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Catatan</label>
                        <textarea name="catatan" rows="3"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white"
                            placeholder="Catatan tambahan (opsional)"></textarea>
                    </div>
                </div>

                <!-- Recipe Preview -->
                <div id="recipePreview" class="<?= empty($resepItems) ? 'hidden' : '' ?>">
                    <div class="p-4 border-t border-slate-700 bg-slate-800/50">
                        <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-list-check text-orange-400"></i>
                            Bahan yang Dibutuhkan
                        </h3>

                        <div id="recipeTableContainer">
                            <?php if (!empty($resepItems)): ?>
                                <?php include __DIR__ . '/_recipe_table.php'; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 border-t border-slate-700 flex flex-col sm:flex-row gap-3">
                    <button type="submit" name="submit_action" value="draft"
                        class="flex-1 px-6 py-3 rounded-xl bg-slate-700 text-gray-300 hover:bg-slate-600 font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i> Simpan Draft
                    </button>
                    <button type="submit" name="submit_action" value="complete" id="btnComplete"
                        class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white font-medium hover:from-rose-400 hover:to-pink-400 transition-all shadow-lg shadow-rose-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-fire-burner mr-2"></i> Masak & Kurangi Stok
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-4">
        <!-- Info Box -->
        <div class="glass rounded-xl p-6">
            <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-400"></i>
                Informasi
            </h3>
            <div class="space-y-3 text-sm text-gray-400">
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
                    <p><strong class="text-white">Simpan Draft:</strong> Menyimpan tanpa mengurangi stok</p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-fire text-rose-400 mt-0.5"></i>
                    <p><strong class="text-white">Masak & Kurangi Stok:</strong> Stok bahan akan otomatis berkurang
                        sesuai resep</p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-calculator text-orange-400 mt-0.5"></i>
                    <p>Jumlah bahan dihitung berdasarkan <strong class="text-white">porsi standar</strong> dalam resep
                    </p>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="glass rounded-xl p-6" id="summaryBox">
            <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-receipt text-green-400"></i>
                Ringkasan
            </h3>
            <?php if ($selectedMenu):
                $totalBiaya = array_sum(array_map(fn($i) => $i['jumlah'] * $i['harga_satuan'], $resepItems));
                ?>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Menu</span>
                        <span class="text-white font-medium"><?= htmlspecialchars($selectedMenu['nama']) ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Jumlah Bahan</span>
                        <span class="text-white"><?= count($resepItems) ?> jenis</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Porsi Standar</span>
                        <span class="text-white"><?= $selectedMenu['porsi_standar'] ?> porsi</span>
                    </div>
                    <div class="pt-3 border-t border-slate-700">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Estimasi Biaya</span>
                            <span class="text-xl font-bold text-green-400"
                                id="totalCost"><?= formatRupiah($totalBiaya) ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">*per porsi standar</p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm">Pilih menu untuk melihat ringkasan</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function loadRecipe(menuId) {
        if (!menuId) {
            document.getElementById('recipePreview').classList.add('hidden');
            return;
        }
        window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?menu_id=' + menuId;
    }

    function updateCalculation() {
        const porsi = parseInt(document.getElementById('jumlahPorsi').value) || 1;
        const porsiStandar = <?= $selectedMenu['porsi_standar'] ?? 1 ?>;
        const multiplier = porsi / porsiStandar;

        // Update all quantity displays
        document.querySelectorAll('[data-base-qty]').forEach(el => {
            const baseQty = parseFloat(el.dataset.baseQty);
            const newQty = (baseQty * multiplier).toFixed(2);
            el.textContent = newQty;
        });

        // Update total cost
        let totalCost = 0;
        document.querySelectorAll('[data-base-cost]').forEach(el => {
            const baseCost = parseFloat(el.dataset.baseCost);
            totalCost += baseCost * multiplier;
        });

        document.getElementById('totalCost').textContent = 'Rp ' + totalCost.toLocaleString('id-ID');

        // Check stock availability
        let hasError = false;
        document.querySelectorAll('[data-stock-check]').forEach(el => {
            const needed = parseFloat(el.dataset.baseQty) * multiplier;
            const available = parseFloat(el.dataset.stock);

            if (needed > available) {
                el.classList.add('text-red-400');
                el.classList.remove('text-orange-400');
                hasError = true;
            } else {
                el.classList.remove('text-red-400');
                el.classList.add('text-orange-400');
            }
        });

        // Enable/disable complete button
        document.getElementById('btnComplete').disabled = hasError;
    }

    // Toggle penerima section visibility
    function togglePenerimaSection() {
        const section = document.getElementById('penerimaSection');
        const toggleText = document.getElementById('penerimaToggleText');

        if (section.classList.contains('hidden')) {
            section.classList.remove('hidden');
            toggleText.textContent = 'Sembunyikan';
        } else {
            section.classList.add('hidden');
            toggleText.textContent = 'Tampilkan';
        }
    }

    // Calculate total from selected penerima
    function calculateFromPenerima() {
        const checkboxes = document.querySelectorAll('input[name="penerima_ids[]"]:checked');
        let totalOrang = 0;
        let totalPorsi = 0;

        checkboxes.forEach(cb => {
            const jumlah = parseInt(cb.dataset.jumlah) || 0;
            const faktor = parseFloat(cb.dataset.faktor) || 1;
            totalOrang += jumlah;
            totalPorsi += jumlah * faktor;
        });

        document.getElementById('totalPenerimaOrang').textContent = totalOrang;
        document.getElementById('totalPenerimaPorsi').textContent = totalPorsi.toFixed(1);
    }

    // Apply penerima porsi to the main input
    function applyPenerimaPorsi() {
        const totalPorsi = parseFloat(document.getElementById('totalPenerimaPorsi').textContent) || 0;

        if (totalPorsi > 0) {
            document.getElementById('jumlahPorsi').value = Math.ceil(totalPorsi);
            updateCalculation();

            // Show notification
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-teal-500 text-white px-4 py-3 rounded-xl shadow-lg z-50 animate-fade-in';
            toast.innerHTML = '<i class="fas fa-check mr-2"></i> Porsi diterapkan: ' + Math.ceil(totalPorsi) + ' porsi';
            document.body.appendChild(toast);

            setTimeout(() => toast.remove(), 3000);
        }
    }

    <?php if ($selectedMenu): ?>
        document.addEventListener('DOMContentLoaded', updateCalculation);
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
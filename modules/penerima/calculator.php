<?php
/**
 * Kalkulator Porsi - Hitung kebutuhan bahan berdasarkan penerima
 */

require_once __DIR__ . '/../../includes/header.php';

// Get active penerima
$penerimaList = fetchAll("SELECT * FROM penerima WHERE is_active = 1 ORDER BY nama ASC");

// Get menus with recipes
$menuList = fetchAll("SELECT m.*, 
                      (SELECT COUNT(*) FROM menu_resep WHERE menu_id = m.id) as total_bahan
                      FROM menu m 
                      WHERE m.is_active = 1 
                      AND EXISTS (SELECT 1 FROM menu_resep WHERE menu_id = m.id)
                      ORDER BY m.nama ASC");

// Calculate if form submitted
$calculated = false;
$selectedMenu = null;
$selectedPenerima = [];
$totalPorsi = 0;
$bahanNeeded = [];
$estimasiBiaya = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['menu_id'])) {
    $menu_id = (int)($_POST['menu_id'] ?? $_GET['menu_id'] ?? 0);
    $penerima_ids = $_POST['penerima_ids'] ?? [];
    $custom_porsi = isset($_POST['custom_porsi']) ? (float)$_POST['custom_porsi'] : null;
    
    if ($menu_id) {
        $calculated = true;
        $selectedMenu = fetchOne("SELECT * FROM menu WHERE id = ?", [$menu_id], "i");
        
        if ($selectedMenu) {
            // Calculate total porsi from selected penerima
            if (!empty($penerima_ids)) {
                foreach ($penerima_ids as $pid) {
                    $p = fetchOne("SELECT * FROM penerima WHERE id = ?", [(int)$pid], "i");
                    if ($p) {
                        $selectedPenerima[] = $p;
                        $totalPorsi += $p['jumlah_orang'] * $p['faktor_porsi'];
                    }
                }
            }
            
            // Use custom porsi if provided
            if ($custom_porsi !== null && $custom_porsi > 0) {
                $totalPorsi = $custom_porsi;
            }
            
            if ($totalPorsi > 0) {
                // Calculate multiplier based on porsi standar
                $multiplier = $totalPorsi / $selectedMenu['porsi_standar'];
                
                // Get recipe items
                $resepItems = fetchAll("SELECT mr.*, b.nama as bahan_nama, b.kode as bahan_kode, 
                                        b.stok_saat_ini, b.harga_satuan, b.stok_minimum,
                                        s.singkatan as satuan_singkatan
                                        FROM menu_resep mr
                                        JOIN bahan_makanan b ON mr.bahan_id = b.id
                                        LEFT JOIN satuan s ON b.satuan_id = s.id
                                        WHERE mr.menu_id = ?
                                        ORDER BY b.nama ASC", [$menu_id], "i");
                
                foreach ($resepItems as $item) {
                    $jumlahDibutuhkan = $item['jumlah'] * $multiplier;
                    $subtotal = $jumlahDibutuhkan * $item['harga_satuan'];
                    $estimasiBiaya += $subtotal;
                    
                    $bahanNeeded[] = [
                        'bahan_id' => $item['bahan_id'],
                        'bahan_nama' => $item['bahan_nama'],
                        'bahan_kode' => $item['bahan_kode'],
                        'jumlah_resep' => $item['jumlah'],
                        'jumlah_dibutuhkan' => $jumlahDibutuhkan,
                        'stok_saat_ini' => $item['stok_saat_ini'],
                        'satuan' => $item['satuan_singkatan'],
                        'harga_satuan' => $item['harga_satuan'],
                        'subtotal' => $subtotal,
                        'cukup' => $item['stok_saat_ini'] >= $jumlahDibutuhkan
                    ];
                }
            }
        }
    }
}

$kategoriInfo = [
    'balita' => ['icon' => 'fa-baby', 'color' => 'pink'],
    'anak' => ['icon' => 'fa-child', 'color' => 'blue'],
    'dewasa' => ['icon' => 'fa-user', 'color' => 'green'],
    'lansia' => ['icon' => 'fa-person-cane', 'color' => 'purple']
];
?>

<script>setPageTitle('Kalkulator Porsi', 'Hitung kebutuhan bahan');</script>

<!-- Breadcrumb -->
<div class="mb-6">
    <a href="/gudang-gizi/modules/penerima/index.php" class="text-gray-400 hover:text-white text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Penerima
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Calculator Form -->
    <div class="lg:col-span-1">
        <form method="POST" id="calculatorForm">
            <?= csrfField() ?>
            <div class="glass rounded-xl overflow-hidden sticky top-24">
                <div class="p-4 border-b border-slate-700 bg-gradient-to-r from-cyan-500/10 to-teal-500/10">
                    <h2 class="font-bold text-white flex items-center gap-2">
                        <i class="fas fa-calculator text-teal-400"></i>
                        Kalkulator Porsi
                    </h2>
                </div>
                
                <div class="p-4 space-y-4">
                    <!-- Menu Selection -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Pilih Menu *</label>
                        <select name="menu_id" required onchange="this.form.submit()"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                            <option value="">-- Pilih Menu --</option>
                            <?php foreach ($menuList as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($selectedMenu && $selectedMenu['id'] == $m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Penerima Selection -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Pilih Penerima</label>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <?php if (empty($penerimaList)): ?>
                                <p class="text-gray-500 text-sm py-4 text-center">
                                    <i class="fas fa-users-slash mb-2 block text-2xl"></i>
                                    Belum ada penerima.<br>
                                    <a href="/gudang-gizi/modules/penerima/index.php" class="text-cyan-400 underline">Tambah dulu</a>
                                </p>
                            <?php else: ?>
                                <?php foreach ($penerimaList as $p): 
                                    $info = $kategoriInfo[$p['kategori']] ?? ['icon' => 'fa-user', 'color' => 'gray'];
                                    $isSelected = in_array($p['id'], array_column($selectedPenerima, 'id'));
                                ?>
                                    <label class="flex items-center gap-3 p-2 rounded-lg bg-slate-800/50 hover:bg-slate-700/50 cursor-pointer transition-colors">
                                        <input type="checkbox" name="penerima_ids[]" value="<?= $p['id'] ?>" 
                                            <?= $isSelected ? 'checked' : '' ?>
                                            class="w-4 h-4 rounded border-slate-600 text-teal-500 focus:ring-teal-500">
                                        <div class="w-8 h-8 rounded-lg bg-<?= $info['color'] ?>-500/20 text-<?= $info['color'] ?>-400 flex items-center justify-center flex-shrink-0">
                                            <i class="fas <?= $info['icon'] ?> text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-white truncate"><?= htmlspecialchars($p['nama']) ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?= $p['jumlah_orang'] ?> org × <?= $p['faktor_porsi'] ?> = <?= number_format($p['jumlah_orang'] * $p['faktor_porsi'], 1) ?> porsi
                                            </p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Or Custom Porsi -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-700"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="px-2 bg-slate-800 text-gray-500">ATAU</span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Input Manual Porsi</label>
                        <input type="number" name="custom_porsi" step="0.5" min="0.5" 
                            value="<?= isset($_POST['custom_porsi']) ? $_POST['custom_porsi'] : '' ?>"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                            placeholder="Contoh: 50">
                    </div>
                    
                    <button type="submit" 
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-teal-500 text-white font-medium hover:from-cyan-400 hover:to-teal-400 transition-all">
                        <i class="fas fa-calculator mr-2"></i> Hitung Kebutuhan
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Results -->
    <div class="lg:col-span-2">
        <?php if (!$calculated): ?>
            <div class="glass rounded-xl p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-slate-700 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calculator text-3xl text-gray-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">Kalkulator Porsi</h3>
                <p class="text-gray-400 max-w-md mx-auto">
                    Pilih menu dan penerima untuk menghitung kebutuhan bahan makanan. 
                    Sistem akan otomatis menyesuaikan jumlah berdasarkan faktor porsi setiap kategori penerima.
                </p>
            </div>
        <?php elseif (!$selectedMenu): ?>
            <div class="glass rounded-xl p-12 text-center">
                <i class="fas fa-exclamation-circle text-4xl text-yellow-400 mb-4"></i>
                <p class="text-gray-400">Menu tidak ditemukan</p>
            </div>
        <?php elseif ($totalPorsi <= 0): ?>
            <div class="glass rounded-xl p-12 text-center">
                <i class="fas fa-users text-4xl text-gray-500 mb-4"></i>
                <p class="text-gray-400">Pilih penerima atau masukkan jumlah porsi manual</p>
            </div>
        <?php else: ?>
            <!-- Summary -->
            <div class="glass rounded-xl p-6 mb-6">
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="text-center p-4 rounded-xl bg-orange-500/10 border border-orange-500/20">
                        <p class="text-sm text-gray-400">Menu</p>
                        <p class="text-lg font-bold text-orange-400"><?= htmlspecialchars($selectedMenu['nama']) ?></p>
                        <p class="text-xs text-gray-500"><?= $selectedMenu['porsi_standar'] ?> porsi standar</p>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-teal-500/10 border border-teal-500/20">
                        <p class="text-sm text-gray-400">Total Porsi</p>
                        <p class="text-3xl font-bold text-teal-400"><?= number_format($totalPorsi, 1) ?></p>
                        <p class="text-xs text-gray-500"><?= count($selectedPenerima) ?> kelompok penerima</p>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-green-500/10 border border-green-500/20">
                        <p class="text-sm text-gray-400">Estimasi Biaya</p>
                        <p class="text-xl font-bold text-green-400"><?= formatRupiah($estimasiBiaya) ?></p>
                        <p class="text-xs text-gray-500"><?= formatRupiah($estimasiBiaya / $totalPorsi) ?>/porsi</p>
                    </div>
                </div>
            </div>
            
            <!-- Selected Penerima -->
            <?php if (!empty($selectedPenerima)): ?>
                <div class="glass rounded-xl p-4 mb-6">
                    <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-users text-cyan-400"></i>
                        Penerima Terpilih
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($selectedPenerima as $p): 
                            $info = $kategoriInfo[$p['kategori']] ?? ['icon' => 'fa-user', 'color' => 'gray'];
                        ?>
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-700/50">
                                <i class="fas <?= $info['icon'] ?> text-<?= $info['color'] ?>-400 text-sm"></i>
                                <span class="text-white text-sm"><?= htmlspecialchars($p['nama']) ?></span>
                                <span class="text-gray-500 text-xs">(<?= $p['jumlah_orang'] ?> × <?= $p['faktor_porsi'] ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Bahan Needed -->
            <div class="glass rounded-xl overflow-hidden">
                <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-list-check text-blue-400"></i>
                        Kebutuhan Bahan
                    </h3>
                    <a href="/gudang-gizi/modules/produksi/create.php?menu_id=<?= $selectedMenu['id'] ?>" 
                       class="px-3 py-1 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 text-sm font-medium transition-colors">
                        <i class="fas fa-fire-burner mr-1"></i> Proses Masak
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-sm text-gray-400 text-left border-b border-slate-700">
                                <th class="px-4 py-3">Bahan</th>
                                <th class="px-4 py-3 text-center">Resep/Porsi</th>
                                <th class="px-4 py-3 text-center">Dibutuhkan</th>
                                <th class="px-4 py-3 text-center">Stok</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <?php foreach ($bahanNeeded as $item): ?>
                                <tr class="hover:bg-slate-700/30 <?= !$item['cukup'] ? 'bg-red-500/5' : '' ?>">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-white"><?= htmlspecialchars($item['bahan_nama']) ?></p>
                                        <p class="text-xs text-gray-500 font-mono"><?= $item['bahan_kode'] ?></p>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-400">
                                        <?= number_format($item['jumlah_resep'], 2) ?> <?= $item['satuan'] ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-lg font-bold text-orange-400">
                                            <?= number_format($item['jumlah_dibutuhkan'], 2) ?>
                                        </span>
                                        <span class="text-gray-500"><?= $item['satuan'] ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="<?= $item['cukup'] ? 'text-green-400' : 'text-red-400' ?>">
                                            <?= number_format($item['stok_saat_ini'], 2) ?> <?= $item['satuan'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-400 font-medium">
                                        <?= formatRupiah($item['subtotal']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($item['cukup']): ?>
                                            <span class="px-2 py-1 rounded-lg text-xs font-medium bg-green-500/20 text-green-400">
                                                <i class="fas fa-check mr-1"></i> Cukup
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-lg text-xs font-medium bg-red-500/20 text-red-400">
                                                <i class="fas fa-times mr-1"></i> Kurang <?= number_format($item['jumlah_dibutuhkan'] - $item['stok_saat_ini'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-600 bg-slate-800/50">
                                <td colspan="4" class="px-4 py-4 text-right font-semibold text-white">
                                    Total Estimasi Biaya:
                                </td>
                                <td class="px-4 py-4 text-right text-xl font-bold text-green-400">
                                    <?= formatRupiah($estimasiBiaya) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Print Button -->
            <div class="mt-4 flex justify-end gap-2">
                <button onclick="window.print()" 
                    class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white flex items-center gap-2">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

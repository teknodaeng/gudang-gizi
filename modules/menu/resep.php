<?php
/**
 * Manajemen Resep (Komposisi Bahan per Menu)
 */

require_once __DIR__ . '/../../includes/header.php';

// Check permission
if (!hasPermission(['owner', 'admin'])) {
    redirectWith('/gudang-gizi/index.php', 'Anda tidak memiliki akses ke halaman ini', 'error');
}

$menu_id = (int)($_GET['id'] ?? 0);

if (!$menu_id) {
    redirectWith('/gudang-gizi/modules/menu/index.php', 'Menu tidak ditemukan', 'error');
}

// Get menu data
$menu = fetchOne("SELECT * FROM menu WHERE id = ?", [$menu_id], "i");

if (!$menu) {
    redirectWith('/gudang-gizi/modules/menu/index.php', 'Menu tidak ditemukan', 'error');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF'] . "?id=$menu_id");
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $bahan_id = (int)$_POST['bahan_id'];
        $jumlah = (float)$_POST['jumlah'];
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        
        // Check if already exists
        $existing = fetchOne("SELECT id FROM menu_resep WHERE menu_id = ? AND bahan_id = ?", [$menu_id, $bahan_id], "ii");
        
        if ($existing) {
            redirectWith($_SERVER['PHP_SELF'] . "?id=$menu_id", 'Bahan sudah ada dalam resep. Silakan edit atau hapusnya terlebih dahulu.', 'error');
        } else {
            $result = query(
                "INSERT INTO menu_resep (menu_id, bahan_id, jumlah, keterangan) VALUES (?, ?, ?, ?)",
                [$menu_id, $bahan_id, $jumlah, $keterangan],
                "iids"
            );
            if ($result) {
                redirectWith($_SERVER['PHP_SELF'] . "?id=$menu_id", 'Bahan berhasil ditambahkan ke resep!', 'success');
            }
        }
    }
    
    if ($action === 'edit') {
        $resep_id = (int)$_POST['resep_id'];
        $jumlah = (float)$_POST['jumlah'];
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        
        query("UPDATE menu_resep SET jumlah = ?, keterangan = ? WHERE id = ?", [$jumlah, $keterangan, $resep_id], "dsi");
        redirectWith($_SERVER['PHP_SELF'] . "?id=$menu_id", 'Bahan berhasil diperbarui!', 'success');
    }
    
    if ($action === 'delete') {
        $resep_id = (int)$_POST['resep_id'];
        query("DELETE FROM menu_resep WHERE id = ?", [$resep_id], "i");
        redirectWith($_SERVER['PHP_SELF'] . "?id=$menu_id", 'Bahan berhasil dihapus dari resep!', 'success');
    }
}

// Get resep items
$resepItems = fetchAll("SELECT mr.*, b.nama as bahan_nama, b.kode as bahan_kode, b.stok_saat_ini, b.harga_satuan,
                        s.nama as satuan_nama, s.singkatan as satuan_singkatan,
                        (mr.jumlah * b.harga_satuan) as subtotal
                        FROM menu_resep mr
                        JOIN bahan_makanan b ON mr.bahan_id = b.id
                        LEFT JOIN satuan s ON b.satuan_id = s.id
                        WHERE mr.menu_id = ?
                        ORDER BY b.nama ASC", [$menu_id], "i");

// Calculate total
$totalBiaya = array_sum(array_column($resepItems, 'subtotal'));

// Get available bahan for adding
$availableBahan = fetchAll("SELECT b.*, s.singkatan as satuan_singkatan 
                            FROM bahan_makanan b 
                            LEFT JOIN satuan s ON b.satuan_id = s.id 
                            WHERE b.is_active = 1 
                            AND b.id NOT IN (SELECT bahan_id FROM menu_resep WHERE menu_id = ?)
                            ORDER BY b.nama ASC", [$menu_id], "i");

$kategoriList = [
    'makanan_utama' => 'Makanan Utama',
    'lauk' => 'Lauk',
    'sayur' => 'Sayur',
    'minuman' => 'Minuman',
    'snack' => 'Snack'
];
?>

<script>setPageTitle('Resep: <?= htmlspecialchars($menu['nama']) ?>', 'Kelola komposisi bahan');</script>

<!-- Breadcrumb -->
<div class="mb-6">
    <a href="/gudang-gizi/modules/menu/index.php" class="text-gray-400 hover:text-white text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Menu
    </a>
</div>

<!-- Menu Header -->
<div class="glass rounded-xl p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                <?php 
                $icon = match($menu['kategori']) {
                    'makanan_utama' => 'fa-bowl-rice',
                    'lauk' => 'fa-drumstick-bite',
                    'sayur' => 'fa-leaf',
                    'minuman' => 'fa-mug-hot',
                    'snack' => 'fa-cookie',
                    default => 'fa-utensils'
                };
                ?>
                <i class="fas <?= $icon ?> text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($menu['nama']) ?></h1>
                <p class="text-gray-400">
                    <span class="font-mono text-sm"><?= $menu['kode'] ?></span> • 
                    <?= $kategoriList[$menu['kategori']] ?? $menu['kategori'] ?> • 
                    <?= $menu['porsi_standar'] ?> porsi standar
                </p>
            </div>
        </div>
        
        <div class="flex gap-3">
            <div class="text-center px-4 py-2 rounded-xl bg-blue-500/20 border border-blue-500/30">
                <p class="text-2xl font-bold text-blue-400"><?= count($resepItems) ?></p>
                <p class="text-xs text-gray-400">Bahan</p>
            </div>
            <div class="text-center px-4 py-2 rounded-xl bg-green-500/20 border border-green-500/30">
                <p class="text-lg font-bold text-green-400"><?= formatRupiah($totalBiaya) ?></p>
                <p class="text-xs text-gray-400">Est. Biaya/Porsi</p>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Resep List -->
    <div class="lg:col-span-2">
        <div class="glass rounded-xl overflow-hidden">
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-list-check text-orange-400"></i>
                    Komposisi Bahan
                </h3>
                <span class="text-sm text-gray-400"><?= count($resepItems) ?> bahan</span>
            </div>
            
            <?php if (empty($resepItems)): ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-clipboard-list text-5xl mb-4"></i>
                    <p>Belum ada bahan dalam resep ini.</p>
                    <p class="text-sm mt-2">Tambahkan bahan melalui form di samping.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-700/50">
                    <?php foreach ($resepItems as $item): 
                        $stokCukup = $item['stok_saat_ini'] >= $item['jumlah'];
                    ?>
                        <div class="p-4 hover:bg-slate-700/30 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-slate-700 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-box text-gray-400"></i>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-white"><?= htmlspecialchars($item['bahan_nama']) ?></h4>
                                        <span class="text-xs font-mono text-gray-500"><?= $item['bahan_kode'] ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-sm">
                                        <span class="text-orange-400 font-medium">
                                            <?= number_format($item['jumlah'], 2) ?> <?= $item['satuan_singkatan'] ?>
                                        </span>
                                        <span class="text-gray-500">×</span>
                                        <span class="text-gray-400"><?= formatRupiah($item['harga_satuan']) ?></span>
                                        <span class="text-gray-500">=</span>
                                        <span class="text-green-400 font-medium"><?= formatRupiah($item['subtotal']) ?></span>
                                    </div>
                                    <?php if ($item['keterangan']): ?>
                                        <p class="text-xs text-gray-500 mt-1 italic"><?= htmlspecialchars($item['keterangan']) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 mb-1">Stok</p>
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium <?= $stokCukup ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>">
                                        <?= number_format($item['stok_saat_ini']) ?> <?= $item['satuan_singkatan'] ?>
                                    </span>
                                </div>
                                
                                <div class="flex gap-1">
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                                        class="p-2 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Hapus bahan ini dari resep?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="resep_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="p-2 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Total -->
                <div class="p-4 bg-slate-800/50 border-t border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-400">Total Biaya per Porsi Standar:</span>
                        <span class="text-xl font-bold text-green-400"><?= formatRupiah($totalBiaya) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Bahan Form -->
    <div class="lg:col-span-1">
        <div class="glass rounded-xl overflow-hidden sticky top-24">
            <div class="p-4 border-b border-slate-700 bg-orange-500/10">
                <h3 class="font-semibold text-orange-400 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    Tambah Bahan
                </h3>
            </div>
            
            <?php if (empty($availableBahan)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                    <p>Semua bahan sudah ditambahkan!</p>
                </div>
            <?php else: ?>
                <form method="POST" class="p-4 space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Pilih Bahan *</label>
                        <select name="bahan_id" required 
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                            <option value="">-- Pilih Bahan --</option>
                            <?php foreach ($availableBahan as $bahan): ?>
                                <option value="<?= $bahan['id'] ?>" data-satuan="<?= $bahan['satuan_singkatan'] ?>">
                                    <?= htmlspecialchars($bahan['nama']) ?> (<?= $bahan['satuan_singkatan'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Jumlah per Porsi Standar *</label>
                        <input type="number" name="jumlah" step="0.001" min="0.001" required
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                            placeholder="Masukkan jumlah">
                    </div>
                    
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Keterangan</label>
                        <input type="text" name="keterangan"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                            placeholder="Contoh: dicincang halus">
                    </div>
                    
                    <button type="submit" 
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-medium hover:from-orange-400 hover:to-amber-400 transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah ke Resep
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass rounded-xl p-4 mt-4">
            <h4 class="font-medium text-white mb-3">Aksi Cepat</h4>
            <div class="space-y-2">
                <a href="/gudang-gizi/modules/produksi/create.php?menu_id=<?= $menu_id ?>" 
                   class="block w-full py-3 px-4 rounded-xl bg-green-500/20 text-green-400 hover:bg-green-500/30 text-center font-medium transition-colors">
                    <i class="fas fa-fire-burner mr-2"></i> Masak Menu Ini
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-slate-800 rounded-2xl w-full max-w-md shadow-2xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h3 class="text-xl font-bold text-white">Edit Bahan</h3>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="resep_id" id="editResepId">
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Bahan</label>
                    <input type="text" id="editBahanNama" readonly
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-gray-400">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Jumlah per Porsi Standar *</label>
                    <input type="number" name="jumlah" id="editJumlah" step="0.001" min="0.001" required
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Keterangan</label>
                    <input type="text" name="keterangan" id="editKeterangan"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                </div>
            </div>
            
            <div class="p-6 border-t border-slate-700 flex gap-3">
                <button type="button" onclick="closeEditModal()" 
                    class="flex-1 px-4 py-2 rounded-xl bg-slate-700 text-gray-300 hover:bg-slate-600 transition-colors">
                    Batal
                </button>
                <button type="submit" 
                    class="flex-1 px-4 py-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-medium">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(item) {
    document.getElementById('editResepId').value = item.id;
    document.getElementById('editBahanNama').value = item.bahan_nama;
    document.getElementById('editJumlah').value = item.jumlah;
    document.getElementById('editKeterangan').value = item.keterangan || '';
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
/**
 * Detail Produksi
 */

require_once __DIR__ . '/../../includes/header.php';

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    redirectWith('/gudang-gizi/modules/produksi/index.php', 'Produksi tidak ditemukan', 'error');
}

// Get produksi data
$produksi = fetchOne("SELECT p.*, m.nama as menu_nama, m.kode as menu_kode, m.porsi_standar,
                      u.nama_lengkap as user_nama
                      FROM produksi p
                      JOIN menu m ON p.menu_id = m.id
                      LEFT JOIN users u ON p.user_id = u.id
                      WHERE p.id = ?", [$id], "i");

if (!$produksi) {
    redirectWith('/gudang-gizi/modules/produksi/index.php', 'Produksi tidak ditemukan', 'error');
}

// Get detail items
$detailItems = fetchAll("SELECT pd.*, b.nama as bahan_nama, b.kode as bahan_kode,
                         s.singkatan as satuan_singkatan
                         FROM produksi_detail pd
                         JOIN bahan_makanan b ON pd.bahan_id = b.id
                         LEFT JOIN satuan s ON b.satuan_id = s.id
                         WHERE pd.produksi_id = ?
                         ORDER BY b.nama ASC", [$id], "i");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF'] . "?id=$id");

    $action = $_POST['action'] ?? '';

    if ($action === 'complete' && $produksi['status'] === 'draft') {
        // Check stock availability
        $stockError = false;
        foreach ($detailItems as $item) {
            $bahan = fetchOne("SELECT stok_saat_ini FROM bahan_makanan WHERE id = ?", [$item['bahan_id']], "i");
            if ($bahan['stok_saat_ini'] < $item['jumlah_terpakai']) {
                $stockError = true;
                break;
            }
        }

        if ($stockError) {
            redirectWith($_SERVER['PHP_SELF'] . "?id=$id", 'Stok tidak mencukupi untuk menyelesaikan produksi', 'error');
        }

        // Reduce stock and update status
        global $conn;
        $conn->begin_transaction();

        try {
            foreach ($detailItems as $item) {
                updateStock($item['bahan_id'], $item['jumlah_terpakai'], 'out');
            }

            query("UPDATE produksi SET status = 'completed' WHERE id = ?", [$id], "i");

            $conn->commit();
            redirectWith($_SERVER['PHP_SELF'] . "?id=$id", 'Produksi selesai! Stok telah dikurangi.', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            redirectWith($_SERVER['PHP_SELF'] . "?id=$id", 'Terjadi kesalahan', 'error');
        }
    }

    if ($action === 'cancel' && $produksi['status'] === 'draft') {
        query("UPDATE produksi SET status = 'cancelled' WHERE id = ?", [$id], "i");
        redirectWith($_SERVER['PHP_SELF'] . "?id=$id", 'Produksi dibatalkan', 'success');
    }
}

$statusClass = match ($produksi['status']) {
    'completed' => 'bg-green-500/20 text-green-400 border-green-500/30',
    'cancelled' => 'bg-red-500/20 text-red-400 border-red-500/30',
    default => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
};
$statusLabel = match ($produksi['status']) {
    'completed' => 'Selesai',
    'cancelled' => 'Batal',
    default => 'Draft'
};
?>

<script>setPageTitle('Detail Produksi', '<?= $produksi['no_produksi'] ?>');</script>

<!-- Breadcrumb -->
<div class="mb-6 flex items-center justify-between">
    <a href="/gudang-gizi/modules/produksi/index.php" class="text-gray-400 hover:text-white text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
    <button onclick="window.print()" class="text-gray-400 hover:text-white text-sm">
        <i class="fas fa-print mr-2"></i> Cetak
    </button>
</div>

<!-- Header Card -->
<div class="glass rounded-xl p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium border <?= $statusClass ?>">
                    <?= $statusLabel ?>
                </span>
                <h1 class="text-2xl font-bold text-white font-mono">
                    <?= $produksi['no_produksi'] ?>
                </h1>
            </div>
            <p class="text-gray-400">
                <i class="fas fa-calendar mr-2"></i>
                <?= formatTanggal($produksi['tanggal']) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-user mr-2"></i>
                <?= htmlspecialchars($produksi['user_nama']) ?>
            </p>
        </div>

        <?php if ($produksi['status'] === 'draft'): ?>
            <div class="flex gap-2">
                <form method="POST" class="inline" onsubmit="return confirm('Batalkan produksi ini?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit"
                        class="px-4 py-2 rounded-xl bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors">
                        <i class="fas fa-times mr-2"></i> Batalkan
                    </button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('Selesaikan produksi dan kurangi stok?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="complete">
                    <button type="submit"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-400 hover:to-emerald-400 transition-all shadow-lg">
                        <i class="fas fa-check mr-2"></i> Selesaikan & Kurangi Stok
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2">
        <!-- Menu Info -->
        <div class="glass rounded-xl p-6 mb-6">
            <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-utensils text-orange-400"></i>
                Menu yang Diproduksi
            </h3>
            <div class="flex items-center gap-4">
                <div
                    class="w-16 h-16 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                    <i class="fas fa-bowl-rice text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-xl font-bold text-white">
                        <?= htmlspecialchars($produksi['menu_nama']) ?>
                    </h4>
                    <p class="text-gray-400">
                        <span class="font-mono text-sm">
                            <?= $produksi['menu_kode'] ?>
                        </span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-rose-400">
                        <?= $produksi['jumlah_porsi'] ?>
                    </p>
                    <p class="text-gray-500 text-sm">porsi</p>
                </div>
            </div>
        </div>

        <!-- Detail Items -->
        <div class="glass rounded-xl overflow-hidden">
            <div class="p-4 border-b border-slate-700">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-list-check text-blue-400"></i>
                    Bahan yang Digunakan
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-sm text-gray-400 text-left border-b border-slate-700">
                            <th class="px-4 py-3">Bahan</th>
                            <th class="px-4 py-3 text-center">Jumlah</th>
                            <th class="px-4 py-3 text-right">Harga Satuan</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($detailItems as $item): ?>
                            <tr class="hover:bg-slate-700/30">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-white">
                                        <?= htmlspecialchars($item['bahan_nama']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 font-mono">
                                        <?= $item['bahan_kode'] ?>
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-orange-400 font-medium">
                                        <?= number_format($item['jumlah_terpakai'], 2) ?>
                                    </span>
                                    <span class="text-gray-500">
                                        <?= $item['satuan_singkatan'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400">
                                    <?= formatRupiah($item['harga_satuan']) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-green-400 font-medium">
                                    <?= formatRupiah($item['subtotal']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-600 bg-slate-800/50">
                            <td colspan="3" class="px-4 py-4 text-right font-semibold text-white">
                                Total Biaya Produksi:
                            </td>
                            <td class="px-4 py-4 text-right text-xl font-bold text-green-400">
                                <?= formatRupiah($produksi['total_biaya']) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-4">
        <!-- Summary -->
        <div class="glass rounded-xl p-6">
            <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-receipt text-green-400"></i>
                Ringkasan
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Tanggal</span>
                    <span class="text-white">
                        <?= formatTanggal($produksi['tanggal']) ?>
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Jumlah Porsi</span>
                    <span class="text-white">
                        <?= $produksi['jumlah_porsi'] ?> porsi
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Jenis Bahan</span>
                    <span class="text-white">
                        <?= count($detailItems) ?> jenis
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Biaya per Porsi</span>
                    <span class="text-white">
                        <?= formatRupiah($produksi['total_biaya'] / $produksi['jumlah_porsi']) ?>
                    </span>
                </div>
                <div class="pt-3 border-t border-slate-700">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Biaya</span>
                        <span class="text-xl font-bold text-green-400">
                            <?= formatRupiah($produksi['total_biaya']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan -->
        <?php if ($produksi['catatan']): ?>
            <div class="glass rounded-xl p-6">
                <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
                    <i class="fas fa-sticky-note text-yellow-400"></i>
                    Catatan
                </h3>
                <p class="text-gray-400">
                    <?= nl2br(htmlspecialchars($produksi['catatan'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="glass rounded-xl p-6">
            <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-clock text-blue-400"></i>
                Timeline
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex gap-3">
                    <div
                        class="w-6 h-6 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-plus text-xs"></i>
                    </div>
                    <div>
                        <p class="text-white">Dibuat</p>
                        <p class="text-gray-500">
                            <?= formatTanggal($produksi['created_at'], true) ?>
                        </p>
                    </div>
                </div>
                <?php if ($produksi['status'] === 'completed'): ?>
                    <div class="flex gap-3">
                        <div
                            class="w-6 h-6 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                        <div>
                            <p class="text-white">Selesai</p>
                            <p class="text-gray-500">
                                <?= formatTanggal($produksi['updated_at'], true) ?>
                            </p>
                        </div>
                    </div>
                <?php elseif ($produksi['status'] === 'cancelled'): ?>
                    <div class="flex gap-3">
                        <div
                            class="w-6 h-6 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-times text-xs"></i>
                        </div>
                        <div>
                            <p class="text-white">Dibatalkan</p>
                            <p class="text-gray-500">
                                <?= formatTanggal($produksi['updated_at'], true) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
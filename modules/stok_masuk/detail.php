<?php
/**
 * Stok Masuk - Detail View
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/../../includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get transaction data
$transaksi = fetchOne("SELECT sm.*, s.nama as supplier_nama, s.alamat as supplier_alamat, 
                        s.telepon as supplier_telepon, u.nama_lengkap as user_nama
                       FROM stok_masuk sm
                       LEFT JOIN supplier s ON sm.supplier_id = s.id
                       LEFT JOIN users u ON sm.user_id = u.id
                       WHERE sm.id = ?", [$id], 'i');

if (!$transaksi) {
    redirectWith('/gudang-gizi/modules/stok_masuk/index.php', 'Transaksi tidak ditemukan', 'error');
}

// Get detail items
$items = fetchAll("SELECT smd.*, b.nama as bahan_nama, b.kode as bahan_kode, 
                   st.singkatan as satuan_singkatan
                   FROM stok_masuk_detail smd
                   LEFT JOIN bahan_makanan b ON smd.bahan_id = b.id
                   LEFT JOIN satuan st ON b.satuan_id = st.id
                   WHERE smd.stok_masuk_id = ?", [$id], 'i');
?>

<script>setPageTitle('Detail Stok Masuk', '<?= htmlspecialchars($transaksi['no_transaksi']) ?>');</script>

<!-- Back Button & Actions -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <a href="/gudang-gizi/modules/stok_masuk/index.php"
        class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Daftar
    </a>

    <div class="flex items-center gap-2">
        <a href="/gudang-gizi/modules/stok_masuk/print.php?id=<?= $id ?>" target="_blank"
            class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white transition-colors flex items-center gap-2">
            <i class="fas fa-print"></i>
            Cetak
        </a>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Transaction Info -->
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice text-green-400"></i>
                Informasi Transaksi
            </h3>

            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-400">No. Transaksi</p>
                    <p class="font-mono text-lg font-bold text-primary-400">
                        <?= htmlspecialchars($transaksi['no_transaksi']) ?>
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Tanggal</p>
                    <p class="text-white">
                        <?= formatTanggal($transaksi['tanggal']) ?>
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Status</p>
                    <?php
                    $statusClass = match ($transaksi['status']) {
                        'completed' => 'bg-green-500/20 text-green-400',
                        'draft' => 'bg-yellow-500/20 text-yellow-400',
                        'cancelled' => 'bg-red-500/20 text-red-400',
                        default => 'bg-gray-500/20 text-gray-400'
                    };
                    ?>
                    <span class="inline-block px-3 py-1 rounded-lg text-sm font-medium <?= $statusClass ?>">
                        <?= ucfirst($transaksi['status']) ?>
                    </span>
                </div>

                <?php if ($transaksi['supplier_nama']): ?>
                    <div class="pt-4 border-t border-slate-700">
                        <p class="text-sm text-gray-400 mb-2">Supplier</p>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <p class="font-medium text-white">
                                    <?= htmlspecialchars($transaksi['supplier_nama']) ?>
                                </p>
                                <?php if ($transaksi['supplier_telepon']): ?>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-phone mr-1"></i>
                                        <?= htmlspecialchars($transaksi['supplier_telepon']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="pt-4 border-t border-slate-700">
                    <p class="text-sm text-gray-400 mb-2">Diinput oleh</p>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-primary-500/20 text-primary-400 flex items-center justify-center font-bold">
                            <?= strtoupper(substr($transaksi['user_nama'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-medium text-white">
                                <?= htmlspecialchars($transaksi['user_nama']) ?>
                            </p>
                            <p class="text-xs text-gray-400">
                                <?= formatTanggal($transaksi['created_at'], true) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php if ($transaksi['catatan']): ?>
                    <div class="pt-4 border-t border-slate-700">
                        <p class="text-sm text-gray-400 mb-2">Catatan</p>
                        <p class="text-white text-sm bg-slate-800/50 rounded-lg p-3">
                            <?= nl2br(htmlspecialchars($transaksi['catatan'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($transaksi['nota_file'])): ?>
                    <div class="pt-4 border-t border-slate-700">
                        <p class="text-sm text-gray-400 mb-2">
                            <i class="fas fa-receipt text-yellow-400 mr-1"></i>
                            Nota/Bukti Belanja
                        </p>
                        <div class="relative group">
                            <?php
                            $notaPath = '/gudang-gizi/' . $transaksi['nota_file'];
                            $isPdf = str_ends_with(strtolower($transaksi['nota_file']), '.pdf');
                            ?>

                            <?php if ($isPdf): ?>
                                <a href="<?= $notaPath ?>" target="_blank"
                                    class="flex items-center gap-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-colors">
                                    <div
                                        class="w-12 h-12 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center">
                                        <i class="fas fa-file-pdf text-2xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-white text-sm font-medium">Dokumen PDF</p>
                                        <p class="text-xs text-gray-400">Klik untuk membuka</p>
                                    </div>
                                </a>
                            <?php else: ?>
                                <a href="<?= $notaPath ?>" target="_blank" class="block">
                                    <img src="<?= $notaPath ?>" alt="Nota"
                                        class="w-full rounded-lg border border-slate-600 hover:border-green-500 transition-colors cursor-zoom-in">
                                </a>
                                <div
                                    class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center pointer-events-none">
                                    <span class="text-white text-sm">
                                        <i class="fas fa-search-plus mr-1"></i> Klik untuk memperbesar
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="mt-2 flex gap-2">
                                <a href="<?= $notaPath ?>" download
                                    class="text-xs text-gray-400 hover:text-green-400 transition-colors">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($transaksi['supplier']) && empty($transaksi['supplier_nama'])): ?>
                    <div class="pt-4 border-t border-slate-700">
                        <p class="text-sm text-gray-400 mb-2">Toko/Supplier</p>
                        <p class="text-white"><?= htmlspecialchars($transaksi['supplier']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="lg:col-span-2">
        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-boxes text-green-400"></i>
                    Daftar Bahan (
                    <?= count($items) ?> item)
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-modern">
                    <thead>
                        <tr class="text-left text-sm text-gray-400">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Bahan</th>
                            <th class="px-4 py-3 text-center">Jumlah</th>
                            <th class="px-4 py-3 text-right">Harga</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                            <th class="px-4 py-3 text-center hidden md:table-cell">Kadaluarsa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php $no = 1;
                        foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-700/30">
                                <td class="px-4 py-3 text-gray-500">
                                    <?= $no++ ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-white">
                                            <?= htmlspecialchars($item['bahan_nama']) ?>
                                        </p>
                                        <p class="text-xs text-gray-400 font-mono">
                                            <?= htmlspecialchars($item['bahan_kode']) ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-medium text-white">
                                        <?= number_format($item['jumlah']) ?>
                                    </span>
                                    <span class="text-gray-500 text-sm">
                                        <?= $item['satuan_singkatan'] ?? '' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-300">
                                    <?= formatRupiah($item['harga_satuan']) ?>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-green-400">
                                    <?= formatRupiah($item['subtotal']) ?>
                                </td>
                                <td class="px-4 py-3 text-center hidden md:table-cell">
                                    <?php if ($item['tanggal_kadaluarsa']): ?>
                                        <span class="text-sm text-gray-300">
                                            <?= formatTanggal($item['tanggal_kadaluarsa']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-800/50">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-white">
                                Total Nilai
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-xl font-bold text-green-400">
                                    <?= formatRupiah($transaksi['total_nilai']) ?>
                                </span>
                            </td>
                            <td class="hidden md:table-cell"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
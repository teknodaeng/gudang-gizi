<?php
/**
 * Stok Keluar - Detail View
 */

require_once __DIR__ . '/../../includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$transaksi = fetchOne("SELECT sk.*, u.nama_lengkap as user_nama FROM stok_keluar sk
                       LEFT JOIN users u ON sk.user_id = u.id WHERE sk.id = ?", [$id], 'i');

if (!$transaksi) {
    redirectWith('/gudang-gizi/modules/stok_keluar/index.php', 'Transaksi tidak ditemukan', 'error');
}

$items = fetchAll("SELECT skd.*, b.nama as bahan_nama, b.kode as bahan_kode, st.singkatan as satuan_singkatan
                   FROM stok_keluar_detail skd
                   LEFT JOIN bahan_makanan b ON skd.bahan_id = b.id
                   LEFT JOIN satuan st ON b.satuan_id = st.id WHERE skd.stok_keluar_id = ?", [$id], 'i');
?>

<script>setPageTitle('Detail Stok Keluar', '<?= htmlspecialchars($transaksi['no_transaksi']) ?>');</script>

<a href="/gudang-gizi/modules/stok_keluar/index.php"
    class="inline-flex items-center gap-2 text-gray-400 hover:text-white mb-6">
    <i class="fas fa-arrow-left"></i> Kembali
</a>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4"><i class="fas fa-file-invoice text-red-400 mr-2"></i>Info
                Transaksi</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-400">No. Transaksi</p>
                    <p class="font-mono text-lg font-bold text-red-400">
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
                    <p class="text-sm text-gray-400">Tujuan</p>
                    <p class="text-white">
                        <?= htmlspecialchars($transaksi['tujuan'] ?? 'Dapur') ?>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-700">
                    <p class="text-sm text-gray-400">Diinput oleh</p>
                    <p class="text-white">
                        <?= htmlspecialchars($transaksi['user_nama']) ?>
                    </p>
                    <p class="text-xs text-gray-400">
                        <?= formatTanggal($transaksi['created_at'], true) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-700">
                <h3 class="font-semibold text-white"><i class="fas fa-boxes text-red-400 mr-2"></i>Daftar Bahan (
                    <?= count($items) ?> item)
                </h3>
            </div>
            <table class="w-full table-modern">
                <thead>
                    <tr class="text-left text-sm text-gray-400">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Bahan</th>
                        <th class="px-4 py-3 text-center">Jumlah</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php $no = 1;
                    foreach ($items as $item): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <?= $no++ ?>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">
                                    <?= htmlspecialchars($item['bahan_nama']) ?>
                                </p>
                                <p class="text-xs text-gray-400">
                                    <?= $item['bahan_kode'] ?>
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?= number_format($item['jumlah']) ?>
                                <?= $item['satuan_singkatan'] ?>
                            </td>
                            <td class="px-4 py-3 text-right text-red-400 font-medium">
                                <?= formatRupiah($item['subtotal']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-800/50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-semibold text-white">Total</td>
                        <td class="px-4 py-3 text-right text-xl font-bold text-red-400">
                            <?= formatRupiah($transaksi['total_nilai']) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
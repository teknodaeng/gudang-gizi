<?php
/**
 * Recipe Table Partial - for create.php
 */
if (!isset($resepItems) || !isset($selectedMenu))
    return;

$porsiStandar = $selectedMenu['porsi_standar'];
?>

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-gray-400 text-left">
                <th class="pb-2">Bahan</th>
                <th class="pb-2 text-center">Dibutuhkan</th>
                <th class="pb-2 text-center">Stok</th>
                <th class="pb-2 text-right">Harga</th>
                <th class="pb-2 text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            <?php
            $totalCost = 0;
            foreach ($resepItems as $item):
                $subtotal = $item['jumlah'] * $item['harga_satuan'];
                $totalCost += $subtotal;
                $stokCukup = $item['stok_saat_ini'] >= $item['jumlah'];
                ?>
                <tr>
                    <td class="py-2">
                        <p class="text-white font-medium">
                            <?= htmlspecialchars($item['bahan_nama']) ?>
                        </p>
                        <p class="text-xs text-gray-500 font-mono">
                            <?= $item['bahan_kode'] ?>
                        </p>
                    </td>
                    <td class="py-2 text-center">
                        <span class="text-orange-400 font-medium" data-base-qty="<?= $item['jumlah'] ?>"
                            data-stock="<?= $item['stok_saat_ini'] ?>" data-stock-check>
                            <?= number_format($item['jumlah'], 2) ?>
                        </span>
                        <span class="text-gray-500">
                            <?= $item['satuan_singkatan'] ?>
                        </span>
                    </td>
                    <td class="py-2 text-center">
                        <span class="<?= $stokCukup ? 'text-green-400' : 'text-red-400' ?>">
                            <?= number_format($item['stok_saat_ini'], 2) ?>
                            <?= $item['satuan_singkatan'] ?>
                        </span>
                        <?php if (!$stokCukup): ?>
                            <span class="block text-xs text-red-400">Tidak cukup!</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 text-right text-gray-400">
                        <?= formatRupiah($item['harga_satuan']) ?>
                    </td>
                    <td class="py-2 text-right text-green-400 font-medium" data-base-cost="<?= $subtotal ?>">
                        <?= formatRupiah($subtotal) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="border-t border-slate-600">
                <td colspan="4" class="py-3 text-right font-medium text-gray-300">Total Biaya:</td>
                <td class="py-3 text-right text-lg font-bold text-green-400">
                    <?= formatRupiah($totalCost) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
<?php
/**
 * Stok Keluar - Print View
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$transaksi = fetchOne("SELECT sk.*, u.nama_lengkap as user_nama FROM stok_keluar sk
                       LEFT JOIN users u ON sk.user_id = u.id WHERE sk.id = ?", [$id], 'i');

if (!$transaksi) {
    die('Transaksi tidak ditemukan');
}

$items = fetchAll("SELECT skd.*, b.nama as bahan_nama, b.kode as bahan_kode, st.singkatan as satuan_singkatan
                   FROM stok_keluar_detail skd
                   LEFT JOIN bahan_makanan b ON skd.bahan_id = b.id
                   LEFT JOIN satuan st ON b.satuan_id = st.id WHERE skd.stok_keluar_id = ?", [$id], 'i');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Stok Keluar -
        <?= htmlspecialchars($transaksi['no_transaksi']) ?>
    </title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            font-size: 12px;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #dc2626;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #dc2626;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #dc2626;
            color: white;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background: #fef2f2;
        }

        .total-row td {
            border-top: 2px solid #dc2626;
            color: #dc2626;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">🖨️ Cetak</button>
    <div class="container">
        <div class="header">
            <h1>📦 GUDANG GIZI</h1>
            <p>Dapur Makan Gizi Gratis</p>
            <h2 style="margin-top:15px;">BUKTI PENGELUARAN STOK</h2>
        </div>
        <p><strong>No. Transaksi:</strong>
            <?= htmlspecialchars($transaksi['no_transaksi']) ?>
        </p>
        <p><strong>Tanggal:</strong>
            <?= formatTanggal($transaksi['tanggal']) ?>
        </p>
        <p><strong>Tujuan:</strong>
            <?= htmlspecialchars($transaksi['tujuan'] ?? 'Dapur') ?>
        </p>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Bahan</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($items as $item): ?>
                    <tr>
                        <td class="text-center">
                            <?= $no++ ?>
                        </td>
                        <td>
                            <?= $item['bahan_kode'] ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($item['bahan_nama']) ?>
                        </td>
                        <td class="text-center">
                            <?= number_format($item['jumlah']) ?>
                            <?= $item['satuan_singkatan'] ?>
                        </td>
                        <td class="text-right">
                            <?= formatRupiah($item['subtotal']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL</td>
                    <td class="text-right">
                        <?= formatRupiah($transaksi['total_nilai']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <p style="text-align:center;color:#999;font-size:10px;">Dicetak:
            <?= date('d/m/Y H:i:s') ?> | Oleh:
            <?= htmlspecialchars($transaksi['user_nama']) ?>
        </p>
    </div>
</body>

</html>
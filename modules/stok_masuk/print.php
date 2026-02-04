<?php
/**
 * Stok Masuk - Print View
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get transaction data
$transaksi = fetchOne("SELECT sm.*, s.nama as supplier_nama, s.alamat as supplier_alamat, 
                        s.telepon as supplier_telepon, u.nama_lengkap as user_nama
                       FROM stok_masuk sm
                       LEFT JOIN supplier s ON sm.supplier_id = s.id
                       LEFT JOIN users u ON sm.user_id = u.id
                       WHERE sm.id = ?", [$id], 'i');

if (!$transaksi) {
    die('Transaksi tidak ditemukan');
}

// Get detail items
$items = fetchAll("SELECT smd.*, b.nama as bahan_nama, b.kode as bahan_kode, 
                   st.singkatan as satuan_singkatan
                   FROM stok_masuk_detail smd
                   LEFT JOIN bahan_makanan b ON smd.bahan_id = b.id
                   LEFT JOIN satuan st ON b.satuan_id = st.id
                   WHERE smd.stok_masuk_id = ?", [$id], 'i');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Masuk -
        <?= htmlspecialchars($transaksi['no_transaksi']) ?>
    </title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #16a34a;
        }

        .header h1 {
            font-size: 24px;
            color: #16a34a;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
        }

        .doc-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .doc-title h2 {
            font-size: 18px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-box {
            padding: 15px;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .info-box h4 {
            color: #16a34a;
            margin-bottom: 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-box p {
            margin: 3px 0;
        }

        .info-box strong {
            display: inline-block;
            min-width: 100px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #16a34a;
            color: white;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background: #f0fdf4;
        }

        .total-row td {
            border-top: 2px solid #16a34a;
            color: #16a34a;
            font-size: 14px;
        }

        .footer {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            text-align: center;
        }

        .signature {
            height: 80px;
        }

        .signature p {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-btn:hover {
            background: #15803d;
        }

        @media print {
            .print-btn {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">
        🖨️ Cetak
    </button>

    <div class="container">
        <div class="header">
            <h1>🏪 GUDANG GIZI</h1>
            <p>Dapur Makan Gizi Gratis</p>
            <p>Sistem Manajemen Stok & Inventory</p>
        </div>

        <div class="doc-title">
            <h2>📦 Bukti Penerimaan Stok</h2>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h4>Informasi Transaksi</h4>
                <p><strong>No. Transaksi:</strong>
                    <?= htmlspecialchars($transaksi['no_transaksi']) ?>
                </p>
                <p><strong>Tanggal:</strong>
                    <?= formatTanggal($transaksi['tanggal']) ?>
                </p>
                <p><strong>Dibuat:</strong>
                    <?= formatTanggal($transaksi['created_at'], true) ?>
                </p>
            </div>
            <div class="info-box">
                <h4>Supplier</h4>
                <?php if ($transaksi['supplier_nama']): ?>
                    <p><strong>Nama:</strong>
                        <?= htmlspecialchars($transaksi['supplier_nama']) ?>
                    </p>
                    <?php if ($transaksi['supplier_alamat']): ?>
                        <p><strong>Alamat:</strong>
                            <?= htmlspecialchars($transaksi['supplier_alamat']) ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($transaksi['supplier_telepon']): ?>
                        <p><strong>Telepon:</strong>
                            <?= htmlspecialchars($transaksi['supplier_telepon']) ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><em>- Tidak ada supplier -</em></p>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px">No</th>
                    <th>Kode</th>
                    <th>Nama Bahan</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-right">Harga</th>
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
                            <?= htmlspecialchars($item['bahan_kode']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($item['bahan_nama']) ?>
                        </td>
                        <td class="text-center">
                            <?= number_format($item['jumlah']) ?>
                            <?= $item['satuan_singkatan'] ?? '' ?>
                        </td>
                        <td class="text-right">
                            <?= formatRupiah($item['harga_satuan']) ?>
                        </td>
                        <td class="text-right">
                            <?= formatRupiah($item['subtotal']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL</td>
                    <td class="text-right">
                        <?= formatRupiah($transaksi['total_nilai']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <?php if ($transaksi['catatan']): ?>
            <div class="info-box" style="margin-bottom: 20px;">
                <h4>Catatan</h4>
                <p>
                    <?= nl2br(htmlspecialchars($transaksi['catatan'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <div class="signature">
                <p>Penerima</p>
            </div>
            <div class="signature">
                <p>Petugas Gudang</p>
            </div>
            <div class="signature">
                <p>Diketahui oleh</p>
            </div>
        </div>

        <p style="text-align: center; margin-top: 30px; color: #999; font-size: 10px;">
            Dicetak pada:
            <?= date('d/m/Y H:i:s') ?> | Oleh:
            <?= htmlspecialchars($transaksi['user_nama']) ?>
        </p>
    </div>
</body>

</html>
<?php
/**
 * Export Laporan Stok ke PDF/Excel
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /gudang-gizi/modules/auth/login.php');
    exit;
}

$format = $_GET['format'] ?? 'pdf';
$filter = $_GET['filter'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE b.is_active = 1";
if ($filter === 'low') {
    $where .= " AND b.stok_saat_ini <= b.stok_minimum";
}
if ($kategori) {
    $where .= " AND b.kategori_id = " . (int) $kategori;
}
if ($search) {
    $search = escape($search);
    $where .= " AND (b.nama LIKE '%$search%' OR b.kode LIKE '%$search%')";
}

$items = fetchAll("SELECT b.*, k.nama as kategori_nama, s.singkatan as satuan_singkatan,
                   (b.stok_saat_ini * b.harga_satuan) as nilai_stok
                   FROM bahan_makanan b
                   LEFT JOIN kategori k ON b.kategori_id = k.id
                   LEFT JOIN satuan s ON b.satuan_id = s.id
                   $where ORDER BY b.nama ASC");

$summary = fetchOne("SELECT COUNT(*) as total_item, SUM(stok_saat_ini) as total_stok, 
                     SUM(stok_saat_ini * harga_satuan) as total_nilai FROM bahan_makanan b $where");

if ($format === 'excel') {
    // Export to Excel (CSV format with Excel-compatible encoding)
    $filename = 'laporan_stok_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Add BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Header info
    fputcsv($output, ['LAPORAN STOK BAHAN MAKANAN']);
    fputcsv($output, ['Tanggal Export:', formatTanggal(date('Y-m-d'))]);
    fputcsv($output, ['']);

    // Summary
    fputcsv($output, ['RINGKASAN']);
    fputcsv($output, ['Total Item:', number_format($summary['total_item'])]);
    fputcsv($output, ['Total Stok:', number_format($summary['total_stok'])]);
    fputcsv($output, ['Total Nilai:', formatRupiah($summary['total_nilai'])]);
    fputcsv($output, ['']);

    // Table header
    fputcsv($output, ['No', 'Kode', 'Nama Bahan', 'Kategori', 'Stok', 'Satuan', 'Stok Min', 'Harga Satuan', 'Nilai Stok', 'Status']);

    // Data rows
    $no = 1;
    foreach ($items as $item) {
        $isLow = $item['stok_saat_ini'] <= $item['stok_minimum'];
        fputcsv($output, [
            $no++,
            $item['kode'],
            $item['nama'],
            $item['kategori_nama'] ?? '-',
            number_format($item['stok_saat_ini']),
            $item['satuan_singkatan'] ?? '',
            number_format($item['stok_minimum']),
            formatRupiah($item['harga_satuan']),
            formatRupiah($item['nilai_stok']),
            $isLow ? 'STOK MENIPIS' : 'Normal'
        ]);
    }

    fclose($output);
    exit;
} else {
    // Export to PDF (HTML-based for simplicity)
    $filename = 'laporan_stok_' . date('Y-m-d_His') . '.pdf';
    ?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Stok -
            <?= date('d/m/Y H:i') ?>
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
                line-height: 1.4;
                color: #333;
                background: #fff;
                padding: 20px;
            }

            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #22c55e;
            }

            .header h1 {
                font-size: 24px;
                color: #166534;
                margin-bottom: 5px;
            }

            .header h2 {
                font-size: 18px;
                color: #333;
                font-weight: normal;
            }

            .header p {
                color: #666;
                margin-top: 10px;
            }

            .summary {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
            }

            .summary-box {
                flex: 1;
                padding: 15px;
                background: #f0fdf4;
                border-radius: 8px;
                text-align: center;
                border: 1px solid #bbf7d0;
            }

            .summary-box h3 {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                margin-bottom: 5px;
            }

            .summary-box p {
                font-size: 18px;
                font-weight: bold;
                color: #166534;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            th,
            td {
                padding: 10px 8px;
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
            }

            th {
                background: linear-gradient(135deg, #166534 0%, #22c55e 100%);
                color: white;
                font-weight: 600;
                font-size: 11px;
                text-transform: uppercase;
            }

            tr:nth-child(even) {
                background: #f9fafb;
            }

            tr:hover {
                background: #f0fdf4;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: 600;
            }

            .badge-success {
                background: #dcfce7;
                color: #166534;
            }

            .badge-warning {
                background: #fef3c7;
                color: #92400e;
            }

            .low-stock {
                background: #fef3c7 !important;
            }

            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                color: #666;
                font-size: 11px;
            }

            .print-btn {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 24px;
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                box-shadow: 0 4px 6px rgba(34, 197, 94, 0.3);
            }

            .print-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 10px rgba(34, 197, 94, 0.4);
            }

            @media print {
                .print-btn {
                    display: none;
                }

                body {
                    padding: 0;
                }

                .summary-box {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                th {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .low-stock {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>

    <body>
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>

        <div class="header">
            <h1>🏪 GUDANG GIZI</h1>
            <h2>Laporan Stok Bahan Makanan</h2>
            <p>Dicetak pada:
                <?= formatTanggal(date('Y-m-d'), true) ?>
            </p>
            <?php if ($filter === 'low'): ?>
                <p style="color: #f97316; font-weight: bold;">Filter: Stok Menipis</p>
            <?php endif; ?>
        </div>

        <div class="summary">
            <div class="summary-box">
                <h3>Total Item</h3>
                <p>
                    <?= number_format($summary['total_item']) ?>
                </p>
            </div>
            <div class="summary-box">
                <h3>Total Stok</h3>
                <p>
                    <?= number_format($summary['total_stok']) ?>
                </p>
            </div>
            <div class="summary-box">
                <h3>Total Nilai</h3>
                <p>
                    <?= formatRupiah($summary['total_nilai']) ?>
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Bahan</th>
                    <th>Kategori</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Min</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Nilai</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($items as $item):
                    $isLow = $item['stok_saat_ini'] <= $item['stok_minimum'];
                    ?>
                    <tr class="<?= $isLow ? 'low-stock' : '' ?>">
                        <td>
                            <?= $no++ ?>
                        </td>
                        <td style="font-family: monospace;">
                            <?= $item['kode'] ?>
                        </td>
                        <td><strong>
                                <?= htmlspecialchars($item['nama']) ?>
                            </strong></td>
                        <td>
                            <?= htmlspecialchars($item['kategori_nama'] ?? '-') ?>
                        </td>
                        <td class="text-center">
                            <?= number_format($item['stok_saat_ini']) ?>
                            <?= $item['satuan_singkatan'] ?>
                        </td>
                        <td class="text-center">
                            <?= number_format($item['stok_minimum']) ?>
                        </td>
                        <td class="text-right">
                            <?= formatRupiah($item['harga_satuan']) ?>
                        </td>
                        <td class="text-right"><strong>
                                <?= formatRupiah($item['nilai_stok']) ?>
                            </strong></td>
                        <td class="text-center">
                            <span class="badge <?= $isLow ? 'badge-warning' : 'badge-success' ?>">
                                <?= $isLow ? '⚠️ MENIPIS' : '✓ Normal' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            <div>
                <strong>Gudang Gizi</strong> - Sistem Manajemen Stok
            </div>
            <div>
                Halaman 1 | Total
                <?= count($items) ?> item
            </div>
        </div>

        <script>
            // Auto print dialog untuk PDF
            // window.onload = function() { window.print(); }
        </script>
    </body>

    </html>
    <?php
}
?>
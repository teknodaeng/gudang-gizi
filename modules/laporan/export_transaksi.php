<?php
/**
 * Export Laporan Transaksi ke PDF/Excel
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
$tipe = $_GET['tipe'] ?? 'all';
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');

// Get stok masuk with details
$masukData = [];
if ($tipe !== 'out') {
    $masukData = fetchAll("SELECT sm.*, s.nama as supplier_nama, u.nama_lengkap as user_nama,
                           (SELECT COUNT(*) FROM stok_masuk_detail WHERE stok_masuk_id = sm.id) as jumlah_item
                           FROM stok_masuk sm
                           LEFT JOIN supplier s ON sm.supplier_id = s.id
                           LEFT JOIN users u ON sm.user_id = u.id
                           WHERE sm.tanggal BETWEEN '$mulai' AND '$akhir' AND sm.status = 'completed'
                           ORDER BY sm.tanggal DESC, sm.id DESC");
}

// Get stok keluar with details
$keluarData = [];
if ($tipe !== 'in') {
    $keluarData = fetchAll("SELECT sk.*, u.nama_lengkap as user_nama,
                            (SELECT COUNT(*) FROM stok_keluar_detail WHERE stok_keluar_id = sk.id) as jumlah_item
                            FROM stok_keluar sk
                            LEFT JOIN users u ON sk.user_id = u.id
                            WHERE sk.tanggal BETWEEN '$mulai' AND '$akhir' AND sk.status = 'completed'
                            ORDER BY sk.tanggal DESC, sk.id DESC");
}

$totalMasuk = array_sum(array_column($masukData, 'total_nilai'));
$totalKeluar = array_sum(array_column($keluarData, 'total_nilai'));

if ($format === 'excel') {
    // Export to Excel (CSV format)
    $filename = 'laporan_transaksi_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Add BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header info
    fputcsv($output, ['LAPORAN TRANSAKSI STOK']);
    fputcsv($output, ['Periode:', formatTanggal($mulai) . ' - ' . formatTanggal($akhir)]);
    fputcsv($output, ['Tanggal Export:', formatTanggal(date('Y-m-d'), true)]);
    fputcsv($output, ['']);
    
    // Summary
    fputcsv($output, ['RINGKASAN']);
    fputcsv($output, ['Total Stok Masuk:', formatRupiah($totalMasuk), count($masukData) . ' transaksi']);
    fputcsv($output, ['Total Stok Keluar:', formatRupiah($totalKeluar), count($keluarData) . ' transaksi']);
    fputcsv($output, ['Selisih:', formatRupiah($totalMasuk - $totalKeluar)]);
    fputcsv($output, ['']);
    
    // Stok Masuk
    if ($tipe !== 'out' && !empty($masukData)) {
        fputcsv($output, ['=== STOK MASUK ===']);
        fputcsv($output, ['No', 'No. Transaksi', 'Tanggal', 'Supplier', 'Jumlah Item', 'Total Nilai', 'Operator']);
        
        $no = 1;
        foreach ($masukData as $item) {
            fputcsv($output, [
                $no++,
                $item['no_transaksi'],
                formatTanggal($item['tanggal']),
                $item['supplier_nama'] ?? '-',
                $item['jumlah_item'],
                formatRupiah($item['total_nilai']),
                $item['user_nama'] ?? '-'
            ]);
        }
        fputcsv($output, ['']);
    }
    
    // Stok Keluar
    if ($tipe !== 'in' && !empty($keluarData)) {
        fputcsv($output, ['=== STOK KELUAR ===']);
        fputcsv($output, ['No', 'No. Transaksi', 'Tanggal', 'Tujuan', 'Jumlah Item', 'Total Nilai', 'Operator']);
        
        $no = 1;
        foreach ($keluarData as $item) {
            fputcsv($output, [
                $no++,
                $item['no_transaksi'],
                formatTanggal($item['tanggal']),
                $item['tujuan'] ?? 'Dapur',
                $item['jumlah_item'],
                formatRupiah($item['total_nilai']),
                $item['user_nama'] ?? '-'
            ]);
        }
    }
    
    fclose($output);
    exit;
} else {
    // Export to PDF (HTML-based)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - <?= date('d/m/Y H:i') ?></title>
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
            border-bottom: 2px solid #3b82f6;
        }
        
        .header h1 {
            font-size: 24px;
            color: #1e40af;
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
        
        .period {
            background: #eff6ff;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 10px;
            color: #1e40af;
            font-weight: 600;
        }
        
        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-box {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary-box.in {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
        }
        
        .summary-box.out {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
        }
        
        .summary-box.diff {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
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
        }
        
        .summary-box.in p { color: #166534; }
        .summary-box.out p { color: #dc2626; }
        .summary-box.diff p { color: #1e40af; }
        
        .summary-box small {
            font-size: 11px;
            color: #666;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-header {
            padding: 12px 16px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 14px;
        }
        
        .section-header.in {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        
        .section-header.out {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        
        th, td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .mono {
            font-family: 'Courier New', monospace;
            font-size: 11px;
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
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            
            .summary-box, .section-header, th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        🖨️ Cetak / Simpan PDF
    </button>
    
    <div class="header">
        <h1>🏪 GUDANG GIZI</h1>
        <h2>Laporan Transaksi Stok</h2>
        <div class="period">
            📅 <?= formatTanggal($mulai) ?> - <?= formatTanggal($akhir) ?>
        </div>
        <p style="margin-top: 15px;">Dicetak pada: <?= formatTanggal(date('Y-m-d'), true) ?></p>
    </div>
    
    <div class="summary">
        <div class="summary-box in">
            <h3>Total Stok Masuk</h3>
            <p><?= formatRupiah($totalMasuk) ?></p>
            <small><?= count($masukData) ?> transaksi</small>
        </div>
        <div class="summary-box out">
            <h3>Total Stok Keluar</h3>
            <p><?= formatRupiah($totalKeluar) ?></p>
            <small><?= count($keluarData) ?> transaksi</small>
        </div>
        <div class="summary-box diff">
            <h3>Selisih</h3>
            <p><?= formatRupiah($totalMasuk - $totalKeluar) ?></p>
            <small><?= $totalMasuk >= $totalKeluar ? 'Surplus' : 'Defisit' ?></small>
        </div>
    </div>
    
    <?php if ($tipe !== 'out'): ?>
    <div class="section">
        <div class="section-header in">
            ⬇️ STOK MASUK
        </div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th class="text-center">Item</th>
                    <th class="text-right">Total Nilai</th>
                    <th>Operator</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($masukData)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">Tidak ada data transaksi stok masuk</td>
                    </tr>
                <?php else: 
                    $no = 1;
                    foreach ($masukData as $item): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="mono" style="color: #22c55e;"><?= $item['no_transaksi'] ?></td>
                        <td><?= formatTanggal($item['tanggal']) ?></td>
                        <td><?= htmlspecialchars($item['supplier_nama'] ?? '-') ?></td>
                        <td class="text-center"><?= $item['jumlah_item'] ?></td>
                        <td class="text-right"><strong style="color: #22c55e;"><?= formatRupiah($item['total_nilai']) ?></strong></td>
                        <td><?= htmlspecialchars($item['user_nama'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if ($tipe !== 'in'): ?>
    <div class="section">
        <div class="section-header out">
            ⬆️ STOK KELUAR
        </div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Tujuan</th>
                    <th class="text-center">Item</th>
                    <th class="text-right">Total Nilai</th>
                    <th>Operator</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($keluarData)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">Tidak ada data transaksi stok keluar</td>
                    </tr>
                <?php else: 
                    $no = 1;
                    foreach ($keluarData as $item): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="mono" style="color: #ef4444;"><?= $item['no_transaksi'] ?></td>
                        <td><?= formatTanggal($item['tanggal']) ?></td>
                        <td><?= htmlspecialchars($item['tujuan'] ?? 'Dapur') ?></td>
                        <td class="text-center"><?= $item['jumlah_item'] ?></td>
                        <td class="text-right"><strong style="color: #ef4444;"><?= formatRupiah($item['total_nilai']) ?></strong></td>
                        <td><?= htmlspecialchars($item['user_nama'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <div>
            <strong>Gudang Gizi</strong> - Sistem Manajemen Stok
        </div>
        <div>
            Total: <?= count($masukData) + count($keluarData) ?> transaksi
        </div>
    </div>
</body>
</html>
<?php
}
?>

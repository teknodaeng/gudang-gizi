<?php
/**
 * Database Migration - Add nota_file column to stok_masuk
 * Run this file once to update the database
 */

require_once __DIR__ . '/../config/database.php';

// Add nota_file column to stok_masuk table
$queries = [
    "ALTER TABLE stok_masuk ADD COLUMN IF NOT EXISTS nota_file VARCHAR(255) DEFAULT NULL AFTER catatan",
    "ALTER TABLE stok_masuk ADD COLUMN IF NOT EXISTS supplier VARCHAR(255) DEFAULT NULL AFTER tanggal",
    "ALTER TABLE stok_masuk ADD COLUMN IF NOT EXISTS total_nilai DECIMAL(15,2) DEFAULT 0 AFTER catatan"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "✅ Query executed: " . substr($sql, 0, 60) . "...\n";
    } else {
        echo "⚠️ Query skipped or failed: " . $conn->error . "\n";
    }
}

echo "\n📌 Columns added to stok_masuk table:\n";
echo "- nota_file: untuk menyimpan path file nota\n";
echo "- supplier: nama supplier/toko\n";
echo "- total_nilai: total nilai pembelian\n";
?>
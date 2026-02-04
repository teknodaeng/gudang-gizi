<?php
/**
 * Helper Functions
 * Gudang Gizi - Sistem Manajemen Stok
 */

/**
 * Format currency
 */
function formatRupiah($number)
{
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * Format date to Indonesian
 */
function formatTanggal($date, $withTime = false)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($date);
    $hari = date('d', $timestamp);
    $bln = (int) date('m', $timestamp);
    $tahun = date('Y', $timestamp);

    $result = $hari . ' ' . $bulan[$bln] . ' ' . $tahun;

    if ($withTime) {
        $result .= ' ' . date('H:i', $timestamp);
    }

    return $result;
}

/**
 * Generate transaction number
 */
function generateNoTransaksi($prefix = 'TRX')
{
    return $prefix . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Check low stock and create notification
 */
function checkLowStock($bahan_id = null)
{
    global $conn;

    $sql = "SELECT id, kode, nama, stok_saat_ini, stok_minimum 
            FROM bahan_makanan 
            WHERE stok_saat_ini <= stok_minimum AND is_active = 1";

    if ($bahan_id) {
        $sql .= " AND id = $bahan_id";
    }

    $result = $conn->query($sql);
    $lowStockItems = [];

    while ($row = $result->fetch_assoc()) {
        $lowStockItems[] = $row;

        // Check if notification already exists
        $check = fetchOne(
            "SELECT id FROM notifikasi WHERE bahan_id = ? AND tipe = 'low_stock' AND is_read = 0",
            [$row['id']],
            'i'
        );

        if (!$check) {
            // Create notification
            $conn->query("INSERT INTO notifikasi (tipe, judul, pesan, bahan_id) VALUES 
                ('low_stock', 'Stok Menipis!', 'Stok {$row['nama']} ({$row['kode']}) tinggal {$row['stok_saat_ini']} unit', {$row['id']})");
        }
    }

    return $lowStockItems;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $description = '')
{
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $action, $description, $ip);
    $stmt->execute();
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount()
{
    $result = fetchOne("SELECT COUNT(*) as count FROM notifikasi WHERE is_read = 0");
    return $result['count'] ?? 0;
}

/**
 * Get latest notifications
 */
function getLatestNotifications($limit = 5)
{
    return fetchAll("SELECT n.*, b.nama as bahan_nama 
                     FROM notifikasi n 
                     LEFT JOIN bahan_makanan b ON n.bahan_id = b.id 
                     ORDER BY n.created_at DESC 
                     LIMIT $limit");
}

/**
 * Update stock
 */
function updateStock($bahan_id, $jumlah, $type = 'in')
{
    global $conn;

    if ($type === 'in') {
        $conn->query("UPDATE bahan_makanan SET stok_saat_ini = stok_saat_ini + $jumlah WHERE id = $bahan_id");
    } else {
        $conn->query("UPDATE bahan_makanan SET stok_saat_ini = stok_saat_ini - $jumlah WHERE id = $bahan_id");
    }

    // Check low stock
    checkLowStock($bahan_id);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats()
{
    global $conn;

    $stats = [];

    // Total bahan makanan
    $result = fetchOne("SELECT COUNT(*) as count FROM bahan_makanan WHERE is_active = 1");
    $stats['total_bahan'] = $result['count'];

    // Total stok value
    $result = fetchOne("SELECT SUM(stok_saat_ini * harga_satuan) as total FROM bahan_makanan WHERE is_active = 1");
    $stats['total_nilai_stok'] = $result['total'] ?? 0;

    // Low stock items
    $result = fetchOne("SELECT COUNT(*) as count FROM bahan_makanan WHERE stok_saat_ini <= stok_minimum AND is_active = 1");
    $stats['stok_menipis'] = $result['count'];

    // Today's transactions
    $today = date('Y-m-d');
    $result = fetchOne("SELECT COUNT(*) as count FROM stok_masuk WHERE tanggal = '$today'");
    $stats['stok_masuk_hari_ini'] = $result['count'];

    $result = fetchOne("SELECT COUNT(*) as count FROM stok_keluar WHERE tanggal = '$today'");
    $stats['stok_keluar_hari_ini'] = $result['count'];

    // Monthly summary
    $bulan = date('Y-m');
    $result = fetchOne("SELECT SUM(total_nilai) as total FROM stok_masuk WHERE tanggal LIKE '$bulan%' AND status = 'completed'");
    $stats['nilai_masuk_bulan'] = $result['total'] ?? 0;

    $result = fetchOne("SELECT SUM(total_nilai) as total FROM stok_keluar WHERE tanggal LIKE '$bulan%' AND status = 'completed'");
    $stats['nilai_keluar_bulan'] = $result['total'] ?? 0;

    return $stats;
}

/**
 * Sanitize input
 */
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check permission based on role
 */
function hasPermission($requiredRoles)
{
    if (!isset($_SESSION['user'])) {
        return false;
    }

    $userRole = $_SESSION['user']['role'];

    if (is_string($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }

    return in_array($userRole, $requiredRoles);
}

/**
 * Redirect with message
 */
function redirectWith($url, $message = '', $type = 'success')
{
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 */
function showFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);

        $bgColor = match ($type) {
            'success' => 'bg-green-500',
            'error' => 'bg-red-500',
            'warning' => 'bg-yellow-500',
            'info' => 'bg-blue-500',
            default => 'bg-gray-500'
        };

        return "<div class=\"$bgColor text-white px-4 py-3 rounded-lg mb-4 flex items-center justify-between flash-message\">
                    <span>$message</span>
                    <button onclick=\"this.parentElement.remove()\" class=\"ml-4 font-bold\">&times;</button>
                </div>";
    }
    return '';
}
?>
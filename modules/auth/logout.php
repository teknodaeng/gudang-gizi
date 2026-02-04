<?php
/**
 * Logout Handler
 * Gudang Gizi - Sistem Manajemen Stok
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (isset($_SESSION['user'])) {
    logActivity($_SESSION['user']['id'], 'logout', 'User logged out');
}

session_destroy();
header('Location: /gudang-gizi/modules/auth/login.php');
exit;
?>
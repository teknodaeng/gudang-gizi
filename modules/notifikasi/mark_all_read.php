<?php
/**
 * Mark All Notifications as Read
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

query("UPDATE notifikasi SET is_read = 1");

$_SESSION['flash_message'] = 'Semua notifikasi telah ditandai dibaca';
$_SESSION['flash_type'] = 'success';

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/gudang-gizi/index.php'));
exit;

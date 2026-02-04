<?php
/**
 * Notifikasi - List
 */

require_once __DIR__ . '/../../includes/header.php';

$filter = $_GET['filter'] ?? '';

$where = "";
if ($filter === 'unread') {
    $where = "WHERE n.is_read = 0";
} elseif ($filter === 'low_stock') {
    $where = "WHERE n.tipe = 'low_stock'";
}

$notifications = fetchAll("SELECT n.*, b.nama as bahan_nama, b.kode as bahan_kode
                           FROM notifikasi n
                           LEFT JOIN bahan_makanan b ON n.bahan_id = b.id
                           $where ORDER BY n.created_at DESC LIMIT 100");
?>

<script>setPageTitle('Notifikasi', 'Semua notifikasi sistem');</script>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-yellow-500/20 text-yellow-400 flex items-center justify-center">
            <i class="fas fa-bell"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Notifikasi</h1>
            <p class="text-sm text-gray-400">
                <?= count($notifications) ?> notifikasi
            </p>
        </div>
    </div>
    <a href="/gudang-gizi/modules/notifikasi/mark_all_read.php"
        class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white text-sm">
        <i class="fas fa-check-double mr-2"></i>Tandai Semua Dibaca
    </a>
</div>

<!-- Filters -->
<div class="flex gap-2 mb-6">
    <a href="?filter="
        class="px-4 py-2 rounded-lg text-sm <?= !$filter ? 'bg-primary-500 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' ?>">Semua</a>
    <a href="?filter=unread"
        class="px-4 py-2 rounded-lg text-sm <?= $filter === 'unread' ? 'bg-primary-500 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' ?>">Belum
        Dibaca</a>
    <a href="?filter=low_stock"
        class="px-4 py-2 rounded-lg text-sm <?= $filter === 'low_stock' ? 'bg-primary-500 text-white' : 'bg-slate-700 text-gray-300 hover:bg-slate-600' ?>">Stok
        Menipis</a>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <?php if (empty($notifications)): ?>
        <div class="p-12 text-center text-gray-500">
            <i class="fas fa-bell-slash text-4xl mb-3"></i>
            <p>Tidak ada notifikasi</p>
        </div>
    <?php else: ?>
        <div class="divide-y divide-slate-700/50">
            <?php foreach ($notifications as $notif): ?>
                <div class="p-4 hover:bg-slate-700/30 transition-colors <?= $notif['is_read'] ? 'opacity-60' : '' ?>">
                    <div class="flex gap-4">
                        <div
                            class="w-12 h-12 rounded-xl flex-shrink-0 flex items-center justify-center
                    <?= $notif['tipe'] === 'low_stock' ? 'bg-yellow-500/20 text-yellow-400' :
                        ($notif['tipe'] === 'expired' ? 'bg-red-500/20 text-red-400' : 'bg-blue-500/20 text-blue-400') ?>">
                            <i
                                class="fas <?= $notif['tipe'] === 'low_stock' ? 'fa-triangle-exclamation' :
                                    ($notif['tipe'] === 'expired' ? 'fa-calendar-xmark' : 'fa-info-circle') ?> text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="font-medium text-white">
                                        <?= htmlspecialchars($notif['judul']) ?>
                                    </h4>
                                    <p class="text-sm text-gray-400 mt-1">
                                        <?= htmlspecialchars($notif['pesan']) ?>
                                    </p>
                                    <?php if ($notif['bahan_nama']): ?>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="fas fa-box mr-1"></i>
                                            <?= htmlspecialchars($notif['bahan_nama']) ?> (
                                            <?= $notif['bahan_kode'] ?>)
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs text-gray-500">
                                        <?= formatTanggal($notif['created_at'], true) ?>
                                    </p>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="inline-block w-2 h-2 rounded-full bg-yellow-400 mt-2"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
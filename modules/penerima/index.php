<?php
/**
 * Manajemen Penerima (Anak/Kelompok)
 */

require_once __DIR__ . '/../../includes/header.php';

// Check permission
if (!hasPermission(['owner', 'admin'])) {
    redirectWith('/gudang-gizi/index.php', 'Anda tidak memiliki akses ke halaman ini', 'error');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $kode = sanitize($_POST['kode']);
        $nama = sanitize($_POST['nama']);
        $kategori = $_POST['kategori'];
        $lokasi = sanitize($_POST['lokasi'] ?? '');
        $jumlah_orang = (int) $_POST['jumlah_orang'];
        $faktor_porsi = (float) $_POST['faktor_porsi'];
        $catatan = sanitize($_POST['catatan'] ?? '');

        if ($action === 'add') {
            $result = query(
                "INSERT INTO penerima (kode, nama, kategori, lokasi, jumlah_orang, faktor_porsi, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$kode, $nama, $kategori, $lokasi, $jumlah_orang, $faktor_porsi, $catatan],
                "ssssisd"
            );
            if ($result) {
                redirectWith($_SERVER['PHP_SELF'], 'Penerima berhasil ditambahkan!', 'success');
            } else {
                redirectWith($_SERVER['PHP_SELF'], 'Gagal menambahkan. Kode mungkin sudah ada.', 'error');
            }
        } else {
            $result = query(
                "UPDATE penerima SET nama = ?, kategori = ?, lokasi = ?, jumlah_orang = ?, faktor_porsi = ?, catatan = ? WHERE id = ?",
                [$nama, $kategori, $lokasi, $jumlah_orang, $faktor_porsi, $catatan, $id],
                "sssisdi"
            );
            if ($result) {
                redirectWith($_SERVER['PHP_SELF'], 'Penerima berhasil diperbarui!', 'success');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        query("DELETE FROM penerima WHERE id = ?", [$id], "i");
        redirectWith($_SERVER['PHP_SELF'], 'Penerima berhasil dihapus!', 'success');
    }

    if ($action === 'toggle') {
        $id = (int) $_POST['id'];
        query("UPDATE penerima SET is_active = NOT is_active WHERE id = ?", [$id], "i");
        redirectWith($_SERVER['PHP_SELF'], 'Status berhasil diubah!', 'success');
    }
}

// Get all penerima
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';

$where = "WHERE 1=1";
if ($search) {
    $search = escape($search);
    $where .= " AND (nama LIKE '%$search%' OR kode LIKE '%$search%' OR lokasi LIKE '%$search%')";
}
if ($kategori_filter) {
    $where .= " AND kategori = '$kategori_filter'";
}

$penerimaList = fetchAll("SELECT * FROM penerima $where ORDER BY nama ASC");

// Calculate totals
$totalOrang = array_sum(array_column($penerimaList, 'jumlah_orang'));
$totalPorsi = 0;
foreach ($penerimaList as $p) {
    if ($p['is_active']) {
        $totalPorsi += $p['jumlah_orang'] * $p['faktor_porsi'];
    }
}

$kategoriList = [
    'balita' => ['label' => 'Balita (0-5 thn)', 'faktor' => 0.50, 'color' => 'pink'],
    'anak' => ['label' => 'Anak (6-12 thn)', 'faktor' => 0.75, 'color' => 'blue'],
    'dewasa' => ['label' => 'Dewasa (13-59 thn)', 'faktor' => 1.00, 'color' => 'green'],
    'lansia' => ['label' => 'Lansia (60+ thn)', 'faktor' => 0.80, 'color' => 'purple']
];
?>

<script>setPageTitle('Penerima Makanan', 'Kelola data anak/penerima');</script>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-teal-500 flex items-center justify-center shadow-lg">
            <i class="fas fa-users text-white"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">Penerima Makanan</h1>
            <p class="text-sm text-gray-400">
                <?= count($penerimaList) ?> kelompok • <?= number_format($totalOrang) ?> orang
            </p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="/gudang-gizi/modules/penerima/calculator.php"
            class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white flex items-center gap-2">
            <i class="fas fa-calculator"></i> Kalkulator Porsi
        </a>
        <button onclick="openModal('add')"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 text-white flex items-center gap-2 shadow-lg shadow-cyan-500/20 transition-all">
            <i class="fas fa-plus"></i> Tambah Penerima
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ($kategoriList as $key => $info):
        $count = array_sum(array_map(fn($p) => $p['kategori'] === $key ? $p['jumlah_orang'] : 0, $penerimaList));
        ?>
        <div class="glass rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-<?= $info['color'] ?>-500/20 text-<?= $info['color'] ?>-400 flex items-center justify-center">
                    <i
                        class="fas fa-<?= $key === 'balita' ? 'baby' : ($key === 'anak' ? 'child' : ($key === 'lansia' ? 'person-cane' : 'user')) ?>"></i>
                </div>
                <div>
                    <p class="text-lg font-bold text-white"><?= number_format($count) ?></p>
                    <p class="text-xs text-gray-400"><?= $info['label'] ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Total Porsi Info -->
<div class="glass rounded-xl p-4 mb-6 bg-gradient-to-r from-teal-500/10 to-cyan-500/10 border border-teal-500/20">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-teal-500 flex items-center justify-center">
                <i class="fas fa-calculator text-white text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-400">Total Kebutuhan Porsi (Aktif)</p>
                <p class="text-3xl font-bold text-teal-400"><?= number_format($totalPorsi, 1) ?> <span
                        class="text-lg text-gray-400">porsi</span></p>
            </div>
        </div>
        <div class="text-sm text-gray-400">
            <p><i class="fas fa-info-circle mr-1"></i> Porsi dihitung berdasarkan faktor usia:</p>
            <p class="text-xs mt-1">Balita ×0.5 | Anak ×0.75 | Dewasa ×1.0 | Lansia ×0.8</p>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="glass rounded-xl p-4 mb-6">
    <form method="GET" class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Cari</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm"
                placeholder="Nama/kode/lokasi...">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Kategori</label>
            <select name="kategori"
                class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $key => $info): ?>
                    <option value="<?= $key ?>" <?= $kategori_filter === $key ? 'selected' : '' ?>><?= $info['label'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="bg-slate-800 text-gray-400 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Penerima List -->
<div class="glass rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full table-modern">
            <thead>
                <tr class="text-left text-sm text-gray-400">
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Kelompok</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 hidden md:table-cell">Lokasi</th>
                    <th class="px-4 py-3 text-center">Jumlah</th>
                    <th class="px-4 py-3 text-center">Faktor</th>
                    <th class="px-4 py-3 text-center">Total Porsi</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <?php if (empty($penerimaList)): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3"></i>
                            <p>Belum ada data penerima</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($penerimaList as $item):
                        $info = $kategoriList[$item['kategori']] ?? ['label' => $item['kategori'], 'color' => 'gray'];
                        $totalPorsiItem = $item['jumlah_orang'] * $item['faktor_porsi'];
                        ?>
                        <tr class="hover:bg-slate-700/30 <?= !$item['is_active'] ? 'opacity-50' : '' ?>">
                            <td class="px-4 py-3 font-mono text-sm text-cyan-400">
                                <?= $item['kode'] ?>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-white"><?= htmlspecialchars($item['nama']) ?></p>
                                <?php if ($item['catatan']): ?>
                                    <p class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($item['catatan']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 rounded-lg text-xs font-medium bg-<?= $info['color'] ?>-500/20 text-<?= $info['color'] ?>-400">
                                    <?= $info['label'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-gray-400">
                                <?= htmlspecialchars($item['lokasi'] ?: '-') ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-lg font-bold text-white"><?= $item['jumlah_orang'] ?></span>
                                <span class="text-xs text-gray-500">org</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400">
                                ×<?= number_format($item['faktor_porsi'], 2) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-lg font-bold text-teal-400"><?= number_format($totalPorsiItem, 1) ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit"
                                        class="px-2 py-1 rounded-lg text-xs font-medium <?= $item['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' ?>">
                                        <?= $item['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button onclick="openModal('edit', <?= htmlspecialchars(json_encode($item)) ?>)"
                                        class="px-2 py-1 rounded-lg bg-slate-700 text-gray-300 hover:bg-slate-600 text-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Hapus penerima ini?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit"
                                            class="px-2 py-1 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="penerimaModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-slate-800 rounded-2xl w-full max-w-lg shadow-2xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h3 id="modalTitle" class="text-xl font-bold text-white">Tambah Penerima</h3>
        </div>
        <form method="POST" id="penerimaForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId">

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Kode *</label>
                        <input type="text" name="kode" id="formKode" required
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                            placeholder="Contoh: TK-001">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Kategori</label>
                        <select name="kategori" id="formKategori" onchange="updateFaktor()"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                            <?php foreach ($kategoriList as $key => $info): ?>
                                <option value="<?= $key ?>" data-faktor="<?= $info['faktor'] ?>"><?= $info['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nama Kelompok/Penerima *</label>
                    <input type="text" name="nama" id="formNama" required
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                        placeholder="Contoh: TK Mawar Indah">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Lokasi</label>
                    <input type="text" name="lokasi" id="formLokasi"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                        placeholder="Contoh: Jl. Merdeka No. 10">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Jumlah Orang *</label>
                        <input type="number" name="jumlah_orang" id="formJumlah" value="1" min="1" required
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Faktor Porsi</label>
                        <input type="number" name="faktor_porsi" id="formFaktor" value="1.00" min="0.1" max="2"
                            step="0.05"
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                        <p class="text-xs text-gray-500 mt-1">Auto berdasarkan kategori</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Catatan</label>
                    <textarea name="catatan" id="formCatatan" rows="2"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white"
                        placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>

            <div class="p-6 border-t border-slate-700 flex gap-3">
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-4 py-2 rounded-xl bg-slate-700 text-gray-300 hover:bg-slate-600 transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-teal-500 text-white font-medium">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const faktorDefaults = {
        balita: 0.50,
        anak: 0.75,
        dewasa: 1.00,
        lansia: 0.80
    };

    function updateFaktor() {
        const kategori = document.getElementById('formKategori').value;
        document.getElementById('formFaktor').value = faktorDefaults[kategori] || 1.00;
    }

    function openModal(action, data = null) {
        const modal = document.getElementById('penerimaModal');
        const title = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');

        if (action === 'edit' && data) {
            title.textContent = 'Edit Penerima';
            formAction.value = 'edit';
            document.getElementById('formId').value = data.id;
            document.getElementById('formKode').value = data.kode;
            document.getElementById('formKode').readOnly = true;
            document.getElementById('formNama').value = data.nama;
            document.getElementById('formKategori').value = data.kategori;
            document.getElementById('formLokasi').value = data.lokasi || '';
            document.getElementById('formJumlah').value = data.jumlah_orang;
            document.getElementById('formFaktor').value = data.faktor_porsi;
            document.getElementById('formCatatan').value = data.catatan || '';
        } else {
            title.textContent = 'Tambah Penerima';
            formAction.value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('formKode').value = '';
            document.getElementById('formKode').readOnly = false;
            document.getElementById('formNama').value = '';
            document.getElementById('formKategori').value = 'anak';
            document.getElementById('formLokasi').value = '';
            document.getElementById('formJumlah').value = 1;
            document.getElementById('formFaktor').value = 0.75;
            document.getElementById('formCatatan').value = '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        document.getElementById('penerimaModal').classList.add('hidden');
        document.getElementById('penerimaModal').classList.remove('flex');
    }

    document.getElementById('penerimaModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
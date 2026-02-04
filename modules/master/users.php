<?php
/**
 * Master Users
 * Gudang Gizi - Sistem Manajemen Stok
 */

require_once __DIR__ . '/../../includes/header.php';

// Check permission - Owner only
if (!hasPermission(['owner'])) {
    redirectWith('/gudang-gizi/index.php', 'Anda tidak memiliki akses ke halaman ini', 'error');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Can't delete yourself
    if ($id === $currentUser['id']) {
        redirectWith('/gudang-gizi/modules/master/users.php', 'Tidak dapat menghapus akun sendiri', 'error');
    }

    query("UPDATE users SET is_active = 0 WHERE id = ?", [$id], 'i');
    logActivity($currentUser['id'], 'delete_user', "Menonaktifkan user ID: $id");
    redirectWith('/gudang-gizi/modules/master/users.php', 'User berhasil dinonaktifkan', 'success');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    verifyCsrf($_SERVER['PHP_SELF']);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $username = sanitize($_POST['username']);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);
    $password = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];

    if (empty($username))
        $errors[] = 'Username harus diisi';
    if (empty($nama_lengkap))
        $errors[] = 'Nama lengkap harus diisi';
    if (!in_array($role, ['owner', 'admin', 'gudang']))
        $errors[] = 'Role tidak valid';

    // Check duplicate username
    $checkUser = fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id], 'si');
    if ($checkUser) {
        $errors[] = 'Username sudah digunakan';
    }

    if ($id == 0 && empty($password)) {
        $errors[] = 'Password harus diisi untuk user baru';
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, email = ?, phone = ?, role = ?, is_active = ?";
            $params = [$username, $nama_lengkap, $email, $phone, $role, $is_active];
            $types = "sssssi";

            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = hashPassword($password);
                $types .= "s";
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";

            query($sql, $params, $types);
            logActivity($currentUser['id'], 'update_user', "Mengupdate user: $username");
            redirectWith('/gudang-gizi/modules/master/users.php', 'User berhasil diupdate', 'success');
        } else {
            $hashedPassword = hashPassword($password);
            query(
                "INSERT INTO users (username, password, nama_lengkap, email, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$username, $hashedPassword, $nama_lengkap, $email, $phone, $role, $is_active],
                'ssssssi'
            );
            logActivity($currentUser['id'], 'create_user', "Menambah user: $username");
            redirectWith('/gudang-gizi/modules/master/users.php', 'User berhasil ditambahkan', 'success');
        }
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
    }
}

// Get data
$users = fetchAll("SELECT * FROM users ORDER BY role, nama_lengkap ASC");

$editItem = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editItem = fetchOne("SELECT * FROM users WHERE id = ?", [(int) $_GET['edit']], 'i');
}
?>

<script>setPageTitle('Manajemen User', 'Kelola akun pengguna sistem');</script>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-6 sticky top-24">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-<?= $editItem ? 'edit' : 'user-plus' ?> text-primary-400"></i>
                <?= $editItem ? 'Edit User' : 'Tambah User' ?>
            </h3>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Username *</label>
                    <input type="text" name="username" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['username'] ?? '') ?>" placeholder="Username untuk login">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Password
                        <?= $editItem ? '' : '*' ?>
                    </label>
                    <input type="password" name="password" <?= $editItem ? '' : 'required' ?> class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white
                    focus:border-primary-500" placeholder="
                    <?= $editItem ? 'Kosongkan jika tidak ingin mengubah' : 'Password untuk login' ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['nama_lengkap'] ?? '') ?>"
                        placeholder="Nama lengkap user">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" name="email"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['email'] ?? '') ?>" placeholder="email@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">No. Telepon</label>
                    <input type="text" name="phone"
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500"
                        value="<?= htmlspecialchars($editItem['phone'] ?? '') ?>" placeholder="Nomor telepon">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Role *</label>
                    <select name="role" required
                        class="w-full bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-white focus:border-primary-500">
                        <option value="gudang" <?= ($editItem['role'] ?? '') === 'gudang' ? 'selected' : '' ?>>Staff
                            Gudang</option>
                        <option value="admin" <?= ($editItem['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin
                        </option>
                        <option value="owner" <?= ($editItem['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Owner
                        </option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        <span class="text-primary-400">Owner:</span> Akses penuh |
                        <span class="text-blue-400">Admin:</span> Master data |
                        <span class="text-orange-400">Gudang:</span> Transaksi
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" class="sr-only peer" <?= ($editItem['is_active'] ?? true) ? 'checked' : '' ?>>
                        <div
                            class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-500">
                        </div>
                    </label>
                    <span class="text-sm text-gray-300">Status Aktif</span>
                </div>

                <div class="flex gap-2">
                    <?php if ($editItem): ?>
                        <a href="/gudang-gizi/modules/master/users.php"
                            class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-center transition-colors">
                            Batal
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="flex-1 btn-primary px-4 py-3 rounded-xl text-white font-medium">
                        <i class="fas fa-save mr-2"></i>
                        <?= $editItem ? 'Update' : 'Simpan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="lg:col-span-2">
        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-users text-primary-400"></i>
                    Daftar User
                </h3>
                <span class="text-sm text-gray-400">
                    <?= count($users) ?> user
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-modern">
                    <thead>
                        <tr class="text-left text-sm text-gray-400">
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3 hidden md:table-cell">Kontak</th>
                            <th class="px-4 py-3 text-center">Role</th>
                            <th class="px-4 py-3 text-center hidden lg:table-cell">Login Terakhir</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-700/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white
                                        <?= $user['role'] === 'owner' ? 'bg-gradient-to-br from-primary-500 to-primary-600' :
                                            ($user['role'] === 'admin' ? 'bg-gradient-to-br from-blue-500 to-blue-600' :
                                                'bg-gradient-to-br from-orange-500 to-orange-600') ?>">
                                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-white">
                                                <?= htmlspecialchars($user['nama_lengkap']) ?>
                                            </p>
                                            <p class="text-xs text-gray-400">@
                                                <?= htmlspecialchars($user['username']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-sm">
                                    <?php if ($user['email']): ?>
                                        <p class="text-gray-300">
                                            <?= htmlspecialchars($user['email']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($user['phone']): ?>
                                        <p class="text-gray-400 text-xs">
                                            <?= htmlspecialchars($user['phone']) ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $roleClass = match ($user['role']) {
                                        'owner' => 'bg-primary-500/20 text-primary-400',
                                        'admin' => 'bg-blue-500/20 text-blue-400',
                                        default => 'bg-orange-500/20 text-orange-400'
                                    };
                                    ?>
                                    <span
                                        class="inline-block px-2 py-1 rounded-lg text-xs font-medium capitalize <?= $roleClass ?>">
                                        <?= $user['role'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center hidden lg:table-cell text-sm text-gray-400">
                                    <?= $user['last_login'] ? formatTanggal($user['last_login'], true) : '-' ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($user['is_active']): ?>
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-green-500/20 text-green-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="?edit=<?= $user['id'] ?>"
                                            class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] !== $currentUser['id']): ?>
                                            <button
                                                onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')"
                                                class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
        <div class="bg-slate-800 rounded-2xl shadow-2xl p-6 border border-slate-700">
            <div class="text-center mb-6">
                <div
                    class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-slash text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Nonaktifkan User?</h3>
                <p class="text-gray-400">Apakah Anda yakin ingin menonaktifkan user <span id="deleteName"
                        class="text-white font-medium"></span>?</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal()"
                    class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-colors">
                    Batal
                </button>
                <a id="deleteBtn" href="#"
                    class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-center transition-colors">
                    Ya, Nonaktifkan
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteBtn').href = '?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php
check_admin();

$pdo = get_connection();

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $username = sanitize($_POST['username']);
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $role = sanitize($_POST['role']);
        $password = $_POST['password'];

        if ($action === 'add') {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashed, $nama_lengkap, $role]);
                audit_log('Tambah', 'users', $pdo->lastInsertId(), "User: $username");
                set_flash('Pengguna berhasil ditambahkan');
            } else {
                set_flash('Password harus diisi!', 'danger');
                header('Location: index.php?page=users');
                exit;
            }
        } else {
            $id = (int)$_POST['id'];
            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, role=? WHERE id=?");
                $stmt->execute([$username, $hashed, $nama_lengkap, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=?, role=? WHERE id=?");
                $stmt->execute([$username, $nama_lengkap, $role, $id]);
            }
            audit_log('Edit', 'users', $id, "User: $username");
            set_flash('Pengguna berhasil diperbarui');
        }
        header('Location: index.php?page=users');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['user_id']) {
        set_flash('Tidak dapat menghapus akun sendiri!', 'danger');
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        audit_log('Hapus', 'users', $id, 'Hapus user');
        set_flash('Pengguna berhasil dihapus');
    }
    header('Location: index.php?page=users');
    exit;
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-1"></i> Manajemen Pengguna</span>
        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="resetModalUser()">
            <i class="bi bi-plus-lg"></i> Tambah User
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Bergabung</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-3">Belum ada pengguna</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= sanitize($u['username']) ?></strong></td>
                            <td><?= sanitize($u['nama_lengkap']) ?></td>
                            <td>
                                <?php if ($u['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Bendahara</span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_datetime($u['created_at']) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal"
                                    data-bs-target="#modalUser"
                                    data-id="<?= $u['id'] ?>"
                                    data-username="<?= sanitize($u['username']) ?>"
                                    data-nama_lengkap="<?= sanitize($u['nama_lengkap']) ?>"
                                    data-role="<?= $u['role'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="index.php?page=users&delete=<?= $u['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    data-confirm="Yakin ingin menghapus user <?= sanitize($u['username']) ?>?">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal User -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Form Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="id" value="">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="bendahara">Bendahara</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password <small class="text-secondary">(kosongkan jika tidak diubah)</small></label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-accent">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetModalUser() {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('username').value = '';
    document.getElementById('nama_lengkap').value = '';
    document.getElementById('role').value = 'bendahara';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
}

document.querySelectorAll('[data-bs-target="#modalUser"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (!id) {
            resetModalUser();
            return;
        }
        document.getElementById('action').value = 'edit';
        document.getElementById('id').value = id;
        document.getElementById('username').value = btn.getAttribute('data-username') || '';
        document.getElementById('nama_lengkap').value = btn.getAttribute('data-nama_lengkap') || '';
        document.getElementById('role').value = btn.getAttribute('data-role') || 'bendahara';
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
    });
});
</script>

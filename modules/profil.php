<?php
$pdo = get_connection();

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'admin') {
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, nama_lengkap=? WHERE id=?");
            $stmt->execute([$username, $hashed, $nama_lengkap, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=? WHERE id=?");
            $stmt->execute([$username, $nama_lengkap, $_SESSION['user_id']]);
        }

        $_SESSION['nama_lengkap'] = $nama_lengkap;
        audit_log('Edit', 'users', $_SESSION['user_id'], 'Update profil admin');
        set_flash('Profil admin berhasil diperbarui');
        header('Location: index.php?page=profil');
        exit;

    } elseif ($type === 'masjid') {
        $fields = ['nama_masjid', 'alamat_masjid', 'telepon_masjid', 'email_masjid', 'website_masjid'];
        foreach ($fields as $field) {
            $value = sanitize($_POST[$field] ?? '');
            $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
            $stmt->execute([$field, $value]);
        }
        audit_log('Edit', 'settings', null, 'Update profil masjid');
        set_flash('Profil masjid berhasil diperbarui');
        header('Location: index.php?page=profil');
        exit;
    }
}

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$nama_masjid = get_setting('nama_masjid') ?: 'Masjid Al-Hidayah';
$alamat_masjid = get_setting('alamat_masjid') ?: '';
$telepon_masjid = get_setting('telepon_masjid') ?: '';
$email_masjid = get_setting('email_masjid') ?: '';
$website_masjid = get_setting('website_masjid') ?: '';
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-person-circle me-1"></i> Profil Admin
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="admin">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control"
                            value="<?= sanitize($user['nama_lengkap']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control"
                            value="<?= sanitize($user['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-secondary">(kosongkan jika tidak diubah)</small></label>
                        <div class="input-group">
                            <input type="password" name="password" id="passAdmin" class="form-control">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passAdmin')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-building me-1"></i> Profil Masjid
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="masjid">
                    <div class="mb-3">
                        <label class="form-label">Nama Masjid</label>
                        <input type="text" name="nama_masjid" class="form-control"
                            value="<?= sanitize($nama_masjid) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat_masjid" class="form-control" rows="2"><?= sanitize($alamat_masjid) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon_masjid" class="form-control"
                            value="<?= sanitize($telepon_masjid) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email_masjid" class="form-control"
                            value="<?= sanitize($email_masjid) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" name="website_masjid" class="form-control"
                            value="<?= sanitize($website_masjid) ?>">
                    </div>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$pdo = get_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $kode_akun = sanitize($_POST['kode_akun']);
        $nama_akun = sanitize($_POST['nama_akun']);
        $kategori = sanitize($_POST['kategori']);
        $saldo_normal = sanitize($_POST['saldo_normal']);
        $jenis_dana = sanitize($_POST['jenis_dana'] ?? 'tidak_terikat');

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO akun (kode_akun, nama_akun, kategori, saldo_normal, jenis_dana) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$kode_akun, $nama_akun, $kategori, $saldo_normal, $jenis_dana]);
            audit_log('Tambah', 'akun', $pdo->lastInsertId(), "Akun: $kode_akun - $nama_akun");
            set_flash('Akun berhasil ditambahkan');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE akun SET kode_akun=?, nama_akun=?, kategori=?, saldo_normal=?, jenis_dana=? WHERE id=?");
            $stmt->execute([$kode_akun, $nama_akun, $kategori, $saldo_normal, $jenis_dana, $id]);
            audit_log('Edit', 'akun', $id, "Akun: $kode_akun - $nama_akun");
            set_flash('Akun berhasil diperbarui');
        }
        header('Location: index.php?page=akun');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $akun = get_akun($id);
    if ($akun) {
        $stmt = $pdo->prepare("DELETE FROM akun WHERE id=?");
        $stmt->execute([$id]);
        audit_log('Hapus', 'akun', $id, "Akun: {$akun['kode_akun']} - {$akun['nama_akun']}");
        set_flash('Akun berhasil dihapus');
    }
    header('Location: index.php?page=akun');
    exit;
}

$akun_list = get_all_akun();
$kategori_list = ['Aset', 'Kewajiban', 'Aset Neto', 'Pendapatan', 'Beban', 'Dana Khusus'];
$saldo_normal_list = ['Debit', 'Kredit'];
$jenis_dana_list = ['terikat', 'tidak_terikat', 'neto'];
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list me-1"></i> Daftar Akun</span>
        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalAkun" onclick="resetModal('modalAkun')">
            <i class="bi bi-plus-lg"></i> Tambah Akun
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Kategori</th>
                        <th>Jenis Dana</th>
                        <th>Saldo Normal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($akun_list)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-3">Belum ada akun</td></tr>
                    <?php else: ?>
                        <?php foreach ($akun_list as $a): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= sanitize($a['kode_akun']) ?></span></td>
                            <td class="fw-medium"><?= sanitize($a['nama_akun']) ?></td>
                            <td>
                                <?php
                                $badge_color = match($a['kategori']) {
                                    'Aset' => 'primary',
                                    'Kewajiban' => 'warning',
                                    'Aset Neto' => 'info',
                                    'Pendapatan' => 'success',
                                    'Beban' => 'danger',
                                    'Dana Khusus' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge_color ?>"><?= $a['kategori'] ?></span>
                            </td>
                            <td><small class="text-secondary"><?= $a['jenis_dana'] ?? 'tidak_terikat' ?></small></td>
                            <td><?= $a['saldo_normal'] ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal"
                                    data-bs-target="#modalAkun"
                                    data-id="<?= $a['id'] ?>"
                                    data-kode_akun="<?= sanitize($a['kode_akun']) ?>"
                                    data-nama_akun="<?= sanitize($a['nama_akun']) ?>"
                                    data-kategori="<?= $a['kategori'] ?>"
                                    data-saldo_normal="<?= $a['saldo_normal'] ?>"
                                    data-jenis_dana="<?= $a['jenis_dana'] ?? 'tidak_terikat' ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="index.php?page=akun&delete=<?= $a['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    data-confirm="Yakin ingin menghapus akun <?= sanitize($a['nama_akun']) ?>?">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalAkun" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Form Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="id" value="">

                <div class="mb-3">
                    <label class="form-label">Kode Akun</label>
                    <input type="text" name="kode_akun" id="kode_akun" class="form-control" placeholder="Contoh: 1-1000" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Akun</label>
                    <input type="text" name="nama_akun" id="nama_akun" class="form-control" placeholder="Nama akun" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" id="kategori" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jenis Dana</label>
                            <select name="jenis_dana" id="jenis_dana" class="form-select">
                                <?php foreach ($jenis_dana_list as $j): ?>
                                <option value="<?= $j ?>"><?= ucfirst(str_replace('_', ' ', $j)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-secondary">Terikat = dana dengan batasan donatur</small>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Saldo Normal</label>
                    <select name="saldo_normal" id="saldo_normal" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($saldo_normal_list as $s): ?>
                        <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
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
function resetModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('kode_akun').value = '';
    document.getElementById('nama_akun').value = '';
    document.getElementById('kategori').value = '';
    document.getElementById('saldo_normal').value = '';
    document.getElementById('jenis_dana').value = 'tidak_terikat';
}

document.querySelectorAll('[data-bs-target="#modalAkun"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (!id) { resetModal('modalAkun'); return; }
        document.getElementById('action').value = 'edit';
        document.getElementById('id').value = id;
        document.getElementById('kode_akun').value = btn.getAttribute('data-kode_akun') || '';
        document.getElementById('nama_akun').value = btn.getAttribute('data-nama_akun') || '';
        document.getElementById('kategori').value = btn.getAttribute('data-kategori') || '';
        document.getElementById('saldo_normal').value = btn.getAttribute('data-saldo_normal') || '';
        document.getElementById('jenis_dana').value = btn.getAttribute('data-jenis_dana') || 'tidak_terikat';
    });
});
</script>

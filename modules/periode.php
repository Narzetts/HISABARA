<?php
check_admin();
$pdo = get_connection();

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $bulan = (int)$_POST['bulan'];
        $tahun = (int)$_POST['tahun'];
        $nama = sanitize($_POST['nama'] ?: bulan_nama($bulan) . " $tahun");

        $stmt = $pdo->prepare("INSERT INTO periode (bulan, tahun, nama) VALUES (?, ?, ?)");
        $stmt->execute([$bulan, $tahun, $nama]);
        audit_log('Tambah', 'periode', $pdo->lastInsertId(), "Periode: $nama");
        set_flash("Periode $nama berhasil ditambahkan");
        header('Location: index.php?page=periode');
        exit;
    }

    if ($action === 'tutup') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE periode SET status='tutup' WHERE id=?")->execute([$id]);
        audit_log('Tutup', 'periode', $id, 'Tutup buku periode');
        set_flash('Periode berhasil ditutup. Transaksi pada periode ini tidak bisa diubah lagi.');
        header('Location: index.php?page=periode');
        exit;
    }

    if ($action === 'buka') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE periode SET status='aktif' WHERE id=?")->execute([$id]);
        audit_log('Buka', 'periode', $id, 'Buka kembali periode');
        set_flash('Periode berhasil dibuka kembali.');
        header('Location: index.php?page=periode');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $periode = get_periode($id);
    if ($periode && $periode['status'] == 'tutup') {
        set_flash('Periode yang sudah ditutup tidak bisa dihapus', 'danger');
    } else {
        $pdo->prepare("DELETE FROM periode WHERE id=?")->execute([$id]);
        audit_log('Hapus', 'periode', $id, 'Hapus periode');
        set_flash('Periode berhasil dihapus');
    }
    header('Location: index.php?page=periode');
    exit;
}

function bulan_nama($b) {
    $nama = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $nama[$b] ?? '';
}

$periode_list = get_all_periode();
$tahun_terpilih = $_GET['tahun'] ?? date('Y');
?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Tambah Periode</div>
            <div class="card-body">
                <form method="POST" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="col-5">
                        <select name="bulan" class="form-select" required>
                            <option value="">Bulan</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>"><?= bulan_nama($m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <select name="tahun" class="form-select" required>
                            <option value="">Tahun</option>
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <button class="btn btn-accent w-100"><i class="bi bi-plus"></i></button>
                    </div>
                    <div class="col-12">
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama periode (opsional)">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i> Info Periode</div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li>Periode <strong>aktif</strong> = transaksi bisa ditambah/diedit/dihapus</li>
                    <li>Periode <strong>tutup</strong> = transaksi terkunci (tidak bisa diubah)</li>
                    <li>Tutup buku setelah laporan bulanan final</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3 me-1"></i> Daftar Periode</span>
        <span class="text-secondary small"><?= count($periode_list) ?> periode</span>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periode_list)): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada periode</td></tr>
                    <?php else: ?>
                        <?php foreach ($periode_list as $p): ?>
                        <tr>
                            <td><?= sanitize($p['nama']) ?></td>
                            <td><?= bulan_nama((int)$p['bulan']) ?></td>
                            <td><?= $p['tahun'] ?></td>
                            <td>
                                <?php if ($p['status'] == 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Tutup</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($p['status'] == 'aktif'): ?>
                                <form method="POST" class="d-inline" data-confirm="Tutup periode <?= sanitize($p['nama']) ?>? Transaksi tidak bisa diubah lagi." data-danger>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="tutup">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-lock"></i> Tutup</button>
                                </form>
                                <a href="index.php?page=periode&delete=<?= $p['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    data-confirm="Hapus periode <?= sanitize($p['nama']) ?>?">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="buka">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success"><i class="bi bi-unlock"></i> Buka</button>
                                </form>
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

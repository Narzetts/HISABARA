<?php
$pdo = get_connection();

$jenis_filter = $_GET['jenis'] ?? '';
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$periode = $_GET['periode'] ?? '';
$status_filter = $_GET['status'] ?? '';

if ($periode) {
    $pecah = explode('-', $periode);
    if (count($pecah) == 2) {
        $dari = $pecah[0] . '-' . $pecah[1] . '-01';
        $sampai = date('Y-m-t', strtotime($dari));
    }
}

$dana_khusus_list = get_all_dana_khusus();

function cek_lock_periode($tanggal) {
    if (is_periode_tutup($tanggal)) {
        set_flash('Periode ini sudah ditutup. Transaksi tidak bisa diubah.', 'danger');
        header('Location: index.php?page=transaksi');
        exit;
    }
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $tanggal = $_POST['tanggal'];
        $keterangan = sanitize($_POST['keterangan']);
        $jenis = $_POST['jenis'];
        $akun_id = (int)$_POST['akun_id'];
        $jumlah = str_replace(['.', ','], ['', '.'], $_POST['jumlah']);
        $jumlah = (float)$jumlah;
        $sumber_dana = sanitize($_POST['sumber_dana'] ?? '');
        $metode_bayar = sanitize($_POST['metode_bayar'] ?? '');
        $tujuan = sanitize($_POST['tujuan'] ?? '');
        $dana_khusus_id = $_POST['dana_khusus_id'] ? (int)$_POST['dana_khusus_id'] : null;

        if ($tanggal && $keterangan && $akun_id && $jumlah > 0) {
            cek_lock_periode($tanggal);
            $kas_id = get_akun_kas();

            $status = is_admin() ? 'approved' : 'pending';

            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, keterangan, sumber_dana, metode_bayar, tujuan, dana_khusus_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tanggal, $keterangan, $sumber_dana, $metode_bayar, $tujuan, $dana_khusus_id, $_SESSION['user_id'], $status]);
            $transaksi_id = $pdo->lastInsertId();

            // Handle file upload
            if (!empty($_FILES['bukti_file']) && $_FILES['bukti_file']['error'] === UPLOAD_ERR_OK) {
                $err = validate_upload_file($_FILES['bukti_file']);
                if (!$err) {
                    $path = save_upload_file($_FILES['bukti_file'], 'bukti');
                    if ($path) {
                        $pdo->prepare("UPDATE transaksi SET bukti_file = ? WHERE id = ?")->execute([$path, $transaksi_id]);
                    }
                }
            }

            if ($status === 'approved') {
                if ($jenis === 'Pemasukan') {
                    $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$transaksi_id, $kas_id, $jumlah]);
                    $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$transaksi_id, $akun_id, $jumlah]);
                } else {
                    $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$transaksi_id, $akun_id, $jumlah]);
                    $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$transaksi_id, $kas_id, $jumlah]);
                }
                audit_log('Tambah', 'transaksi', $transaksi_id, "$jenis: $keterangan - " . format_rupiah($jumlah));
            } else {
                audit_log('Tambah (Pending)', 'transaksi', $transaksi_id, "$jenis: $keterangan - " . format_rupiah($jumlah));
            }

            set_flash('Transaksi berhasil ' . ($status === 'pending' ? 'ditambahkan, menunggu persetujuan admin.' : 'ditambahkan.'));
            header('Location: index.php?page=transaksi');
            exit;
        } else {
            set_flash('Data transaksi tidak valid!', 'danger');
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $tanggal = $_POST['tanggal'];
        $keterangan = sanitize($_POST['keterangan']);
        $jenis = $_POST['jenis'];
        $akun_id = (int)$_POST['akun_id'];
        $jumlah = str_replace(['.', ','], ['', '.'], $_POST['jumlah']);
        $jumlah = (float)$jumlah;
        $sumber_dana = sanitize($_POST['sumber_dana'] ?? '');
        $metode_bayar = sanitize($_POST['metode_bayar'] ?? '');
        $tujuan = sanitize($_POST['tujuan'] ?? '');
        $dana_khusus_id = $_POST['dana_khusus_id'] ? (int)$_POST['dana_khusus_id'] : null;

        if ($id && $tanggal && $keterangan && $akun_id && $jumlah > 0) {
            cek_lock_periode($tanggal);
            $kas_id = get_akun_kas();

            // Get old data for audit
            $old = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
            $old->execute([$id]);
            $old_data = $old->fetch();

            $pdo->prepare("UPDATE transaksi SET tanggal=?, keterangan=?, sumber_dana=?, metode_bayar=?, tujuan=?, dana_khusus_id=? WHERE id=?")
                ->execute([$tanggal, $keterangan, $sumber_dana, $metode_bayar, $tujuan, $dana_khusus_id, $id]);

            // Handle file upload
            if (!empty($_FILES['bukti_file']) && $_FILES['bukti_file']['error'] === UPLOAD_ERR_OK) {
                $err = validate_upload_file($_FILES['bukti_file']);
                if (!$err) {
                    if ($old_data && $old_data['bukti_file']) {
                        delete_upload_file($old_data['bukti_file']);
                    }
                    $path = save_upload_file($_FILES['bukti_file'], 'bukti');
                    if ($path) {
                        $pdo->prepare("UPDATE transaksi SET bukti_file = ? WHERE id = ?")->execute([$path, $id]);
                    }
                }
            }

            $pdo->prepare("DELETE FROM jurnal_detail WHERE transaksi_id=?")->execute([$id]);

            if ($jenis === 'Pemasukan') {
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$id, $kas_id, $jumlah]);
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$id, $akun_id, $jumlah]);
            } else {
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$id, $akun_id, $jumlah]);
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$id, $kas_id, $jumlah]);
            }

            audit_log('Edit', 'transaksi', $id, "$jenis: $keterangan - " . format_rupiah($jumlah), json_encode($old_data));
            set_flash('Transaksi berhasil diperbarui');
            header('Location: index.php?page=transaksi');
            exit;
        }
    }

    if ($action === 'approve') {
        $id = (int)$_POST['id'];
        $trans = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
        $trans->execute([$id]);
        $trans = $trans->fetch();
        if ($trans && $trans['status'] == 'pending') {
            $kas_id = get_akun_kas();
            $detail = get_jumlah_transaksi_detail($id);
            $info = get_jenis_transaksi($detail);

            $jumlah = $info['jumlah'];
            $akun_id = $info['akun_lawan']['akun_id'] ?? null;
            $jenis = $info['jenis'];

            if ($jenis === 'Pemasukan') {
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$id, $kas_id, $jumlah]);
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$id, $akun_id, $jumlah]);
            } else {
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$id, $akun_id, $jumlah]);
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$id, $kas_id, $jumlah]);
            }

            $pdo->prepare("UPDATE transaksi SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")
                ->execute([$_SESSION['user_id'], $id]);
            audit_log('Approve', 'transaksi', $id, "Menyetujui transaksi: {$trans['keterangan']}");
            set_flash('Transaksi berhasil disetujui');
        }
        header('Location: index.php?page=transaksi');
        exit;
    }

    if ($action === 'reject') {
        $id = (int)$_POST['id'];
        $note = sanitize($_POST['rejected_note'] ?? '');
        $pdo->prepare("UPDATE transaksi SET status='rejected', rejected_note=?, approved_by=?, approved_at=NOW() WHERE id=?")
            ->execute([$note, $_SESSION['user_id'], $id]);
        audit_log('Reject', 'transaksi', $id, "Menolak transaksi: $note");
        set_flash('Transaksi ditolak');
        header('Location: index.php?page=transaksi');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT tanggal, bukti_file FROM transaksi WHERE id=?");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if ($t) {
        cek_lock_periode($t['tanggal']);
        if ($t['bukti_file']) delete_upload_file($t['bukti_file']);
    }
    $pdo->prepare("DELETE FROM transaksi WHERE id=?")->execute([$id]);
    audit_log('Hapus', 'transaksi', $id, 'Hapus transaksi');
    set_flash('Transaksi berhasil dihapus');
    header('Location: index.php?page=transaksi');
    exit;
}

// Filter query
$where = '';
$params = [];
if ($jenis_filter) {
    if ($jenis_filter == 'Pemasukan') {
        $where .= " AND t.id IN (SELECT DISTINCT jd.transaksi_id FROM jurnal_detail jd JOIN akun a ON jd.akun_id = a.id WHERE a.kategori = 'Pendapatan')";
    } elseif ($jenis_filter == 'Pengeluaran') {
        $where .= " AND t.id IN (SELECT DISTINCT jd.transaksi_id FROM jurnal_detail jd JOIN akun a ON jd.akun_id = a.id WHERE a.kategori = 'Beban')";
    }
}
if ($dari && $sampai) {
    $where .= " AND t.tanggal BETWEEN ? AND ?";
    $params = array_merge($params, [$dari, $sampai]);
}
if ($status_filter) {
    $where .= " AND t.status = ?";
    $params[] = $status_filter;
}

$stmt = $pdo->prepare("SELECT t.*, dk.nama_dana, u.nama_lengkap as user_name, au.nama_lengkap as approved_name FROM transaksi t LEFT JOIN dana_khusus dk ON t.dana_khusus_id = dk.id LEFT JOIN users u ON t.created_by = u.id LEFT JOIN users au ON t.approved_by = au.id WHERE 1=1 $where ORDER BY t.tanggal DESC, t.id DESC");
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll();

$akun_pendapatan = get_akun_by_kategori('Pendapatan');
$akun_beban = get_akun_by_kategori('Beban');
?>

<!-- Status tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= !$status_filter ? 'active' : '' ?>" href="index.php?page=transaksi">Semua</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="index.php?page=transaksi&status=pending">
            Perlu Disetujui
            <?php
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE status='pending'");
            $cnt->execute();
            $pc = $cnt->fetchColumn();
            if ($pc > 0) echo " <span class=\"badge bg-warning text-dark\">$pc</span>";
            ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $status_filter == 'approved' ? 'active' : '' ?>" href="index.php?page=transaksi&status=approved">Disetujui</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $status_filter == 'rejected' ? 'active' : '' ?>" href="index.php?page=transaksi&status=rejected">Ditolak</a>
    </li>
</ul>

<!-- Jenis tabs -->
<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link <?= !$jenis_filter ? 'active' : '' ?>" href="index.php?page=transaksi<?= $status_filter ? "&status=$status_filter" : '' ?>">Semua Jenis</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $jenis_filter == 'Pemasukan' ? 'active' : '' ?>" href="index.php?page=transaksi&jenis=Pemasukan<?= $status_filter ? "&status=$status_filter" : '' ?>">Pemasukan</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $jenis_filter == 'Pengeluaran' ? 'active' : '' ?>" href="index.php?page=transaksi&jenis=Pengeluaran<?= $status_filter ? "&status=$status_filter" : '' ?>">Pengeluaran</a>
    </li>
</ul>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="btn-group">
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#modalTransaksi" onclick="setModalJenis('Pemasukan')">
                <i class="bi bi-plus-circle"></i> Pemasukan
            </button>
            <button class="btn btn-outline-accent" data-bs-toggle="modal" data-bs-target="#modalTransaksi" onclick="setModalJenis('Pengeluaran')">
                <i class="bi bi-dash-circle"></i> Pengeluaran
            </button>
        </div>
        <button class="btn btn-sm btn-outline-warning ms-2" onclick="loadAI('anomali')">
            <i class="bi bi-shield-exclamation"></i> Deteksi Anomali
        </button>
        <button class="btn btn-sm btn-outline-danger ms-1" onclick="cekPerbaikanAI()">
            <i class="bi bi-tools"></i> Analisis & Perbaikan
        </button>
        <div id="aiTransaksiResult" class="ai-response mt-2" style="display:none;">
            <div class="ai-response-header"><i class="bi bi-robot"></i> HISA AI — Analisis Transaksi</div>
            <div class="ai-response-body" id="aiTransaksiBody"></div>
        </div>
        <div id="aiPerbaikanResult" class="ai-response mt-2" style="display:none;">
            <div class="ai-response-header"><i class="bi bi-tools"></i> HISA AI — Analisis & Perbaikan</div>
            <div class="ai-response-body" id="aiPerbaikanBody"></div>
        </div>
        <div id="aiCekStrukResult" class="ai-response mt-2" style="display:none;">
            <div class="ai-response-header"><i class="bi bi-receipt"></i> HISA AI — Analisis Struk
                <button class="btn-close btn-sm ms-auto" onclick="document.getElementById('aiCekStrukResult').style.display='none'"></button>
            </div>
            <div class="ai-response-body" id="aiCekStrukBody"></div>
        </div>
    </div>
    <div class="col-md-6">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="transaksi">
            <div class="col-3">
                <input type="date" name="dari" class="form-control form-control-sm" value="<?= $dari ?>">
            </div>
            <div class="col-3">
                <input type="date" name="sampai" class="form-control form-control-sm" value="<?= $sampai ?>">
            </div>
            <div class="col-3">
                <button class="btn btn-sm btn-accent w-100">Filter</button>
            </div>
            <div class="col-3">
                <a href="index.php?page=transaksi" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list me-1"></i> Daftar Transaksi</span>
        <span class="text-secondary small"><?= count($transaksi_list) ?> transaksi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Jenis</th>
                        <th>Akun</th>
                        <th>Status</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th>Bukti</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi_list)): ?>
                        <tr><td colspan="9" class="text-center text-secondary py-4">Belum ada transaksi</td></tr>
                    <?php else: ?>
                        <?php foreach ($transaksi_list as $t): ?>
                        <?php
                            $detail = get_jumlah_transaksi_detail($t['id']);
                            $info = get_jenis_transaksi($detail);
                            $debit = 0; $kredit = 0;
                            foreach ($detail as $d) {
                                if ($d['kode_akun'] == '1-1000') {
                                    $debit = $d['debit'];
                                    $kredit = $d['kredit'];
                                }
                            }
                        ?>
                        <tr class="<?= $t['status'] == 'pending' ? 'table-warning' : ($t['status'] == 'rejected' ? 'table-danger' : '') ?>">
                            <td><?= format_tanggal($t['tanggal']) ?></td>
                            <td>
                                <?= sanitize($t['keterangan']) ?>
                                <small class="d-block text-secondary">oleh: <?= sanitize($t['user_name'] ?? '-') ?></small>
                            </td>
                            <td>
                                <?php if ($info['jenis'] == 'Lainnya'): ?>
                                    <span class="badge bg-secondary"><?= $t['status'] == 'rejected' ? 'Ditolak' : 'Pending' ?></span>
                                <?php elseif ($info['jenis'] == 'Pemasukan'): ?>
                                    <span class="badge-pemasukan">Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge-pengeluaran">Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= $info['akun_lawan'] ? sanitize($info['akun_lawan']['nama_akun']) : '-' ?></small></td>
                            <td>
                                <?php if ($t['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($t['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Disetujui</span>
                                <?php elseif ($t['status'] == 'rejected'): ?>
                                    <span class="badge bg-danger" title="<?= sanitize($t['rejected_note']) ?>">Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end amount-text text-success"><?= $debit > 0 ? format_rupiah($debit) : '-' ?></td>
                            <td class="text-end amount-text text-danger"><?= $kredit > 0 ? format_rupiah($kredit) : '-' ?></td>
                            <td>
                                <?php if ($t['bukti_file']): ?>
                                    <div class="d-flex gap-1">
                                    <a href="<?= $t['bukti_file'] ?>" target="_blank" class="btn btn-sm btn-outline-accent" title="Lihat Bukti">
                                        <i class="bi bi-paperclip"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-warning cek-struk-btn" title="Cek Keaslian Struk"
                                        data-file="<?= realpath($t['bukti_file']) ?: $t['bukti_file'] ?>">
                                        <i class="bi bi-robot"></i>
                                    </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($t['status'] == 'pending' && is_admin()): ?>
                                    <form method="POST" class="d-inline" data-confirm="Setujui transaksi ini?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                        data-bs-target="#modalReject"
                                        data-id="<?= $t['id'] ?>">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal"
                                    data-bs-target="#modalTransaksi"
                                    data-id="<?= $t['id'] ?>"
                                    data-tanggal="<?= $t['tanggal'] ?>"
                                    data-keterangan="<?= sanitize($t['keterangan']) ?>"
                                    data-jenis="<?= $info['jenis'] ?>"
                                    data-akun_id="<?= $info['akun_lawan'] ? $info['akun_lawan']['akun_id'] : '' ?>"
                                    data-jumlah="<?= $info['jumlah'] ?>"
                                    data-sumber_dana="<?= sanitize($t['sumber_dana'] ?? '') ?>"
                                    data-metode_bayar="<?= sanitize($t['metode_bayar'] ?? '') ?>"
                                    data-tujuan="<?= sanitize($t['tujuan'] ?? '') ?>"
                                    data-dana_khusus_id="<?= $t['dana_khusus_id'] ?? '' ?>"
                                    data-bukti_file="<?= sanitize($t['bukti_file'] ?? '') ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($t['status'] != 'approved'): ?>
                                <a href="index.php?page=transaksi&delete=<?= $t['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    data-confirm="Yakin ingin menghapus transaksi ini?">
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

<!-- Modal Reject -->
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tolak Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="reject_id" value="">
                <div class="mb-3">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="rejected_note" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Transaksi -->
<div class="modal fade" id="modalTransaksi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Form Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="id" value="">
                <input type="hidden" name="jenis" id="jenis" value="Pemasukan">

                <div class="card mb-3 border-accent">
                    <div class="card-body">
                        <style>
        .ai-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1060;
            display: flex; align-items: center; justify-content: center;
            animation: aiFadeIn 0.2s ease;
        }
        .ai-confirm {
            background: var(--bs-body-bg, #fff);
            border-radius: 20px;
            width: 440px;
            max-width: 92vw;
            box-shadow: 0 25px 80px rgba(0,0,0,0.35);
            animation: aiSlideUp 0.35s cubic-bezier(0.16,1,0.3,1);
            overflow: hidden;
        }
        .ai-confirm-glow {
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6, #10b981);
            background-size: 200% 100%;
            animation: aiShimmer 2s linear infinite;
        }
        .ai-confirm-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 0.75rem;
        }
        .ai-confirm-icon.masuk { background: #d1fae5; color: #10b981; }
        .ai-confirm-icon.keluar { background: #fee2e2; color: #ef4444; }
        .ai-confirm-body { padding: 1.5rem 1.5rem 0; text-align: center; }
        .ai-confirm-footer { padding: 1rem 1.5rem 1.5rem; display: flex; gap: 0.75rem; }
        .ai-confirm-footer .btn { flex: 1; padding: 0.6rem; border-radius: 12px; font-weight: 600; }
        .ai-confirm-table { text-align: left; margin: 1rem 0; }
        .ai-confirm-table td { padding: 0.4rem 0; border: none; vertical-align: top; }
        .ai-confirm-table td:first-child { color: var(--bs-secondary-color, #64748b); width: 100px; }
        @keyframes aiFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes aiSlideUp { from { opacity: 0; transform: translateY(40px) scale(0.92); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes aiShimmer { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        </style>
        <div class="mb-2">
                            <label class="form-label fw-semibold"><i class="bi bi-robot text-accent me-1"></i>Deskripsikan Transaksi</label>
                            <div class="input-group">
                                <input type="text" id="aiPrompt" class="form-control" placeholder="Contoh: infak jumat 50000 dari jamaah" onkeypress="if(event.key==='Enter') prosesAI()">
                                <button class="btn btn-accent" type="button" onclick="prosesAI()">Proses</button>
                            </div>
                            <div id="aiPreview" class="ai-box mt-2" style="display:none;"></div>
                        </div>
        <!-- AI Confirm Popup -->
        <div id="aiConfirmPopup" class="ai-overlay" style="display:none;" onclick="if(event.target===this) tutupConfirm()">
                            <div class="ai-confirm">
                                <div class="ai-confirm-glow"></div>
                                <div class="ai-confirm-body">
                                    <div class="ai-confirm-icon masuk" id="aiConfirmIcon"><i class="bi bi-check-lg"></i></div>
                                    <h5 class="fw-bold mb-1">Konfirmasi & Edit Transaksi</h5>
                                    <p class="small text-secondary mb-2">Hasil analisis AI — bisa diedit sebelum ditambahkan:</p>
                                    <table class="ai-confirm-table w-100">
                                        <tr><td>Jenis</td><td><select id="aiCJenis" class="form-select form-select-sm"></select></td></tr>
                                        <tr><td>Jumlah</td><td style="position:relative;"><span style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.85rem;z-index:5;">Rp</span><input type="text" id="aiCJumlah" class="form-control" style="padding-left:28px;font-weight:700;color:var(--accent);"></td></tr>
                                        <tr><td>Keterangan</td><td><input type="text" id="aiCKeterangan" class="form-control"></td></tr>
                                        <tr><td>Akun</td><td><select id="aiCAkun" class="form-select form-select-sm"></select></td></tr>
                                        <tr><td>Tanggal</td><td><input type="date" id="aiCTanggal" class="form-control"></td></tr>
                                    </table>
                                </div>
                                <div class="ai-confirm-footer">
                                    <button class="btn btn-outline-secondary" onclick="tutupConfirm()">Batal</button>
                                    <button class="btn btn-accent" id="aiConfirmBtn" onclick="konfirmasiSimpan()"><i class="bi bi-check-lg"></i> Tambahkan Transaksi</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Transaksi</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success flex-fill" onclick="pilihJenis('Pemasukan')">
                            <i class="bi bi-plus-circle"></i> Pemasukan
                        </button>
                        <button type="button" class="btn btn-danger flex-fill" onclick="pilihJenis('Pengeluaran')">
                            <i class="bi bi-dash-circle"></i> Pengeluaran
                        </button>
                    </div>
                </div>

                <div id="jenisBadge" class="mb-3 text-center">
                    <span class="badge-pemasukan fs-6" id="jenisLabel">Pemasukan</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Dana Khusus</label>
                            <select name="dana_khusus_id" id="dana_khusus_id" class="form-select">
                                <option value="">-- Umum --</option>
                                <?php foreach ($dana_khusus_list as $dk): ?>
                                <option value="<?= $dk['id'] ?>"><?= sanitize($dk['nama_dana']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="2" required></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" id="akunLabel">Akun Pendapatan</label>
                            <select name="akun_id" id="akun_id" class="form-select" required>
                                <option value="">-- Pilih Akun --</option>
                                <optgroup label="Pendapatan" id="optPendapatan">
                                    <?php foreach ($akun_pendapatan as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= sanitize($a['kode_akun']) ?> - <?= sanitize($a['nama_akun']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Beban" id="optBeban">
                                    <?php foreach ($akun_beban as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= sanitize($a['kode_akun']) ?> - <?= sanitize($a['nama_akun']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jumlah (Rp)</label>
                            <input type="text" name="jumlah" id="jumlah" class="form-control" placeholder="0" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3" id="rowPemasukan">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Sumber Dana</label>
                            <input type="text" name="sumber_dana" id="sumber_dana" class="form-control" placeholder="Contoh: Jamaah, Donatur, PT ...">
                        </div>
                    </div>
                </div>

                <div class="row g-3" id="rowPengeluaran" style="display:none;">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tujuan Pembayaran</label>
                            <input type="text" name="tujuan" id="tujuan" class="form-control" placeholder="Kepada siapa">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Metode Bayar</label>
                            <select name="metode_bayar" id="metode_bayar" class="form-select">
                                <option value="Tunai">Tunai</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Bukti Struk <span class="text-danger">*</span> <small class="text-secondary">(JPG/PNG, max 5MB)</small></label>
                    <input type="file" name="bukti_file" id="bukti_file" class="form-control" accept=".jpg,.jpeg,.png">
                    <div id="bukti_file_current" class="small mt-1"></div>
                    <div id="buktiWarning" class="small text-danger mt-1" style="display:none;">Bukti struk wajib diisi</div>
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
function setModalJenis(jenis) {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('tanggal').value = '<?= date('Y-m-d') ?>';
    document.getElementById('keterangan').value = '';
    document.getElementById('jumlah').value = '';
    document.getElementById('sumber_dana').value = '';
    document.getElementById('tujuan').value = '';
    document.getElementById('metode_bayar').value = 'Tunai';
    document.getElementById('dana_khusus_id').value = '';
    document.getElementById('bukti_file').value = '';
    document.getElementById('bukti_file').required = false;
    document.getElementById('bukti_file_current').innerHTML = '';
    document.getElementById('buktiWarning').style.display = 'none';
    pilihJenis(jenis);
}

function pilihJenis(jenis) {
    document.getElementById('jenis').value = jenis;
    const label = document.getElementById('jenisLabel');
    const akunSelect = document.getElementById('akun_id');
    const rowPemasukan = document.getElementById('rowPemasukan');
    const rowPengeluaran = document.getElementById('rowPengeluaran');

    if (jenis === 'Pemasukan') {
        label.textContent = 'Pemasukan';
        label.className = 'badge-pemasukan fs-6';
        document.getElementById('akunLabel').textContent = 'Akun Pendapatan';
        for (const opt of akunSelect.options) {
            opt.style.display = opt.parentElement.id === 'optPendapatan' ? '' : 'none';
        }
        rowPemasukan.style.display = '';
        rowPengeluaran.style.display = 'none';
    } else {
        label.textContent = 'Pengeluaran';
        label.className = 'badge-pengeluaran fs-6';
        document.getElementById('akunLabel').textContent = 'Akun Beban';
        for (const opt of akunSelect.options) {
            opt.style.display = opt.parentElement.id === 'optBeban' ? '' : 'none';
        }
        rowPemasukan.style.display = 'none';
        rowPengeluaran.style.display = '';
    }
    akunSelect.value = '';
}

// Auto-fill from ai_keuangan parse redirect
(function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('ai_fill')) {
        const jenis = params.get('jenis');
        if (jenis === 'Pemasukan' || jenis === 'Pengeluaran') {
            setTimeout(() => {
                pilihJenis(jenis);
                if (params.get('tanggal')) document.getElementById('tanggal').value = params.get('tanggal');
                if (params.get('keterangan')) document.getElementById('keterangan').value = params.get('keterangan');
                if (params.get('jumlah')) document.getElementById('jumlah').value = params.get('jumlah');
                const akunId = params.get('akun_id');
                const sel = document.getElementById('akun_id');
                if (akunId && sel.querySelector('option[value="' + akunId + '"]')) sel.value = akunId;
                // Clean URL
                const url = new URL(window.location);
                url.searchParams.delete('ai_fill');
                url.searchParams.delete('jenis');
                url.searchParams.delete('jumlah');
                url.searchParams.delete('keterangan');
                url.searchParams.delete('akun_id');
                url.searchParams.delete('tanggal');
                window.history.replaceState({}, '', url);
            }, 100);
        }
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    pilihJenis('Pemasukan');
});

document.querySelectorAll('[data-bs-target="#modalTransaksi"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (!id) return;

        document.getElementById('action').value = 'edit';
        document.getElementById('id').value = id;
        document.getElementById('tanggal').value = btn.getAttribute('data-tanggal') || '';
        document.getElementById('keterangan').value = btn.getAttribute('data-keterangan') || '';
        document.getElementById('jumlah').value = btn.getAttribute('data-jumlah') || '0';
        document.getElementById('sumber_dana').value = btn.getAttribute('data-sumber_dana') || '';
        document.getElementById('tujuan').value = btn.getAttribute('data-tujuan') || '';
        document.getElementById('metode_bayar').value = btn.getAttribute('data-metode_bayar') || 'Tunai';
        document.getElementById('dana_khusus_id').value = btn.getAttribute('data-dana_khusus_id') || '';

        const bukti = btn.getAttribute('data-bukti_file') || '';
        if (bukti) {
            document.getElementById('bukti_file_current').innerHTML = 'File saat ini: <a href="' + bukti + '" target="_blank">Lihat</a>';
        } else {
            document.getElementById('bukti_file_current').innerHTML = '';
        }

        const jenis = btn.getAttribute('data-jenis') || 'Pemasukan';
        pilihJenis(jenis);
        document.getElementById('akun_id').value = btn.getAttribute('data-akun_id') || '';
    });
});

document.querySelectorAll('[data-bs-target="#modalReject"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('reject_id').value = btn.getAttribute('data-id') || '';
    });
});

let aiData = null;

function populateAkunSelect(selectId, selectedId) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '';
    const main = document.getElementById('akun_id');
    for (const opt of main.options) {
        if (opt.value === '') continue;
        const clone = new Option(opt.text, opt.value);
        sel.add(clone);
    }
    if (selectedId && sel.querySelector('option[value="' + selectedId + '"]')) {
        sel.value = selectedId;
    }
}

async function prosesAI() {
    const prompt = document.getElementById('aiPrompt').value.trim();
    const el = document.getElementById('aiPreview');
    if (!prompt || prompt.length < 3) { return; }
    el.style.display = 'block';
    el.innerHTML = '<span class="text-secondary"><span class="spinner-border spinner-border-sm"></span> Memproses...</span>';

    try {
        const res = await fetch('ajax_ai.php?action=parse_transaksi&teks=' + encodeURIComponent(prompt));
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { data = null; }

        if (!data || data.error) {
            el.innerHTML = '<span class="text-warning">' + (data?.error || 'Gagal diproses') + '</span>';
            return;
        }
        if (!data.jenis || !data.jumlah) {
            el.innerHTML = '<span class="text-warning">Tidak dapat memahami transaksi ini</span>';
            return;
        }

        aiData = data;
        const jenis = data.jenis === 'Pemasukan' ? 'Pemasukan' : 'Pengeluaran';

        const icon = document.getElementById('aiConfirmIcon');
        icon.className = 'ai-confirm-icon ' + (jenis === 'Pemasukan' ? 'masuk' : 'keluar');
        icon.innerHTML = jenis === 'Pemasukan' ? '<i class="bi bi-arrow-down-circle"></i>' : '<i class="bi bi-arrow-up-circle"></i>';

        // Populate jenis select
        const jenisSel = document.getElementById('aiCJenis');
        jenisSel.innerHTML = '<option value="Pemasukan">Pemasukan</option><option value="Pengeluaran">Pengeluaran</option>';
        jenisSel.value = jenis;

        // Populate jumlah
        document.getElementById('aiCJumlah').value = Number(data.jumlah).toLocaleString('id-ID');

        // Populate keterangan
        document.getElementById('aiCKeterangan').value = data.keterangan || '';

        // Populate akun select
        populateAkunSelect('aiCAkun', data.akun_id);

        // Populate tanggal
        document.getElementById('aiCTanggal').value = data.tanggal || new Date().toISOString().split('T')[0];

        el.style.display = 'none';
        document.getElementById('aiConfirmPopup').style.display = 'flex';
        document.getElementById('aiConfirmBtn').focus();
    } catch(e) {
        el.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

function tutupConfirm() {
    const popup = document.getElementById('aiConfirmPopup');
    popup.style.animation = 'aiFadeIn 0.15s ease reverse';
    setTimeout(() => { popup.style.display = 'none'; popup.style.animation = ''; }, 150);
}

function konfirmasiSimpan() {
    tutupConfirm();
    const jenis = document.getElementById('aiCJenis').value;
    const jumlah = document.getElementById('aiCJumlah').value.replace(/[^\d]/g, '');
    const keterangan = document.getElementById('aiCKeterangan').value;
    const akun_id = document.getElementById('aiCAkun').value;
    const tanggal = document.getElementById('aiCTanggal').value;

    if (!jenis || !jumlah || !akun_id) return;

    pilihJenis(jenis);
    if (tanggal) document.getElementById('tanggal').value = tanggal;
    if (keterangan) document.getElementById('keterangan').value = keterangan;
    if (jumlah) document.getElementById('jumlah').value = jumlah;
    const select = document.getElementById('akun_id');
    if (akun_id && select.querySelector('option[value="' + akun_id + '"]')) {
        select.value = akun_id;
    }
    document.getElementById('bukti_file').required = true;
    document.getElementById('buktiWarning').style.display = '';
    setTimeout(() => {
        document.getElementById('bukti_file').focus();
        document.getElementById('bukti_file').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 200);
    aiData = null;
}

function aiMarkdown(text) {
    text = String(text);
    text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    text = text.replace(/^#### (.+)$/gm, '<h4>$1</h4>');
    text = text.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    text = text.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    text = text.replace(/^# (.+)$/gm, '<h1>$1</h1>');
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
    text = text.replace(/`(.+?)`/g, '<code>$1</code>');
    text = text.replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>');
    text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
    text = text.replace(/(<li>.*<\/li>\n?)/gs, function(m) {
        if (!m.includes('<ul>')) return '<ul>' + m.trimEnd() + '</ul>';
        return m;
    });
    text = text.replace(/<\/ul>\n<ul>/g, '');
    text = text.replace(/---+/g, '<hr>');
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/<br><br>/g, '</p><p>');
    text = text.replace(/<br><\/?[^>]+>/g, function(m) { return m.replace('<br>', ''); });
    text = '<p>' + text + '</p>';
    text = text.replace(/<p><\/p>/g, '');
    text = text.replace(/<p><br><\/p>/g, '');
    return text;
}

async function loadAI(type) {
    const el = document.getElementById('aiTransaksiResult');
    const body = document.getElementById('aiTransaksiBody');
    el.style.display = 'block';
    body.innerHTML = '<div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Menganalisis...</span></div>';
    try {
        const res = await fetch('ajax_ai.php?action=' + type + '&stream=1');
        body.innerHTML = '';
        if (!res.ok) { body.innerHTML = '<span class="text-danger">Gagal memuat: ' + res.status + '</span>'; return; }
        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buf = '';
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buf += decoder.decode(value, { stream: true });
            body.innerHTML = aiMarkdown(buf);
        }
    } catch(e) {
        body.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

let perbaikanList = [];
let perbaikanIdx = 0;

async function cekPerbaikanAI() {
    const el = document.getElementById('aiPerbaikanResult');
    const body = document.getElementById('aiPerbaikanBody');
    el.style.display = 'block';
    body.innerHTML = '<div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Menganalisis data transaksi...</span></div>';

    try {
        const res = await fetch('ajax_ai.php?action=perbaikan_pencatatan');
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { data = null; }

        if (!data || data.error) {
            body.innerHTML = '<span class="text-warning">' + (data?.error || 'Gagal memeriksa') + '</span>';
            return;
        }
        if (!data.ditemukan) {
            body.innerHTML = '<div class="ai-message success"><i class="bi bi-check-circle"></i> ' + (data.pesan || 'Tidak ada indikasi masalah pada transaksi') + '</div>';
            return;
        }

        perbaikanList = data.masalah || [];
        perbaikanIdx = 0;

        let html = '<div class="ai-info-bar"><i class="bi bi-exclamation-triangle text-danger"></i> Ditemukan <strong>' + data.jumlah_masalah + '</strong> indikasi masalah transaksi</div>';
        html += '<div id="perbaikanList">';
        for (let i = 0; i < perbaikanList.length; i++) {
            const p = perbaikanList[i];
            html += '<div class="ai-problem-card" id="perbaikanCard' + i + '">';
            html += '<div class="problem-body">';
            const label = p.jenis.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            html += '<div class="d-flex align-items-center gap-1 flex-wrap"><span class="badge bg-warning text-dark">' + label + '</span> <strong>#' + (Array.isArray(p.transaksi_id) ? p.transaksi_id.join(', #') : p.transaksi_id) + '</strong></div>';
            html += '<div class="problem-detail">' + p.tanggal + ' - ' + p.keterangan + '</div>';
            html += '<div class="problem-detail">' + p.detail + '</div>';
            html += '<div class="problem-saran"><i class="bi bi-lightbulb"></i> ' + p.perbaikan_saran + '</div>';
            html += '</div></div>';
        }
        html += '</div>';
        html += '<div class="d-flex gap-2 mt-2">';
        html += '<button class="btn btn-success flex-fill" id="btnSetujuSemua" onclick="setujuSemuaPerbaikan()"><i class="bi bi-check-all"></i> Setuju Semua, Perbaiki Sekarang</button>';
        html += '<button class="btn btn-outline-secondary" onclick="document.getElementById(\'aiPerbaikanResult\').style.display=\'none\'">Batal</button>';
        html += '</div>';

        body.innerHTML = html;
    } catch(e) {
        el.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

async function setujuSemuaPerbaikan() {
    const btn = document.getElementById('btnSetujuSemua');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memperbaiki...</span>';

    let sukses = 0;
    let gagal = 0;

    for (let i = 0; i < perbaikanList.length; i++) {
        const p = perbaikanList[i];
        const card = document.getElementById('perbaikanCard' + i);
        card.style.opacity = '0.5';

        let ids = Array.isArray(p.transaksi_id) ? p.transaksi_id : [p.transaksi_id];

        if (p.jenis === 'duplikat_transaksi' && ids.length > 1) {
            ids = ids.slice(1);
        }

        for (const id of ids) {
            try {
                const res = await fetch('ajax_ai.php?action=terapkan_perbaikan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        jenis_perbaikan: 'hapus_transaksi',
                        transaksi_id: id
                    })
                });
                const result = await res.json();
                if (result.success) sukses++;
                else gagal++;
            } catch(e) {
                gagal++;
            }
        }

        card.style.opacity = '1';
        card.innerHTML = '<div class="text-success small"><i class="bi bi-check-circle"></i> Selesai diperbaiki</div>';
    }

    const body = document.getElementById('aiPerbaikanBody');
    let msg = '<div class="ai-message success"><i class="bi bi-check-circle"></i>';
    if (gagal > 0) {
        msg += ' ' + sukses + ' berhasil, ' + gagal + ' gagal. Refresh halaman untuk melihat perubahan.';
    } else {
        msg += ' Perbaikan selesai diproses. Refresh halaman untuk melihat perubahan.';
    }
    msg += '</div>';
    body.innerHTML = msg + '<button class="btn btn-sm btn-accent mt-2" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh Halaman</button>';
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.cek-struk-btn');
    if (!btn) return;
    const file = btn.getAttribute('data-file');
    const el = document.getElementById('aiCekStrukResult');
    const content = document.getElementById('aiCekStrukBody');
    el.style.display = 'block';
    content.innerHTML = '<div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Menganalisis struk...</span></div>';
    fetch('ajax_ai.php?action=cek_struk&file=' + encodeURIComponent(file))
        .then(r => r.text())
        .then(text => {
            let skor = '', kesimpulan = '', alasan = '';
            const s = text.match(/SKOR\s*:?\s*([0-9%]+)/i);
            const k = text.match(/KESIMPULAN\s*:?\s*([^\n\|]+)/i);
            const a = text.match(/ALASAN\s*:?\s*([\s\S]+)/i);
            if (s) skor = s[1].trim();
            if (k) kesimpulan = k[1].trim();
            if (a) alasan = a[1].trim();
            if (skor || kesimpulan) {
                const isMatch = /cocok/i.test(kesimpulan) || /asli/i.test(kesimpulan);
                const isUncertain = /tidak yakin/i.test(kesimpulan);
                const color = isMatch ? '#10b981' : isUncertain ? '#f59e0b' : '#ef4444';
                const bg = isMatch ? 'rgba(16,185,129,0.1)' : isUncertain ? 'rgba(245,158,11,0.1)' : 'rgba(239,68,68,0.1)';
                const icon = isMatch ? 'bi-check-circle-fill' : isUncertain ? 'bi-question-circle-fill' : 'bi-x-circle-fill';
                const label = isMatch ? 'Cocok' : isUncertain ? 'Tidak Yakin' : 'Tidak Cocok';
                let html = '<div style="border:1px solid ' + color + '44;border-radius:14px;overflow:hidden;background:#fff;">';
                html += '<div style="background:' + bg + ';padding:0.85rem 1rem;display:flex;align-items:center;gap:0.5rem;font-weight:700;font-size:0.9rem;color:' + color + ';"><i class="bi ' + icon + '"></i> ' + label + '</div>';
                html += '<div style="padding:0.75rem 1rem;">';
                if (skor) html += '<div style="display:flex;gap:0.5rem;padding:0.4rem 0;border-bottom:1px solid #f1f5f9;"><span style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;font-weight:600;width:80px;">Skor</span><span style="font-size:0.88rem;color:#1e293b;font-weight:700;">' + skor + '</span></div>';
                if (kesimpulan) html += '<div style="display:flex;gap:0.5rem;padding:0.4rem 0;border-bottom:1px solid #f1f5f9;"><span style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;font-weight:600;width:80px;">Kesimpulan</span><span style="font-size:0.88rem;color:#1e293b;">' + kesimpulan + '</span></div>';
                if (alasan) html += '<div style="display:flex;gap:0.5rem;padding:0.4rem 0;"><span style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;font-weight:600;width:80px;">Alasan</span><span style="font-size:0.88rem;color:#1e293b;line-height:1.55;">' + alasan + '</span></div>';
                html += '</div></div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = aiMarkdown(text);
            }
        })
        .catch(() => {
            content.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
        });
});
</script>

<?php
$pdo = get_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $nama_barang = sanitize($_POST['nama_barang']);
        $tgl_perolehan = $_POST['tgl_perolehan'] ?: null;
        $tahun_pembelian = $tgl_perolehan ? date('Y', strtotime($tgl_perolehan)) : ((int)$_POST['tahun_pembelian'] ?: null);
        $harga = str_replace(['.', ','], ['', '.'], $_POST['harga']);
        $harga = (float)$harga;
        $kondisi = sanitize($_POST['kondisi']);
        $lokasi = sanitize($_POST['lokasi'] ?? '');
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $umur_ekonomis = (int)($_POST['umur_ekonomis'] ?? 0);
        $nilai_residu = str_replace(['.', ','], ['', '.'], $_POST['nilai_residu'] ?? '0');
        $nilai_residu = (float)$nilai_residu;
        $metode_penyusutan = sanitize($_POST['metode_penyusutan'] ?? 'garis_lurus');

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO inventaris (nama_barang, tahun_pembelian, harga, kondisi, lokasi, keterangan, umur_ekonomis, nilai_residu, metode_penyusutan, tgl_perolehan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama_barang, $tahun_pembelian, $harga, $kondisi, $lokasi, $keterangan, $umur_ekonomis, $nilai_residu, $metode_penyusutan, $tgl_perolehan]);
            audit_log('Tambah', 'inventaris', $pdo->lastInsertId(), "Barang: $nama_barang");
            set_flash('Inventaris berhasil ditambahkan');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE inventaris SET nama_barang=?, tahun_pembelian=?, harga=?, kondisi=?, lokasi=?, keterangan=?, umur_ekonomis=?, nilai_residu=?, metode_penyusutan=?, tgl_perolehan=? WHERE id=?");
            $stmt->execute([$nama_barang, $tahun_pembelian, $harga, $kondisi, $lokasi, $keterangan, $umur_ekonomis, $nilai_residu, $metode_penyusutan, $tgl_perolehan, $id]);
            audit_log('Edit', 'inventaris', $id, "Barang: $nama_barang");
            set_flash('Inventaris berhasil diperbarui');
        }
        header('Location: index.php?page=inventaris');
        exit;
    }

    if ($action === 'hitung_penyusutan') {
        $id = (int)$_POST['id'];
        $inv = get_inventaris($id);
        if ($inv && $inv['umur_ekonomis'] > 0) {
            $penyusutan = hitung_penyusutan_garis_lurus($inv['harga'], $inv['nilai_residu'], $inv['umur_ekonomis']);
            $akumulasi_baru = $inv['akumulasi_penyusutan'] + $penyusutan;
            $nilai_buku = hitung_nilai_buku($inv['harga'], $akumulasi_baru);

            $pdo->prepare("UPDATE inventaris SET akumulasi_penyusutan = ? WHERE id = ?")->execute([$akumulasi_baru, $id]);

            // Generate jurnal penyusutan
            $akun_beban = get_akun_by_kode('5-2100');
            $akun_akumulasi = get_akun_by_kode('1-1299');
            $kas_id = get_akun_kas();

            if ($akun_beban && $akun_akumulasi) {
                $tgl = date('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, keterangan, status, created_by) VALUES (?, ?, 'approved', ?)");
                $stmt->execute([$tgl, "Penyusutan: {$inv['nama_barang']} bulan " . date('m-Y'), $_SESSION['user_id']]);
                $tid = $pdo->lastInsertId();

                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$tid, $akun_beban['id'], $penyusutan]);
                $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$tid, $akun_akumulasi['id'], $penyusutan]);

                $pdo->prepare("INSERT INTO jurnal_penyusutan (inventaris_id, tanggal, beban_penyusutan, akumulasi_penyusutan) VALUES (?, ?, ?, ?)")
                    ->execute([$id, $tgl, $penyusutan, $akumulasi_baru]);

                audit_log('Penyusutan', 'inventaris', $id, "Penyusutan: {$inv['nama_barang']} - Rp " . number_format($penyusutan, 0, ',', '.'));
            }
            set_flash("Penyusutan {$inv['nama_barang']} berhasil: Rp " . number_format($penyusutan, 0, ',', '.'));
        } else {
            set_flash('Umur ekonomis belum diatur untuk barang ini', 'warning');
        }
        header('Location: index.php?page=inventaris');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM inventaris WHERE id=?")->execute([$id]);
    audit_log('Hapus', 'inventaris', $id, 'Hapus inventaris');
    set_flash('Inventaris berhasil dihapus');
    header('Location: index.php?page=inventaris');
    exit;
}

$inventaris_list = get_all_inventaris();
$total_nilai = get_total_nilai_inventaris();
$total_akumulasi = $pdo->query("SELECT COALESCE(SUM(akumulasi_penyusutan), 0) FROM inventaris")->fetchColumn();
$total_nilai_buku = $total_nilai - $total_akumulasi;
$kondisi_list = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
$metode_list = ['garis_lurus'];
?>

<!-- Stat -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
            <div class="stat-label">Total Barang</div>
            <div class="stat-value"><?= count($inventaris_list) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="stat-icon green"><i class="bi bi-coin"></i></div>
            <div class="stat-label">Nilai Perolehan</div>
            <div class="stat-value text-accent"><?= format_rupiah($total_nilai) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="stat-icon purple"><i class="bi bi-calculator"></i></div>
            <div class="stat-label">Akum. Penyusutan</div>
            <div class="stat-value"><?= format_rupiah($total_akumulasi) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="stat-icon orange"><i class="bi bi-bookmark"></i></div>
            <div class="stat-label">Nilai Buku</div>
            <div class="stat-value"><?= format_rupiah($total_nilai_buku) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box me-1"></i> Daftar Inventaris</span>
        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalInventaris" onclick="resetInventaris()">
            <i class="bi bi-plus-lg"></i> Tambah Barang
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Tgl Perolehan</th>
                        <th class="text-end">Harga</th>
                        <th>Kondisi</th>
                        <th class="text-end">Umur (thn)</th>
                        <th class="text-end">Nilai Buku</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventaris_list)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-3">Belum ada inventaris</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventaris_list as $i):
                            $nilai_buku = hitung_nilai_buku($i['harga'], $i['akumulasi_penyusutan']);
                        ?>
                        <tr>
                            <td class="fw-medium"><?= sanitize($i['nama_barang']) ?></td>
                            <td><small><?= $i['tgl_perolehan'] ? format_tanggal($i['tgl_perolehan']) : ($i['tahun_pembelian'] ?: '-') ?></small></td>
                            <td class="text-end amount-text"><?= format_rupiah($i['harga']) ?></td>
                            <td>
                                <?php
                                $badge_kondisi = match($i['kondisi']) {
                                    'Baik' => 'success',
                                    'Rusak Ringan' => 'warning',
                                    'Rusak Berat' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge_kondisi ?>"><?= $i['kondisi'] ?></span>
                            </td>
                            <td class="text-end"><?= $i['umur_ekonomis'] ? $i['umur_ekonomis'] . ' thn' : '-' ?></td>
                            <td class="text-end amount-text"><?= format_rupiah($nilai_buku) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal"
                                    data-bs-target="#modalInventaris"
                                    data-id="<?= $i['id'] ?>"
                                    data-nama_barang="<?= sanitize($i['nama_barang']) ?>"
                                    data-tgl_perolehan="<?= $i['tgl_perolehan'] ?>"
                                    data-tahun_pembelian="<?= $i['tahun_pembelian'] ?>"
                                    data-harga="<?= $i['harga'] ?>"
                                    data-kondisi="<?= $i['kondisi'] ?>"
                                    data-lokasi="<?= sanitize($i['lokasi'] ?? '') ?>"
                                    data-keterangan="<?= sanitize($i['keterangan'] ?? '') ?>"
                                    data-umur_ekonomis="<?= $i['umur_ekonomis'] ?>"
                                    data-nilai_residu="<?= $i['nilai_residu'] ?>"
                                    data-metode_penyusutan="<?= $i['metode_penyusutan'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($i['umur_ekonomis'] > 0): ?>
                                <form method="POST" class="d-inline" data-confirm="Hitung penyusutan untuk <?= sanitize($i['nama_barang']) ?>?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hitung_penyusutan">
                                    <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                    <button class="btn btn-sm btn-outline-info" title="Hitung Penyusutan">
                                        <i class="bi bi-calculator"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <a href="index.php?page=inventaris&delete=<?= $i['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    data-confirm="Yakin ingin menghapus <?= sanitize($i['nama_barang']) ?>?">
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
<div class="modal fade" id="modalInventaris" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Form Inventaris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="id" value="">

                <div class="mb-3">
                    <label class="form-label">Nama Barang</label>
                    <input type="text" name="nama_barang" id="nama_barang" class="form-control" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Perolehan</label>
                            <input type="date" name="tgl_perolehan" id="tgl_perolehan" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="text" name="harga" id="harga" class="form-control" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Kondisi</label>
                            <select name="kondisi" id="kondisi" class="form-select">
                                <?php foreach ($kondisi_list as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="lokasi" id="lokasi" class="form-control" placeholder="Ruang ...">
                        </div>
                    </div>
                </div>

                <hr>
                <h6 class="fw-semibold">Penyusutan</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Umur Ekonomis (tahun)</label>
                            <input type="number" name="umur_ekonomis" id="umur_ekonomis" class="form-control" placeholder="Contoh: 5" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nilai Residu (Rp)</label>
                            <input type="text" name="nilai_residu" id="nilai_residu" class="form-control" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Metode Penyusutan</label>
                    <select name="metode_penyusutan" id="metode_penyusutan" class="form-select">
                        <option value="garis_lurus">Garis Lurus (Straight Line)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
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
function resetInventaris() {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('nama_barang').value = '';
    document.getElementById('tgl_perolehan').value = '';
    document.getElementById('tahun_pembelian').value = '';
    document.getElementById('harga').value = '';
    document.getElementById('kondisi').value = 'Baik';
    document.getElementById('lokasi').value = '';
    document.getElementById('keterangan').value = '';
    document.getElementById('umur_ekonomis').value = '';
    document.getElementById('nilai_residu').value = '';
    document.getElementById('metode_penyusutan').value = 'garis_lurus';
}

document.querySelectorAll('[data-bs-target="#modalInventaris"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (!id) { resetInventaris(); return; }
        document.getElementById('action').value = 'edit';
        document.getElementById('id').value = id;
        document.getElementById('nama_barang').value = btn.getAttribute('data-nama_barang') || '';
        document.getElementById('tgl_perolehan').value = btn.getAttribute('data-tgl_perolehan') || '';
        document.getElementById('tahun_pembelian').value = btn.getAttribute('data-tahun_pembelian') || '';
        document.getElementById('harga').value = btn.getAttribute('data-harga') || '0';
        document.getElementById('kondisi').value = btn.getAttribute('data-kondisi') || 'Baik';
        document.getElementById('lokasi').value = btn.getAttribute('data-lokasi') || '';
        document.getElementById('keterangan').value = btn.getAttribute('data-keterangan') || '';
        document.getElementById('umur_ekonomis').value = btn.getAttribute('data-umur_ekonomis') || '';
        document.getElementById('nilai_residu').value = btn.getAttribute('data-nilai_residu') || '0';
        document.getElementById('metode_penyusutan').value = btn.getAttribute('data-metode_penyusutan') || 'garis_lurus';
    });
});
</script>

<?php
$pdo = get_connection();
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$bulan = (int)($_GET['bulan'] ?? 0);

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $akun_id = (int)$_POST['akun_id'];
        $jenis = sanitize($_POST['jenis'] ?? 'bulanan');
        $bulan_val = $jenis === 'bulanan' ? (int)$_POST['bulan'] : null;
        $tahun_val = (int)$_POST['tahun'];
        $jumlah = str_replace(['.', ','], ['', '.'], $_POST['jumlah']);
        $jumlah = (float)$jumlah;

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO anggaran (akun_id, jenis, bulan, tahun, jumlah) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$akun_id, $jenis, $bulan_val, $tahun_val, $jumlah]);
            audit_log('Tambah', 'anggaran', $pdo->lastInsertId(), "Anggaran akun_id=$akun_id");
            set_flash('Anggaran berhasil ditambahkan');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE anggaran SET akun_id=?, jenis=?, bulan=?, tahun=?, jumlah=? WHERE id=?");
            $stmt->execute([$akun_id, $jenis, $bulan_val, $tahun_val, $jumlah, $id]);
            audit_log('Edit', 'anggaran', $id, "Anggaran akun_id=$akun_id");
            set_flash('Anggaran berhasil diperbarui');
        }
        header('Location: index.php?page=anggaran&tahun=' . $tahun_val . ($bulan_val ? "&bulan=$bulan_val" : ''));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM anggaran WHERE id=?")->execute([$id]);
        set_flash('Anggaran berhasil dihapus');
        header('Location: index.php?page=anggaran&tahun=' . $tahun . ($bulan ? "&bulan=$bulan" : ''));
        exit;
    }
}

$akun_pendapatan = get_akun_by_kategori('Pendapatan');
$akun_beban = get_akun_by_kategori('Beban');
$semua_akun = array_merge($akun_pendapatan, $akun_beban);

// Get budgets for selected period
$where = '';
$params = [$tahun];
if ($bulan) {
    $where = "AND (bulan = ? OR jenis = 'tahunan')";
    $params[] = $bulan;
}
$stmt = $pdo->prepare("SELECT a.*, ak.nama_akun, ak.kode_akun, ak.kategori FROM anggaran a JOIN akun ak ON a.akun_id = ak.id WHERE a.tahun = ? $where ORDER BY ak.kode_akun");
$stmt->execute($params);
$anggaran_list = $stmt->fetchAll();
?>

<div class="card card-ai mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-7">
                <label class="form-label fw-semibold small"><i class="bi bi-robot text-accent me-1"></i>HISA Budget — Rencana Anggaran</label>
                <input type="text" id="aiAnggaranPrompt" class="form-control" placeholder="Contoh: buat anggaran prioritas kebersihan dan konsumsi, target hemat 20%" onkeypress="if(event.key==='Enter') buatAnggaranAI()">
            </div>
            <div class="col-md-3">
                <button class="btn btn-accent w-100" onclick="buatAnggaranAI()"><i class="bi bi-stars"></i> Buat Rencana</button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-warning w-100" onclick="cekAnggaranAI()"><i class="bi bi-tools"></i> Perbaiki</button>
            </div>
        </div>
        <div id="aiBuatAnggaranResult" class="ai-response mt-2" style="display:none;">
            <div class="ai-response-header"><i class="bi bi-stars"></i> HISA AI — Budget Maker</div>
            <div class="ai-response-body" id="aiBuatAnggaranBody"></div>
        </div>
    </div>
</div>

<div id="aiAnggaranResult" class="ai-response mb-3" style="display:none;">
    <div class="ai-response-header"><i class="bi bi-tools"></i> HISA AI — Analisis Anggaran</div>
    <div class="ai-response-body" id="aiAnggaranBody"></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="anggaran">
            <div class="col-auto">
                <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">Semua Bulan</option>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $nama_bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    ?>
                    <option value="<?= $m ?>" <?= $bulan == $m ? 'selected' : '' ?>><?= $nama_bulan[$m-1] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                    <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-accent">Tampilkan</button>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalAnggaran" onclick="resetAnggaran()">
            <i class="bi bi-plus-lg"></i> Tambah Anggaran
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-graph-up-arrow me-1"></i> Anggaran <?= $bulan ? $nama_bulan[$bulan-1] . ' ' : '' ?> <?= $tahun ?></span>
        <span class="badge bg-secondary"><?= count($anggaran_list) ?> item</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Akun</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th class="text-end">Anggaran</th>
                        <th class="text-end">Realisasi</th>
                        <th class="text-end">Sisa</th>
                        <th>Progress</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($anggaran_list)): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-4">Belum ada anggaran</td></tr>
                    <?php else: ?>
                        <?php foreach ($anggaran_list as $a):
                            $realisasi = get_realisasi_budget($a['akun_id'], $bulan ?: date('m'), $tahun);
                            $sisa = $a['jumlah'] - $realisasi;
                            $persen = $a['jumlah'] > 0 ? min(100, round(($realisasi / $a['jumlah']) * 100)) : 0;
                            $over = $realisasi > $a['jumlah'] && $a['jumlah'] > 0;
                        ?>
                        <tr class="<?= $over ? 'table-danger' : '' ?>">
                            <td><small><?= $a['kode_akun'] ?> - <?= sanitize($a['nama_akun']) ?></small></td>
                            <td><span class="badge bg-<?= $a['kategori'] == 'Pendapatan' ? 'success' : 'danger' ?>"><?= $a['kategori'] ?></span></td>
                            <td><small><?= $a['jenis'] ?></small></td>
                            <td class="text-end amount-text"><?= format_rupiah($a['jumlah']) ?></td>
                            <td class="text-end amount-text <?= $over ? 'text-danger' : '' ?>"><?= format_rupiah($realisasi) ?></td>
                            <td class="text-end amount-text <?= $sisa < 0 ? 'text-danger' : 'text-success' ?>"><?= format_rupiah($sisa) ?></td>
                            <td style="min-width:120px;">
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar <?= $over ? 'bg-danger' : ($persen > 80 ? 'bg-warning' : 'bg-success') ?>"
                                        style="width: <?= $persen ?>%"></div>
                                </div>
                                <small class="text-secondary"><?= $persen ?>%</small>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal"
                                    data-bs-target="#modalAnggaran"
                                    data-id="<?= $a['id'] ?>"
                                    data-akun_id="<?= $a['akun_id'] ?>"
                                    data-jenis="<?= $a['jenis'] ?>"
                                    data-bulan="<?= $a['bulan'] ?>"
                                    data-tahun="<?= $a['tahun'] ?>"
                                    data-jumlah="<?= $a['jumlah'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" data-confirm="Hapus anggaran?" data-danger>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
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
<div class="modal fade" id="modalAnggaran" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Form Anggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="id" value="">

                <div class="mb-3">
                    <label class="form-label">Akun</label>
                    <select name="akun_id" id="akun_id" class="form-select" required>
                        <option value="">-- Pilih Akun --</option>
                        <?php foreach ($semua_akun as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= sanitize($a['kode_akun']) ?> - <?= sanitize($a['nama_akun']) ?> (<?= $a['kategori'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="mb-3">
                            <label class="form-label">Jenis</label>
                            <select name="jenis" id="jenis" class="form-select" required onchange="toggleBulan()">
                                <option value="bulanan">Bulanan</option>
                                <option value="tahunan">Tahunan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4" id="colBulan">
                        <div class="mb-3">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" id="bulan" class="form-select">
                                <option value="">-- Pilih --</option>
                                <?php foreach ($nama_bulan ?? [] as $i => $nb): ?>
                                <option value="<?= $i+1 ?>"><?= $nb ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" id="tahun" class="form-select">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Anggaran (Rp)</label>
                    <input type="text" name="jumlah" id="jumlah" class="form-control" placeholder="0" required>
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
function resetAnggaran() {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('akun_id').value = '';
    document.getElementById('jenis').value = 'bulanan';
    document.getElementById('bulan').value = '<?= $bulan ?: '' ?>';
    document.getElementById('tahun').value = '<?= $tahun ?>';
    document.getElementById('jumlah').value = '';
    toggleBulan();
}

function toggleBulan() {
    const col = document.getElementById('colBulan');
    const jenis = document.getElementById('jenis').value;
    col.style.display = jenis === 'bulanan' ? '' : 'none';
}

document.querySelectorAll('[data-bs-target="#modalAnggaran"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (!id) { resetAnggaran(); return; }
        document.getElementById('action').value = 'edit';
        document.getElementById('id').value = id;
        document.getElementById('akun_id').value = btn.getAttribute('data-akun_id') || '';
        document.getElementById('jenis').value = btn.getAttribute('data-jenis') || 'bulanan';
        document.getElementById('bulan').value = btn.getAttribute('data-bulan') || '';
        document.getElementById('tahun').value = btn.getAttribute('data-tahun') || '<?= $tahun ?>';
        document.getElementById('jumlah').value = btn.getAttribute('data-jumlah') || '0';
        toggleBulan();
    });
});

document.addEventListener('DOMContentLoaded', toggleBulan);

let anggaranList = [];
let selectedOpsi = null;

async function buatAnggaranAI() {
    const input = document.getElementById('aiAnggaranPrompt');
    const teks = input.value.trim();
    const el = document.getElementById('aiBuatAnggaranResult');
    const body = document.getElementById('aiBuatAnggaranBody');
    if (!teks || teks.length < 5) { return; }
    el.style.display = 'block';
    body.innerHTML = '<div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Menyusun opsi rencana anggaran...</span></div>';

    try {
        const res = await fetch('ajax_ai.php?action=buat_anggaran&teks=' + encodeURIComponent(teks));
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { data = null; }

        if (!data || data.error) {
            body.innerHTML = '<span class="text-warning">' + (data?.error || 'Gagal membuat anggaran') + '</span>';
            return;
        }
        if (!data.opsi || data.opsi.length === 0) {
            body.innerHTML = '<span class="text-warning">AI tidak dapat menghasilkan opsi anggaran. Coba instruksi yang lebih jelas.</span>';
            return;
        }

        let html = '<div class="ai-info-bar"><i class="bi bi-stars text-accent"></i> Pilih salah satu opsi rencana anggaran di bawah</div>';
        for (let o = 0; o < data.opsi.length; o++) {
            const opsi = data.opsi[o];
            html += '<div class="ai-option-card">';
            html += '<div class="option-header"><i class="bi bi-check-circle"></i> ' + opsi.nama + '</div>';
            html += '<div class="option-body">';
            html += '<div class="option-desc">' + opsi.deskripsi + '</div>';
            html += '<table class="option-table">';
            let total = 0;
            for (const item of (opsi.items || [])) {
                total += item.jumlah;
                html += '<tr><td>Akun #' + item.akun_id + '</td><td class="text-end">Rp ' + Number(item.jumlah).toLocaleString('id-ID') + '</td><td style="color:var(--text-muted);">' + (item.jenis || 'bulanan') + '</td></tr>';
            }
            html += '<tr><td><strong>Total</strong></td><td class="text-end"><strong>Rp ' + Number(total).toLocaleString('id-ID') + '</strong></td><td></td></tr>';
            html += '</table>';
            if (opsi.alasan) {
                html += '<div class="option-note alasan"><i class="bi bi-quote"></i> ' + opsi.alasan + '</div>';
            }
            if (opsi.proyeksi) {
                html += '<div class="option-note proyeksi"><i class="bi bi-graph-up-arrow"></i> <strong>Proyeksi:</strong> ' + opsi.proyeksi + '</div>';
            }
            if (opsi.rekomendasi) {
                html += '<div class="option-note rekomendasi"><i class="bi bi-info-circle"></i> ' + opsi.rekomendasi + '</div>';
            }
            html += '<button class="btn btn-sm btn-success mt-2 w-100" onclick="pilihOpsi(' + o + ')"><i class="bi bi-check-lg"></i> Pilih Opsi Ini</button>';
            html += '</div></div>';
        }
        body.innerHTML = html;
        window._aiAnggaranData = data.opsi;
    } catch(e) {
        body.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

async function pilihOpsi(idx) {
    const opsi = window._aiAnggaranData[idx];
    if (!opsi || !opsi.items) return;
    const el = document.getElementById('aiBuatAnggaranResult');
    const body = document.getElementById('aiBuatAnggaranBody');
    body.innerHTML = '<div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Menerapkan anggaran...</span></div>';
    try {
        const res = await fetch('ajax_ai.php?action=terapkan_anggaran', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: opsi.items })
        });
        const result = await res.json();
        if (result.success) {
            body.innerHTML = '<div class="ai-message success"><i class="bi bi-check-circle"></i> ' + (result.message || 'Rencana anggaran berhasil diterapkan!') + ' Refresh halaman untuk melihat.</div>';
            body.innerHTML += '<button class="btn btn-sm btn-accent mt-2" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh Halaman</button>';
        } else {
            body.innerHTML = '<div class="ai-message error"><i class="bi bi-exclamation-triangle"></i> ' + (result.error || 'Gagal') + '</div>';
        }
    } catch(e) {
        body.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

async function cekAnggaranAI() {
    const el = document.getElementById('aiAnggaranResult');
    const body = document.getElementById('aiAnggaranBody');
    el.style.display = 'block';
    body.innerHTML = '<div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Menganalisis data anggaran...</span></div>';

    try {
        const res = await fetch('ajax_ai.php?action=perbaikan_anggaran');
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { data = null; }

        if (!data || data.error) {
            body.innerHTML = '<span class="text-warning">' + (data?.error || 'Gagal memeriksa') + '</span>';
            return;
        }
        if (!data.ditemukan) {
            body.innerHTML = '<div class="ai-message success"><i class="bi bi-check-circle"></i> ' + (data.pesan || 'Tidak ada indikasi masalah pada anggaran') + '</div>';
            return;
        }

        anggaranList = data.masalah || [];
        const saldo = anggaranList.length > 0 ? anggaranList[0].saldo_kas : 0;

        let html = '<div class="ai-info-bar"><i class="bi bi-piggy-bank"></i> Saldo kas saat ini: <strong>Rp ' + Number(saldo).toLocaleString('id-ID') + '</strong> — Ditemukan <strong>' + data.jumlah_masalah + '</strong> indikasi masalah anggaran</div>';
        html += '<div id="anggaranList">';
        for (let i = 0; i < anggaranList.length; i++) {
            const p = anggaranList[i];
            html += '<div class="ai-problem-card" id="anggaranCard' + i + '">';
            html += '<div class="problem-body">';
            const label = p.jenis.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            html += '<div class="d-flex align-items-center gap-1 flex-wrap"><span class="badge bg-warning text-dark">' + label + '</span> <strong>' + p.kode_akun + ' - ' + p.nama_akun + '</strong> <span class="badge bg-' + (p.kategori == 'Pendapatan' ? 'success' : 'danger') + '">' + p.kategori + '</span></div>';
            html += '<div class="problem-detail">Anggaran: <strong>Rp ' + Number(p.anggaran).toLocaleString('id-ID') + '</strong> | Realisasi: <strong>Rp ' + Number(p.realisasi).toLocaleString('id-ID') + '</strong> (' + p.persen + '%)</div>';
            html += '<div class="problem-saran"><i class="bi bi-lightbulb"></i> ' + p.perbaikan_saran + '</div>';
            html += '</div></div>';
        }
        html += '</div>';

        html += '<div class="d-flex gap-2 mt-2">';
        html += '<button class="btn btn-success flex-fill" id="btnSetujuAnggaran" onclick="setujuSemuaAnggaran()"><i class="bi bi-check-all"></i> Setuju Semua, Perbaiki Anggaran</button>';
        html += '<button class="btn btn-outline-secondary" onclick="document.getElementById(\'aiAnggaranResult\').style.display=\'none\'">Batal</button>';
        html += '</div>';

        body.innerHTML = html;
    } catch(e) {
        body.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}

async function setujuSemuaAnggaran() {
    const btn = document.getElementById('btnSetujuAnggaran');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memperbaiki...</span>';

    const items = anggaranList.map(p => {
        const match = p.perbaikan_saran.match(/Rp\s+([\d.]+)/);
        const jumlah = match ? parseInt(match[1].replace(/\./g, '')) : Math.ceil(p.realisasi * 1.2);
        return {
            anggaran_id: p.anggaran_id,
            akun_id: p.akun_id,
            jumlah: jumlah,
            jenis: p.bulan ? 'bulanan' : 'tahunan',
            bulan: p.bulan || 1,
            tahun: new Date().getFullYear()
        };
    });

    try {
        const res = await fetch('ajax_ai.php?action=terapkan_anggaran', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: items })
        });
        const result = await res.json();
        const body = document.getElementById('aiAnggaranBody');
        if (result.success) {
            body.innerHTML = '<div class="ai-message success"><i class="bi bi-check-circle"></i> ' + result.message + ' Refresh halaman untuk melihat perubahan.</div>';
            body.innerHTML += '<button class="btn btn-sm btn-accent mt-2" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh Halaman</button>';
        } else {
            body.innerHTML = '<div class="ai-message error"><i class="bi bi-exclamation-triangle"></i> ' + (result.error || 'Gagal') + '</div>';
        }
    } catch(e) {
        document.getElementById('aiAnggaranBody').innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}
</script>

<?php
check_admin();
$pdo = get_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $judul = sanitize($_POST['judul']);
        $konten = $_POST['konten'];
        $urutan = (int)($_POST['urutan'] ?? 0);
        $tahun = (int)($_POST['tahun'] ?? date('Y'));

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO calk (judul, konten, urutan, tahun) VALUES (?, ?, ?, ?)");
            $stmt->execute([$judul, $konten, $urutan, $tahun]);
            set_flash('Catatan berhasil ditambahkan');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE calk SET judul=?, konten=?, urutan=?, tahun=? WHERE id=?");
            $stmt->execute([$judul, $konten, $urutan, $tahun, $id]);
            set_flash('Catatan berhasil diperbarui');
        }
        header('Location: index.php?page=calk');
        exit;
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM calk WHERE id=?")->execute([$id]);
        set_flash('Catatan berhasil dihapus');
        header('Location: index.php?page=calk');
        exit;
    }
}

$tahun_filter = (int)($_GET['tahun'] ?? date('Y'));
$stmt = $pdo->prepare("SELECT * FROM calk WHERE tahun = ? ORDER BY urutan, id");
$stmt->execute([$tahun_filter]);
$catatan = $stmt->fetchAll();
?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="calk">
            <div class="col-auto">
                <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $tahun_filter == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="col-md-6 text-end d-flex gap-2 justify-content-end">
        <button class="btn btn-sm btn-outline-info" onclick="generateCALK()" id="btnGenCalk" title="Buat catatan keuangan otomatis berdasarkan data yang ada">
            <i class="bi bi-magic"></i> Buat Otomatis
        </button>
        <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#modalCalk" onclick="resetCalk()">
            <i class="bi bi-plus-lg"></i> Tambah Catatan
        </button>
    </div>
</div>

<div id="aiCalkResult" class="ai-response mb-3" style="display:none;">
    <div class="ai-response-header"><i class="bi bi-robot"></i> HISA AI — Catatan Keuangan</div>
    <div id="aiCalkLoading" style="display:none;padding:1rem;">
        <div class="ai-loading"><div class="ai-typing"><span></span><span></span><span></span></div> <span>Sedang menyusun catatan keuangan...</span></div>
    </div>
    <div id="aiCalkContent" class="ai-response-body"></div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-file-earmark-text me-1"></i> Catatan Atas Laporan Keuangan (CALK) - <?= $tahun_filter ?>
    </div>
    <div class="card-body">
        <?php if (empty($catatan)): ?>
            <p class="text-center text-secondary py-4">Belum ada catatan untuk tahun <?= $tahun_filter ?></p>
        <?php else: ?>
            <?php foreach ($catatan as $c): ?>
            <div class="mb-4 p-3 rounded-3" style="background:var(--bg-body);">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-semibold mb-0"><?= sanitize($c['judul']) ?></h6>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-accent" data-bs-toggle="modal"
                            data-bs-target="#modalCalk"
                            data-id="<?= $c['id'] ?>"
                            data-judul="<?= sanitize($c['judul']) ?>"
                            data-konten="<?= sanitize($c['konten']) ?>"
                            data-urutan="<?= $c['urutan'] ?>"
                            data-tahun="<?= $c['tahun'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" data-confirm="Hapus catatan?" data-danger>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="small lh-lg"><?= nl2br(sanitize($c['konten'])) ?></div>
                <small class="text-muted">Urutan: <?= $c['urutan'] ?> | Tahun: <?= $c['tahun'] ?></small>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalCalk" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Form Catatan Atas Laporan Keuangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="id" value="">

                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="judul" id="judul" class="form-control" placeholder="Contoh: Kebijakan Akuntansi" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" id="urutan" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" id="tahun" class="form-select">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $tahun_filter ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konten</label>
                    <textarea name="konten" id="konten" class="form-control" rows="8" placeholder="Tulis catatan atas laporan keuangan..." required></textarea>
                    <small class="text-secondary">Gunakan teks biasa. Untuk format paragraf, gunakan baris kosong.</small>
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
function resetCalk() {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('judul').value = '';
    document.getElementById('konten').value = '';
    document.getElementById('urutan').value = '0';
    document.getElementById('tahun').value = '<?= $tahun_filter ?>';
}

document.querySelectorAll('[data-bs-target="#modalCalk"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (!id) { resetCalk(); return; }
        document.getElementById('action').value = 'edit';
        document.getElementById('id').value = id;
        document.getElementById('judul').value = btn.getAttribute('data-judul') || '';
        document.getElementById('konten').value = btn.getAttribute('data-konten') || '';
        document.getElementById('urutan').value = btn.getAttribute('data-urutan') || '0';
        document.getElementById('tahun').value = btn.getAttribute('data-tahun') || '<?= $tahun_filter ?>';
    });
});

async function generateCALK() {
    const btn = document.getElementById('btnGenCalk');
    const result = document.getElementById('aiCalkResult');
    const loading = document.getElementById('aiCalkLoading');
    const content = document.getElementById('aiCalkContent');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
    result.style.display = 'block';
    loading.style.display = 'flex';
    content.innerHTML = '';

    const tahun = document.querySelector('select[name="tahun"]')?.value || new Date().getFullYear();

    try {
        const res = await fetch('ajax_ai.php?action=generate_calk&tahun=' + tahun);
        const json = await res.json();

        loading.style.display = 'none';

        if (json.error) {
            content.innerHTML = '<div class="ai-message error">' + json.error + '</div>';
            return;
        }

        if (!json.data || !json.data.length) {
            content.innerHTML = '<div class="ai-message error">Gagal membuat catatan. Coba lagi.</div>';
            return;
        }

        // Tampilkan pratinjau
        let html = '<div class="ai-message success mb-3"><i class="bi bi-check-circle"></i> Berhasil membuat ' + json.data.length + ' bagian catatan keuangan.</div>';
        html += '<div class="list-group mb-3">';
        for (const item of json.data) {
            html += '<div class="list-group-item"><strong>' + sanitizeHTML(item.judul || '(tanpa judul)') + '</strong><br><small class="text-secondary">' + (item.konten ? item.konten.substring(0, 120) + '...' : '') + '</small></div>';
        }
        html += '</div>';
        html += '<div class="d-flex gap-2">';
        html += '<button class="btn btn-sm btn-accent" onclick="saveCALK(\'' + tahun + '\')"><i class="bi bi-save"></i> Simpan Semua</button>';
        html += '<button class="btn btn-sm btn-secondary" onclick="document.getElementById(\'aiCalkResult\').style.display=\'none\'">Batal</button>';
        html += '</div>';
        content.innerHTML = html;

        // Simpan data di atribut untuk digunakan nanti
        content.dataset.calkData = JSON.stringify(json.data);

    } catch (e) {
        loading.style.display = 'none';
        content.innerHTML = '<div class="alert alert-danger">Gagal terhubung ke layanan pembuat catatan. ' + e.message + '</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-magic"></i> Buat Otomatis';
    }
}

async function saveCALK(tahun) {
    const content = document.getElementById('aiCalkContent');
    const data = JSON.parse(content.dataset.calkData || '[]');
    if (!data.length) return;

    const btn = content.querySelector('.btn-accent');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

    try {
        const res = await fetch('ajax_ai.php?action=save_calk', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ entries: data, tahun: parseInt(tahun) })
        });
        const json = await res.json();

        if (json.success) {
            content.innerHTML = '<div class="ai-message success"><i class="bi bi-check-circle"></i> ' + json.saved + ' dari ' + json.total + ' catatan berhasil disimpan. Halaman akan dimuat ulang...</div>';
            setTimeout(() => window.location.reload(), 1500);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Simpan Semua';
            content.innerHTML += '<div class="ai-message error">Gagal menyimpan.</div>';
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save"></i> Simpan Semua';
        content.innerHTML += '<div class="ai-message error">Gagal menyimpan: ' + e.message + '</div>';
    }
}

function sanitizeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}
</script>

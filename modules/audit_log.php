<?php
check_admin();
$pdo = get_connection();

$limit = (int)($_GET['limit'] ?? 100);
$aksi_filter = $_GET['aksi'] ?? '';
$tabel_filter = $_GET['tabel'] ?? '';

$where = '';
$params = [];
if ($aksi_filter) {
    $where .= " AND a.aksi = ?";
    $params[] = $aksi_filter;
}
if ($tabel_filter) {
    $where .= " AND a.tabel = ?";
    $params[] = $tabel_filter;
}

$sql = "SELECT a.*, u.nama_lengkap as user_name
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1=1 $where
        ORDER BY a.id DESC
        LIMIT ?";
$params[] = $limit;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$distinct_aksi = $pdo->query("SELECT DISTINCT aksi FROM audit_logs ORDER BY aksi")->fetchAll(PDO::FETCH_COLUMN);
$distinct_tabel = $pdo->query("SELECT DISTINCT tabel FROM audit_logs ORDER BY tabel")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="audit_log">
            <div class="col-auto">
                <select name="aksi" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Aksi</option>
                    <?php foreach ($distinct_aksi as $a): ?>
                    <option value="<?= $a ?>" <?= $aksi_filter == $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="tabel" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Tabel</option>
                    <?php foreach ($distinct_tabel as $t): ?>
                    <option value="<?= $t ?>" <?= $tabel_filter == $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="limit" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200</option>
                </select>
            </div>
            <div class="col-auto">
                <a href="index.php?page=audit_log" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <span class="text-secondary small"><?= count($logs) ?> catatan</span>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clipboard-data me-1"></i> Catatan Aktivitas</div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:600px;overflow-y:auto;">
            <table class="table table-sm table-hover mb-0">
                <thead style="position:sticky;top:0;background:var(--bg-card);">
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Tabel</th>
                        <th>Detail</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada catatan aktivitas</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><small><?= format_datetime($l['created_at']) ?></small></td>
                            <td><small><?= sanitize($l['user_name'] ?? '-') ?></small></td>
                            <td>
                                <span class="badge bg-<?= in_array($l['aksi'], ['Hapus','Reject','Delete']) ? 'danger' : (in_array($l['aksi'], ['Tambah','Add','Approve','Login']) ? 'success' : 'info') ?>">
                                    <?= $l['aksi'] ?>
                                </span>
                            </td>
                            <td><small><?= $l['tabel'] ?></small></td>
                            <td><small class="text-secondary"><?= sanitize(substr($l['detail'] ?? '', 0, 100)) ?></small></td>
                            <td><small class="text-muted"><?= $l['ip_address'] ?? '-' ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

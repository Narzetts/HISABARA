<?php
$keuangan = get_total_saldo();
$health_score = get_financial_health_score();
$growth = get_growth_percentage();
$total_aset = get_total_aset();

$grafik = get_data_grafik();
$labels = []; $pemasukan = []; $pengeluaran = [];
foreach ($grafik as $g) {
    $labels[] = format_tanggal($g['bulan'] . '-01');
    $pemasukan[] = (float)$g['pemasukan'];
    $pengeluaran[] = (float)$g['pengeluaran'];
}

$beban = get_data_grafik_beban();
$beban_labels = []; $beban_values = [];
foreach ($beban as $b) {
    $beban_labels[] = $b['nama_akun'];
    $beban_values[] = (float)$b['total'];
}

$transaksi_terbaru = get_transaksi_terbaru(5);

// Budget warnings
$pdo = get_connection();
$budget_warnings = [];
$tahun = date('Y');
$bulan = date('m');
$all_akun = get_all_akun();
foreach ($all_akun as $akn) {
    $budgets = get_budget($akn['id'], $bulan, $tahun);
    foreach ($budgets as $bdgt) {
        $realisasi = get_realisasi_budget($akn['id'], $bulan, $tahun);
        if ($bdgt['jumlah'] > 0 && $realisasi > $bdgt['jumlah']) {
            $budget_warnings[] = [
                'akun' => $akn['nama_akun'],
                'budget' => $bdgt['jumlah'],
                'realisasi' => $realisasi,
                'lebih' => $realisasi - $bdgt['jumlah'],
            ];
        }
    }
}
?>
<?php require_once 'config/ai.php'; ?>

<!-- KPI Cards Baris 1 -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Saldo Kas</div>
                    <div class="stat-value text-accent"><?= format_rupiah($keuangan['saldo_kas']) ?></div>
                </div>
                <div class="stat-icon green" style="width:44px;height:44px;font-size:1.1rem;margin:0;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <div class="mt-2">
                <span class="small text-secondary">Total Aset: <?= format_rupiah($total_aset) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Pemasukan</div>
                    <div class="stat-value text-success"><?= format_rupiah($keuangan['pemasukan']) ?></div>
                </div>
                <div class="stat-icon blue" style="width:44px;height:44px;font-size:1.1rem;margin:0;">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
            </div>
            <div class="mt-2">
                <?php if ($growth != 0): ?>
                <span class="small <?= $growth >= 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-<?= $growth >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= abs($growth) ?>% dari sebelumnya
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Total Pengeluaran</div>
                    <div class="stat-value text-warning"><?= format_rupiah($keuangan['pengeluaran']) ?></div>
                </div>
                <div class="stat-icon orange" style="width:44px;height:44px;font-size:1.1rem;margin:0;">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Surplus/Defisit</div>
                    <div class="stat-value <?= $keuangan['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= format_rupiah($keuangan['saldo']) ?>
                    </div>
                </div>
                <div class="stat-icon purple" style="width:44px;height:44px;font-size:1.1rem;margin:0;">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards Baris 2: Health & Warnings -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:80px;height:80px;position:relative;">
                        <canvas id="healthChart" data-score="<?= $health_score ?>"></canvas>
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:1.5rem;font-weight:700;color:var(--text-primary);">
                            <?= $health_score ?>
                        </div>
                    </div>
                    <div>
                        <div class="fw-semibold">Skor Kesehatan Keuangan</div>
                        <div class="small text-secondary">
                            <?php if ($health_score >= 80): ?>
                                Kesehatan keuangan sangat baik
                            <?php elseif ($health_score >= 60): ?>
                                Kesehatan keuangan cukup baik
                            <?php elseif ($health_score >= 40): ?>
                                Kesehatan keuangan perlu perhatian
                            <?php else: ?>
                                Kesehatan keuangan kritis
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="text-center p-2 rounded-3" style="background:var(--accent-light);">
                            <div class="small text-secondary">Rasio Kas</div>
                            <div class="fw-bold fs-5">
                                <?= $keuangan['pengeluaran'] > 0 ? number_format($keuangan['saldo_kas'] / ($keuangan['pengeluaran'] / 12), 1) : 'N/A' ?>x
                            </div>
                            <div class="small text-secondary">Bulan operasional</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 rounded-3" style="background:#fef3c7;">
                            <div class="small text-secondary">Efisiensi</div>
                            <div class="fw-bold fs-5">
                                <?= $keuangan['pemasukan'] > 0 ? number_format(($keuangan['pengeluaran'] / $keuangan['pemasukan']) * 100, 1) : 0 ?>%
                            </div>
                            <div class="small text-secondary">Beban vs Pendapatan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Budget Warnings -->
<?php if (!empty($budget_warnings)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-exclamation-triangle me-1"></i> Peringatan Anggaran Melebihi Batas
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Akun</th>
                                <th class="text-end">Anggaran</th>
                                <th class="text-end">Realisasi</th>
                                <th class="text-end">Lebih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budget_warnings as $w): ?>
                            <tr>
                                <td><?= sanitize($w['akun']) ?></td>
                                <td class="text-end"><?= format_rupiah($w['budget']) ?></td>
                                <td class="text-end text-danger"><?= format_rupiah($w['realisasi']) ?></td>
                                <td class="text-end text-danger fw-bold"><?= format_rupiah($w['lebih']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-up me-1"></i> Grafik Pemasukan & Pengeluaran (12 Bulan)</div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="chartKeuangan"
                        data-labels='<?= json_encode($labels) ?>'
                        data-pemasukan='<?= json_encode($pemasukan) ?>'
                        data-pengeluaran='<?= json_encode($pengeluaran) ?>'>
                    </canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart me-1"></i> Komposisi Beban</div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="chartBeban"
                        data-labels='<?= json_encode($beban_labels) ?>'
                        data-values='<?= json_encode($beban_values) ?>'>
                    </canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-1"></i> Transaksi Terbaru</span>
        <a href="index.php?page=transaksi" class="btn btn-sm btn-outline-accent">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi_terbaru)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-3">Belum ada transaksi</td></tr>
                    <?php else: ?>
                        <?php foreach ($transaksi_terbaru as $t): ?>
                        <?php
                            $detail = get_jumlah_transaksi_detail($t['id']);
                            $info = get_jenis_transaksi($detail);
                        ?>
                        <tr>
                            <td><?= format_tanggal($t['tanggal']) ?></td>
                            <td><?= sanitize($t['keterangan']) ?></td>
                            <td>
                                <?php if ($info['jenis'] == 'Pemasukan'): ?>
                                    <span class="badge-pemasukan">Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge-pengeluaran">Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($t['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Disetujui</span>
                                <?php elseif ($t['status'] == 'rejected'): ?>
                                    <span class="badge bg-danger">Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end amount-text">
                                <?= $info['jumlah'] ? format_rupiah($info['jumlah']) : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const score = document.getElementById('healthChart')?.getAttribute('data-score');
    if (score && document.getElementById('healthChart')) {
        const ctx = document.getElementById('healthChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [
                        score >= 80 ? '#10b981' : score >= 60 ? '#f59e0b' : '#ef4444',
                        '#e2e8f0'
                    ],
                    borderWidth: 0,
                    cutout: '70%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { tooltip: { enabled: false }, legend: { display: false } }
            }
        });
    }
});
</script>

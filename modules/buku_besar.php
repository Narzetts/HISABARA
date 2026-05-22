<?php
$pdo = get_connection();

$akun_id = $_GET['akun_id'] ?? null;
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$periode = $_GET['periode'] ?? '';

if ($periode) {
    $pecah = explode('-', $periode);
    if (count($pecah) == 2) {
        $dari = $pecah[0] . '-' . $pecah[1] . '-01';
        $sampai = date('Y-m-t', strtotime($dari));
    }
}

$all_akun = get_all_akun();
$ledger = null;
if ($akun_id) {
    $ledger = get_buku_besar($akun_id, $dari ?: null, $sampai ?: null);
}
?>

<?php if ($akun_id && $ledger): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-text me-1"></i> Buku Besar: <?= sanitize($ledger['akun']['kode_akun']) ?> - <?= sanitize($ledger['akun']['nama_akun']) ?></span>
        <span class="badge bg-secondary"><?= sanitize($ledger['akun']['saldo_normal']) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary">
                        <td colspan="2"><strong>Saldo Awal</strong></td>
                        <td></td>
                        <td></td>
                        <td class="text-end"><strong><?= format_rupiah($ledger['saldo_awal']) ?></strong></td>
                    </tr>
                    <?php if (empty($ledger['entries'])): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-3">Belum ada transaksi untuk akun ini</td></tr>
                    <?php else: ?>
                        <?php foreach ($ledger['entries'] as $e): ?>
                        <tr>
                            <td><?= format_tanggal($e['tanggal']) ?></td>
                            <td><?= sanitize($e['keterangan']) ?></td>
                            <td class="text-end text-success"><?= $e['debit'] > 0 ? format_rupiah($e['debit']) : '-' ?></td>
                            <td class="text-end text-danger"><?= $e['kredit'] > 0 ? format_rupiah($e['kredit']) : '-' ?></td>
                            <td class="text-end"><?= format_rupiah($e['saldo']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-end small text-secondary">
        <a href="index.php?page=buku_besar" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
</div>

<?php else: ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-journal-text me-1"></i> Buku Besar
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center mb-3">
            <input type="hidden" name="page" value="buku_besar">
            <div class="col-auto">
                <input type="date" name="dari" class="form-control form-control-sm" value="<?= $dari ?>">
            </div>
            <div class="col-auto">
                <input type="date" name="sampai" class="form-control form-control-sm" value="<?= $sampai ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-accent">Filter</button>
            </div>
            <div class="col-auto">
                <a href="index.php?page=buku_besar" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="row g-3">
            <?php
            $kategoris = ['Aset', 'Kewajiban', 'Aset Neto', 'Pendapatan', 'Beban', 'Dana Khusus'];
            foreach ($kategoris as $kat):
                $list = array_filter($all_akun, function($a) use ($kat) { return $a['kategori'] == $kat; });
                if (empty($list)) continue;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header py-2 small fw-semibold"><?= $kat ?></div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($list as $a): ?>
                        <a href="index.php?page=buku_besar&akun_id=<?= $a['id'] ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>"
                           class="list-group-item list-group-item-action py-2 small d-flex justify-content-between">
                            <span><?= sanitize($a['kode_akun']) ?> - <?= sanitize($a['nama_akun']) ?></span>
                            <span class="badge bg-secondary"><?= sanitize($a['saldo_normal']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$pdo = get_connection();

$dari = $_GET['dari'] ?? date('Y-01-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$tab = $_GET['tab'] ?? 'neraca';
$periode = $_GET['periode'] ?? '';

if ($periode) {
    $pecah = explode('-', $periode);
    if (count($pecah) == 2) {
        $dari = $pecah[0] . '-' . $pecah[1] . '-01';
        $sampai = date('Y-m-t', strtotime($dari));
    }
}

$neraca_saldo = get_neraca_saldo($dari, $sampai);
$posisi_keuangan = get_laporan_posisi_keuangan($dari, $sampai);
$aktivitas = get_laporan_aktivitas($dari, $sampai);
$arus_kas = get_laporan_arus_kas($dari, $sampai);

$total_debit = 0; $total_kredit = 0;
foreach ($neraca_saldo as $r) {
    $total_debit += $r['total_debit'];
    $total_kredit += $r['total_kredit'];
}

$dana_khusus_list = get_all_dana_khusus();
$dana_khusus_transaksi = [];
foreach ($dana_khusus_list as $dk) {
    $dana_khusus_transaksi[$dk['id']] = get_laporan_dana_khusus($dk['id']);
}

// CALK
$stmt = $pdo->prepare("SELECT * FROM calk WHERE tahun = ? ORDER BY urutan, id");
$stmt->execute([date('Y')]);
$calk_list = $stmt->fetchAll();

// Export handlers
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'pdf';

    if ($export_type === 'pdf') {
        export_pdf($tab, $dari, $sampai, $posisi_keuangan, $aktivitas, $arus_kas, $neraca_saldo, $calk_list);
    } elseif ($export_type === 'excel') {
        export_excel($tab, $dari, $sampai, $posisi_keuangan, $aktivitas, $arus_kas, $neraca_saldo);
    }
}

function export_pdf($tab, $dari, $sampai, $posisi, $aktivitas, $arus_kas, $neraca, $calk = []) {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($vendor)) {
        set_flash('Library PDF belum diinstal. Jalankan: composer install', 'warning');
        header('Location: index.php?page=laporan&tab=' . $tab);
        exit;
    }
    require $vendor;

    $html = '<html><head><style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; }
        h2, h3, h4 { text-align: center; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background: #0f172a; color: white; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .total-row { background: #f0fdf4; font-weight: bold; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 20px; }
        .section-title { background: #e2e8f0; padding: 4px 8px; font-weight: bold; }
        .catatan { margin-top: 20px; }
        .catatan h4 { text-align: left; }
    </style></head><body>';

    $html .= '<h2>' . get_setting('nama_masjid') . '</h2>';
    $html .= '<h3>Laporan Keuangan</h3>';
    $html .= '<p class="text-center">Periode: ' . format_tanggal($dari) . ' s/d ' . format_tanggal($sampai) . '</p>';
    $html .= '<p class="text-center"><em>Entitas Nonlaba</em></p>';
    $html .= '<hr>';

    if ($tab === 'neraca' || $tab === 'all') {
        $html .= '<h3>Neraca Saldo</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Kode</th><th>Nama Akun</th><th>Kategori</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr>';
        foreach ($neraca as $r) {
            $html .= '<tr>';
            $html .= '<td>' . $r['kode_akun'] . '</td>';
            $html .= '<td>' . $r['nama_akun'] . '</td>';
            $html .= '<td>' . $r['kategori'] . '</td>';
            $html .= '<td class="text-end">' . ($r['total_debit'] > 0 ? 'Rp ' . number_format($r['total_debit'], 0, ',', '.') : '-') . '</td>';
            $html .= '<td class="text-end">' . ($r['total_kredit'] > 0 ? 'Rp ' . number_format($r['total_kredit'], 0, ',', '.') : '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '<tr class="total-row"><td colspan="3">Total</td><td class="text-end">Rp ' . number_format(array_sum(array_column($neraca, 'total_debit')), 0, ',', '.') . '</td><td class="text-end">Rp ' . number_format(array_sum(array_column($neraca, 'total_kredit')), 0, ',', '.') . '</td></tr>';
        $html .= '</table>';
    }

    if ($tab === 'posisi' || $tab === 'all') {
        $html .= '<h3>Laporan Posisi Keuangan</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Akun</th><th class="text-end">Jumlah</th></tr>';
        $html .= '<tr class="section-title"><td colspan="2">ASET</td></tr>';
        foreach ($posisi['aset'] as $r) {
            $html .= '<tr><td>&nbsp;&nbsp;' . $r['nama'] . '</td><td class="text-end">Rp ' . number_format($r['saldo'], 0, ',', '.') . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Aset</td><td class="text-end">Rp ' . number_format($posisi['total_aset'], 0, ',', '.') . '</td></tr>';

        $html .= '<tr class="section-title"><td colspan="2">KEWAJIBAN</td></tr>';
        foreach ($posisi['kewajiban'] as $r) {
            $html .= '<tr><td>&nbsp;&nbsp;' . $r['nama'] . '</td><td class="text-end">Rp ' . number_format($r['saldo'], 0, ',', '.') . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Kewajiban</td><td class="text-end">Rp ' . number_format($posisi['total_kewajiban'], 0, ',', '.') . '</td></tr>';

        $html .= '<tr class="section-title"><td colspan="2">ASET NETO</td></tr>';
        $html .= '<tr><td>&nbsp;&nbsp;Dana Tidak Terikat</td><td class="text-end">Rp ' . number_format($posisi['total_aset_neto'], 0, ',', '.') . '</td></tr>';
        foreach ($posisi['aset_neto'] as $r) {
            $html .= '<tr><td>&nbsp;&nbsp;' . $r['nama'] . '</td><td class="text-end">Rp ' . number_format($r['saldo'], 0, ',', '.') . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Aset Neto</td><td class="text-end">Rp ' . number_format($posisi['total_aset_neto'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr style="background:#dbeafe"><td><b>Total Kewajiban + Aset Neto</b></td><td class="text-end"><b>Rp ' . number_format($posisi['total_kewajiban'] + $posisi['total_aset_neto'], 0, ',', '.') . '</b></td></tr>';
        $html .= '</table>';
    }

    if ($tab === 'aktivitas' || $tab === 'all') {
        $html .= '<h3>Laporan Aktivitas</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Akun</th><th class="text-end">Jumlah</th></tr>';
        $html .= '<tr class="section-title"><td colspan="2">PENDAPATAN</td></tr>';
        foreach ($aktivitas['pendapatan'] as $r) {
            $html .= '<tr><td>&nbsp;&nbsp;' . $r['nama'] . '</td><td class="text-end">Rp ' . number_format($r['saldo'], 0, ',', '.') . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Pendapatan</td><td class="text-end">Rp ' . number_format($aktivitas['total_pendapatan'], 0, ',', '.') . '</td></tr>';

        $html .= '<tr class="section-title"><td colspan="2">BEBAN</td></tr>';
        foreach ($aktivitas['beban'] as $r) {
            $html .= '<tr><td>&nbsp;&nbsp;' . $r['nama'] . '</td><td class="text-end">Rp ' . number_format($r['saldo'], 0, ',', '.') . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Beban</td><td class="text-end">Rp ' . number_format($aktivitas['total_beban'], 0, ',', '.') . '</td></tr>';

        $surplus_class = $aktivitas['surplus'] >= 0 ? 'style="background:#f0fdf4"' : 'style="background:#fee2e2"';
        $html .= '<tr ' . $surplus_class . '><td><b>' . ($aktivitas['surplus'] >= 0 ? 'Surplus' : 'Defisit') . '</b></td><td class="text-end"><b>Rp ' . number_format(abs($aktivitas['surplus']), 0, ',', '.') . '</b></td></tr>';
        $html .= '</table>';
    }

    if ($tab === 'arus_kas' || $tab === 'all') {
        $html .= '<h3>Laporan Arus Kas</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Keterangan</th><th class="text-end">Jumlah</th></tr>';
        $html .= '<tr><td>Saldo Awal Kas</td><td class="text-end">Rp ' . number_format($arus_kas['saldo_awal'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr style="background:#f0fdf4"><td>Penerimaan Kas</td><td class="text-end">Rp ' . number_format($arus_kas['penerimaan'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr style="background:#fee2e2"><td>Pengeluaran Kas</td><td class="text-end">(Rp ' . number_format($arus_kas['pengeluaran'], 0, ',', '.') . ')</td></tr>';
        $html .= '<tr class="total-row"><td>Saldo Akhir Kas</td><td class="text-end">Rp ' . number_format($arus_kas['saldo_akhir'], 0, ',', '.') . '</td></tr>';
        $html .= '</table>';
    }

    // CALK
    if (!empty($calk)) {
        $html .= '<div class="catatan">';
        $html .= '<h4>Catatan Atas Laporan Keuangan (CALK)</h4>';
        foreach ($calk as $c) {
            $html .= '<h5>' . $c['judul'] . '</h5>';
            $html .= '<p>' . nl2br($c['konten']) . '</p>';
        }
        $html .= '</div>';
    }


    $html .= '</body></html>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('Laporan_Keuangan_' . date('Ymd') . '.pdf', ['Attachment' => true]);
    exit;
}

function export_excel($tab, $dari, $sampai, $posisi, $aktivitas, $arus_kas, $neraca) {
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($vendor)) {
        set_flash('Library Excel belum diinstal. Jalankan: composer install', 'warning');
        header('Location: index.php?page=laporan&tab=' . $tab);
        exit;
    }
    require $vendor;

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Laporan Keuangan');
    $row = 1;

    $sheet->setCellValue('A' . $row, get_setting('nama_masjid'));
    $sheet->mergeCells('A' . $row . ':B' . $row);
    $row += 2;

    if ($tab === 'neraca' || $tab === 'all') {
        $sheet->setCellValue('A' . $row, 'NERACA SALDO');
        $row++;
        $sheet->setCellValue('A' . $row, 'Kode');
        $sheet->setCellValue('B' . $row, 'Nama Akun');
        $sheet->setCellValue('C' . $row, 'Debit');
        $sheet->setCellValue('D' . $row, 'Kredit');
        $row++;
        foreach ($neraca as $r) {
            $sheet->setCellValue('A' . $row, $r['kode_akun']);
            $sheet->setCellValue('B' . $row, $r['nama_akun']);
            $sheet->setCellValue('C' . $row, $r['total_debit'] > 0 ? $r['total_debit'] : '');
            $sheet->setCellValue('D' . $row, $r['total_kredit'] > 0 ? $r['total_kredit'] : '');
            $row++;
        }
        $row += 2;
    }

    $sheet->setCellValue('A' . $row, 'Periode: ' . format_tanggal($dari) . ' - ' . format_tanggal($sampai));

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Laporan_Keuangan_' . date('Ymd') . '.xlsx"');
    $writer->save('php://output');
    exit;
}

$tahun_sekarang = date('Y');
$calk_list = $pdo->prepare("SELECT * FROM calk WHERE tahun = ? ORDER BY urutan, id");
$calk_list->execute([$tahun_sekarang]);
$calk_list = $calk_list->fetchAll();
?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab == 'neraca' ? 'active' : '' ?>" href="index.php?page=laporan&tab=neraca&dari=<?= $dari ?>&sampai=<?= $sampai ?>">Neraca Saldo</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab == 'posisi' ? 'active' : '' ?>" href="index.php?page=laporan&tab=posisi&dari=<?= $dari ?>&sampai=<?= $sampai ?>">Posisi Keuangan</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab == 'aktivitas' ? 'active' : '' ?>" href="index.php?page=laporan&tab=aktivitas&dari=<?= $dari ?>&sampai=<?= $sampai ?>">Aktivitas</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab == 'arus_kas' ? 'active' : '' ?>" href="index.php?page=laporan&tab=arus_kas&dari=<?= $dari ?>&sampai=<?= $sampai ?>">Arus Kas</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab == 'dana_khusus' ? 'active' : '' ?>" href="index.php?page=laporan&tab=dana_khusus">Dana Khusus</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab == 'calk' ? 'active' : '' ?>" href="index.php?page=laporan&tab=calk">CALK</a>
    </li>
</ul>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="page" value="laporan">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <div class="col-auto">
            <input type="date" name="dari" class="form-control form-control-sm" value="<?= $dari ?>">
        </div>
        <div class="col-auto">
            <input type="date" name="sampai" class="form-control form-control-sm" value="<?= $sampai ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-accent">Tampilkan</button>
        </div>
    </form>
    <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-warning" onclick="loadAILaporan('audit')" title="Audit Otomatis">
            <i class="bi bi-shield-check"></i> Audit
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="loadAILaporan('perbaikan_data')" title="Perbaikan Data Otomatis">
            <i class="bi bi-heart-pulse"></i> Perbaikan Data
        </button>
        <button class="btn btn-sm btn-outline-accent" onclick="loadAILaporan('narasi')" title="Buat Narasi">
            <i class="bi bi-pencil-square"></i> Narasi
        </button>
        <a href="index.php?page=laporan&tab=<?= $tab ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&export=pdf" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-filetype-pdf"></i> PDF
        </a>
        <a href="index.php?page=laporan&tab=<?= $tab ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&export=excel" class="btn btn-sm btn-outline-success">
            <i class="bi bi-filetype-xlsx"></i> Excel
        </a>

    </div>
</div>

<div id="aiLaporanResult" class="ai-response mb-3" style="display:none;">
    <div class="ai-response-header"><i class="bi bi-robot"></i> HISA AI — Analisis Laporan</div>
    <div id="aiLaporanLoading" class="ai-loading" style="padding:1rem;">
        <div class="ai-typing"><span></span><span></span><span></span></div>
        <span>Menganalisis...</span>
    </div>
    <div id="aiLaporanContent" class="ai-response-body"></div>
</div>

<script>
function aiMarkdown(text) {
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

async function loadAILaporan(type) {
    const el = document.getElementById('aiLaporanResult');
    const loading = document.getElementById('aiLaporanLoading');
    const content = document.getElementById('aiLaporanContent');
    el.style.display = 'block';
    el.classList.add('show');
    loading.style.display = 'flex';
    content.innerHTML = '';

    if (type === 'perbaikan_data') {
        // Non-streaming JSON response
        try {
            const res = await fetch('ajax_ai.php?action=' + type);
            loading.style.display = 'none';
            const text = await res.text();
            let parsed;
            try { parsed = JSON.parse(text); } catch(e) { parsed = null; }
            if (parsed && parsed.error) { content.innerHTML = '<span class="text-warning">' + parsed.error + '</span>'; return; }
            if (parsed) {
                let html = '';
                const items = Array.isArray(parsed) ? parsed : [parsed];
                for (const item of items) {
                    const masalah = (item.masalah || '').toLowerCase();
                    const isClean = masalah.includes('tidak ada masalah');
                    const border = isClean ? '#10b981' : '#f59e0b';
                    const bgHead = isClean ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)';
                    const icon = isClean ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
                    const title = isClean ? 'Tidak Ada Masalah' : 'Masalah Ditemukan';
                    const iconColor = isClean ? '#10b981' : '#f59e0b';
                    html += '<div style="border:1px solid ' + border + '44;border-radius:14px;overflow:hidden;margin-bottom:1rem;background:#fff;">';
                    html += '<div style="background:' + bgHead + ';padding:0.75rem 1rem;display:flex;align-items:center;gap:0.5rem;font-weight:700;font-size:0.9rem;color:#1e293b;"><i class="bi ' + icon + '" style="color:' + iconColor + ';"></i> ' + title + '</div>';
                    html += '<div style="padding:0.85rem 1rem;">';
                    for (const [k, v] of Object.entries(item)) {
                        if (k === 'sql' && isClean) continue;
                        const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                        const val = typeof v === 'object' ? JSON.stringify(v, null, 2) : String(v);
                        html += '<div style="display:flex;gap:0.6rem;padding:0.5rem 0;border-bottom:1px solid #f1f5f9;"><div style="width:28px;flex-shrink:0;color:' + iconColor + ';font-size:0.9rem;display:flex;align-items:flex-start;padding-top:2px;"><i class="bi bi-chevron-right"></i></div><div style="flex:1;min-width:0;"><div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;font-weight:600;margin-bottom:0.1rem;">' + label + '</div><div style="font-size:0.88rem;color:#1e293b;line-height:1.55;">' + aiMarkdown(val) + '</div></div></div>';
                    }
                    html += '</div></div>';
                }
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="ai-response"><div class="ai-response-body">' + aiMarkdown(text) + '</div></div>';
            }
        } catch(e) {
            loading.style.display = 'none';
            content.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
        }
        return;
    }

    let url = 'ajax_ai.php?action=' + type + '&stream=1';
    if (type === 'narasi') {
        const data = {
            'Total Pendapatan': '<?= format_rupiah($aktivitas['total_pendapatan']) ?>',
            'Total Beban': '<?= format_rupiah($aktivitas['total_beban']) ?>',
            'Surplus/Defisit': '<?= format_rupiah($aktivitas['surplus']) ?>',
            'Saldo Kas': '<?= format_rupiah($arus_kas['saldo_akhir']) ?>',
            'Periode': '<?= format_tanggal($dari) ?> - <?= format_tanggal($sampai) ?>'
        };
        url += '&judul=' + encodeURIComponent('Laporan Keuangan') + '&data=' + encodeURIComponent(JSON.stringify(data));
    }

    try {
        const res = await fetch(url);
        loading.style.display = 'none';
        content.innerHTML = '';
        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buf = '';
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buf += decoder.decode(value, { stream: true });
            content.innerHTML = aiMarkdown(buf);
        }
    } catch(e) {
        loading.style.display = 'none';
        content.innerHTML = '<span class="text-danger">Gagal terhubung</span>';
    }
}
</script>

<!-- NERACA SALDO -->
<?php if ($tab == 'neraca'): ?>
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?= $total_debit == $total_kredit ? 'green' : 'orange' ?>" style="width:56px;height:56px;font-size:1.5rem;">
                    <i class="bi <?= $total_debit == $total_kredit ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                </div>
                <div>
                    <div class="stat-label">Status Balance</div>
                    <div class="stat-value" style="font-size:1.1rem;">
                        <?php if ($total_debit == $total_kredit): ?>
                            <span class="text-accent">&#10003; Balance (Debit = Kredit)</span>
                        <?php else: ?>
                            <span class="text-warning">&#10007; Not Balance</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-secondary">Debit: <?= format_rupiah($total_debit) ?> | Kredit: <?= format_rupiah($total_kredit) ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header"><i class="bi bi-list-check me-1"></i> Neraca Saldo</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Kategori</th>
                        <th>Jenis Dana</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($neraca_saldo)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-3">Tidak ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($neraca_saldo as $r): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= $r['kode_akun'] ?></span></td>
                            <td><?= $r['nama_akun'] ?></td>
                            <td><span class="badge bg-info"><?= $r['kategori'] ?></span></td>
                            <td><small class="text-secondary"><?= $r['jenis_dana'] ?? '-' ?></small></td>
                            <td class="text-end amount-text text-success"><?= $r['total_debit'] > 0 ? format_rupiah($r['total_debit']) : '-' ?></td>
                            <td class="text-end amount-text text-danger"><?= $r['total_kredit'] > 0 ? format_rupiah($r['total_kredit']) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">Total</td>
                        <td class="text-end text-success"><?= format_rupiah($total_debit) ?></td>
                        <td class="text-end text-danger"><?= format_rupiah($total_kredit) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- POSISI KEUANGAN -->
<?php elseif ($tab == 'posisi'): ?>
<?php $pk_total_aset = $posisi_keuangan['total_aset']; ?>
<?php $pk_total_passiva = $posisi_keuangan['total_kewajiban'] + $posisi_keuangan['total_aset_neto']; ?>
<?php $pk_balance = $pk_total_aset == $pk_total_passiva; ?>
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?= $pk_balance ? 'green' : 'orange' ?>" style="width:56px;height:56px;font-size:1.5rem;">
                    <i class="bi <?= $pk_balance ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                </div>
                <div>
                    <div class="stat-label">Status Balance</div>
                    <div class="stat-value" style="font-size:1.1rem;">
                        <?php if ($pk_balance): ?>
                            <span class="text-accent">&#10003; Balance (Aset = Kewajiban + Aset Neto)</span>
                        <?php else: ?>
                            <span class="text-warning">&#10007; Not Balance</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-secondary">Aset: <?= format_rupiah($pk_total_aset) ?> | Kewajiban + Aset Neto: <?= format_rupiah($pk_total_passiva) ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header"><i class="bi bi-building me-1"></i> Laporan Posisi Keuangan </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Akun</th><th class="text-end">Jumlah</th></tr>
                </thead>
                <tbody>
                    <tr style="background: #e0f2fe"><td colspan="2"><b>ASET</b></td></tr>
                    <?php foreach ($posisi_keuangan['aset'] as $r): ?>
                    <tr><td>&nbsp;&nbsp; <?= $r['nama'] ?></td><td class="text-end"><?= format_rupiah($r['saldo']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background: #f0fdf4">
                        <td>Total Aset</td><td class="text-end"><?= format_rupiah($posisi_keuangan['total_aset']) ?></td>
                    </tr>

                    <tr style="background: #fef3c7"><td colspan="2"><b>KEWAJIBAN</b></td></tr>
                    <?php foreach ($posisi_keuangan['kewajiban'] as $r): ?>
                    <tr><td>&nbsp;&nbsp; <?= $r['nama'] ?></td><td class="text-end"><?= format_rupiah($r['saldo']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background: #fef3c7">
                        <td>Total Kewajiban</td><td class="text-end"><?= format_rupiah($posisi_keuangan['total_kewajiban']) ?></td>
                    </tr>

                    <tr style="background: #f0fdf4"><td colspan="2"><b>ASET NETO</b></td></tr>
                    <?php foreach ($posisi_keuangan['aset_neto'] as $r): ?>
                    <tr><td>&nbsp;&nbsp; <?= $r['nama'] ?></td><td class="text-end"><?= format_rupiah($r['saldo']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background: #f0fdf4">
                        <td>Total Aset Neto</td><td class="text-end"><?= format_rupiah($posisi_keuangan['total_aset_neto']) ?></td>
                    </tr>

                    <tr class="fw-bold" style="background: #dbeafe">
                        <td>Total Kewajiban + Aset Neto</td>
                        <td class="text-end"><?= format_rupiah($posisi_keuangan['total_kewajiban'] + $posisi_keuangan['total_aset_neto']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- AKTIVITAS -->
<?php elseif ($tab == 'aktivitas'): ?>
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?= $aktivitas['surplus'] >= 0 ? 'green' : 'orange' ?>" style="width:56px;height:56px;font-size:1.5rem;">
                    <i class="bi <?= $aktivitas['surplus'] >= 0 ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill' ?>"></i>
                </div>
                <div>
                    <div class="stat-label">Ringkasan Aktivitas</div>
                    <div class="stat-value" style="font-size:1.1rem;">
                        <?php if ($aktivitas['surplus'] >= 0): ?>
                            <span class="text-accent">Surplus (<?= format_rupiah($aktivitas['surplus']) ?>)</span>
                        <?php else: ?>
                            <span class="text-danger">Defisit (<?= format_rupiah(abs($aktivitas['surplus'])) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-secondary">Pendapatan: <?= format_rupiah($aktivitas['total_pendapatan']) ?> | Beban: <?= format_rupiah($aktivitas['total_beban']) ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header"><i class="bi bi-activity me-1"></i> Laporan Aktivitas </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Akun</th><th class="text-end">Jumlah</th></tr>
                </thead>
                <tbody>
                    <tr style="background: #e0f2fe"><td colspan="2"><b>PENDAPATAN</b></td></tr>
                    <?php foreach ($aktivitas['pendapatan'] as $r): ?>
                    <tr><td>&nbsp;&nbsp; <?= $r['nama'] ?></td><td class="text-end"><?= format_rupiah($r['saldo']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background: #f0fdf4">
                        <td>Total Pendapatan</td><td class="text-end"><?= format_rupiah($aktivitas['total_pendapatan']) ?></td>
                    </tr>

                    <tr style="background: #fee2e2"><td colspan="2"><b>BEBAN</b></td></tr>
                    <?php foreach ($aktivitas['beban'] as $r): ?>
                    <tr><td>&nbsp;&nbsp; <?= $r['nama'] ?></td><td class="text-end"><?= format_rupiah($r['saldo']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background: #fee2e2">
                        <td>Total Beban</td><td class="text-end"><?= format_rupiah($aktivitas['total_beban']) ?></td>
                    </tr>

                    <tr class="fw-bold" style="background: <?= $aktivitas['surplus'] >= 0 ? '#f0fdf4' : '#fee2e2' ?>">
                        <td><?= $aktivitas['surplus'] >= 0 ? 'Surplus' : 'Defisit' ?></td>
                        <td class="text-end"><?= format_rupiah(abs($aktivitas['surplus'])) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ARUS KAS -->
<?php elseif ($tab == 'arus_kas'): ?>
<div class="card">
    <div class="card-header"><i class="bi bi-cash me-1"></i> Laporan Arus Kas</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Keterangan</th><th class="text-end">Jumlah</th></tr>
                </thead>
                <tbody>
                    <tr><td>Saldo Awal Kas</td><td class="text-end"><?= format_rupiah($arus_kas['saldo_awal']) ?></td></tr>
                    <tr style="background: #f0fdf4"><td>Penerimaan Kas</td><td class="text-end"><?= format_rupiah($arus_kas['penerimaan']) ?></td></tr>
                    <tr style="background: #fee2e2"><td>Pengeluaran Kas</td><td class="text-end">(<?= format_rupiah($arus_kas['pengeluaran']) ?>)</td></tr>
                    <tr class="fw-bold" style="background: #dbeafe">
                        <td>Saldo Akhir Kas</td><td class="text-end"><?= format_rupiah($arus_kas['saldo_akhir']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DANA KHUSUS -->
<?php elseif ($tab == 'dana_khusus'): ?>
<div class="row g-3 mb-4">
    <?php foreach ($dana_khusus_list as $dk): ?>
    <?php $saldo_dk = hitung_saldo_dana_khusus($dk['id']); ?>
    <div class="col-md-4 col-6">
        <div class="card stat-card">
            <div class="stat-icon" style="background:#ede9fe;color:#8b5cf6;"><i class="bi bi-piggy-bank"></i></div>
            <div class="stat-label"><?= sanitize($dk['nama_dana']) ?></div>
            <div class="stat-value" style="color:#8b5cf6;"><?= format_rupiah($saldo_dk) ?></div>
            <small class="text-secondary"><?= sanitize($dk['deskripsi']) ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php foreach ($dana_khusus_list as $dk): ?>
<?php $saldo_dk = hitung_saldo_dana_khusus($dk['id']); ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal me-1"></i> <?= sanitize($dk['nama_dana']) ?> (<?= sanitize($dk['kode_dana']) ?>)</span>
        <span class="badge bg-info">Saldo: <?= format_rupiah($saldo_dk) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Jenis</th>
                        <th>Akun</th>
                        <th class="text-end">Pemasukan</th>
                        <th class="text-end">Pengeluaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $trans = $dana_khusus_transaksi[$dk['id']] ?? [];
                    if (empty($trans)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-2">Belum ada transaksi</td></tr>
                    <?php else: ?>
                        <?php foreach ($trans as $t):
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
                            <td><?= $info['akun_lawan'] ? sanitize($info['akun_lawan']['nama_akun']) : '-' ?></td>
                            <td class="text-end text-success"><?= $info['jenis'] == 'Pemasukan' ? format_rupiah($info['jumlah']) : '-' ?></td>
                            <td class="text-end text-danger"><?= $info['jenis'] == 'Pengeluaran' ? format_rupiah($info['jumlah']) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- CALK -->
<?php elseif ($tab == 'calk'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Catatan Atas Laporan Keuangan (CALK) - <?= date('Y') ?></h5>
    <a href="index.php?page=calk" class="btn btn-sm btn-accent"><i class="bi bi-gear"></i> Kelola CALK</a>
</div>
<div class="card">
    <div class="card-body">
        <?php if (empty($calk_list)): ?>
            <p class="text-center text-secondary py-4">Belum ada catatan untuk tahun ini.</p>
        <?php else: ?>
            <?php foreach ($calk_list as $c): ?>
            <div class="mb-4">
                <h6 class="fw-semibold"><?= sanitize($c['judul']) ?></h6>
                <div class="small lh-lg"><?= nl2br(sanitize($c['konten'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

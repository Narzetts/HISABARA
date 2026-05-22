<?php
$pdo = get_connection();
$vendor = __DIR__ . '/../vendor/autoload.php';

$step = $_POST['step'] ?? 'upload';
$import_type = $_POST['import_type'] ?? ($_GET['type'] ?? 'transaksi');
$allowed_types = ['transaksi', 'inventaris'];
if (!in_array($import_type, $allowed_types)) $import_type = 'transaksi';

$akun_list = $pdo->query("SELECT id, kode_akun, nama_akun FROM akun ORDER BY kode_akun")->fetchAll();

if (!function_exists('cek_lock_periode')) {
    function cek_lock_periode($tanggal) {
        if (function_exists('is_periode_tutup') && is_periode_tutup($tanggal)) {
            throw new Exception('Periode sudah ditutup. Transaksi tidak bisa diubah.');
        }
    }
}
?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="hero-icon" style="width:48px;height:48px;background:rgba(16,185,129,0.12);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--accent,#10b981);flex-shrink:0;">
      <i class="bi bi-upload"></i>
    </div>
    <div>
      <h4 class="fw-bold mb-0">Import Data</h4>
      <p class="mb-0 text-secondary" style="font-size:0.88rem;">Import transaksi atau inventaris dari file CSV / Excel</p>
    </div>
  </div>

  <?php if ($step === 'upload'): ?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card card-fade">
        <div class="card-body p-4">
          <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="step" value="preview">

            <div class="mb-4">
              <label class="form-label fw-semibold">Tipe Data</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="import_type" value="transaksi" id="tTransaksi" <?= $import_type === 'transaksi' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="tTransaksi"><i class="bi bi-wallet2 me-1"></i>Transaksi</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="import_type" value="inventaris" id="tInventaris" <?= $import_type === 'inventaris' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="tInventaris"><i class="bi bi-box me-1"></i>Inventaris</label>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">File CSV / Excel</label>
              <div class="upload-zone" id="uploadZone">
                <i class="bi bi-cloud-arrow-up upload-zone-icon"></i>
                <div class="upload-zone-text">Klik atau seret file ke sini</div>
                <div class="upload-zone-hint">CSV, XLSX, atau XLS</div>
                <input type="file" name="file" id="fileInput" class="upload-zone-input" accept=".csv,.xlsx,.xls" required>
              </div>
            </div>

            <div class="mb-4 p-3" style="background:var(--bg-body);border-radius:12px;border:1px solid var(--border-color);">
              <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i>Format Kolom yang Didukung</h6>
              <?php if ($import_type === 'transaksi'): ?>
              <table class="table table-sm table-borderless mb-0" style="font-size:0.82rem;">
                <tr><td class="text-nowrap fw-medium">tanggal *</td><td class="text-muted">YYYY-MM-DD atau DD/MM/YYYY</td></tr>
                <tr><td class="text-nowrap fw-medium">jenis *</td><td class="text-muted">Pemasukan / Pengeluaran</td></tr>
                <tr><td class="text-nowrap fw-medium">jumlah *</td><td class="text-muted">Angka (contoh: 50000 atau 50.000)</td></tr>
                <tr><td class="text-nowrap fw-medium">keterangan *</td><td class="text-muted">Deskripsi transaksi</td></tr>
                <tr><td class="text-nowrap fw-medium">akun *</td><td class="text-muted">Kode atau nama akun (contoh: 4-1000 atau Kas)</td></tr>
                <tr><td class="text-nowrap fw-medium">sumber_dana</td><td class="text-muted">Opsional</td></tr>
                <tr><td class="text-nowrap fw-medium">metode_bayar</td><td class="text-muted">Opsional (Tunai / Transfer / dll)</td></tr>
              </table>
              <?php else: ?>
              <table class="table table-sm table-borderless mb-0" style="font-size:0.82rem;">
                <tr><td class="text-nowrap fw-medium">nama_barang *</td><td class="text-muted">Nama inventaris</td></tr>
                <tr><td class="text-nowrap fw-medium">harga *</td><td class="text-muted">Angka (contoh: 5000000)</td></tr>
                <tr><td class="text-nowrap fw-medium">kondisi</td><td class="text-muted">Baik / Rusak Ringan / Rusak Berat (default: Baik)</td></tr>
                <tr><td class="text-nowrap fw-medium">lokasi</td><td class="text-muted">Opsional</td></tr>
                <tr><td class="text-nowrap fw-medium">keterangan</td><td class="text-muted">Opsional</td></tr>
                <tr><td class="text-nowrap fw-medium">tgl_perolehan</td><td class="text-muted">YYYY-MM-DD (opsional)</td></tr>
                <tr><td class="text-nowrap fw-medium">umur_ekonomis</td><td class="text-muted">Tahun (opsional)</td></tr>
                <tr><td class="text-nowrap fw-medium">nilai_residu</td><td class="text-muted">Angka (opsional)</td></tr>
              </table>
              <?php endif; ?>
              <a href="#" class="small text-accent" onclick="return downloadTemplate('<?= $import_type ?>')"><i class="bi bi-download me-1"></i>Download Template CSV</a>
            </div>

            <button type="submit" class="btn btn-accent w-100"><i class="bi bi-eye me-1"></i> Lihat Pratinjau</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php elseif ($step === 'preview'): ?>
  <?php
  $file = $_FILES['file'] ?? null;
  $errors = [];
  $rows = [];
  $header = [];

  if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Pilih file terlebih dahulu.';
  } else {
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
          $errors[] = 'Format file tidak didukung. Gunakan CSV, XLSX, atau XLS.';
      } else {
          try {
              if ($ext === 'csv') {
                  $fh = fopen($file['tmp_name'], 'r');
                  if (!$fh) { $errors[] = 'Gagal membaca file.'; }
                  else {
                      $header = fgetcsv($fh);
                      if (!$header) { $errors[] = 'File kosong atau tidak valid.'; }
                      else {
                          $header = array_map('trim', $header);
                          while (($row = fgetcsv($fh)) !== false) {
                              $rows[] = $row;
                          }
                      }
                      fclose($fh);
                  }
              } else {
                  require $vendor;
                  $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                  if ($ext === 'xls') {
                      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                  }
                  $spreadsheet = $reader->load($file['tmp_name']);
                  $sheet = $spreadsheet->getActiveSheet();
                  $data = $sheet->toArray();
                  if (count($data) < 2) { $errors[] = 'File kosong atau hanya header.'; }
                  else {
                      $header = array_map('trim', array_map('strval', $data[0]));
                      for ($i = 1; $i < count($data); $i++) {
                          $rows[] = $data[$i];
                      }
                  }
              }
              $_SESSION['import_preview'] = [
                  'type' => $import_type,
                  'header' => $header,
                  'rows' => $rows
              ];
          } catch (Exception $e) {
              $errors[] = 'Gagal membaca file: ' . $e->getMessage();
          }
      }
  }

  if (!empty($errors)): ?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
      <a href="index.php?page=import&type=<?= $import_type ?>" class="btn btn-outline-accent"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
  </div>
  <?php else:
  $header_lower = array_map('strtolower', $header);
  $total = count($rows);
  $max_preview = min(5, $total);
  ?>
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <form method="post" id="importForm">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="import">
        <input type="hidden" name="import_type" value="<?= $import_type ?>">
        <input type="hidden" name="header_json" value="<?= htmlspecialchars(json_encode($header)) ?>">
        <input type="hidden" name="total_rows" value="<?= $total ?>">

        <div class="card card-fade mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table me-1"></i> Pratinjau Data</span>
            <span class="badge bg-accent"><?= $total ?> baris</span>
          </div>
          <div class="card-body p-0" style="overflow-x:auto;">
            <table class="table table-sm table-hover mb-0 import-table">
              <thead>
                <tr>
                  <th class="text-center" style="width:40px;">#</th>
                  <?php foreach ($header as $h): ?>
                  <th><?= htmlspecialchars($h) ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php for ($i = 0; $i < $max_preview; $i++): ?>
                <tr>
                  <td class="text-center text-muted"><?= $i + 1 ?></td>
                  <?php foreach ($rows[$i] as $cell): ?>
                  <td><?= htmlspecialchars($cell ?? '') ?></td>
                  <?php endforeach; ?>
                </tr>
                <?php endfor; ?>
                <?php if ($total > $max_preview): ?>
                <tr><td colspan="<?= count($header) + 1 ?>" class="text-center text-muted py-2">... dan <?= $total - $max_preview ?> baris lainnya</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card card-fade mb-3">
          <div class="card-header"><i class="bi bi-gear me-1"></i> Pemetaan Kolom</div>
          <div class="card-body">
            <p class="small text-secondary mb-3">Cocokkan kolom dari file dengan field database. Kolom yang sama persis akan terdeteksi otomatis.</p>
            <div class="row g-3">
              <?php
              $field_map = $import_type === 'transaksi'
                  ? [
                      'tanggal' => ['label' => 'Tanggal *', 'required' => true],
                      'jenis' => ['label' => 'Jenis *', 'required' => true],
                      'jumlah' => ['label' => 'Jumlah *', 'required' => true],
                      'keterangan' => ['label' => 'Keterangan *', 'required' => true],
                      'akun' => ['label' => 'Akun *', 'required' => true],
                      'sumber_dana' => ['label' => 'Sumber Dana', 'required' => false],
                      'metode_bayar' => ['label' => 'Metode Bayar', 'required' => false],
                      'tujuan' => ['label' => 'Tujuan', 'required' => false],
                  ]
                  : [
                      'nama_barang' => ['label' => 'Nama Barang *', 'required' => true],
                      'harga' => ['label' => 'Harga *', 'required' => true],
                      'kondisi' => ['label' => 'Kondisi', 'required' => false],
                      'lokasi' => ['label' => 'Lokasi', 'required' => false],
                      'keterangan' => ['label' => 'Keterangan', 'required' => false],
                      'tgl_perolehan' => ['label' => 'Tgl Perolehan', 'required' => false],
                      'umur_ekonomis' => ['label' => 'Umur Ekonomis', 'required' => false],
                      'nilai_residu' => ['label' => 'Nilai Residu', 'required' => false],
                      'metode_penyusutan' => ['label' => 'Metode Penyusutan', 'required' => false],
                  ];

              $hl_map = [];
              foreach ($header_lower as $idx => $hl) {
                  $hl_clean = str_replace([' ', '-', '/'], '_', $hl);
                  $hl_map[$hl_clean] = $idx;
              }

              foreach ($field_map as $field => $info):
                  $auto_idx = $hl_map[$field] ?? $hl_map[str_replace('_', '', $field)] ?? null;
                  $auto_val = $auto_idx !== null ? $auto_idx : '';
              ?>
              <div class="col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1"><?= $info['label'] ?></label>
                <select name="map[<?= $field ?>]" class="form-select form-select-sm" <?= $info['required'] ? 'required' : '' ?>>
                  <option value="">— Lewati —</option>
                  <?php foreach ($header as $hidx => $hname): ?>
                  <option value="<?= $hidx ?>" <?= (string)$auto_val === (string)$hidx ? 'selected' : '' ?>><?= htmlspecialchars($hname) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="index.php?page=import&type=<?= $import_type ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
          <button type="submit" class="btn btn-accent flex-grow-1" data-confirm="Import <?= $total ?> baris data?"><i class="bi bi-upload me-1"></i> Import <?= $total ?> Baris</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php elseif ($step === 'import'):
  $import_type = $_POST['import_type'] ?? 'transaksi';
  $total_rows = (int)($_POST['total_rows'] ?? 0);
  $map = $_POST['map'] ?? [];
  $import_data = $_SESSION['import_preview'] ?? null;
  unset($_SESSION['import_preview']);

  $imported = 0;
  $failed = 0;
  $errors = [];
  $kas_id = get_akun_kas();

  if ($import_data && $import_data['type'] === $import_type) {
      $all_rows = $import_data['rows'];

      try {
          foreach ($all_rows as $row_idx => $row) {
              $row_num = $row_idx + 2;
              $row_errors = [];

              try {
                  if ($import_type === 'transaksi') {
                      // Ambil field dari mapping
                      $tanggal = trim($row[(int)($map['tanggal'] ?? 0)] ?? '');
                      $jenis = trim($row[(int)($map['jenis'] ?? 0)] ?? '');
                      $jumlah_raw = trim($row[(int)($map['jumlah'] ?? 0)] ?? '');
                      $keterangan = trim($row[(int)($map['keterangan'] ?? 0)] ?? '');
                      $akun_input = trim($row[(int)($map['akun'] ?? 0)] ?? '');
                      $sumber_dana = isset($map['sumber_dana']) ? trim($row[(int)$map['sumber_dana']] ?? '') : '';
                      $metode_bayar = isset($map['metode_bayar']) ? trim($row[(int)$map['metode_bayar']] ?? '') : '';
                      $tujuan = isset($map['tujuan']) ? trim($row[(int)$map['tujuan']] ?? '') : '';

                      // Validasi
                      if (!$tanggal) { $row_errors[] = 'Tanggal kosong'; }
                      if (!in_array($jenis, ['Pemasukan', 'Pengeluaran'])) { $row_errors[] = 'Jenis harus Pemasukan/Pengeluaran'; }
                      if (!$keterangan) { $row_errors[] = 'Keterangan kosong'; }

                      // Parse jumlah
                      $jumlah = str_replace(['.', ','], ['', '.'], $jumlah_raw);
                      $jumlah = (float)$jumlah;
                      if ($jumlah <= 0) { $row_errors[] = 'Jumlah tidak valid'; }

                      // Cari akun
                      $akun_id = null;
                      if ($akun_input) {
                          $stmt = $pdo->prepare("SELECT id FROM akun WHERE kode_akun = ? OR nama_akun = ? LIMIT 1");
                          $stmt->execute([$akun_input, $akun_input]);
                          $akun_id = $stmt->fetchColumn();
                          if (!$akun_id) { $row_errors[] = "Akun '$akun_input' tidak ditemukan"; }
                      } else { $row_errors[] = 'Akun kosong'; }

                      // Parse tanggal
                      $tanggal_fix = null;
                      if ($tanggal) {
                          if (strpos($tanggal, '/') !== false) {
                              $parts = explode('/', $tanggal);
                              $tanggal_fix = count($parts) === 3 ? $parts[2] . '-' . $parts[1] . '-' . $parts[0] : $tanggal;
                          } else { $tanggal_fix = $tanggal; }
                      }

                      if (empty($row_errors)) {
                          cek_lock_periode($tanggal_fix);
                          $pdo->beginTransaction();
                          $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, keterangan, sumber_dana, metode_bayar, tujuan, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
                          $stmt->execute([$tanggal_fix, $keterangan, $sumber_dana, $metode_bayar, $tujuan, $_SESSION['user_id']]);
                          $tid = $pdo->lastInsertId();

                          if ($jenis === 'Pemasukan') {
                              $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$tid, $kas_id, $jumlah]);
                              $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$tid, $akun_id, $jumlah]);
                          } else {
                              $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$tid, $akun_id, $jumlah]);
                              $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$tid, $kas_id, $jumlah]);
                          }
                          audit_log('Import', 'transaksi', $tid, "$jenis: $keterangan - " . format_rupiah($jumlah));
                          $pdo->commit();
                          $imported++;
                      }
                  } else {
                      // Inventaris
                      $nama_barang = trim($row[(int)($map['nama_barang'] ?? 0)] ?? '');
                      $harga_raw = trim($row[(int)($map['harga'] ?? 0)] ?? '');
                      $kondisi = trim($row[(int)($map['kondisi'] ?? 0)] ?? '');
                      $lokasi = isset($map['lokasi']) ? trim($row[(int)$map['lokasi']] ?? '') : '';
                      $keterangan = isset($map['keterangan']) ? trim($row[(int)$map['keterangan']] ?? '') : '';
                      $tgl_perolehan = isset($map['tgl_perolehan']) ? trim($row[(int)$map['tgl_perolehan']] ?? '') : '';
                      $umur_ekonomis_raw = isset($map['umur_ekonomis']) ? trim($row[(int)$map['umur_ekonomis']] ?? '') : '';
                      $nilai_residu_raw = isset($map['nilai_residu']) ? trim($row[(int)$map['nilai_residu']] ?? '') : '';
                      $metode_penyusutan = isset($map['metode_penyusutan']) ? trim($row[(int)$map['metode_penyusutan']] ?? '') : 'garis_lurus';

                      if (!$nama_barang) { $row_errors[] = 'Nama barang kosong'; }

                      $harga = str_replace(['.', ','], ['', '.'], $harga_raw);
                      $harga = (float)$harga;
                      if ($harga <= 0) { $row_errors[] = 'Harga tidak valid'; }

                      $kondisi = in_array($kondisi, ['Baik', 'Rusak Ringan', 'Rusak Berat']) ? $kondisi : 'Baik';
                      $tahun_pembelian = $tgl_perolehan ? date('Y', strtotime($tgl_perolehan)) : (is_numeric($umur_ekonomis_raw) ? (int)$umur_ekonomis_raw : null);
                      $umur_ekonomis = is_numeric($umur_ekonomis_raw) ? (int)$umur_ekonomis_raw : 0;
                      $nilai_residu = str_replace(['.', ','], ['', '.'], $nilai_residu_raw);
                      $nilai_residu = is_numeric($nilai_residu) ? (float)$nilai_residu : 0;
                      $metode_penyusutan = in_array($metode_penyusutan, ['garis_lurus', 'saldo_menurun']) ? $metode_penyusutan : 'garis_lurus';

                      if (empty($row_errors)) {
                          $stmt = $pdo->prepare("INSERT INTO inventaris (nama_barang, tahun_pembelian, harga, kondisi, lokasi, keterangan, umur_ekonomis, nilai_residu, metode_penyusutan, tgl_perolehan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                          $stmt->execute([$nama_barang, $tahun_pembelian, $harga, $kondisi, $lokasi, $keterangan, $umur_ekonomis, $nilai_residu, $metode_penyusutan, $tgl_perolehan ?: null]);
                          audit_log('Import', 'inventaris', $pdo->lastInsertId(), "Barang: $nama_barang");
                          $imported++;
                      }
                  }

                  if (!empty($row_errors)) {
                      $failed++;
                      $errors[] = 'Baris ' . $row_num . ': ' . implode(', ', $row_errors);
                  }
              } catch (Exception $e) {
                  if ($pdo->inTransaction()) $pdo->rollBack();
                  $failed++;
                  $errors[] = 'Baris ' . $row_num . ': ' . $e->getMessage();
              }
          }
      } catch (Exception $e) {
          $failed = $total_rows;
          $errors[] = 'Gagal memproses data: ' . $e->getMessage();
      }
  } else {
      $errors[] = 'Sesi import tidak ditemukan. Silakan upload ulang.';
  }
  ?>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card card-fade">
        <div class="card-body p-4 text-center">
          <div style="font-size:3rem;margin-bottom:1rem;">
            <?php if ($failed === 0): ?>
            <i class="bi bi-check-circle" style="color:var(--accent,#10b981);"></i>
            <?php else: ?>
            <i class="bi bi-exclamation-triangle" style="color:#f59e0b;"></i>
            <?php endif; ?>
          </div>
          <h5 class="fw-bold mb-2">Import Selesai</h5>
          <div class="d-flex justify-content-center gap-4 mb-3">
            <div>
              <div class="fw-bold fs-4" style="color:var(--accent,#10b981);"><?= $imported ?></div>
              <div class="small text-secondary">Berhasil</div>
            </div>
            <div>
              <div class="fw-bold fs-4" style="color:#ef4444;"><?= $failed ?></div>
              <div class="small text-secondary">Gagal</div>
            </div>
            <div>
              <div class="fw-bold fs-4"><?= $imported + $failed ?></div>
              <div class="small text-secondary">Total</div>
            </div>
          </div>

          <?php if (!empty($errors)): ?>
          <div class="text-start mb-3">
            <div class="fw-semibold small mb-1">Detail Error:</div>
            <div style="max-height:200px;overflow-y:auto;font-size:0.8rem;background:var(--bg-body);border-radius:8px;padding:0.75rem;border:1px solid var(--border-color);">
              <?php foreach ($errors as $err): ?>
              <div class="mb-1"><?= htmlspecialchars($err) ?></div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="d-flex gap-2 justify-content-center">
            <a href="index.php?page=import&type=<?= $import_type ?>" class="btn btn-outline-accent"><i class="bi bi-upload me-1"></i>Import Lagi</a>
            <a href="index.php?page=<?= $import_type ?>" class="btn btn-accent"><i class="bi bi-arrow-right me-1"></i>Lihat <?= $import_type === 'transaksi' ? 'Transaksi' : 'Inventaris' ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function downloadTemplate(type) {
  if (type === 'transaksi') {
    const headers = ['tanggal','jenis','jumlah','keterangan','akun','sumber_dana','metode_bayar'];
    const sample = ['2026-01-15','Pemasukan','500000','Infak Jumat','4-1000','Umum','Tunai'];
    let csv = headers.join(',') + '\n' + sample.join(',') + '\n';
    const blob = new Blob(["\uFEFF" + csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'template_transaksi.csv'; a.click();
  } else {
    const headers = ['nama_barang','harga','kondisi','lokasi','keterangan','tgl_perolehan','umur_ekonomis','nilai_residu'];
    const sample = ['Meja Rapat','2000000','Baik','Ruang Rapat','Meja rapat pengurus','2026-01-01','5','200000'];
    let csv = headers.join(',') + '\n' + sample.join(',') + '\n';
    const blob = new Blob(["\uFEFF" + csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'template_inventaris.csv'; a.click();
  }
  return false;
}

// Upload zone drag & drop
const zone = document.getElementById('uploadZone');
const input = document.getElementById('fileInput');
if (zone && input) {
  zone.addEventListener('click', () => input.click());
  input.addEventListener('change', () => {
    if (input.files.length > 0) {
      zone.querySelector('.upload-zone-text').textContent = input.files[0].name;
      zone.classList.add('has-file');
    }
  });
  zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', (e) => {
    e.preventDefault(); zone.classList.remove('drag-over');
    if (e.dataTransfer.files.length > 0) {
      input.files = e.dataTransfer.files;
      zone.querySelector('.upload-zone-text').textContent = input.files[0].name;
      zone.classList.add('has-file');
    }
  });
}
</script>

<style>
.upload-zone {
  border: 2px dashed var(--border-color);
  border-radius: 16px;
  padding: 2.5rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: var(--bg-body);
}
.upload-zone:hover { border-color: var(--accent); background: rgba(16,185,129,0.02); }
.upload-zone.drag-over { border-color: var(--accent); background: rgba(16,185,129,0.05); transform: scale(1.01); }
.upload-zone.has-file { border-style: solid; border-color: var(--accent); background: rgba(16,185,129,0.03); }
.upload-zone-icon { font-size: 2.5rem; color: var(--text-muted); opacity: 0.4; margin-bottom: 0.5rem; }
.upload-zone-text { font-weight: 600; color: var(--text-primary); }
.upload-zone-hint { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem; }
.upload-zone-input { display: none; }
.import-table { font-size: 0.82rem; }
.import-table thead th { background: var(--bg-body); position: sticky; top: 0; white-space: nowrap; }
</style>
<?php ?>
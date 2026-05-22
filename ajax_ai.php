<?php
error_reporting(0);
require_once 'config/database.php';
require_once 'config/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
check_auth();

$action = $_GET['action'] ?? '';
$stream = isset($_GET['stream']) && $_GET['stream'] === '1';

require_once __DIR__ . '/config/ai.php';

if ($stream) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    ob_implicit_flush(true);
    while (ob_get_level()) ob_end_clean();
}

switch ($action) {
    case 'insight':
        $res = ai_insight_keuangan($stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'prediksi':
        $res = ai_prediksi_saldo($stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'anomali':
        $res = ai_deteksi_anomali($stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'rekomendasi':
        $res = ai_rekomendasi_hemat($stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'audit':
        $res = ai_audit_keuangan($stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'narasi_laporan':
        $dari = date('Y-m-01');
        $sampai = date('Y-m-t');
        $aktivitas = get_laporan_aktivitas($dari, $sampai);
        $arus_kas = get_laporan_arus_kas($dari, $sampai);
        $data = [
            'Total Pendapatan' => format_rupiah($aktivitas['total_pendapatan'] ?? 0),
            'Total Beban' => format_rupiah($aktivitas['total_beban'] ?? 0),
            'Surplus/Defisit' => format_rupiah($aktivitas['surplus'] ?? 0),
            'Saldo Kas' => format_rupiah($arus_kas['saldo_akhir'] ?? 0),
        ];
        $res = ai_narasi_laporan('Laporan Keuangan ' . date('F Y'), $data, false);
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'narasi':
        $judul = $_GET['judul'] ?? 'Laporan Keuangan';
        $data_raw = $_GET['data'] ?? '{}';
        $data = json_decode($data_raw, true) ?: [];
        $res = ai_narasi_laporan($judul, $data, $stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'chat_keuangan':
        $pesan = $_GET['pesan'] ?? '';
        if (!$pesan) { echo json_encode(['error' => 'Silakan tulis pertanyaan.']); break; }

        // Coba parse sebagai transaksi dulu
        $res = ai_parse_transaksi($pesan);
        $parsed = json_decode($res['content'] ?? '', true);

        if ($parsed && !empty($parsed['_parse'])) {
            unset($parsed['_parse']);
            $parsed['_parse'] = true;
            while (ob_get_level()) ob_end_clean();
            echo json_encode($parsed);
            break;
        }

        // Jika bukan transaksi, jawab sebagai chat biasa
        $saldo = get_total_saldo();
        $pdo = get_connection();
        $total_dana_sosial = $pdo->query("SELECT COALESCE(SUM(jd.kredit),0) FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id WHERE a.kategori='Beban' AND a.nama_akun LIKE '%sosial%'")->fetchColumn();
        $konteks = "Data keuangan masjid:\nSaldo: " . format_rupiah($saldo['saldo_kas']);
        $konteks .= "\nPemasukan: " . format_rupiah($saldo['pemasukan']);
        $konteks .= "\nPengeluaran: " . format_rupiah($saldo['pengeluaran']);
        $konteks .= "\nDana sosial: " . format_rupiah($total_dana_sosial);

        // Tambahkan request khusus
        $isRingkasan = stripos($pesan, 'ringkasan') !== false || stripos($pesan, 'summary') !== false;
        $isNarasi = stripos($pesan, 'narasi') !== false;
        $isEfisiensi = stripos($pesan, 'efisiensi') !== false || stripos($pesan, 'hemat') !== false;

        if ($isRingkasan) {
            $res = ai_insight_keuangan(false);
            while (ob_get_level()) ob_end_clean();
            echo $res['error'] ?? $res['content'];
            break;
        }
        if ($isNarasi) {
            $aktivitas = get_laporan_aktivitas(date('Y-m-01'), date('Y-m-t'));
            $arus_kas = get_laporan_arus_kas(date('Y-m-01'), date('Y-m-t'));
            $data = [
                'Total Pendapatan' => format_rupiah($aktivitas['total_pendapatan'] ?? 0),
                'Total Beban' => format_rupiah($aktivitas['total_beban'] ?? 0),
                'Surplus/Defisit' => format_rupiah($aktivitas['surplus'] ?? 0),
                'Saldo Kas' => format_rupiah($arus_kas['saldo_akhir'] ?? 0),
            ];
            $res = ai_narasi_laporan('Laporan Keuangan', $data, false);
            while (ob_get_level()) ob_end_clean();
            echo $res['error'] ?? $res['content'];
            break;
        }
        if ($isEfisiensi) {
            $res = ai_rekomendasi_hemat(false);
            while (ob_get_level()) ob_end_clean();
            echo $res['error'] ?? $res['content'];
            break;
        }

        $system = "Anda adalah asisten keuangan masjid yang membantu pengurus. Jawab pertanyaan seputar keuangan masjid dengan ringkas dan jelas. Gunakan bahasa Indonesia. Jika ditanya di luar topik keuangan masjid, jawab: Maaf, saya hanya dapat menjawab pertanyaan seputar keuangan masjid.";
        $res = ai_prompt($system, $konteks . "\n\nPertanyaan: " . $pesan, 300);
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'chat':
        $pesan = $_GET['pesan'] ?? '';
        if (!$pesan) { echo json_encode(['error' => 'Silakan tulis pertanyaan.']); break; }

        // Normal chat without transaction parsing
        $konteks = "Data keuangan masjid:\nSaldo: " . format_rupiah($saldo['saldo_kas']);
        $konteks .= "\nPemasukan: " . format_rupiah($saldo['pemasukan']);
        $konteks .= "\nPengeluaran: " . format_rupiah($saldo['pengeluaran']);
        $konteks .= "\nDana khusus: " . format_rupiah(get_total_dana_khusus());

        $system = "Asisten keuangan masjid yang ramah dan membantu. Jawab pertanyaan seputar keuangan masjid dengan cara yang mudah dipahami. Gunakan emoji secara terbatas. Bahasa Indonesia.";
        $res = ai_prompt($system, $konteks . "\n\nPertanyaan: " . $pesan, 300, $stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'financial_monitor':
        try {
            $alerts = get_financial_alerts();
            while (ob_get_level()) ob_end_clean();
            echo json_encode($alerts);
        } catch (Throwable $e) {
            while (ob_get_level()) ob_end_clean();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'cek_struk':
        $file = $_GET['file'] ?? '';
        if (!$file || !file_exists($file)) {
            echo 'File tidak ditemukan.';
            break;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);
        $allowed = [
            'image/jpeg', 'image/png', 'image/webp',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword'
        ];
        if (!in_array($mime, $allowed)) {
            echo 'Tipe file tidak didukung. Gunakan JPG, PNG, PDF, atau Word.';
            break;
        }
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            $img_info = getimagesize($file);
            $max_dim = 1200;
            if ($img_info && ($img_info[0] > $max_dim || $img_info[1] > $max_dim)) {
                $src = null;
                if ($mime === 'image/jpeg') $src = imagecreatefromjpeg($file);
                elseif ($mime === 'image/png') $src = imagecreatefrompng($file);
                elseif ($mime === 'image/webp') $src = imagecreatefromwebp($file);
                if ($src) {
                    $ratio = min($max_dim / $img_info[0], $max_dim / $img_info[1]);
                    $new_w = round($img_info[0] * $ratio);
                    $new_h = round($img_info[1] * $ratio);
                    $dst = imagecreatetruecolor($new_w, $new_h);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $img_info[0], $img_info[1]);
                    ob_start();
                    if ($mime === 'image/jpeg') imagejpeg($dst, null, 85);
                    elseif ($mime === 'image/png') imagepng($dst, null, 6);
                    elseif ($mime === 'image/webp') imagewebp($dst, null, 85);
                    $compressed = ob_get_clean();
                    imagedestroy($src);
                    imagedestroy($dst);
                    $img_data = base64_encode($compressed);
                } else {
                    $img_data = base64_encode(file_get_contents($file));
                }
            } else {
                $img_data = base64_encode(file_get_contents($file));
            }
            $res = ai_cek_struk($img_data, $mime);
        } else {
            $teks = ekstrak_teks_dokumen($file, $mime);
            if (!$teks) {
                echo 'Gagal membaca isi dokumen. Pastikan file tidak rusak.';
                break;
            }
            $res = ai_cek_struk_teks($teks);
        }
        echo $res['error'] ?? $res['content'];
        break;

    case 'parse_transaksi':
        error_reporting(0);
        $teks = $_GET['teks'] ?? '';
        if (!$teks) { echo json_encode(['error' => 'Masukkan deskripsi transaksi']); break; }
        $res = ai_parse_transaksi($teks);
        while (ob_get_level()) ob_end_clean();
        if ($res['error']) { echo json_encode(['error' => $res['error']]); break; }
        echo $res['content'];
        break;

    case 'ringkasan':
        $res = ai_ringkasan_jamaah($stream);
        if (!$stream) echo $res['error'] ?? $res['content'];
        break;

    case 'perbaikan_data':
        $res = ai_perbaikan_data();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'perbaikan_pencatatan':
        $res = ai_perbaikan_pencatatan();
        while (ob_get_level()) ob_end_clean();
        if ($res['error']) { echo json_encode(['error' => $res['error']]); break; }
        echo $res['content'];
        break;

    case 'perbaikan_anggaran':
        $res = ai_perbaikan_anggaran();
        while (ob_get_level()) ob_end_clean();
        if ($res['error']) { echo json_encode(['error' => $res['error']]); break; }
        echo $res['content'];
        break;

    case 'buat_anggaran':
        $teks = $_GET['teks'] ?? '';
        if (!$teks) { echo json_encode(['error' => 'Masukkan instruksi anggaran']); break; }
        $res = ai_buat_anggaran($teks);
        while (ob_get_level()) ob_end_clean();
        if ($res['error']) { echo json_encode(['error' => $res['error']]); break; }
        echo $res['content'];
        break;

    case 'terapkan_anggaran':
        header('Content-Type: application/json; charset=utf-8');
        while (ob_get_level()) ob_end_clean();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['items'])) {
            echo json_encode(['success' => false, 'error' => 'Data anggaran tidak valid.']);
            break;
        }
        $res = ai_terapkan_anggaran($input);
        echo json_encode($res);
        break;

    case 'terapkan_perbaikan':
        header('Content-Type: application/json; charset=utf-8');
        while (ob_get_level()) ob_end_clean();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['transaksi_id'])) {
            echo json_encode(['success' => false, 'error' => 'Data perbaikan tidak valid.']);
            break;
        }
        $res = ai_terapkan_perbaikan($input);
        echo json_encode($res);
        break;

    case 'ketahanan':
        $res = ai_ketahanan_finansial();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'dampak_sosial':
        $res = ai_dampak_sosial();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'skor_transparansi':
        $res = ai_skor_transparansi();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'tren_keuangan':
        $res = ai_tren_keuangan();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'pola_transaksi':
        $res = ai_pola_transaksi();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'kepatuhan':
        $keputusan = $_GET['keputusan'] ?? 'Pengelolaan keuangan masjid saat ini';
        $res = ai_evaluasi_kepatuhan($keputusan);
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'risiko_kas':
        $keputusan = $_GET['keputusan'] ?? 'Pengelolaan keuangan masjid saat ini';
        $res = ai_risiko_kas($keputusan);
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'cerita_dana':
        $res = ai_cerita_dana();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'generate_calk':
        $tahun = (int)($_GET['tahun'] ?? date('Y'));
        $res = ai_generate_calk($tahun);
        while (ob_get_level()) ob_end_clean();
        if ($res['error']) {
            echo json_encode(['error' => $res['error']]);
        } else {
            $text = trim(preg_replace('/```json\s*|\s*```/', '', $res['content']));
            $json = json_decode($text, true);
            if (!$json) {
                echo json_encode(['error' => 'Gagal memproses respon: ' . substr($text, 0, 200)]);
            } else {
                echo json_encode(['data' => $json]);
            }
        }
        break;

    case 'save_calk':
        header('Content-Type: application/json; charset=utf-8');
        while (ob_get_level()) ob_end_clean();
        $input = json_decode(file_get_contents('php://input'), true);
        $entries = $input['entries'] ?? [];
        $tahun = (int)($input['tahun'] ?? date('Y'));
        $pdo = get_connection();
        $saved = 0;
        $errors = [];
        foreach ($entries as $i => $item) {
            $judul = trim($item['judul'] ?? '');
            $konten = trim($item['konten'] ?? '');
            $urutan = (int)($item['urutan'] ?? ($i + 1));
            if (!$judul || !$konten) continue;
            try {
                $stmt = $pdo->prepare("INSERT INTO calk (judul, konten, urutan, tahun) VALUES (?, ?, ?, ?)");
                $stmt->execute([$judul, $konten, $urutan, $tahun]);
                $saved++;
            } catch (Exception $e) {
                $errors[] = $judul . ': ' . $e->getMessage();
            }
        }
        echo json_encode([
            'success' => $saved > 0,
            'saved' => $saved,
            'total' => count($entries),
            'errors' => $errors
        ]);
        break;

    case 'audit_risk':
        $res = ai_audit_risk_assessment();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'audit_timing':
        $res = ai_audit_timing_analysis();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'audit_comprehensive':
        $res = ai_audit_comprehensive_report();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    default:
        echo 'Aksi tidak dikenal.';
}

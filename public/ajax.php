<?php
error_reporting(0);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/ai.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'ringkasan':
        $res = ai_ringkasan_jamaah(false);
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
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

    case 'cerita_dana':
        $res = ai_cerita_dana();
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    case 'chat':
        $pesan = $_GET['pesan'] ?? '';
        if (!$pesan) { echo json_encode(['error' => 'Silakan tulis pertanyaan.']); break; }

        // Kumpulkan data keuangan untuk konteks
        $saldo = get_total_saldo();
        $pdo = get_connection();
        $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();
        $total_dana_sosial = $pdo->query("SELECT COALESCE(SUM(jd.kredit),0) FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id WHERE a.kategori='Beban' AND a.nama_akun LIKE '%sosial%'")->fetchColumn();
        $pengeluaran = $pdo->query("SELECT a.nama_akun, SUM(jd.debit) total FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Beban' AND t.status='approved' AND jd.debit>0 GROUP BY a.nama_akun ORDER BY total DESC LIMIT 5")->fetchAll();
        $pemasukan = $pdo->query("SELECT a.nama_akun, SUM(jd.kredit) total FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Pendapatan' AND t.status='approved' AND jd.kredit>0 GROUP BY a.nama_akun ORDER BY total DESC LIMIT 5")->fetchAll();

        $konteks = "Data keuangan " . (get_setting('nama_masjid') ?: 'Masjid') . ":\n";
        $konteks .= "Saldo kas: " . format_rupiah($saldo['saldo_kas']) . "\n";
        $konteks .= "Total pemasukan: " . format_rupiah($saldo['pemasukan']) . "\n";
        $konteks .= "Total pengeluaran: " . format_rupiah($saldo['pengeluaran']) . "\n";
        $konteks .= "Total transaksi: $total_transaksi\n";
        $konteks .= "Dana sosial: " . format_rupiah($total_dana_sosial) . "\n\n";
        $konteks .= "Pemasukan terbesar:\n";
        foreach ($pemasukan as $p) $konteks .= "- {$p['nama_akun']}: " . format_rupiah($p['total']) . "\n";
        $konteks .= "Pengeluaran terbesar:\n";
        foreach ($pengeluaran as $p) $konteks .= "- {$p['nama_akun']}: " . format_rupiah($p['total']) . "\n";

        $system = "Anda adalah asisten keuangan masjid. Anda HANYA boleh menjawab pertanyaan yang terkait dengan keuangan masjid, pengelolaan dana masjid, program masjid, atau transparansi keuangan masjid. Untuk SEMUA pertanyaan lain (termasuk namun tidak terbatas pada: agama, politik, kesehatan, teknologi, hiburan, pendidikan umum, berita, opini, resep, saran pribadi, matematika, IPA, sejarah, geografi, bahasa asing, terjemahan, coding, dll.), jawab dengan tegas: 'Maaf, saya hanya dapat menjawab pertanyaan seputar keuangan dan pengelolaan masjid.' Jangan berikan jawaban lain di luar topik masjid. Gunakan bahasa Indonesia yang ramah dan mudah dipahami publik.";
        $res = ai_prompt($system, $konteks . "\nPertanyaan: " . $pesan, 300);
        while (ob_get_level()) ob_end_clean();
        echo $res['error'] ?? $res['content'];
        break;

    default:
        echo json_encode(['error' => 'Aksi tidak dikenal.']);
}

<?php

// Load API key priority: .env > fallback
if (!defined('AI_API_KEY')) {
    $key = getenv('AI_API_KEY');
    if (!$key) {
        $env_file = __DIR__ . '/../.env';
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'AI_API_KEY=') === 0) {
                    $key = trim(substr($line, 11));
                    break;
                }
            }
        }
    }
    define('AI_API_KEY', $key ?: '');
}
if (!defined('AI_MODEL')) define('AI_MODEL', 'deepseek/deepseek-chat');
if (!defined('AI_API_URL')) define('AI_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
if (!defined('AI_CACHE_DIR')) define('AI_CACHE_DIR', __DIR__ . '/../cache');
if (!defined('AI_CACHE_TTL')) define('AI_CACHE_TTL', 7200);
if (!defined('AI_MAX_HISTORY')) define('AI_MAX_HISTORY', 6);

function ai_cache_key($messages) {
    return 'ai_' . md5(json_encode($messages) . AI_MODEL);
}

function ai_cache_get($key) {
    $file = AI_CACHE_DIR . '/' . $key . '.cache';
    if (file_exists($file) && (time() - filemtime($file)) < AI_CACHE_TTL) {
        return file_get_contents($file);
    }
    return null;
}

function ai_cache_set($key, $content) {
    if (!is_dir(AI_CACHE_DIR)) mkdir(AI_CACHE_DIR, 0755, true);
    file_put_contents(AI_CACHE_DIR . '/' . $key . '.cache', $content);
}

function ai_chat($messages, $max_tokens = 200, $stream = false) {
    $cache_key = ai_cache_key($messages);
    $cached = ai_cache_get($cache_key);
    if ($cached && !$stream) return ['content' => $cached];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . AI_API_KEY,
        'HTTP-Referer: http://localhost/bis',
        'X-Title: Hisabara',
    ];

    $body = json_encode([
        'model' => AI_MODEL,
        'messages' => $messages,
        'max_tokens' => $max_tokens,
        'temperature' => 0.1,
        'stream' => $stream,
    ]);

    $max_retry = 1;
    $retry_delay = 1;
    $response = '';
    $http_code = 0;
    $error = '';

    for ($attempt = 1; $attempt <= $max_retry; $attempt++) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => AI_API_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TCP_FASTOPEN => 1,
        ]);

        if ($stream) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, 'data: ') === 0) {
                        $json = substr($line, 6);
                        if ($json === '[DONE]') continue;
                        $parsed = json_decode($json, true);
                        if (isset($parsed['choices'][0]['delta']['content'])) {
                            echo $parsed['choices'][0]['delta']['content'];
                            flush();
                        }
                    }
                }
                return strlen($data);
            });
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($error) return ['error' => 'Koneksi gagal: ' . $error];
            return ['stream' => true];
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) return ['error' => 'Koneksi gagal: ' . $error];
            if ($http_code !== 429 && $http_code !== 503) break;

            if ($attempt < $max_retry) {
                sleep($retry_delay * $attempt);
            }
        }
    }

    if ($stream) return ['stream' => true];

    $data = json_decode($response, true);

    if ($http_code != 200) {
        $msg = $data['error']['message'] ?? substr($response, 0, 300);
        return ['error' => 'HTTP ' . $http_code . ': ' . $msg];
    }

    if (!$data) return ['error' => 'Respon JSON tidak valid'];
    if (isset($data['error'])) return ['error' => 'API Error: ' . ($data['error']['message'] ?? json_encode($data['error']))];
    if (!isset($data['choices'][0]['message']['content'])) return ['error' => 'Respon tidak valid'];

    $content = trim($data['choices'][0]['message']['content']);
    ai_cache_set($cache_key, $content);
    return ['content' => $content];
}

function ai_prompt($system, $user_input, $max_tokens = 200, $stream = false) {
    $system = rtrim($system, '.') . '. Jawab singkat dan langsung ke poin.';
    return ai_chat([
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user_input],
    ], $max_tokens, $stream);
}

function ai_insight_keuangan($stream = false) {
    $pemasukan = get_data_grafik();
    $pengeluaran = get_data_grafik_beban();
    $saldo = get_total_saldo();

    $data = "Saldo: " . format_rupiah($saldo['saldo_kas']) . " | Pemasukan: " . format_rupiah($saldo['pemasukan']) . " | Pengeluaran: " . format_rupiah($saldo['pengeluaran']) . "\n";
    foreach ($pemasukan as $g) {
        $data .= $g['bulan'] . " Msk:" . format_rupiah($g['pemasukan']) . " Klr:" . format_rupiah($g['pengeluaran']) . "\n";
    }
    foreach ($pengeluaran as $b) {
        $data .= $b['nama_akun'] . ":" . format_rupiah($b['total']) . "\n";
    }

    $system = "Analis keuangan masjid. Berikan 2-3 insight singkat dari data berikut. Bahasa Indonesia. Langsung ke inti.";
    return ai_prompt($system, $data, 200, $stream);
}

function ai_deteksi_anomali($stream = false) {
    $pdo = get_connection();
    $stmt = $pdo->query("
        SELECT t.id, t.tanggal, t.keterangan, a.nama_akun,
               CASE WHEN jd.debit > 0 THEN jd.debit ELSE jd.kredit END as jumlah,
               CASE WHEN jd.kredit > 0 THEN 'Pemasukan' ELSE 'Pengeluaran' END as jenis
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        WHERE a.kode_akun != '1-1000'
        ORDER BY t.tanggal DESC LIMIT 20
    ");
    $trans = $stmt->fetchAll();

    if (empty($trans)) return ['content' => 'Belum ada data transaksi.'];

    $data = "";
    foreach ($trans as $t) {
        $data .= $t['tanggal'] . "|" . $t['keterangan'] . "|" . $t['nama_akun'] . "|" . $t['jenis'] . "|" . format_rupiah($t['jumlah']) . "\n";
    }

    $system = "Auditor keuangan masjid. Bahasa Indonesia. Langsung ke poin. Maksimal 3 kalimat. Hanya sebut 1-2 transaksi mencurigakan teratas.";
    return ai_prompt($system, $data, 120, $stream);
}

function ai_prediksi_saldo($stream = false) {
    $pdo = get_connection();
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(t.tanggal, '%Y-%m') as bulan,
               SUM(CASE WHEN jd.debit > 0 AND a.kode_akun = '1-1000' THEN jd.debit ELSE 0 END) as pemasukan,
               SUM(CASE WHEN jd.kredit > 0 AND a.kode_akun = '1-1000' THEN jd.kredit ELSE 0 END) as pengeluaran
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        GROUP BY bulan ORDER BY bulan LIMIT 12
    ");
    $data_histori = $stmt->fetchAll();

    $saldo_sekarang = get_total_saldo()['saldo_kas'];
    $n = count($data_histori);
    if ($n < 2) return ['content' => 'Data minimal 2 bulan untuk prediksi.'];

    $ma_p = min(3, $n);
    $total_p = 0; $total_b = 0;
    for ($i = $n - $ma_p; $i < $n; $i++) {
        $total_p += (float)$data_histori[$i]['pemasukan'];
        $total_b += (float)$data_histori[$i]['pengeluaran'];
    }
    $sumX = 0; $sumY_p = 0; $sumXY_p = 0; $sumX2 = 0;
    $sumY_b = 0; $sumXY_b = 0;
    for ($i = 0; $i < $n; $i++) {
        $x = $i + 1;
        $y_p = (float)$data_histori[$i]['pemasukan'];
        $y_b = (float)$data_histori[$i]['pengeluaran'];
        $sumX += $x; $sumY_p += $y_p; $sumY_b += $y_b;
        $sumXY_p += $x * $y_p; $sumXY_b += $x * $y_b;
        $sumX2 += $x * $x;
    }
    $slope_p = ($n * $sumXY_p - $sumX * $sumY_p) / ($n * $sumX2 - $sumX * $sumX);
    $intercept_p = ($sumY_p - $slope_p * $sumX) / $n;
    $pred_p = max(0, $slope_p * ($n + 1) + $intercept_p);
    $slope_b = ($n * $sumXY_b - $sumX * $sumY_b) / ($n * $sumX2 - $sumX * $sumX);
    $intercept_b = ($sumY_b - $slope_b * $sumX) / $n;
    $pred_b = max(0, $slope_b * ($n + 1) + $intercept_b);
    $pred_saldo = $saldo_sekarang + $pred_p - $pred_b;

    $data = "MA($ma_p bln): Rerata Msk=" . format_rupiah($total_p/$ma_p) . " Klr=" . format_rupiah($total_b/$ma_p);
    $data .= " | Prediksi: Msk=" . format_rupiah($pred_p) . " Klr=" . format_rupiah($pred_b);
    $data .= " | Saldo=" . format_rupiah($saldo_sekarang) . " -> " . format_rupiah($pred_saldo);

    $system = "Analis kuantitatif. Jelaskan hasil prediksi dalam 2-3 kalimat. Bahasa Indonesia. Singkat.";
    return ai_prompt($system, $data, 200, $stream);
}

function ai_narasi_laporan($judul, $data_array, $stream = false) {
    $text = "$judul: ";
    foreach ($data_array as $key => $val) {
        $text .= "$key=$val ";
    }

    $system = "Buat narasi singkat 1-2 kalimat dari data laporan. Bahasa Indonesia. Langsung.";
    return ai_prompt($system, $text, 200, $stream);
}

function ai_audit_keuangan($stream = false) {
    $neraca = get_neraca_saldo();
    $total_debit = 0; $total_kredit = 0;
    $data = "";
    foreach ($neraca as $r) {
        $total_debit += $r['total_debit'];
        $total_kredit += $r['total_kredit'];
        $data .= $r['kode_akun'] . " " . $r['nama_akun'] . " D:" . format_rupiah($r['total_debit']) . " K:" . format_rupiah($r['total_kredit']) . "\n";
    }
    $data .= "Total D:" . format_rupiah($total_debit) . " K:" . format_rupiah($total_kredit);
    $data .= " Balance:" . ($total_debit == $total_kredit ? "OK" : "SELISIH " . format_rupiah(abs($total_debit - $total_kredit)));

    $system = "Auditor. Berikan catatan audit singkat atas neraca saldo. Bahasa Indonesia. 1-2 kalimat.";
    return ai_prompt($system, $data, 200, $stream);
}

function ai_ringkasan_jamaah($stream = false) {
    $saldo = get_total_saldo();
    $total_aset = get_total_aset();
    $pemasukan = get_total_pemasukan();
    $pengeluaran = get_total_pengeluaran();

    $data = "Aset:" . format_rupiah($total_aset) . " Saldo:" . format_rupiah($saldo['saldo_kas']);
    $data .= " Msk:" . format_rupiah($pemasukan) . " Klr:" . format_rupiah($pengeluaran);
    $data .= " S/D:" . format_rupiah($pemasukan - $pengeluaran);

    $system = "Buat ringkasan 1 kalimat untuk jamaah. Bahasa Indonesia. Ramah dan mudah dipahami.";
    return ai_prompt($system, $data, 150, $stream);
}

function ai_cek_struk($image_base64, $mime_type = 'image/jpeg') {
    $pdo = get_connection();
    $trans = $pdo->query("SELECT t.tanggal, t.keterangan, a.nama_akun, CASE WHEN jd.debit>0 THEN jd.debit ELSE jd.kredit END as jumlah, CASE WHEN jd.kredit>0 THEN 'Pemasukan' ELSE 'Pengeluaran' END as jenis FROM transaksi t JOIN jurnal_detail jd ON t.id=jd.transaksi_id JOIN akun a ON jd.akun_id=a.id WHERE a.kode_akun!='1-1000' ORDER BY t.tanggal DESC LIMIT 10")->fetchAll();
    $dataTrans = "Data transaksi terbaru:\n";
    foreach ($trans as $t) {
        $dataTrans .= "- {$t['tanggal']} | {$t['keterangan']} | {$t['nama_akun']} | {$t['jenis']} | Rp " . number_format($t['jumlah'],0,',','.') . "\n";
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . AI_API_KEY,
        'HTTP-Referer: http://localhost/bis',
        'X-Title: Hisabara',
    ];

    $body = json_encode([
        'model' => 'google/gemini-2.0-flash-001',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => "Cocokkan struk ini dengan data transaksi berikut:\n\n$dataTrans\n\nAnalisis apakah struk sesuai dengan transaksi yang tercatat. Format: SKOR:0-100% | KESIMPULAN:Cocok/Tidak Cocok/Tidak Yakin | ALASAN:..."],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:$mime_type;base64,$image_base64"]]
                ]
            ]
        ],
        'max_tokens' => 400,
        'temperature' => 0.1,
    ]);

    $max_retry = 2;
    $retry_delay = 2;
    $response = '';
    $http_code = 0;
    $error = '';

    for ($attempt = 1; $attempt <= $max_retry; $attempt++) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => AI_API_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => 'Koneksi gagal: ' . $error];
        if ($http_code !== 429 && $http_code !== 503) break;
        if ($attempt < $max_retry) sleep($retry_delay * $attempt);
    }

    $data = json_decode($response, true);

    if ($http_code != 200) {
        $msg = $data['error']['message'] ?? substr($response, 0, 300);
        return ['error' => 'HTTP ' . $http_code . ': ' . $msg];
    }

    if (!$data) return ['error' => 'Respon JSON tidak valid'];
    if (isset($data['error'])) return ['error' => 'API Error: ' . ($data['error']['message'] ?? json_encode($data['error']))];
    if (!isset($data['choices'][0]['message']['content'])) return ['error' => 'Respon tidak valid'];

    return ['content' => trim($data['choices'][0]['message']['content'])];
}

function ai_cek_struk_teks($teks) {
    $system = "Analis dokumen. Berikan indikasi keaslian struk berikut. Format: SKOR:0-100% | KESIMPULAN:Asli/Palsu/Tidak Yakin | ALASAN:...";
    return ai_prompt($system, "Teks:\n$teks", 200);
}

function ai_parse_transaksi($teks) {
    $pdo = get_connection();
    $akun_list = $pdo->query("SELECT id, kode_akun, nama_akun, kategori FROM akun WHERE is_aktif = 1 AND kategori IN ('Pendapatan','Beban') ORDER BY kode_akun")->fetchAll();
    $daftar_akun = "";
    foreach ($akun_list as $a) {
        $daftar_akun .= "[{$a['id']}] {$a['kode_akun']} {$a['nama_akun']}\n";
    }

    $system = "Akuntan masjid. Jika teks berisi deskripsi transaksi (ada nominal uang), ekstrak sebagai JSON. Jika bukan transaksi, balikkan {\"error\":\"bukan transaksi\"}. Pilih akun_id dari daftar. Jawab hanya SATU objek JSON.";
    $user = "Teks: $teks\n\nAkun:\n$daftar_akun\n\nJika transaksi: {\"jenis\":\"Pemasukan/Pengeluaran\",\"tanggal\":\"YYYY-MM-DD\",\"keterangan\":\"...\",\"jumlah\":angka,\"akun_id\":ID}\nJika bukan: {\"error\":\"bukan transaksi\"}";
    $res = ai_prompt($system, $user, 300);

    if ($res['error']) return $res;

    $text = trim(preg_replace('/```json\s*|\s*```/', '', $res['content']));
    $json = json_decode($text, true);
    if (isset($json[0]) && is_array($json[0])) $json = $json[0];

    if (!$json || !isset($json['jenis'], $json['jumlah'])) {
        return ['content' => json_encode(['_parse' => false])];
    }

    $json['jenis'] = ($json['jenis'] === 'Pemasukan') ? 'Pemasukan' : 'Pengeluaran';
    $json['_parse'] = true;

    return ['content' => json_encode($json)];
}

function ai_rekomendasi_hemat($stream = false) {
    $beban = get_data_grafik_beban();
    if (empty($beban)) return ['content' => 'Belum ada data pengeluaran.'];

    $data = "";
    foreach ($beban as $b) {
        $data .= $b['nama_akun'] . ":" . format_rupiah($b['total']) . "\n";
    }

    $system = "Konsultan keuangan. Beri 1-2 rekomendasi hemat spesifik. Bahasa Indonesia. Singkat.";
    return ai_prompt($system, $data, 200, $stream);
}

function ai_perbaikan_data() {
    $pdo = get_connection();
    $found = [];

    $total = $pdo->query("SELECT COUNT(*) c FROM transaksi WHERE status='approved'")->fetchColumn();
    $stmt = $pdo->query("SELECT t.id, t.tanggal, t.keterangan,
        (SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t.id) total_debit,
        (SELECT COALESCE(SUM(jd.kredit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t.id) total_kredit
        FROM transaksi t WHERE t.status='approved' HAVING total_debit != total_kredit LIMIT 10");
    $unbalanced = $stmt->fetchAll();
    if ($unbalanced) $found[] = "jurnal tidak balance: " . count($unbalanced) . " transaksi";

    $stmt2 = $pdo->query("SELECT keterangan, COUNT(*) c FROM transaksi WHERE status='approved' GROUP BY keterangan, DATE(tanggal) HAVING c > 1 ORDER BY c DESC LIMIT 5");
    $duplicates = $stmt2->fetchAll();
    if ($duplicates) $found[] = "duplikat transaksi: " . count($duplicates) . " grup";

    $data = "Total transaksi: $total\n";
    if ($unbalanced) {
        $data .= "\nJurnal tidak balance:\n";
        foreach ($unbalanced as $u) $data .= "- {$u['tanggal']} {$u['keterangan']} (D:{$u['total_debit']} K:{$u['total_kredit']})\n";
    }
    if ($duplicates) {
        $data .= "\nDuplikasi:\n";
        foreach ($duplicates as $d) $data .= "- \"{$d['keterangan']}\" ({$d['c']}x)\n";
    }
    if (!$found) $data .= "\nSemua jurnal balance, tidak ada masalah ditemukan.";
    $data .= "\n\nSaran perbaikan singkat untuk tiap masalah. Format JSON array: [{\"masalah\":\"...\",\"perbaikan\":\"...\"}]. Hanya sertakan sql jika ada masalah nyata. Jika tidak ada masalah, cukup satu item dengan masalah='Tidak ada masalah ditemukan' tanpa sql.";
    $system = "Auditor akuntansi. Beri saran perbaikan. Jawab hanya JSON array, tanpa markdown.";
    $res = ai_prompt($system, $data, 500);
    if (isset($res['content'])) {
        $content = trim($res['content']);
        $content = preg_replace('/^.*?(\[|\{)/s', '$1', $content);
        $content = preg_replace('/\][^\[\]]*$/', ']', $content);
        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            $content = preg_replace('/```(?:json)?\s*|\s*```/', '', $res['content']);
            $content = preg_replace('/^.*?(\[|\{)/s', '$1', $content);
            $content = preg_replace('/\][^\[\]]*$/', ']', $content);
            $parsed = json_decode($content, true);
        }
        if (is_array($parsed)) {
            foreach ($parsed as &$item) {
                if (isset($item['sql'])) {
                    $sql = trim($item['sql']);
                    if (empty($sql) || stripos($sql, 'tidak ada') !== false) {
                        unset($item['sql']);
                    }
                }
            }
            $res['content'] = json_encode($parsed, JSON_UNESCAPED_UNICODE);
        }
    }
    return $res;
}

function ai_ketahanan_finansial() {
    $pdo = get_connection();
    $saldo = get_total_saldo();
    $pemasukan = get_total_pemasukan();
    $pengeluaran = get_total_pengeluaran();
    $beban = get_data_grafik_beban();
    $bulan_data = $pdo->query("SELECT COUNT(DISTINCT DATE_FORMAT(tanggal,'%Y-%m')) bulan FROM transaksi")->fetchColumn();

    $rata_pemasukan = $bulan_data > 0 ? $pemasukan / $bulan_data : 0;
    $rata_pengeluaran = $bulan_data > 0 ? $pengeluaran / $bulan_data : 0;
    $bulan_bertahan = $rata_pengeluaran > 0 ? floor($saldo['saldo_kas'] / $rata_pengeluaran) : 0;
    $rasio_donasi = $pemasukan > 0 ? floor(($pemasukan - $pengeluaran) / $pemasukan * 100) : 0;

    $data = "Saldo kas: " . format_rupiah($saldo['saldo_kas']) . "\n";
    $data .= "Total pemasukan: " . format_rupiah($pemasukan) . " (rata/bulan: " . format_rupiah($rata_pemasukan) . ")\n";
    $data .= "Total pengeluaran: " . format_rupiah($pengeluaran) . " (rata/bulan: " . format_rupiah($rata_pengeluaran) . ")\n";
    $data .= "Surplus/defisit: " . format_rupiah($pemasukan - $pengeluaran) . "\n";
    $data .= "Bulan data: $bulan_data\n";
    $data .= "Estimasi bertahan tanpa donasi: $bulan_bertahan bulan\n";
    $data .= "Rasio surplus: $rasio_donasi%\n\n";
    foreach ($beban as $b) $data .= $b['nama_akun'] . ":" . format_rupiah($b['total']) . "\n";

    $system = "Analis ketahanan finansial masjid. Hitung estimasi ketahanan, ketergantungan donatur, stabilitas kas, tren. Beri skor A-E dan rekomendasi. Bahasa Indonesia. Format JSON: {\"skor\":\"A-E\",\"bulan_bertahan\":angka,\"analisis\":\"...\",\"rekomendasi\":\"...\"}";
    return ai_prompt($system, $data, 400);
}

function ai_dampak_sosial() {
    $pdo = get_connection();
    $total_pemasukan = get_total_pemasukan();
    $total_pengeluaran = get_total_pengeluaran();

    // Dana dari donatur untuk kegiatan sosial
    $donasi_sosial = $pdo->query("SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Dana Khusus' AND (a.nama_akun LIKE '%sosial%' OR a.nama_akun LIKE '%zakat%') AND t.status='approved' AND jd.debit>0")->fetchColumn();

    // Pengeluaran untuk kegiatan sosial (dari akun beban sosial/zakat)
    $beban_sosial = $pdo->query("SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Beban' AND (a.nama_akun LIKE '%sosial%' OR a.nama_akun LIKE '%zakat%' OR a.nama_akun LIKE '%bantuan%') AND t.status='approved' AND jd.debit>0")->fetchColumn();

    // Transaksi dengan keterangan sosial
    $trans_sosial = $pdo->query("SELECT t.tanggal, t.keterangan, a.nama_akun, COALESCE(jd.debit,jd.kredit) as jumlah FROM transaksi t JOIN jurnal_detail jd ON t.id=jd.transaksi_id JOIN akun a ON jd.akun_id=a.id WHERE t.status='approved' AND (t.keterangan LIKE '%sosial%' OR t.keterangan LIKE '%zakat%' OR t.keterangan LIKE '%bantuan%' OR t.keterangan LIKE '%donasi%' OR a.nama_akun LIKE '%sosial%' OR a.nama_akun LIKE '%zakat%') ORDER BY t.tanggal DESC LIMIT 20")->fetchAll();

    // Total transaksi approved
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();

    // Pengeluaran per akun
    $per_kategori = $pdo->query("SELECT a.nama_akun, SUM(jd.debit) total FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Beban' AND t.status='approved' AND jd.debit>0 GROUP BY a.nama_akun ORDER BY total DESC")->fetchAll();

    $total_sosial = $donasi_sosial + $beban_sosial;

    $data = "Total penerimaan: " . format_rupiah($total_pemasukan) . "\n";
    $data .= "Total pengeluaran: " . format_rupiah($total_pengeluaran) . "\n";
    $data .= "Total dana sosial (donasi + beban): " . format_rupiah($total_sosial) . "\n";
    $data .= "Jumlah transaksi: $total_transaksi\n\n";

    if ($trans_sosial) {
        $data .= "Transaksi terkait sosial:\n";
        foreach ($trans_sosial as $t) {
            $data .= "- {$t['tanggal']} | {$t['keterangan']} | {$t['nama_akun']} | Rp " . number_format($t['jumlah'],0,',','.') . "\n";
        }
        $data .= "\n";
    }

    $data .= "Rincian pengeluaran per akun:\n";
    foreach ($per_kategori as $k) {
        $data .= "- {$k['nama_akun']}: " . format_rupiah($k['total']) . "\n";
    }

    $system = "Analis dampak sosial masjid. Analisis berdasarkan data transaksi riil. Format JSON: {\"dana_sosial\":\"...\",\"estimasi_penerima\":angka,\"program\":[{\"nama\":\"...\",\"dampak\":\"...\"}],\"skor_dampak\":\"Tinggi/Sedang/Rendah\",\"narasi\":\"...\"}";
    return ai_prompt($system, $data, 500);
}

function ai_skor_transparansi() {
    $pdo = get_connection();
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
    $dengan_bukti = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE bukti_file IS NOT NULL AND bukti_file != ''")->fetchColumn();
    $publikasi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();
    try {
        $audit_log = $pdo->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
    } catch (Exception $e) {
        $audit_log = 0;
    }

    $skor_bukti = $total_transaksi > 0 ? round($dengan_bukti/$total_transaksi*100) : 0;
    $skor_publikasi = $total_transaksi > 0 ? round($publikasi/$total_transaksi*100) : 0;
    $skor_audit = min(100, round($audit_log/10));

    $data = "Total transaksi: $total_transaksi\n";
    $data .= "Dengan bukti: $dengan_bukti ($skor_bukti%)\n";
    $data .= "Transaksi approved (terpublikasi): $publikasi ($skor_publikasi%)\n";
    $data .= "Audit log entries: $audit_log\n";
    $data .= "Skor sementara - Kelengkapan bukti: $skor_bukti%, Publikasi: $skor_publikasi%, Audit: $skor_audit%\n";

    $system = "Auditor transparansi publik. Nilai skor A-E (A=Excellent, B=Baik, C=Cukup, D=Kurang, E=Buruk). Format JSON: {\"skor\":\"A-E\",\"persentase\":angka,\"analisis\":\"...\",\"rekomendasi\":\"...\"}";
    return ai_prompt($system, $data, 400);
}

function ai_tren_keuangan() {
    $pdo = get_connection();
    $histori = $pdo->query("SELECT DATE_FORMAT(t.tanggal,'%Y-%m') bulan,
        SUM(CASE WHEN a.kategori='Pendapatan' THEN jd.kredit ELSE 0 END) pemasukan,
        SUM(CASE WHEN a.kategori='Beban' THEN jd.debit ELSE 0 END) pengeluaran
        FROM transaksi t JOIN jurnal_detail jd ON t.id=jd.transaksi_id JOIN akun a ON jd.akun_id=a.id
        WHERE t.status='approved' GROUP BY bulan ORDER BY bulan LIMIT 12")->fetchAll();

    $data = "";
    $bulan_defisit = 0;
    foreach ($histori as $h) {
        $s = (float)$h['pemasukan'] - (float)$h['pengeluaran'];
        if ($s < 0) $bulan_defisit++;
        $data .= $h['bulan'] . " M:" . format_rupiah($h['pemasukan']) . " K:" . format_rupiah($h['pengeluaran']) . " S:" . format_rupiah($s) . "\n";
    }
    $data .= "Defisit:$bulan_defisit/" . count($histori) . " bln";

    $system = "Analis keuangan. Analisis tren dan beri rekomendasi. Jawab JSON: {\"analisis\":\"...\",\"pola_ditemukan\":[\"...\"],\"rekomendasi_strategis\":[\"...\"],\"prediksi\":\"...\"}. Langsung JSON, singkat.";
    $user = $data;
    $res = ai_prompt($system, $user, 300);
    if ($res['error']) return $res;
    $text = trim(preg_replace('/```json\s*|\s*```/', '', $res['content']));
    return ['content' => $text];
}

function ai_pola_transaksi() {
    $pdo = get_connection();
    $saldo = get_total_saldo();

    $beban = $pdo->query("SELECT a.nama_akun, SUM(jd.debit) total FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Beban' AND t.status='approved' AND jd.debit>0 GROUP BY a.nama_akun ORDER BY total DESC")->fetchAll();
    $pendapatan = $pdo->query("SELECT a.nama_akun, SUM(jd.kredit) total FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id JOIN transaksi t ON jd.transaksi_id=t.id WHERE a.kategori='Pendapatan' AND t.status='approved' AND jd.kredit>0 GROUP BY a.nama_akun ORDER BY total DESC")->fetchAll();

    $data = "Saldo:" . format_rupiah($saldo['saldo_kas']);
    foreach ($beban as $b) $data .= "|B:" . $b['nama_akun'] . "=" . format_rupiah($b['total']);
    foreach ($pendapatan as $p) $data .= "|P:" . $p['nama_akun'] . "=" . format_rupiah($p['total']);

    $system = "Analis keuangan masjid. Cari 2 pola transaksi menarik dari data. Jawab JSON: {\"pola\":[{\"judul\":\"...\",\"deskripsi\":\"...\",\"dampak\":\"...\",\"saran\":\"...\"}]}. Langsung JSON, singkat.";
    $user = $data;
    $res = ai_prompt($system, $user, 300);
    if ($res['error']) return $res;
    $text = trim(preg_replace('/```json\s*|\s*```/', '', $res['content']));
    return ['content' => $text];
}

function ai_evaluasi_kepatuhan($keputusan) {
    $pdo = get_connection();
    $saldo = get_total_saldo();
    $data_dana_khusus = get_all_dana_khusus();
    $dk = "";
    foreach ($data_dana_khusus as $d) $dk .= "- {$d['nama_dana']}: " . format_rupiah(hitung_saldo_dana_khusus($d['id'])) . "\n";

    $data = "Keputusan: $keputusan\n";
    $data .= "Saldo kas: " . format_rupiah($saldo['saldo_kas']) . "\n";
    $data .= "Dana khusus:\n$dk\n";

    $system = "Ahli etika Islam dan tata kelola masjid. Evaluasi keputusan dari sisi etika, syariah, akuntansi, dan governance. Format JSON: {\"rekomendasi\":\"Setuju/Tidak Setuju/Bersyarat\",\"alasan\":\"...\",\"dampak_keuangan\":\"...\",\"dampak_sosial\":\"...\",\"referensi\":\"...\"}";
    return ai_prompt($system, $data, 400);
}

function ai_generate_calk($tahun = null) {
    if (!$tahun) $tahun = date('Y');
    $pdo = get_connection();

    // Data keuangan komprehensif
    $saldo = get_total_saldo();
    $posisi = get_laporan_posisi_keuangan();
    $aktivitas = get_laporan_aktivitas();
    $arus_kas = get_laporan_arus_kas();
    $neraca = get_neraca_saldo();
    $aset_fisik = get_all_inventaris();
    $dana_khusus = get_all_dana_khusus();
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();

    $nama_masjid = get_setting('nama_masjid') ?: 'Masjid';
    $data = "Nama Masjid: $nama_masjid\n";
    $data .= "=== DATA KEUANGAN TAHUN $tahun ===\n\n";

    $data .= "-- SALDO --\n";
    $data .= "Saldo Kas: " . format_rupiah($saldo['saldo_kas']) . "\n";
    $data .= "Total Pemasukan: " . format_rupiah($saldo['pemasukan']) . "\n";
    $data .= "Total Pengeluaran: " . format_rupiah($saldo['pengeluaran']) . "\n";
    $data .= "Jumlah Transaksi: $total_transaksi\n\n";

    $data .= "-- NERACA SALDO --\n";
    foreach ($neraca as $r) {
        $data .= $r['kode_akun'] . " | " . $r['nama_akun'] . " | " . $r['kategori'] . " | D:" . format_rupiah($r['total_debit']) . " | K:" . format_rupiah($r['total_kredit']) . "\n";
    }
    $data .= "\n";

    $data .= "-- POSISI KEUANGAN --\n";
    $data .= "Total Aset: " . format_rupiah($posisi['total_aset']) . "\n";
    foreach ($posisi['aset'] as $a) $data .= "  Aset: " . $a['nama'] . " = " . format_rupiah($a['saldo']) . "\n";
    $data .= "Total Kewajiban: " . format_rupiah($posisi['total_kewajiban']) . "\n";
    $data .= "Total Aset Neto: " . format_rupiah($posisi['total_aset_neto']) . "\n\n";

    $data .= "-- AKTIVITAS --\n";
    foreach ($aktivitas['pendapatan'] as $p) $data .= "Pendapatan: " . $p['nama'] . " = " . format_rupiah($p['saldo']) . "\n";
    foreach ($aktivitas['beban'] as $b) $data .= "Beban: " . $b['nama'] . " = " . format_rupiah($b['saldo']) . "\n";
    $data .= "Surplus/Defisit: " . format_rupiah($aktivitas['surplus']) . "\n\n";

    $data .= "-- ARUS KAS --\n";
    $data .= "Saldo Awal: " . format_rupiah($arus_kas['saldo_awal']) . "\n";
    $data .= "Penerimaan: " . format_rupiah($arus_kas['penerimaan']) . "\n";
    $data .= "Pengeluaran: " . format_rupiah($arus_kas['pengeluaran']) . "\n";
    $data .= "Saldo Akhir: " . format_rupiah($arus_kas['saldo_akhir']) . "\n\n";

    if ($aset_fisik) {
        $data .= "-- ASET TETAP --\n";
        foreach ($aset_fisik as $a) {
            $nilai_buku = hitung_nilai_buku($a['harga'], $a['akumulasi_penyusutan'] ?? 0);
            $data .= $a['nama_barang'] . " | Harga: " . format_rupiah($a['harga']) . " | Nilai Buku: " . format_rupiah($nilai_buku) . " | Lokasi: " . ($a['lokasi'] ?? '-') . "\n";
        }
        $data .= "\n";
    }

    if ($dana_khusus) {
        $data .= "-- DANA KHUSUS --\n";
        foreach ($dana_khusus as $d) {
            $saldo_dk = hitung_saldo_dana_khusus($d['id']);
            $data .= $d['nama_dana'] . " (" . $d['kode_dana'] . "): " . format_rupiah($saldo_dk) . " - " . $d['deskripsi'] . "\n";
        }
        $data .= "\n";
    }

    $system = "Anda adalah akuntan publik spesialis untuk masjid. 
Buatkan Catatan Atas Laporan Keuangan (CALK) yang lengkap dan profesional dalam Bahasa Indonesia berdasarkan data keuangan yang diberikan.
CALK harus mencakup:
1. Informasi Umum (nama masjid, alamat, tahun operasi)
2. Kebijakan Akuntansi (dasar penyusunan laporan keuangan, pengakuan pendapatan dan beban, pengakuan aset, metode penyusutan, klasifikasi dana)
3. Penjelasan Kas dan Setara Kas
4. Rincian Aset Tetap dan penyusutannya
5. Penjelasan Aset Neto (terikat dan tidak terikat)
6. Penjelasan Pendapatan dan Beban
7. Informasi Dana Khusus (jika ada)
8. Informasi Lainnya

Keluarkan hasil dalam format JSON array of objects dengan properti: judul (string), konten (string, boleh berisi paragraf), urutan (integer).
Setiap objek mewakili satu bagian CALK. Minimal 5 bagian. Gunakan bahasa Indonesia formal dan jelas.";
    $user = "Buat CALK tahun $tahun berdasarkan data berikut:\n\n$data";
    return ai_prompt($system, $user, 3000);
}

function ai_perbaikan_pencatatan() {
    $pdo = get_connection();
    $problems = [];

    // 1. Cek jurnal yang tidak balance
    $stmt = $pdo->query("SELECT t.id, t.tanggal, t.keterangan,
        (SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t.id) total_debit,
        (SELECT COALESCE(SUM(jd.kredit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t.id) total_kredit
        FROM transaksi t WHERE t.status='approved' HAVING total_debit != total_kredit LIMIT 20");
    $unbalanced = $stmt->fetchAll();
    foreach ($unbalanced as $u) {
        $problems[] = [
            'jenis' => 'jurnal_tidak_balance',
            'transaksi_id' => $u['id'],
            'tanggal' => $u['tanggal'],
            'keterangan' => $u['keterangan'],
            'detail' => 'Debit: ' . $u['total_debit'] . ' vs Kredit: ' . $u['total_kredit'],
            'perbaikan_saran' => 'Hapus dan buat ulang jurnal detail dengan nilai debit = kredit.'
        ];
    }

    // 2. Cek duplikasi transaksi (sama keterangan, tanggal, jumlah)
    $stmt2 = $pdo->query("SELECT t1.id, t1.tanggal, t1.keterangan,
        (SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t1.id AND jd.akun_id!=(SELECT id FROM akun WHERE kode_akun='1-1000')) as jumlah
        FROM transaksi t1 WHERE t1.status='approved' AND EXISTS (
            SELECT 1 FROM transaksi t2 WHERE t2.status='approved' AND t2.id != t1.id
            AND DATE(t2.tanggal) = DATE(t1.tanggal) AND t2.keterangan = t1.keterangan
        ) ORDER BY t1.tanggal DESC LIMIT 20");
    $duplicates = $stmt2->fetchAll();
    // Kelompokkan duplikat
    $dup_groups = [];
    foreach ($duplicates as $d) {
        $key = $d['tanggal'] . '|' . $d['keterangan'];
        if (!isset($dup_groups[$key])) $dup_groups[$key] = [];
        $dup_groups[$key][] = $d;
    }
    foreach ($dup_groups as $key => $group) {
        if (count($group) < 2) continue;
        $ids = array_column($group, 'id');
        $problems[] = [
            'jenis' => 'duplikat_transaksi',
            'transaksi_id' => $ids,
            'tanggal' => $group[0]['tanggal'],
            'keterangan' => $group[0]['keterangan'],
            'detail' => 'Ditemukan ' . count($group) . ' transaksi duplikat',
            'perbaikan_saran' => 'Hapus transaksi duplikat, sisakan 1'
        ];
    }

    // 3. Cek transaksi tanpa detail jurnal
    $stmt3 = $pdo->query("SELECT t.id, t.tanggal, t.keterangan FROM transaksi t WHERE t.status='approved' AND NOT EXISTS (SELECT 1 FROM jurnal_detail jd WHERE jd.transaksi_id=t.id) LIMIT 10");
    $no_jurnal = $stmt3->fetchAll();
    foreach ($no_jurnal as $nj) {
        $problems[] = [
            'jenis' => 'tanpa_jurnal',
            'transaksi_id' => $nj['id'],
            'tanggal' => $nj['tanggal'],
            'keterangan' => $nj['keterangan'],
            'detail' => 'Transaksi tidak memiliki jurnal detail',
            'perbaikan_saran' => 'Hapus transaksi atau buat jurnal detail'
        ];
    }

    // 4. Cek transaksi dengan jumlah tidak wajar (> 100 juta)
    $stmt4 = $pdo->query("SELECT t.id, t.tanggal, t.keterangan,
        (SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t.id AND jd.akun_id!=(SELECT id FROM akun WHERE kode_akun='1-1000')) as jumlah
        FROM transaksi t WHERE t.status='approved' HAVING jumlah > 100000000 ORDER BY jumlah DESC LIMIT 10");
    $besar = $stmt4->fetchAll();
    foreach ($besar as $b) {
        $problems[] = [
            'jenis' => 'jumlah_besar',
            'transaksi_id' => $b['id'],
            'tanggal' => $b['tanggal'],
            'keterangan' => $b['keterangan'],
            'detail' => 'Jumlah: Rp ' . number_format($b['jumlah'],0,',','.') . ' (di atas 100 juta)',
            'perbaikan_saran' => 'Verifikasi kembali kebenaran transaksi ini'
        ];
    }

    // 5. Cek transaksi pending yang sudah lama
    $stmt5 = $pdo->query("SELECT id, tanggal, keterangan FROM transaksi WHERE status='pending' AND tanggal < DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY tanggal ASC LIMIT 10");
    $pending_lama = $stmt5->fetchAll();
    foreach ($pending_lama as $pl) {
        $problems[] = [
            'jenis' => 'pending_lama',
            'transaksi_id' => $pl['id'],
            'tanggal' => $pl['tanggal'],
            'keterangan' => $pl['keterangan'],
            'detail' => 'Transaksi pending lebih dari 7 hari',
            'perbaikan_saran' => 'Segera setujui atau tolak transaksi ini'
        ];
    }

    if (empty($problems)) {
        return ['content' => json_encode(['ditemukan' => false, 'pesan' => 'Tidak ada data yang salah'])];
    }

    return ['content' => json_encode(['ditemukan' => true, 'jumlah_masalah' => count($problems), 'masalah' => $problems])];
}

function ai_terapkan_perbaikan($data) {
    $pdo = get_connection();
    $transaksi_id = $data['transaksi_id'] ?? 0;
    $jenis = $data['jenis_perbaikan'] ?? '';
    $data_baru = $data['data_baru'] ?? [];

    if (!$transaksi_id) {
        return ['success' => false, 'error' => 'ID transaksi tidak valid.'];
    }

    $trans = $pdo->prepare("SELECT * FROM transaksi WHERE id = ? AND status='approved'");
    $trans->execute([$transaksi_id]);
    $old = $trans->fetch();
    if (!$old) {
        return ['success' => false, 'error' => 'Transaksi tidak ditemukan atau belum disetujui.'];
    }

    try {
        $pdo->beginTransaction();

        if ($jenis === 'hapus_transaksi') {
            $pdo->prepare("DELETE FROM jurnal_detail WHERE transaksi_id = ?")->execute([$transaksi_id]);
            $pdo->prepare("DELETE FROM transaksi WHERE id = ?")->execute([$transaksi_id]);
            audit_log('Hapus (AI)', 'transaksi', $transaksi_id, 'Dihapus oleh AI: ' . ($old['keterangan'] ?? ''), json_encode($old), null);
        } else {
            $update_fields = [];
            $params = [];

            if (isset($data_baru['keterangan'])) {
                $update_fields[] = "keterangan = ?";
                $params[] = $data_baru['keterangan'];
            }
            if (isset($data_baru['tanggal'])) {
                $update_fields[] = "tanggal = ?";
                $params[] = $data_baru['tanggal'];
            }

            if (!empty($update_fields)) {
                $params[] = $transaksi_id;
                $pdo->prepare("UPDATE transaksi SET " . implode(', ', $update_fields) . " WHERE id = ?")->execute($params);
            }

            if ($jenis === 'ubah_jumlah' || $jenis === 'ubah_akun') {
                $kas_id = get_akun_kas();
                $akun_id = $data_baru['akun_id'] ?? null;
                $jumlah = $data_baru['jumlah'] ?? 0;
                $jenis_trans = $data_baru['jenis'] ?? '';

                if ($akun_id && $jumlah > 0) {
                    $pdo->prepare("DELETE FROM jurnal_detail WHERE transaksi_id = ?")->execute([$transaksi_id]);

                    if ($jenis_trans === 'Pemasukan') {
                        $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$transaksi_id, $kas_id, $jumlah]);
                        $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$transaksi_id, $akun_id, $jumlah]);
                    } else {
                        $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, ?, 0)")->execute([$transaksi_id, $akun_id, $jumlah]);
                        $pdo->prepare("INSERT INTO jurnal_detail (transaksi_id, akun_id, debit, kredit) VALUES (?, ?, 0, ?)")->execute([$transaksi_id, $kas_id, $jumlah]);
                    }
                }
            }

            audit_log('Perbaiki (AI)', 'transaksi', $transaksi_id, 'Diperbaiki AI: ' . ($data['penjelasan'] ?? ''), json_encode($old), json_encode($data_baru));
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Perbaikan berhasil diterapkan.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Gagal menerapkan perbaikan: ' . $e->getMessage()];
    }
}

function ai_perbaikan_anggaran() {
    $pdo = get_connection();
    $tahun = date('Y');
    $masalah = [];

    // Ambil saldo kas & rata-rata pemasukan bulanan
    $saldo = get_total_saldo();
    $saldo_kas = (float)$saldo['saldo_kas'];
    $rata_pemasukan = (float)$saldo['pemasukan'] / max(date('m'), 1);
    $kapasitas_bulanan = $saldo_kas + $rata_pemasukan;

    // 1. Cek anggaran yang sudah terpakai > 100% (over budget)
    $stmt = $pdo->prepare("SELECT a.id, a.akun_id, a.jumlah as anggaran, ak.nama_akun, ak.kode_akun, ak.kategori,
        (SELECT COALESCE(SUM(jd.debit + jd.kredit),0) FROM jurnal_detail jd JOIN transaksi t ON jd.transaksi_id=t.id WHERE jd.akun_id=a.akun_id AND t.status='approved' AND YEAR(t.tanggal)=a.tahun AND (MONTH(t.tanggal)=a.bulan OR a.jenis='tahunan')) as realisasi,
        a.jenis, a.bulan
        FROM anggaran a JOIN akun ak ON a.akun_id=ak.id");
    $stmt->execute();
    $all_anggaran = $stmt->fetchAll();

    foreach ($all_anggaran as $a) {
        $realisasi = (float)$a['realisasi'];
        $anggaran = (float)$a['anggaran'];
        if ($anggaran > 0 && $realisasi > $anggaran) {
            $persen = round(($realisasi / $anggaran) * 100);
            $saran_nominal = $realisasi * 1.2;
            $kategori = $a['kategori'];

            if ($kategori === 'Beban') {
                $batas = $kapasitas_bulanan * 0.8;
                if ($saran_nominal > $batas) $saran_nominal = $batas;
            }

            $masalah[] = [
                'jenis' => 'over_budget',
                'anggaran_id' => $a['id'],
                'akun_id' => $a['akun_id'],
                'nama_akun' => $a['nama_akun'],
                'kode_akun' => $a['kode_akun'],
                'kategori' => $kategori,
                'anggaran' => $anggaran,
                'realisasi' => $realisasi,
                'persen' => $persen,
                'bulan' => $a['bulan'],
                'saldo_kas' => $saldo_kas,
                'perbaikan_saran' => "Anggaran sudah terpakai $persen%. Disarankan menambah anggaran menjadi Rp " . number_format($saran_nominal,0,',','.')
                    . ($kategori === 'Beban' ? " (dibatasi saldo kas: Rp " . number_format($saldo_kas,0,',','.') . ")" : "")
            ];
        }
    }

    // 2. Cek akun yang punya transaksi tapi belum ada anggarannya
    $stmt2 = $pdo->query("SELECT ak.id, ak.kode_akun, ak.nama_akun, ak.kategori,
        COALESCE(SUM(CASE WHEN ak.kategori='Pendapatan' THEN jd.kredit ELSE jd.debit END),0) as total
        FROM akun ak
        LEFT JOIN jurnal_detail jd ON ak.id=jd.akun_id
        LEFT JOIN transaksi t ON jd.transaksi_id=t.id AND t.status='approved' AND YEAR(t.tanggal)=$tahun
        WHERE ak.is_aktif=1 AND ak.kategori IN ('Pendapatan','Beban')
        AND NOT EXISTS (SELECT 1 FROM anggaran ag WHERE ag.akun_id=ak.id AND ag.tahun=$tahun)
        GROUP BY ak.id, ak.kode_akun, ak.nama_akun, ak.kategori
        HAVING total > 0
        ORDER BY total DESC");
    $tanpa_anggaran = $stmt2->fetchAll();

    foreach ($tanpa_anggaran as $ta) {
        $rata_bulan = $tahun == date('Y') ? $ta['total'] / max(date('m'),1) : $ta['total'] / 12;
        $saran_bulan = round($rata_bulan);
        $kategori = $ta['kategori'];

        // Beban dibatasi saldo
        if ($kategori === 'Beban') {
            $batas = $kapasitas_bulanan * 0.8;
            $saran_bulan = min($saran_bulan, $batas);
        }

        $masalah[] = [
            'jenis' => 'tanpa_anggaran',
            'anggaran_id' => null,
            'akun_id' => $ta['id'],
            'nama_akun' => $ta['nama_akun'],
            'kode_akun' => $ta['kode_akun'],
            'kategori' => $kategori,
            'anggaran' => 0,
            'realisasi' => $ta['total'],
            'persen' => 0,
            'bulan' => null,
            'saldo_kas' => $saldo_kas,
            'perbaikan_saran' => "Akun ini belum memiliki anggaran. Disarankan membuat anggaran Rp "
                . number_format($saran_bulan,0,',','.') . "/bulan"
                . ($kategori === 'Beban' ? " (dibatasi saldo kas: Rp " . number_format($saldo_kas,0,',','.') . ")" : " (berdasarkan rata-rata realisasi)")
        ];
    }

    // 3. Cek anggaran yang realisasinya sangat kecil (< 10%)
    foreach ($all_anggaran as $a) {
        $realisasi = (float)$a['realisasi'];
        $anggaran = (float)$a['anggaran'];
        if ($anggaran > 0 && $realisasi > 0 && ($realisasi / $anggaran) < 0.1) {
            $masalah[] = [
                'jenis' => 'kurang_realisasi',
                'anggaran_id' => $a['id'],
                'akun_id' => $a['akun_id'],
                'nama_akun' => $a['nama_akun'],
                'kode_akun' => $a['kode_akun'],
                'kategori' => $a['kategori'],
                'anggaran' => $anggaran,
                'realisasi' => $realisasi,
                'persen' => round(($realisasi / $anggaran) * 100),
                'bulan' => $a['bulan'],
                'saldo_kas' => $saldo_kas,
                'perbaikan_saran' => "Anggaran terlalu besar dari realisasi. Disarankan menurunkan menjadi Rp " . number_format($realisasi * 1.3,0,',','.')
            ];
        }
    }

    if (empty($masalah)) {
        return ['content' => json_encode(['ditemukan' => false, 'pesan' => 'Tidak ada data anggaran yang salah'])];
    }

    return ['content' => json_encode(['ditemukan' => true, 'jumlah_masalah' => count($masalah), 'masalah' => $masalah])];
}

function ai_terapkan_anggaran($data) {
    $pdo = get_connection();
    $items = $data['items'] ?? [];

    if (empty($items)) {
        return ['success' => false, 'error' => 'Tidak ada item anggaran.'];
    }

    $sukses = 0;
    $gagal = 0;

    try {
        $pdo->beginTransaction();

        foreach ($items as $item) {
            $akun_id = $item['akun_id'];
            $jumlah = $item['jumlah'];
            $jenis = $item['jenis'] ?? 'bulanan';
            $bulan = $item['bulan'] ?? date('m');
            $tahun = $item['tahun'] ?? date('Y');
            $anggaran_id = $item['anggaran_id'] ?? null;

            if (!$akun_id || $jumlah <= 0) continue;

            if ($anggaran_id) {
                $stmt = $pdo->prepare("UPDATE anggaran SET jumlah=? WHERE id=?");
                $stmt->execute([$jumlah, $anggaran_id]);
                audit_log('Edit (AI)', 'anggaran', $anggaran_id, "AI perbaiki: akun_id=$akun_id jumlah=" . format_rupiah($jumlah));
            } else {
                $stmt = $pdo->prepare("INSERT INTO anggaran (akun_id, jenis, bulan, tahun, jumlah) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$akun_id, $jenis, $bulan, $tahun, $jumlah]);
                audit_log('Tambah (AI)', 'anggaran', $pdo->lastInsertId(), "AI buat: akun_id=$akun_id jumlah=" . format_rupiah($jumlah));
            }
            $sukses++;
        }

        $pdo->commit();
        return ['success' => true, 'sukses' => $sukses, 'gagal' => $gagal, 'message' => "$sukses anggaran berhasil diperbaiki/dibuat."];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Gagal: ' . $e->getMessage()];
    }
}

function ai_buat_anggaran($teks) {
    $pdo = get_connection();
    $tahun = date('Y');
    $bulan_ini = date('m');

    $saldo = get_total_saldo();
    $saldo_kas = (float)$saldo['saldo_kas'];
    $rata_pemasukan = (float)$saldo['pemasukan'] / max($bulan_ini, 1);
    $rata_pengeluaran = (float)$saldo['pengeluaran'] / max($bulan_ini, 1);

    $akun_pendapatan = $pdo->query("SELECT id, kode_akun, nama_akun FROM akun WHERE is_aktif=1 AND kategori='Pendapatan' ORDER BY kode_akun")->fetchAll();
    $akun_beban = $pdo->query("SELECT id, kode_akun, nama_akun FROM akun WHERE is_aktif=1 AND kategori='Beban' ORDER BY kode_akun")->fetchAll();

    $realisasi_pendapatan = [];
    foreach ($akun_pendapatan as $ap) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(jd.kredit),0) FROM jurnal_detail jd JOIN transaksi t ON jd.transaksi_id=t.id WHERE jd.akun_id=? AND t.status='approved' AND YEAR(t.tanggal)=? AND MONTH(t.tanggal)=?");
        $stmt->execute([$ap['id'], $tahun, $bulan_ini]);
        $realisasi_pendapatan[$ap['id']] = (float)$stmt->fetchColumn();
    }

    $realisasi_beban = [];
    foreach ($akun_beban as $ab) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd JOIN transaksi t ON jd.transaksi_id=t.id WHERE jd.akun_id=? AND t.status='approved' AND YEAR(t.tanggal)=? AND MONTH(t.tanggal)=?");
        $stmt->execute([$ab['id'], $tahun, $bulan_ini]);
        $realisasi_beban[$ab['id']] = (float)$stmt->fetchColumn();
    }

    $daftar_akun = "AKUN PENDAPATAN:\n";
    foreach ($akun_pendapatan as $ap) {
        $r = $realisasi_pendapatan[$ap['id']] ?? 0;
        $daftar_akun .= "[{$ap['id']}] {$ap['kode_akun']} {$ap['nama_akun']} (realisasi bln ini: Rp " . number_format($r,0,',','.') . ")\n";
    }
    $daftar_akun .= "\nAKUN BEBAN:\n";
    foreach ($akun_beban as $ab) {
        $r = $realisasi_beban[$ab['id']] ?? 0;
        $daftar_akun .= "[{$ab['id']}] {$ab['kode_akun']} {$ab['nama_akun']} (realisasi bln ini: Rp " . number_format($r,0,',','.') . ")\n";
    }

    $data_keuangan = "Saldo kas: Rp " . number_format($saldo_kas,0,',','.') . "\n";
    $data_keuangan .= "Rata-rata pemasukan/bulan: Rp " . number_format($rata_pemasukan,0,',','.') . "\n";
    $data_keuangan .= "Rata-rata pengeluaran/bulan: Rp " . number_format($rata_pengeluaran,0,',','.') . "\n";
    $data_keuangan .= "Bulan: " . $bulan_ini . " | Tahun: " . $tahun . "\n";

    $system = "Anda adalah konsultan keuangan masjid yang membuat rencana anggaran.
User akan memberikan instruksi tentang anggaran yang diinginkan.
Tugas Anda: buat 2-3 opsi rencana anggaran yang berbeda berdasarkan data keuangan dan instruksi user.
Setiap opsi harus memiliki detail lengkap dan alasan.

Jawab dengan JSON TANPA markdown:
{
  \"opsi\": [
    {
      \"nama\": \"Nama opsi (misal: Anggaran Hemat, Anggaran Standar, Anggaran Lengkap)\",
      \"deskripsi\": \"Penjelasan singkat tentang pendekatan opsi ini\",
      \"items\": [
        {\"akun_id\": ID, \"jumlah\": angka, \"jenis\": \"bulanan/tahunan\", \"bulan\": bulan_atau_null, \"tahun\": tahun}
      ],
      \"total_anggaran\": total_keseluruhan,
      \"alasan\": \"Alasan pemilihan nominal dan prioritas\",
      \"proyeksi\": \"Proyeksi kondisi keuangan 3-6 bulan ke depan jika opsi ini dipilih\",
      \"rekomendasi\": \"Saran kapan opsi ini cocok digunakan\"
    }
  ]
}";
    $user = "DATA KEUANGAN SAAT INI:\n$data_keuangan\n\nDAFTAR AKUN:\n$daftar_akun\n\nINSTRUKSI USER: $teks\n\nBuat 2-3 opsi rencana anggaran yang sesuai.";
    $res = ai_prompt($system, $user, 2000);

    if ($res['error']) return $res;
    $text = trim(preg_replace('/```json\s*|\s*```/', '', $res['content']));
    $json = json_decode($text, true);
    if (!$json || !isset($json['opsi'])) return ['content' => json_encode(['error' => 'Gagal membuat anggaran: ' . substr($text, 0, 200)])];

    return ['content' => json_encode($json)];
}

function ai_cerita_dana() {
    $pdo = get_connection();
    $saldo = get_total_saldo();
    $total_aset = get_total_aset();
    $pemasukan = get_total_pemasukan();
    $pengeluaran = get_total_pengeluaran();
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();
    $total_dana_sosial = $pdo->query("SELECT COALESCE(SUM(jd.kredit),0) FROM jurnal_detail jd JOIN akun a ON jd.akun_id=a.id WHERE a.kategori='Beban' AND a.nama_akun LIKE '%sosial%'")->fetchColumn();

    $data = "Periode: " . date('F Y') . "\n";
    $data .= "Total aset: " . format_rupiah($total_aset) . "\n";
    $data .= "Saldo kas: " . format_rupiah($saldo['saldo_kas']) . "\n";
    $data .= "Pemasukan: " . format_rupiah($pemasukan) . "\n";
    $data .= "Pengeluaran: " . format_rupiah($pengeluaran) . "\n";
    $data .= "Dana sosial: " . format_rupiah($total_dana_sosial) . "\n";
    $data .= "Total transaksi: $total_transaksi\n";

    $system = "Kepala divisi IT masjid yang juga wartawan. Buat narasi 1 paragraf tentang perjalanan dana dan pencapaian sosial. Bahasa Indonesia. Mengalir seperti cerita. Format JSON: {\"narasi\":\"...\",\"judul\":\"...\",\"highlight\":\"...\"}";
    return ai_prompt($system, $data, 400);
}

function ai_risiko_kas($keputusan) {
    $pdo = get_connection();
    $saldo = get_total_saldo();
    $rata_pengeluaran = 0;
    $stmt = $pdo->query("SELECT AVG(bulan_total) rata FROM (SELECT SUM(jd.kredit) bulan_total FROM transaksi t JOIN jurnal_detail jd ON jd.transaksi_id=t.id JOIN akun a ON jd.akun_id=a.id WHERE t.status='approved' AND a.kategori='Beban' GROUP BY DATE_FORMAT(t.tanggal,'%Y-%m')) sub");
    $r = $stmt->fetch();
    if ($r && $r['rata']) $rata_pengeluaran = $r['rata'];

    $data = "Keputusan akan diambil: $keputusan\n";
    $data .= "Saldo kas saat ini: " . format_rupiah($saldo['saldo_kas']) . "\n";
    $data .= "Rata-rata pengeluaran per bulan: " . format_rupiah($rata_pengeluaran) . "\n";
    $data .= "Bulan bertahan tanpa pemasukan: " . ($rata_pengeluaran > 0 ? floor($saldo['saldo_kas']/$rata_pengeluaran) : 'N/A') . "\n\n";
    $data .= "Analisis dampak positif, negatif, risiko defisit, dan saran.";

    $system = "Analis keputusan keuangan. Prediksi konsekuensi keputusan terhadap kas, operasional, dan program sosial. Format JSON: {\"dampak_kas\":\"...\",\"risiko_defisit\":\"Rendah/Sedang/Tinggi\",\"dampak_operasional\":\"...\",\"rekomendasi\":\"...\",\"analisis_keseluruhan\":\"...\"}";
    return ai_prompt($system, $data, 400);
}

function ai_audit_risk_assessment() {
    $pdo = get_connection();
    $saldo = get_total_saldo();
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();
    $tanpa_bukti = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE (bukti_file IS NULL OR bukti_file='') AND status='approved'")->fetchColumn();
    $besar_mencurigakan = $pdo->query("SELECT COUNT(*) FROM jurnal_detail jd JOIN transaksi t ON jd.transaksi_id=t.id WHERE t.status='approved' AND (jd.debit>10000000 OR jd.kredit>10000000)")->fetchColumn();
    $weekend = $pdo->query("SELECT COUNT(*) FROM transaksi t WHERE t.status='approved' AND DAYOFWEEK(t.tanggal) IN (1,7)")->fetchColumn();
    $unbalanced = $pdo->query("SELECT COUNT(*) c FROM (SELECT t.id FROM transaksi t JOIN jurnal_detail jd ON jd.transaksi_id=t.id WHERE t.status='approved' GROUP BY t.id HAVING SUM(jd.debit)!=SUM(jd.kredit)) sub")->fetchColumn();
    $duplicates = $pdo->query("SELECT COUNT(*) c FROM (SELECT keterangan,DATE(tanggal) FROM transaksi WHERE status='approved' GROUP BY keterangan,DATE(tanggal) HAVING COUNT(*)>1) sub")->fetchColumn();

    $risk_score = 0;
    $risk_score += ($tanpa_bukti / max($total_transaksi, 1)) * 25;
    $risk_score += ($besar_mencurigakan / max($total_transaksi, 1)) * 20;
    $risk_score += ($weekend / max($total_transaksi, 1)) * 15;
    $risk_score += ($unbalanced > 0) ? 20 : 0;
    $risk_score += ($duplicates > 0) ? 20 : 0;
    $risk_score = min(100, round($risk_score));

    $data = "Total transaksi: $total_transaksi\n";
    $data .= "Tanpa bukti: $tanpa_bukti\n";
    $data .= "Transaksi besar (>10jt): $besar_mencurigakan\n";
    $data .= "Transaksi akhir pekan: $weekend\n";
    $data .= "Jurnal tidak balance: " . ($unbalanced > 0 ? "$unbalanced transaksi" : "Tidak ada") . "\n";
    $data .= "Duplikasi: " . ($duplicates > 0 ? "$duplicates grup" : "Tidak ada") . "\n";
    $data .= "Skor risiko mentah: $risk_score/100\n";

    $system = "Auditor risiko keuangan masjid. Analisis risiko fraud dan beri skor A-E (A=Rendah, E=Sangat Tinggi). Format JSON: {\"skor_risiko\":\"A-E\",\"nilai\":$risk_score,\"analisis\":\"...\",\"indikator_bermasalah\":[\"...\"],\"rekomendasi\":[\"...\"],\"kesimpulan\":\"...\"}";
    return ai_prompt($system, $data, 400);
}

function ai_audit_timing_analysis() {
    $pdo = get_connection();
    $stmt = $pdo->query("
        SELECT t.id, t.tanggal, t.keterangan,
               DAYNAME(t.tanggal) as hari,
               HOUR(t.tanggal) as jam,
               SUM(CASE WHEN jd.debit>0 THEN jd.debit ELSE jd.kredit END) as jumlah
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id=jd.transaksi_id
        WHERE t.status='approved'
          AND (DAYOFWEEK(t.tanggal) IN (1,7) OR HOUR(t.tanggal) < 6 OR HOUR(t.tanggal) > 20)
        GROUP BY t.id, t.tanggal, t.keterangan
        ORDER BY t.tanggal DESC
        LIMIT 10
    ");
    $odd = $stmt->fetchAll();

    if (empty($odd)) return ['content' => 'Tidak ditemukan transaksi pada waktu tidak biasa (malam/akhir pekan).'];


    $data = "10 transaksi terakhir pada waktu tidak biasa:\n";
    foreach ($odd as $o) {
        $data .= "- {$o['tanggal']} ({$o['hari']}) jam {$o['jam']}:00 | {$o['keterangan']} | " . format_rupiah($o['jumlah']) . " (ID:{$o['id']})\n";
    }

    $system = "Auditor forensik. Analisis pola waktu transaksi untuk indikasi fraud. Beri skor dan analisis. Format JSON: {\"skor_risiko_waktu\":\"Rendah/Sedang/Tinggi\",\"pola_ditemukan\":[\"...\"],\"transaksi_mencurigakan\":[{\"id\":angka,\"tanggal\":\"...\",\"keterangan\":\"...\",\"alasan\":\"...\"}],\"rekomendasi\":\"...\"}";
    return ai_prompt($system, $data, 500);
}

function ai_audit_comprehensive_report() {
    $pdo = get_connection();
    $saldo = get_total_saldo();
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='approved'")->fetchColumn();
    $total_pemasukan = get_total_pemasukan();
    $total_pengeluaran = get_total_pengeluaran();
    $bulan_data = $pdo->query("SELECT COUNT(DISTINCT DATE_FORMAT(tanggal,'%Y-%m')) bulan FROM transaksi")->fetchColumn();

    $neraca = get_neraca_saldo();
    $total_debit = 0; $total_kredit = 0;
    foreach ($neraca as $r) {
        $total_debit += $r['total_debit'];
        $total_kredit += $r['total_kredit'];
    }
    $balance_ok = ($total_debit == $total_kredit);

    $tidak_balance = $pdo->query("SELECT COUNT(*) FROM (SELECT t.id FROM transaksi t JOIN jurnal_detail jd ON jd.transaksi_id=t.id WHERE t.status='approved' GROUP BY t.id HAVING SUM(jd.debit)!=SUM(jd.kredit)) sub")->fetchColumn();
    $duplicates = $pdo->query("SELECT COUNT(*) FROM (SELECT keterangan,DATE(tanggal) FROM transaksi WHERE status='approved' GROUP BY keterangan,DATE(tanggal) HAVING COUNT(*)>1) sub")->fetchColumn();
    $tanpa_bukti = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE (bukti_file IS NULL OR bukti_file='') AND status='approved'")->fetchColumn();

    $data = "=== LAPORAN AUDIT KOMPREHENSIF ===\n\n";
    $data .= "Ringkasan Keuangan:\n";
    $data .= "Saldo kas: " . format_rupiah($saldo['saldo_kas']) . "\n";
    $data .= "Total pemasukan: " . format_rupiah($total_pemasukan) . "\n";
    $data .= "Total pengeluaran: " . format_rupiah($total_pengeluaran) . "\n";
    $data .= "Bulan data: $bulan_data\n";
    $data .= "Jumlah transaksi: $total_transaksi\n\n";

    $data .= "Neraca Saldo:\n";
    $data .= "Total debit: " . format_rupiah($total_debit) . "\n";
    $data .= "Total kredit: " . format_rupiah($total_kredit) . "\n";
    $data .= "Balance: " . ($balance_ok ? "OK" : "TIDAK SEIMBANG (selisih " . format_rupiah(abs($total_debit-$total_kredit)) . ")") . "\n\n";

    $data .= "Temuan Audit:\n";
    $data .= "Transaksi tidak balance: $tidak_balance\n";
    $data .= "Duplikasi transaksi: $duplicates\n";
    $data .= "Transaksi tanpa bukti: $tanpa_bukti dari $total_transaksi\n";

    $system = "Auditor senior. Buat laporan audit komprehensif dengan skor A-E, temuan, dan rekomendasi prioritas. Format JSON: {\"skor_keseluruhan\":\"A-E\",\"ringkasan_eksekutif\":\"...\",\"temuan\":[{\"kategori\":\"...\",\"masalah\":\"...\",\"dampak\":\"...\",\"rekomendasi\":\"...\",\"prioritas\":\"Tinggi/Sedang/Rendah\"}],\"kesimpulan\":\"...\"}";
    return ai_prompt($system, $data, 600);
}

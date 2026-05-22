<?php

require_once __DIR__ . '/security.php';
init_session_security();
secure_headers();

// ========== AUTH ==========

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function is_bendahara() {
    return ($_SESSION['role'] ?? '') === 'bendahara';
}

function check_admin() {
    if (!is_admin()) {
        set_flash('Akses ditolak!', 'danger');
        header('Location: index.php');
        exit;
    }
}

function can_approve() {
    return is_admin();
}

// ========== SANITIZATION ==========

function sanitize($input) {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return trim($data);
}

// ========== FORMATTING ==========

function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function format_tanggal($date) {
    if (!$date || $date == '0000-00-00' || $date == '') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $t = explode('-', $date);
    if (count($t) < 3) return $date;
    return (int)$t[2] . ' ' . $bulan[(int)$t[1]] . ' ' . $t[0];
}

function format_datetime($datetime) {
    if (!$datetime) return '-';
    $date = substr($datetime, 0, 10);
    $time = substr($datetime, 11, 5);
    return format_tanggal($date) . ' ' . $time;
}

function format_persen($nilai, $total) {
    if ($total <= 0) return 0;
    return round(($nilai / $total) * 100, 1);
}

// ========== FLASH MESSAGES ==========

function set_flash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ========== THEME ==========

function get_theme() {
    return $_COOKIE['theme'] ?? 'light';
}



// ========== SETTINGS ==========

function get_setting($key) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : null;
}

// ========== AKUN ==========

function get_akun_kas() {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT id FROM akun WHERE kode_akun = '1-1000' LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function get_akun_by_kategori($kategori) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM akun WHERE kategori = ? AND is_aktif = 1 ORDER BY kode_akun");
    $stmt->execute([$kategori]);
    return $stmt->fetchAll();
}

function get_all_akun() {
    $pdo = get_connection();
    return $pdo->query("SELECT * FROM akun WHERE is_aktif = 1 ORDER BY kode_akun")->fetchAll();
}

function get_akun($id) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM akun WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_akun_by_kode($kode) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM akun WHERE kode_akun = ? LIMIT 1");
    $stmt->execute([$kode]);
    return $stmt->fetch();
}

// ========== ENHANCED AUDIT LOG ==========

function audit_log($aksi, $tabel, $record_id = null, $detail = null, $before = null, $after = null) {
    $pdo = get_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, tabel, record_id, detail, ip_address, user_agent, before_value, after_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $aksi,
        $tabel,
        $record_id,
        $detail,
        $ip,
        $ua,
        is_array($before) ? json_encode($before) : $before,
        is_array($after) ? json_encode($after) : $after
    ]);
}

function get_audit_logs($limit = 50) {
    $pdo = get_connection();
    return $pdo->query("
        SELECT a.*, u.nama_lengkap as user_name
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.id DESC
        LIMIT $limit
    ")->fetchAll();
}

// ========== ACCOUNTING ==========

function get_total_pemasukan($dari = null, $sampai = null) {
    $pdo = get_connection();
    $where = '';
    $params = [];
    if ($dari && $sampai) {
        $where = " AND t.tanggal BETWEEN ? AND ? AND t.status = 'approved'";
        $params = [$dari, $sampai];
    } else {
        $where = " AND t.status = 'approved'";
    }
    $sql = "SELECT COALESCE(SUM(jd.kredit), 0) as total
            FROM jurnal_detail jd
            JOIN akun a ON jd.akun_id = a.id
            JOIN transaksi t ON jd.transaksi_id = t.id
            WHERE a.kategori = 'Pendapatan' $where";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function get_total_pengeluaran($dari = null, $sampai = null) {
    $pdo = get_connection();
    $where = '';
    $params = [];
    if ($dari && $sampai) {
        $where = " AND t.tanggal BETWEEN ? AND ? AND t.status = 'approved'";
        $params = [$dari, $sampai];
    } else {
        $where = " AND t.status = 'approved'";
    }
    $sql = "SELECT COALESCE(SUM(jd.debit), 0) as total
            FROM jurnal_detail jd
            JOIN akun a ON jd.akun_id = a.id
            JOIN transaksi t ON jd.transaksi_id = t.id
            WHERE a.kategori = 'Beban' $where";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function get_total_saldo() {
    $pemasukan = get_total_pemasukan();
    $pengeluaran = get_total_pengeluaran();

    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(debit - kredit), 0) as saldo FROM jurnal_detail jd JOIN transaksi t ON jd.transaksi_id = t.id WHERE jd.akun_id = ? AND t.status = 'approved'");
    $stmt->execute([get_akun_kas()]);
    $saldo_kas = $stmt->fetchColumn();

    return [
        'saldo_kas' => $saldo_kas,
        'pemasukan' => $pemasukan,
        'pengeluaran' => $pengeluaran,
        'saldo' => $pemasukan - $pengeluaran
    ];
}

function get_transaksi_terbaru($limit = 5) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("
        SELECT t.*, u.nama_lengkap as user_name, au.nama_lengkap as approved_name
        FROM transaksi t
        LEFT JOIN users u ON t.created_by = u.id
        LEFT JOIN users au ON t.approved_by = au.id
        ORDER BY t.id DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function get_jumlah_transaksi_detail($transaksi_id) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("
        SELECT jd.*, a.kode_akun, a.nama_akun, a.kategori
        FROM jurnal_detail jd
        JOIN akun a ON jd.akun_id = a.id
        WHERE jd.transaksi_id = ?
        ORDER BY jd.id
    ");
    $stmt->execute([$transaksi_id]);
    return $stmt->fetchAll();
}

function get_jenis_transaksi($detail) {
    $debit_kas = 0; $kredit_kas = 0; $akun_lawan = null;
    foreach ($detail as $d) {
        if ($d['kode_akun'] == '1-1000') {
            $debit_kas = $d['debit'];
            $kredit_kas = $d['kredit'];
        } else {
            $akun_lawan = $d;
        }
    }
    if ($debit_kas > 0) return ['jenis' => 'Pemasukan', 'akun_lawan' => $akun_lawan, 'jumlah' => $debit_kas];
    if ($kredit_kas > 0) return ['jenis' => 'Pengeluaran', 'akun_lawan' => $akun_lawan, 'jumlah' => $kredit_kas];
    return ['jenis' => 'Lainnya', 'akun_lawan' => $akun_lawan, 'jumlah' => 0];
}

function get_neraca_saldo($dari = null, $sampai = null, $hanya_approved = true) {
    $pdo = get_connection();
    $where = '';
    $params = [];
    $joins = [];
    if ($hanya_approved) {
        $joins[] = "LEFT JOIN transaksi t ON jd.transaksi_id = t.id AND t.status = 'approved'";
    } else {
        $joins[] = "LEFT JOIN transaksi t ON jd.transaksi_id = t.id";
    }
    if ($dari && $sampai) {
        $where = "AND t.tanggal BETWEEN ? AND ?";
        $params = [$dari, $sampai];
    }
    $join_str = implode(' ', $joins);
    $sql = "
        SELECT a.kode_akun, a.nama_akun, a.kategori, a.saldo_normal, a.jenis_dana,
               COALESCE(SUM(jd.debit), 0) as total_debit,
               COALESCE(SUM(jd.kredit), 0) as total_kredit
        FROM akun a
        LEFT JOIN jurnal_detail jd ON a.id = jd.akun_id
        $join_str
        WHERE a.is_aktif = 1 $where
        GROUP BY a.id, a.kode_akun, a.nama_akun, a.kategori, a.saldo_normal, a.jenis_dana
        ORDER BY a.kode_akun
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_laporan_posisi_keuangan($dari = null, $sampai = null) {
    $neraca = get_neraca_saldo($dari, $sampai);
    $aset = []; $kewajiban = []; $aset_neto = [];
    $total_aset = 0; $total_kewajiban = 0; $total_aset_neto = 0;

    $surplus_defisit = get_total_pemasukan($dari, $sampai) - get_total_pengeluaran($dari, $sampai);

    foreach ($neraca as $r) {
        $saldo = $r['total_debit'] - $r['total_kredit'];
        if ($r['kategori'] == 'Aset') {
            if ($r['kode_akun'] == '1-1299') { // Akumulasi Penyusutan (kontra-aset)
                if ($saldo != 0) {
                    $aset[] = ['nama' => $r['nama_akun'], 'saldo' => -abs($saldo), 'kode' => $r['kode_akun']];
                    $total_aset -= abs($saldo);
                }
            } else {
                if ($saldo != 0) {
                    $aset[] = ['nama' => $r['nama_akun'], 'saldo' => abs($saldo), 'kode' => $r['kode_akun']];
                    $total_aset += abs($saldo);
                }
            }
        } elseif ($r['kategori'] == 'Kewajiban') {
            if ($saldo != 0) {
                $kewajiban[] = ['nama' => $r['nama_akun'], 'saldo' => abs($saldo), 'kode' => $r['kode_akun']];
                $total_kewajiban += abs($saldo);
            }
        } elseif ($r['kategori'] == 'Aset Neto' && $r['kode_akun'] != '3-1100') {
            if ($saldo != 0) {
                $aset_neto[] = ['nama' => $r['nama_akun'], 'saldo' => abs($saldo), 'kode' => $r['kode_akun']];
                $total_aset_neto += abs($saldo);
            }
        }
    }

    if ($surplus_defisit != 0) {
        $aset_neto[] = ['nama' => 'Surplus/Defisit', 'saldo' => $surplus_defisit, 'kode' => '3-1100'];
        $total_aset_neto += $surplus_defisit;
    }

    return [
        'aset' => $aset,
        'kewajiban' => $kewajiban,
        'aset_neto' => $aset_neto,
        'total_aset' => $total_aset,
        'total_kewajiban' => $total_kewajiban,
        'total_aset_neto' => $total_aset_neto,
    ];
}

function get_laporan_aktivitas($dari = null, $sampai = null) {
    $neraca = get_neraca_saldo($dari, $sampai);
    $pendapatan = []; $beban = [];
    $total_pendapatan = 0; $total_beban = 0;

    foreach ($neraca as $r) {
        if ($r['kategori'] == 'Pendapatan') {
            $saldo = $r['total_kredit'] - $r['total_debit'];
            if ($saldo != 0) {
                $pendapatan[] = ['nama' => $r['nama_akun'], 'saldo' => $saldo, 'kode' => $r['kode_akun']];
                $total_pendapatan += $saldo;
            }
        } elseif ($r['kategori'] == 'Beban') {
            $saldo = $r['total_debit'] - $r['total_kredit'];
            if ($saldo != 0) {
                $beban[] = ['nama' => $r['nama_akun'], 'saldo' => $saldo, 'kode' => $r['kode_akun']];
                $total_beban += $saldo;
            }
        }
    }

    return [
        'pendapatan' => $pendapatan,
        'beban' => $beban,
        'total_pendapatan' => $total_pendapatan,
        'total_beban' => $total_beban,
        'surplus' => $total_pendapatan - $total_beban,
    ];
}

function get_laporan_arus_kas($dari = null, $sampai = null) {
    $pdo = get_connection();

    $where = '';
    $params = [];
    if ($dari && $sampai) {
        $where = "AND t.tanggal BETWEEN ? AND ?";
        $params = [$dari, $sampai];
    }

    $saldo_awal = get_setting('saldo_awal_kas') ?? 0;

    $sql_penerimaan = "
        SELECT COALESCE(SUM(jd.debit), 0) as total
        FROM jurnal_detail jd
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE jd.akun_id = (SELECT id FROM akun WHERE kode_akun = '1-1000')
        AND jd.debit > 0 AND t.status = 'approved'
        $where
    ";
    $stmt = $pdo->prepare($sql_penerimaan);
    $stmt->execute($params);
    $penerimaan = $stmt->fetchColumn();

    $sql_pengeluaran = "
        SELECT COALESCE(SUM(jd.kredit), 0) as total
        FROM jurnal_detail jd
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE jd.akun_id = (SELECT id FROM akun WHERE kode_akun = '1-1000')
        AND jd.kredit > 0 AND t.status = 'approved'
        $where
    ";
    $stmt = $pdo->prepare($sql_pengeluaran);
    $stmt->execute($params);
    $pengeluaran_kas = $stmt->fetchColumn();

    $saldo_akhir = $saldo_awal + $penerimaan - $pengeluaran_kas;

    return [
        'saldo_awal' => $saldo_awal,
        'penerimaan' => $penerimaan,
        'pengeluaran' => $pengeluaran_kas,
        'saldo_akhir' => $saldo_akhir,
    ];
}

function get_data_grafik() {
    $pdo = get_connection();
    $sql = "
        SELECT DATE_FORMAT(t.tanggal, '%Y-%m') as bulan,
               SUM(CASE WHEN a.kategori = 'Pendapatan' THEN jd.kredit ELSE 0 END) as pemasukan,
               SUM(CASE WHEN a.kategori = 'Beban' THEN jd.debit ELSE 0 END) as pengeluaran
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        WHERE t.status = 'approved'
        GROUP BY DATE_FORMAT(t.tanggal, '%Y-%m')
        ORDER BY bulan
        LIMIT 12
    ";
    return $pdo->query($sql)->fetchAll();
}

function get_data_grafik_publik() {
    $pdo = get_connection();
    $sql = "
        SELECT DATE_FORMAT(t.tanggal, '%Y-%m') as bulan,
               SUM(CASE WHEN a.kategori = 'Pendapatan' THEN jd.kredit ELSE 0 END) as pemasukan,
               SUM(CASE WHEN a.kategori = 'Beban' THEN jd.debit ELSE 0 END) as pengeluaran
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        WHERE t.status = 'approved'
        GROUP BY DATE_FORMAT(t.tanggal, '%Y-%m')
        ORDER BY bulan
        LIMIT 12
    ";
    return $pdo->query($sql)->fetchAll();
}

function get_data_grafik_beban() {
    $pdo = get_connection();
    $sql = "
        SELECT a.nama_akun, SUM(jd.debit) as total
        FROM jurnal_detail jd
        JOIN akun a ON jd.akun_id = a.id
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE a.kategori = 'Beban' AND jd.debit > 0 AND t.status = 'approved'
        GROUP BY a.id, a.nama_akun
        ORDER BY total DESC
    ";
    return $pdo->query($sql)->fetchAll();
}

// ========== DANA KHUSUS ==========

function get_all_dana_khusus() {
    $pdo = get_connection();
    return $pdo->query("SELECT * FROM dana_khusus ORDER BY kode_dana")->fetchAll();
}

function get_dana_khusus($id) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM dana_khusus WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_laporan_dana_khusus($id = null) {
    $pdo = get_connection();
    $where = '';
    $params = [];
    if ($id) {
        $where = "WHERE t.dana_khusus_id = ? AND t.status = 'approved'";
        $params = [$id];
    } else {
        $where = "WHERE t.status = 'approved'";
    }
    $sql = "
        SELECT t.*, u.nama_lengkap as user_name, dk.nama_dana
        FROM transaksi t
        LEFT JOIN users u ON t.created_by = u.id
        LEFT JOIN dana_khusus dk ON t.dana_khusus_id = dk.id
        $where
        ORDER BY t.tanggal DESC, t.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function hitung_saldo_dana_khusus($id) {
    $pdo = get_connection();
    $kas_id = get_akun_kas();
    $sql = "
        SELECT COALESCE(SUM(
            CASE WHEN jd.akun_id = ? AND jd.debit > 0 THEN jd.debit ELSE 0 END
        ), 0) - COALESCE(SUM(
            CASE WHEN jd.akun_id = ? AND jd.kredit > 0 THEN jd.kredit ELSE 0 END
        ), 0) as saldo
        FROM jurnal_detail jd
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE t.dana_khusus_id = ? AND t.status = 'approved'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kas_id, $kas_id, $id]);
    return $stmt->fetchColumn();
}

// ========== INVENTARIS ==========

function get_all_inventaris() {
    $pdo = get_connection();
    return $pdo->query("SELECT * FROM inventaris ORDER BY nama_barang")->fetchAll();
}

function get_inventaris($id) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM inventaris WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_total_nilai_inventaris() {
    $pdo = get_connection();
    return $pdo->query("SELECT COALESCE(SUM(harga), 0) FROM inventaris")->fetchColumn();
}

function get_total_aset() {
    $total_kas = get_total_saldo()['saldo_kas'];
    $total_inventaris = get_total_nilai_inventaris();
    return $total_kas + $total_inventaris;
}

function hitung_penyusutan_garis_lurus($harga, $nilai_residu, $umur_ekonomis) {
    if ($umur_ekonomis <= 0) return 0;
    return ($harga - $nilai_residu) / $umur_ekonomis;
}

function hitung_nilai_buku($harga, $akumulasi_penyusutan) {
    return max(0, $harga - $akumulasi_penyusutan);
}

// ========== PERIODE AKUNTANSI ==========

function get_all_periode() {
    $pdo = get_connection();
    return $pdo->query("SELECT * FROM periode ORDER BY tahun DESC, bulan DESC")->fetchAll();
}

function get_periode_aktif() {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM periode WHERE status = 'aktif' ORDER BY tahun, bulan LIMIT 1");
    $stmt->execute();
    return $stmt->fetch();
}

function get_periode($id) {
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT * FROM periode WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function is_periode_tutup($tanggal) {
    $t = explode('-', $tanggal);
    if (count($t) < 3) return false;
    $bulan = (int)$t[1];
    $tahun = (int)$t[0];
    $pdo = get_connection();
    $stmt = $pdo->prepare("SELECT status FROM periode WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    $row = $stmt->fetch();
    return $row && $row['status'] == 'tutup';
}

function get_bulan_periode_options($selected_bulan = null, $selected_tahun = null) {
    $pdo = get_connection();
    $rows = $pdo->query("SELECT DISTINCT CONCAT(tahun, '-', LPAD(bulan, 2, '0')) as val, nama FROM periode ORDER BY tahun, bulan")->fetchAll();
    $html = '<option value="">-- Semua Periode --</option>';
    foreach ($rows as $r) {
        $sel = ($r['val'] == "$selected_tahun-" . str_pad($selected_bulan, 2, '0', STR_PAD_LEFT)) ? 'selected' : '';
        $html .= "<option value=\"{$r['val']}\" $sel>{$r['nama']}</option>";
    }
    return $html;
}

// ========== BUKU BESAR ==========

function get_buku_besar($akun_id, $dari = null, $sampai = null) {
    $pdo = get_connection();
    $akun = get_akun($akun_id);
    if (!$akun) return [];

    $where = "WHERE jd.akun_id = ? AND t.status = 'approved'";
    $params = [$akun_id];
    if ($dari && $sampai) {
        $where .= " AND t.tanggal BETWEEN ? AND ?";
        $params[] = $dari;
        $params[] = $sampai;
    }

    $sql = "
        SELECT t.id, t.tanggal, t.keterangan,
               jd.debit, jd.kredit
        FROM jurnal_detail jd
        JOIN transaksi t ON jd.transaksi_id = t.id
        $where
        ORDER BY t.tanggal, jd.id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $where_awal = "WHERE jd.akun_id = ? AND t.status = 'approved'";
    $params_awal = [$akun_id];
    if ($dari && $sampai) {
        $where_awal .= " AND t.tanggal < ?";
        $params_awal[] = $dari;
    }
    $sql_awal = "
        SELECT COALESCE(SUM(jd.debit), 0) as total_debit, COALESCE(SUM(jd.kredit), 0) as total_kredit
        FROM jurnal_detail jd
        JOIN transaksi t ON jd.transaksi_id = t.id
        $where_awal
    ";
    $stmt = $pdo->prepare($sql_awal);
    $stmt->execute($params_awal);
    $saldo_awal = $stmt->fetch();

    $saldo_normal = $akun['saldo_normal'];
    $saldo = ($saldo_normal == 'Debit')
        ? $saldo_awal['total_debit'] - $saldo_awal['total_kredit']
        : $saldo_awal['total_kredit'] - $saldo_awal['total_debit'];

    $result = [];
    $result['saldo_awal'] = $saldo;
    $result['akun'] = $akun;
    $result['entries'] = [];

    foreach ($rows as $r) {
        $mutasi = ($saldo_normal == 'Debit')
            ? $r['debit'] - $r['kredit']
            : $r['kredit'] - $r['debit'];
        $saldo += $mutasi;
        $r['saldo'] = $saldo;
        $result['entries'][] = $r;
    }

    return $result;
}

// ========== BUDGET ==========

function get_budget($akun_id, $bulan = null, $tahun = null) {
    if ($tahun === null) $tahun = date('Y');
    $pdo = get_connection();
    $params = [$akun_id, $tahun];
    $sql = "SELECT * FROM anggaran WHERE akun_id = ? AND tahun = ?";
    if ($bulan) {
        $sql .= " AND (bulan = ? OR jenis = 'tahunan')";
        $params[] = $bulan;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_realisasi_budget($akun_id, $bulan, $tahun) {
    $pdo = get_connection();
    $dari = "$tahun-$bulan-01";
    $sampai = date('Y-m-t', strtotime($dari));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(jd.debit + jd.kredit), 0) as total
        FROM jurnal_detail jd
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE jd.akun_id = ? AND t.tanggal BETWEEN ? AND ? AND t.status = 'approved'
    ");
    $stmt->execute([$akun_id, $dari, $sampai]);
    return $stmt->fetchColumn();
}

// ========== FINANCIAL HEALTH ==========

function get_financial_health_score() {
    $saldo = get_total_saldo();
    $total_pemasukan = $saldo['pemasukan'];
    $total_pengeluaran = $saldo['pengeluaran'];

    $score = 100;
    if ($total_pemasukan > 0) {
        $ratio = $total_pengeluaran / $total_pemasukan;
        if ($ratio > 1) $score -= ($ratio - 1) * 50;
        if ($ratio > 0.9) $score -= 10;
    }
    if ($saldo['saldo_kas'] <= 0) $score -= 30;
    if ($total_pemasukan == 0 && $total_pengeluaran == 0) $score = 50;

    return max(0, min(100, round($score)));
}

function get_financial_alerts() {
    $alerts = [];
    $pdo = get_connection();
    
    // Get current financial data
    $saldo = get_total_saldo();
    $pemasukan = $saldo['pemasukan'];
    $pengeluaran = $saldo['pengeluaran'];
    $saldo_kas = $saldo['saldo_kas'];
    
    // Get historical data for trend analysis (last 6 months)
    $historial_data = $pdo->query("
        SELECT DATE_FORMAT(t.tanggal, '%Y-%m') as bulan,
               SUM(CASE WHEN a.kategori='Pendapatan' THEN jd.kredit ELSE 0 END) as pemasukan,
               SUM(CASE WHEN a.kategori='Beban' THEN jd.debit ELSE 0 END) as pengeluaran
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        WHERE t.status='approved'
        GROUP BY bulan
        ORDER BY bulan DESC
        LIMIT 6
    ")->fetchAll();
    
    // Get expense by category for last 3 months
    $expense_by_category = $pdo->query("
        SELECT a.nama_akun, a.kode_akun,
               SUM(jd.debit) as total
        FROM jurnal_detail jd
        JOIN akun a ON jd.akun_id = a.id
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE a.kategori='Beban' 
          AND t.status='approved'
          AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY a.nama_akun, a.kode_akun
        ORDER BY total DESC
        LIMIT 5
    ")->fetchAll();
    
    // Get expense by category for previous 3 months for comparison
    $expense_by_category_prev = $pdo->query("
        SELECT a.nama_akun, a.kode_akun,
               SUM(jd.debit) as total
        FROM jurnal_detail jd
        JOIN akun a ON jd.akun_id = a.id
        JOIN transaksi t ON jd.transaksi_id = t.id
        WHERE a.kategori='Beban' 
          AND t.status='approved'
          AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
          AND t.tanggal < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY a.nama_akun, a.kode_akun
        ORDER BY total DESC
    ")->fetchAll();
    
    // Convert to associative array for easier lookup
    $prev_expenses = [];
    foreach ($expense_by_category_prev as $item) {
        $prev_expenses[$item['nama_akun']] = (float)$item['total'];
    }
    
    // Alert 1: Critical cash balance
    if ($saldo_kas < 1000000) { // Less than 1 juta
        $alerts[] = [
            'type' => 'danger',
            'icon' => 'bi-exclamation-triangle',
            'title' => 'Saldo Kas Kritis',
            'message' => 'Saldo kas saat ini: ' . format_rupiah($saldo_kas) . '. Disarankan untuk segera menambah penerimaan atau mengurangi pengeluaran.',
            'action' => 'lihat_transaksi',
            'url' => 'index.php?page=transaksi'
        ];
    } elseif ($saldo_kas < 5000000) { // Less than 5 juta
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'bi-exclamation-triangle',
            'title' => 'Saldo Kas Rendah',
            'message' => 'Saldo kas saat ini: ' . format_rupiah($saldo_kas) . '. Pertimbangkan untuk menghemat pengeluaran.',
            'action' => 'lihat_transaksi',
            'url' => 'index.php?page=transaksi'
        ];
    }
    
    // Alert 2: Expense increase detection
    foreach ($expense_by_category as $item) {
        $nama_akun = $item['nama_akun'];
        $current_total = (float)$item['total'];
        $prev_total = isset($prev_expenses[$nama_akun]) ? $prev_expenses[$nama_akun] : 0;
        
        if ($prev_total > 0) {
            $increase_percent = (($current_total - $prev_total) / $prev_total) * 100;
            if ($increase_percent > 30) { // More than 30% increase
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'bi-graph-up-arrow',
                    'title' => 'Pengeluaran Meningkat Tajam',
                    'message' => 'Pengeluaran untuk "' . $nama_akun . '" naik ' . number_format($increase_percent, 1) . '% dibandingkan 3 bulan sebelumnya.',
                    'action' => 'lihat_pengeluaran',
                    'url' => 'index.php?page=transaksi&filter=kategori&value=' . urlencode($nama_akun)
                ];
            }
        }
    }
    
    // Alert 3: Income decrease detection
    if (count($historial_data) >= 3) {
        $latest_income = (float)$historial_data[0]['pemasukan'];
        $prev_income = (float)$historial_data[1]['pemasukan'];
        $prev2_income = (float)$historial_data[2]['pemasukan'];
        
        if ($prev_income > 0 && $prev2_income > 0) {
            $recent_avg = ($latest_income + $prev_income) / 2;
            $decline_percent = (($prev2_income - $recent_avg) / $prev2_income) * 100;
            if ($decline_percent > 25) { // More than 25% decline
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'bi-arrow-down',
                    'title' => 'Pemasukkan Menurun Berkelanjutan',
                    'message' => 'Pemasukkan rata-rata 2 bulan terakhir turun ' . number_format($decline_percent, 1) . ' % dibandingkan 2 bulan sebelumnya.',
                    'action' => 'lihat_pemasukan',
                    'url' => 'index.php?page=transaksi&filter=kategori&value=Pendapatan'
                ];
            }
        }
    }
    
    // Alert 4: Unbalanced journals
    $unbalanced_journals = $pdo->query("
        SELECT t.id, t.tanggal, t.keterangan,
               COALESCE(SUM(jd.debit),0) total_debit,
               COALESCE(SUM(jd.kredit),0) total_kredit
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        WHERE t.status='approved'
        GROUP BY t.id, t.tanggal, t.keterangan
        HAVING total_debit != total_kredit
        LIMIT 5
    ")->fetchAll();
    
    if (!empty($unbalanced_journals)) {
        $tx_list = [];
        foreach ($unbalanced_journals as $u) {
            $tx_list[] = [
                'id' => $u['id'],
                'tanggal' => $u['tanggal'],
                'keterangan' => $u['keterangan'],
                'jumlah' => 'D:' . format_rupiah($u['total_debit']) . ' K:' . format_rupiah($u['total_kredit'])
            ];
        }
        $alerts[] = [
            'type' => 'danger',
            'icon' => 'bi-x-circle',
            'title' => 'Jurnal Tidak Balance',
            'message' => 'Ditemukan ' . count($unbalanced_journals) . ' transaksi dengan jurnal tidak seimbang (debit ≠ kredit).',
            'action' => 'lihat_transaksi_bermasalah',
            'url' => 'index.php?page=transaksi',
            'transactions' => $tx_list,
            'solution' => 'Hapus dan buat ulang jurnal detail transaksi tersebut agar debit = kredit. Atau gunakan fitur Perbaikan Data AI di menu AI Audit.'
        ];
    }
    
    // Alert 5: Suspicious transactions (large amounts without proper documentation)
    $suspicious_transactions = $pdo->query("
        SELECT t.id, t.tanggal, t.keterangan,
               SUM(CASE WHEN jd.debit > 0 THEN jd.debit ELSE jd.kredit END) as jumlah
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        WHERE t.status='approved'
          AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
          AND (jd.debit > 10000000 OR jd.kredit > 10000000)
        GROUP BY t.id, t.tanggal, t.keterangan
        ORDER BY jumlah DESC
        LIMIT 5
    ")->fetchAll();
    
    if (!empty($suspicious_transactions)) {
        $tx_list = [];
        foreach ($suspicious_transactions as $st) {
            $tx_list[] = [
                'id' => $st['id'],
                'tanggal' => $st['tanggal'],
                'keterangan' => $st['keterangan'],
                'jumlah' => format_rupiah($st['jumlah'])
            ];
        }
        $alerts[] = [
            'type' => 'info',
            'icon' => 'bi-search',
            'title' => 'Transaksi Besar Terdeteksi',
            'message' => 'Ditemukan ' . count($suspicious_transactions) . ' transaksi besar (>Rp10 juta) dalam bulan terakhir yang perlu diverifikasi.',
            'action' => 'lihat_transaksi_terbaru',
            'url' => 'index.php?page=transaksi',
            'transactions' => $tx_list,
            'solution' => 'Periksa bukti transaksi dan pastikan setiap transaksi besar memiliki dokumentasi lengkap. Jika tidak wajar, ajukan koreksi transaksi.'
        ];
    }
    
    // Alert 6: Weekend/night transactions (potential fraud indicator)
    $odd_time_tx = $pdo->query("
        SELECT t.id, t.tanggal, t.keterangan,
               DAYNAME(t.tanggal) as hari,
               HOUR(t.tanggal) as jam,
               SUM(CASE WHEN jd.debit > 0 THEN jd.debit ELSE jd.kredit END) as jumlah
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        WHERE t.status='approved'
          AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
          AND (DAYOFWEEK(t.tanggal) IN (1,7) OR HOUR(t.tanggal) < 6 OR HOUR(t.tanggal) > 20)
        GROUP BY t.id, t.tanggal, t.keterangan
        ORDER BY t.tanggal DESC
        LIMIT 5
    ")->fetchAll();
    
    if (count($odd_time_tx) > 3) {
        $tx_list = [];
        foreach ($odd_time_tx as $ot) {
            $tx_list[] = [
                'id' => $ot['id'],
                'tanggal' => $ot['tanggal'],
                'keterangan' => $ot['keterangan'] . ' (' . $ot['hari'] . ' jam ' . $ot['jam'] . ':00)',
                'jumlah' => format_rupiah($ot['jumlah'])
            ];
        }
        $alerts[] = [
            'type' => 'info',
            'icon' => 'bi-moon-stars',
            'title' => 'Transaksi Waktu Tidak Biasa',
            'message' => 'Ada ' . count($odd_time_tx) . ' transaksi pada akhir pekan/malam hari dalam seminggu terakhir yang perlu diperiksa.',
            'action' => 'lihat_transaksi_terbaru',
            'url' => 'index.php?page=transaksi',
            'transactions' => $tx_list,
            'solution' => 'Verifikasi keabsahan transaksi yang terjadi di luar jam kerja. Pastikan ada otorisasi yang sah untuk setiap transaksi tersebut.'
        ];
    }
    
    // Alert 7: Duplicate transactions detected
    $dup_transactions = $pdo->query("
        SELECT t1.id, t1.tanggal, t1.keterangan,
               (SELECT COALESCE(SUM(jd.debit),0) FROM jurnal_detail jd WHERE jd.transaksi_id=t1.id AND jd.akun_id!=(SELECT id FROM akun WHERE kode_akun='1-1000')) as jumlah
        FROM transaksi t1 WHERE t1.status='approved' AND EXISTS (
            SELECT 1 FROM transaksi t2 WHERE t2.status='approved' AND t2.id != t1.id
            AND DATE(t2.tanggal) = DATE(t1.tanggal) AND t2.keterangan = t1.keterangan
        ) ORDER BY t1.tanggal DESC LIMIT 5
    ")->fetchAll();
    
    if (count($dup_transactions) >= 2) {
        $tx_list = [];
        foreach ($dup_transactions as $d) {
            $tx_list[] = [
                'id' => $d['id'],
                'tanggal' => $d['tanggal'],
                'keterangan' => $d['keterangan'],
                'jumlah' => format_rupiah($d['jumlah'])
            ];
        }
        $alerts[] = [
            'type' => 'danger',
            'icon' => 'bi-files',
            'title' => 'Duplikasi Transaksi Terdeteksi',
            'message' => 'Ditemukan transaksi duplikat (sama tanggal & keterangan). Kemungkinan pencatatan ganda.',
            'action' => 'lihat_transaksi',
            'url' => 'index.php?page=transaksi',
            'transactions' => $tx_list,
            'solution' => 'Hapus transaksi duplikat, sisakan satu transaksi yang benar. Gunakan AI Perbaikan Data untuk otomatis mendeteksi dan menghapus duplikat.'
        ];
    }
    
    // Alert 8: Donation trend (if applicable)
    $donation_trend = $pdo->query("
        SELECT 
            SUM(CASE WHEN DATE_FORMAT(t.tanggal, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') 
                     THEN CASE WHEN jd.kredit > 0 AND a.kode_akun LIKE '%donasi%' THEN jd.kredit ELSE 0 END ELSE 0 END) as this_month,
            SUM(CASE WHEN DATE_FORMAT(t.tanggal, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') 
                     THEN CASE WHEN jd.kredit > 0 AND a.kode_akun LIKE '%donasi%' THEN jd.kredit ELSE 0 END ELSE 0 END) as last_month
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        WHERE t.status='approved'
          AND a.kode_akun LIKE '%donasi%'
    ")->fetch();
    
    if ($donation_trend && $donation_trend['last_month'] > 0) {
        $this_month = (float)$donation_trend['this_month'];
        $last_month = (float)$donation_trend['last_month'];
        if ($last_month > 0 && $this_month < ($last_month * 0.7)) { // More than 30% decrease
            $decrease_percent = ((($last_month - $this_month) / $last_month) * 100);
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-hand-coin',
                'title' => 'Donasi Menurun Drastis',
                'message' => 'Donasi bulan ini turun ' . number_format($decrease_percent, 1) . '% dibandingkan bulan sebelumnya.',
                'action' => 'lihat_donasi',
                'url' => 'index.php?page=transaksi&filter=kategori&value=Donasi'
            ];
        }
    }
    
    return $alerts;
}

function get_growth_percentage() {
    $pdo = get_connection();
    $now = date('Y-m');
    $prev = date('Y-m', strtotime('-1 month'));

    $sql = "
        SELECT DATE_FORMAT(t.tanggal, '%Y-%m') as bulan,
               SUM(CASE WHEN a.kategori = 'Pendapatan' THEN jd.kredit ELSE 0 END) as pemasukan
        FROM transaksi t
        JOIN jurnal_detail jd ON t.id = jd.transaksi_id
        JOIN akun a ON jd.akun_id = a.id
        WHERE t.status = 'approved' AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        GROUP BY DATE_FORMAT(t.tanggal, '%Y-%m')
        ORDER BY bulan
    ";
    $rows = $pdo->query($sql)->fetchAll();
    if (count($rows) < 2) return 0;
    $last = (float)end($rows)['pemasukan'];
    $first = (float)reset($rows)['pemasukan'];
    if ($first == 0) return $last > 0 ? 100 : 0;
    return round((($last - $first) / $first) * 100, 1);
}

// ========== BACKUP DATABASE ==========

function backup_database() {
    $pdo = get_connection();
    $dbname = DB_NAME;
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $output = "-- Backup Database: $dbname\n";
    $output .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $output .= "USE `$dbname`;\n\n";

    foreach ($tables as $table) {
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $create = $stmt->fetchColumn(1);
        $output .= "$create;\n\n";

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) continue;

        $cols = array_keys($rows[0]);
        $col_list = '`' . implode('`, `', $cols) . '`';
        $output .= "INSERT INTO `$table` ($col_list) VALUES\n";
        $vals = [];
        foreach ($rows as $row) {
            $escaped = [];
            foreach ($row as $v) {
                if ($v === null) {
                    $escaped[] = 'NULL';
                } else {
                    $escaped[] = "'" . str_replace("'", "''", $v) . "'";
                }
            }
            $vals[] = "(" . implode(', ', $escaped) . ")";
        }
        $output .= implode(",\n", $vals) . ";\n\n";
    }

    return $output;
}

// ========== EKSTRAK TEKS DOKUMEN ==========

function ekstrak_teks_dokumen($path, $mime) {
    if ($mime === 'application/pdf') {
        return ekstrak_teks_pdf($path);
    }
    if (in_array($mime, [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword'
    ])) {
        return ekstrak_teks_docx($path);
    }
    return '';
}

function ekstrak_teks_pdf($path) {
    $teks = '';
    $content = file_get_contents($path);
    if ($content === false) return '';

    // Coba ekstrak teks dari PDF dengan regex sederhana
    // PDF stream text biasanya di dalam kurung ( ... ) atau BT...ET blocks
    preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches);
    foreach ($matches[1] as $m) {
        $m = preg_replace_callback('/\\\\([0-7]{3})/', function($match) { return chr(octdec($match[1])); }, $m);
        $m = str_replace(['\\(', '\\)', '\\n'], ['(', ')', "\n"], $m);
        $teks .= $m . ' ';
    }

    if (empty(trim($teks))) {
        // Fallback: ambil teks antar BT...ET (PDF text objects)
        preg_match_all('/BT(.*?)ET/s', $content, $blocks);
        foreach ($blocks[1] as $block) {
            preg_match_all('/Tj|TJ\s*(.*?)\s*Tj/s', $block, $lines);
            $teks .= implode(' ', $lines[1]) . "\n";
        }
    }

    return trim($teks) ?: 'Tidak dapat mengekstrak teks dari PDF. File mungkin berupa scan/gambar.';
}

function ekstrak_teks_docx($path) {
    if (!class_exists('ZipArchive')) {
        return 'Ekstensi ZIP tidak tersedia.';
    }
    $zip = new ZipArchive;
    if ($zip->open($path) !== true) {
        return 'Gagal membuka file DOCX.';
    }

    // DOCX: word/document.xml, fallback ke word/header.xml dll
    $xml_content = '';
    $paths = ['word/document.xml', 'word/header1.xml', 'word/footer1.xml'];
    foreach ($paths as $p) {
        $content = $zip->getFromName($p);
        if ($content !== false) {
            $xml_content .= $content . ' ';
        }
    }
    $zip->close();

    if (empty($xml_content)) {
        return 'Tidak dapat membaca isi dokumen Word.';
    }

    // Hapus namespace XML
    $xml_content = preg_replace('/<[\/]?w:[^>]*>/', '', $xml_content);
    // Ambil teks antar tag
    preg_match_all('/<t[^>]*>(.*?)<\/t[^>]*>/s', $xml_content, $matches);
    $teks = implode(' ', $matches[1]);
    $teks = html_entity_decode($teks, ENT_QUOTES, 'UTF-8');
    $teks = preg_replace('/\s+/', ' ', $teks);

    return trim($teks) ?: 'Tidak dapat mengekstrak teks dari dokumen Word.';
}

// ========== TOGGLE PASSWORD ==========

function toggle_password_js() {
    return '
    <script>
    function togglePassword(id) {
        const el = document.getElementById(id);
        el.type = el.type === "password" ? "text" : "password";
    }
    </script>';
}

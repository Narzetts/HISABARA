<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['token'] ?? '';
$valid_token = get_setting('api_token');

if (!$valid_token || $token !== $valid_token) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API token']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';
$dari = $_GET['dari'] ?? date('Y-01-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

switch ($endpoint) {
    case 'dashboard':
        $keuangan = get_total_saldo();
        echo json_encode([
            'saldo_kas' => $keuangan['saldo_kas'],
            'total_pemasukan' => $keuangan['pemasukan'],
            'total_pengeluaran' => $keuangan['pengeluaran'],
            'surplus_defisit' => $keuangan['saldo'],
            'health_score' => get_financial_health_score(),
            'total_aset' => get_total_aset(),
        ]);
        break;

    case 'transaksi':
        $pdo = get_connection();
        $limit = (int)($_GET['limit'] ?? 50);
        $status = $_GET['status'] ?? '';
        $where = '';
        $params = [];
        if ($status) {
            $where = "WHERE t.status = ?";
            $params[] = $status;
        }
        $stmt = $pdo->prepare("SELECT t.*, u.nama_lengkap as user_name FROM transaksi t LEFT JOIN users u ON t.created_by = u.id $where ORDER BY t.id DESC LIMIT ?");
        $stmt->execute(array_merge($params, [$limit]));
        echo json_encode($stmt->fetchAll());
        break;

    case 'laporan':
        $posisi = get_laporan_posisi_keuangan($dari, $sampai);
        $aktivitas = get_laporan_aktivitas($dari, $sampai);
        $arus_kas = get_laporan_arus_kas($dari, $sampai);
        echo json_encode([
            'posisi_keuangan' => $posisi,
            'aktivitas' => $aktivitas,
            'arus_kas' => $arus_kas,
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
        ]);
        break;

    case 'neraca':
        $neraca = get_neraca_saldo($dari, $sampai);
        echo json_encode($neraca);
        break;

    case 'grafik':
        $grafik = get_data_grafik();
        echo json_encode($grafik);
        break;

    case 'beban':
        $beban = get_data_grafik_beban();
        echo json_encode($beban);
        break;

    case 'dana_khusus':
        $list = get_all_dana_khusus();
        $result = [];
        foreach ($list as $dk) {
            $result[] = [
                'id' => $dk['id'],
                'kode' => $dk['kode_dana'],
                'nama' => $dk['nama_dana'],
                'saldo' => hitung_saldo_dana_khusus($dk['id']),
            ];
        }
        echo json_encode($result);
        break;

    default:
        echo json_encode([
            'status' => 'ok',
            'message' => 'Hisabara API',
            'version' => '0.1',
            'endpoints' => ['dashboard', 'transaksi', 'laporan', 'neraca', 'grafik', 'beban', 'dana_khusus'],
            'token' => 'Gunakan header X-API-Token atau parameter ?token=',
        ]);
}

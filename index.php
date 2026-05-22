<?php
ob_start();
require_once 'config/database.php';
require_once 'config/functions.php';

run_migration();

// Redirect unauthenticated users to public landing page
if (!isset($_SESSION['user_id'])) {
    header('Location: public/index.php');
    exit;
}
require_csrf();

$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'akun', 'transaksi', 'laporan', 'buku_besar', 'inventaris', 'import', 'ai_assistant', 'ai_center', 'ai_keuangan', 'ai_analisis', 'ai_pengawas', 'ai_sosial', 'ai_hisa', 'ai_audit_intelligence', 'users', 'profil', 'periode', 'backup', 'anggaran', 'audit_log', 'calk'];
if (!in_array($page, $allowed)) $page = 'dashboard';

$page_title = [
    'dashboard' => 'Beranda',
    'akun' => 'Daftar Akun Keuangan',
    'transaksi' => 'Catat Keuangan',
    'laporan' => 'Laporan Keuangan',
    'buku_besar' => 'Riwayat Transaksi',
    'inventaris' => 'Data Inventaris',
    'import' => 'Upload Data',
    'anggaran' => 'Rencana Anggaran',
    'ai_assistant' => 'Asisten Keuangan AI',
    'ai_center' => 'HISA AI',
    'ai_keuangan' => 'AI Keuangan',
    'ai_analisis' => 'AI Analisis',
    'ai_pengawas' => 'AI Pengawas',
    'ai_sosial' => 'AI Dampak Sosial',
    'ai_hisa' => 'HISA AI',
    'ai_audit_intelligence' => 'Audit Pintar AI',
    'periode' => 'Periode Keuangan',
    'users' => 'Kelola Pengguna',
    'profil' => 'Profil Masjid',
    'backup' => 'Cadangan Data',
    'audit_log' => 'Riwayat Aktivitas',
    'calk' => 'Catatan Laporan',
];

$page_icon = [
    'dashboard' => 'grid-1x2',
    'akun' => 'book',
    'transaksi' => 'cash-stack',
    'laporan' => 'file-text',
    'buku_besar' => 'journal-text',
    'inventaris' => 'box',
    'ai_assistant' => 'robot',
    'ai_center' => 'cpu',
    'ai_keuangan' => 'wallet2',
    'ai_analisis' => 'graph-up',
    'ai_pengawas' => 'shield-check',
    'ai_sosial' => 'people',
    'ai_hisa' => 'cpu',
    'users' => 'people',
    'profil' => 'person-gear',
    'periode' => 'calendar3',
    'backup' => 'database-gear',
    'anggaran' => 'graph-up-arrow',
    'audit_log' => 'clipboard-data',
    'calk' => 'file-earmark-text',
    'import' => 'upload',
    'ai_audit_intelligence' => 'search',
];

$flash = get_flash();

// Handle export before layout
if ($page === 'laporan' && isset($_GET['export'])) {
    include "modules/laporan.php";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= get_theme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title[$page] ?> - Hisabara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="images/logo/logo.png">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="images/logo/logo.png" alt="Hisabara">
            </div>
            <div class="brand-text">
                Hisabara
                <small>v0.1</small>
            </div>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-label">📊 Dashboard</div>
            <a href="index.php?page=dashboard" class="nav-item <?= $page == 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> Beranda
            </a>

            <div class="menu-label mt-3">💰 Keuangan & Anggaran</div>
            <a href="index.php?page=transaksi" class="nav-item <?= $page == 'transaksi' ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i> Catat Keuangan
            </a>
            <a href="index.php?page=import" class="nav-item <?= $page == 'import' ? 'active' : '' ?>">
                <i class="bi bi-upload"></i> Upload Data
            </a>
            <a href="index.php?page=anggaran" class="nav-item <?= $page == 'anggaran' ? 'active' : '' ?>">
                <i class="bi bi-graph-up-arrow"></i> Rencana Anggaran
            </a>

            <div class="menu-label mt-3">📁 Data Utama</div>
            <a href="index.php?page=akun" class="nav-item <?= $page == 'akun' ? 'active' : '' ?>">
                <i class="bi bi-book"></i> Daftar Akun Keuangan
            </a>
            <a href="index.php?page=inventaris" class="nav-item <?= $page == 'inventaris' ? 'active' : '' ?>">
                <i class="bi bi-box"></i> Data Inventaris
            </a>

            <div class="menu-label mt-3">📈 Laporan & Pengawasan</div>
            <a href="index.php?page=laporan" class="nav-item <?= $page == 'laporan' ? 'active' : '' ?>">
                <i class="bi bi-file-text"></i> Laporan Keuangan
            </a>
            <a href="index.php?page=buku_besar" class="nav-item <?= $page == 'buku_besar' ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> Riwayat Transaksi
            </a>
            <a href="index.php?page=calk" class="nav-item <?= $page == 'calk' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i> Catatan Laporan
            </a>
            <a href="index.php?page=audit_log" class="nav-item <?= $page == 'audit_log' ? 'active' : '' ?>">
                <i class="bi bi-clipboard-data"></i> Riwayat Aktivitas
            </a>

            <?php if (is_admin()): ?>
            <div class="menu-label mt-3">⚙️ Pengaturan</div>
            <a href="index.php?page=periode" class="nav-item <?= $page == 'periode' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> Periode Keuangan
            </a>
            <a href="index.php?page=users" class="nav-item <?= $page == 'users' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Kelola Pengguna
            </a>
            <a href="index.php?page=profil" class="nav-item <?= $page == 'profil' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Profil Masjid
            </a>
            <a href="index.php?page=backup" class="nav-item <?= $page == 'backup' ? 'active' : '' ?>">
                <i class="bi bi-database-gear"></i> Cadangan Data
            </a>
            <?php endif; ?>

            <div class="menu-label mt-3">🤖 HISA AI</div>
            <a href="index.php?page=ai_hisa" class="nav-item <?= in_array($page, ['ai_hisa','ai_center','ai_keuangan','ai_analisis','ai_pengawas','ai_sosial','ai_audit_intelligence','ai_assistant']) ? 'active' : '' ?>">
                <i class="bi bi-cpu text-accent"></i> HISA AI
            </a>

            <div class="menu-label mt-3">🌐 Halaman Publik</div>
            <a href="public/index.php" target="_blank" class="nav-item">
                <i class="bi bi-globe"></i> Transparansi Keuangan
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="index.php?page=profil" class="user-info <?= $page == 'profil' ? 'active' : '' ?>">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div class="user-name"><?= sanitize($_SESSION['nama_lengkap'] ?? 'User') ?></div>
                    <div class="user-role"><?= sanitize($_SESSION['role'] ?? '') ?></div>
                </div>
                <i class="bi bi-gear ms-auto" style="opacity:.5"></i>
            </a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main-content">

        <!-- NAVBAR -->
        <nav class="navbar-top">
            <div class="navbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">
                    <i class="bi bi-<?= $page_icon[$page] ?> me-2"></i>
                    <?= $page_title[$page] ?>
                </h1>
            </div>
            <div class="navbar-right">
                <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                    <i class="bi <?= get_theme() == 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>"></i>
                </button>
                <a href="logout.php" class="btn btn-outline-danger btn-sm" data-confirm="Yakin ingin logout?">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </nav>

        <!-- CONTENT -->
        <div class="content-wrapper">

            <!-- FLASH MESSAGES -->
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php include "modules/{$page}.php"; ?>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <?php if ($flash): ?>
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        <div id="toastNotification" class="toast align-items-center text-bg-<?= $flash['type'] ?>" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <?= $flash['message'] ?>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/chart.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>

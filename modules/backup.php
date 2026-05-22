<?php
check_admin();

// Handle download backup
if (isset($_GET['download'])) {
    $sql = backup_database();
    $filename = 'backup_hisabara_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit;
}

// Handle restore upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            set_flash('File harus berekstensi .sql', 'danger');
            header('Location: index.php?page=backup');
            exit;
        }
        $content = file_get_contents($file['tmp_name']);
        if (!$content) {
            set_flash('File kosong atau tidak terbaca', 'danger');
            header('Location: index.php?page=backup');
            exit;
        }

        try {
            $pdo = get_connection();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Split by semicolon and execute each statement
            $statements = explode(";\n", $content);
            $count = 0;
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt && stripos($stmt, 'CREATE DATABASE') === false && stripos($stmt, 'USE ') === false) {
                    $pdo->exec($stmt);
                    $count++;
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            audit_log('Restore', 'database', null, 'Restore dari file: ' . $file['name']);
            set_flash("Database berhasil direstore ($count query dieksekusi)");
        } catch (Exception $e) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            set_flash('Gagal restore: ' . $e->getMessage(), 'danger');
        }
    } else {
        set_flash('Gagal upload file', 'danger');
    }
    header('Location: index.php?page=backup');
    exit;
}

// Get backup info
$backup_dir = __DIR__ . '/../exports';
$backup_files = glob($backup_dir . '/*.sql');
?>

<div class="row g-4">
    <!-- Backup -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-download me-1"></i> Backup Database</div>
            <div class="card-body text-center py-5">
                <div style="font-size:4rem;color:var(--accent);" class="mb-3">
                    <i class="bi bi-database-gear"></i>
                </div>
                <p class="text-secondary mb-3">Download file SQL lengkap dengan struktur tabel dan data.</p>
                <a href="index.php?page=backup&download=1" class="btn btn-accent btn-lg">
                    <i class="bi bi-download"></i> Download Backup (.sql)
                </a>
                <p class="text-secondary small mt-2">Ukuran: ~<?= number_format(strlen(backup_database()) / 1024, 1) ?> KB</p>
            </div>
        </div>
    </div>

    <!-- Restore -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-upload me-1"></i> Restore Database</div>
            <div class="card-body text-center py-5">
                <div style="font-size:4rem;color:#f59e0b;" class="mb-3">
                    <i class="bi bi-cloud-upload"></i>
                </div>
                <p class="text-secondary mb-3">Upload file SQL backup untuk mengembalikan data.</p>
                <form method="POST" enctype="multipart/form-data" data-confirm="Yakin restore database? Semua data saat ini akan diganti." data-danger>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                    </div>
                    <button class="btn btn-warning"><i class="bi bi-upload"></i> Restore Sekarang</button>
                </form>
            </div>
        </div>
    </div>

    <!-- File backup tersimpan -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-archive me-1"></i> Backup Tersimpan di Server</div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backup_files)): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada backup tersimpan</td></tr>
                            <?php else: ?>
                                <?php rsort($backup_files); foreach (array_slice($backup_files, 0, 20) as $f): $name = basename($f); $size = filesize($f); ?>
                                <tr>
                                    <td><?= $name ?></td>
                                    <td><?= number_format($size / 1024, 1) ?> KB</td>
                                    <td class="text-end">
                                        <a href="exports/<?= $name ?>" class="btn btn-sm btn-outline-accent" download><i class="bi bi-download"></i></a>
                                        <a href="index.php?page=backup&delete_file=<?= urlencode($name) ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            data-confirm="Hapus file <?= $name ?>?">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Handle file deletion
if (isset($_GET['delete_file'])) {
    $name = basename($_GET['delete_file']);
    $path = $backup_dir . '/' . $name;
    if (file_exists($path)) {
        unlink($path);
        set_flash("File $name berhasil dihapus");
    }
    header('Location: index.php?page=backup');
    exit;
}
?>

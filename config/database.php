<?php

function load_env() {
    $env_file = __DIR__ . '/../.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!defined($key)) {
                    define($key, $value);
                }
            }
        }
    } else {
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'keuangan_masjid');
        define('DB_USER', 'root');
        define('DB_PASS', '');
    }
}

load_env();

function get_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Koneksi database gagal. Silakan cek konfigurasi.');
        }
    }
    return $pdo;
}

function run_migration() {
    $pdo = get_connection();
    try {
        $pdo->query("SELECT status FROM transaksi LIMIT 1");
    } catch (PDOException $e) {
        $sql = file_get_contents(__DIR__ . '/../sql/migration.sql');
        $statements = explode(';', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e2) {
                    // skip errors for existing columns
                }
            }
        }
    }
}

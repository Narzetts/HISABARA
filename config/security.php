<?php

define('CSRF_TOKEN_KEY', 'csrf_token');

function generate_csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function csrf_field() {
    return '<input type="hidden" name="' . CSRF_TOKEN_KEY . '" value="' . generate_csrf_token() . '">';
}

function verify_csrf_token($token = null) {
    if ($token === null) $token = $_POST[CSRF_TOKEN_KEY] ?? '';
    if (empty($token)) {
        set_flash('Token CSRF tidak ditemukan. Refresh halaman dan coba lagi.', 'danger');
        return false;
    }
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        set_flash('Sesi habis. Silakan submit ulang.', 'warning');
        return false;
    }
    if (!hash_equals($_SESSION[CSRF_TOKEN_KEY], $token)) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        log_activity('CSRF token mismatch');
        set_flash('Token CSRF tidak valid. Refresh halaman dan coba lagi.', 'danger');
        return false;
    }
    return true;
}

function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) {
            $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

function init_session_security() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        ini_set('session.sid_length', 48);
        ini_set('session.sid_bits_per_character', 6);
        session_start();
    }

    $timeout = defined('SESSION_TIMEOUT') ? (int)SESSION_TIMEOUT : 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        log_activity('Session expired for user: ' . ($_SESSION['username'] ?? 'unknown'));
        session_unset();
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 86400) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

function rate_limit_check($key, $max_attempts = null, $lockout_duration = null) {
    if ($max_attempts === null) $max_attempts = defined('MAX_LOGIN_ATTEMPTS') ? (int)MAX_LOGIN_ATTEMPTS : 5;
    if ($lockout_duration === null) $lockout_duration = defined('LOCKOUT_DURATION') ? (int)LOCKOUT_DURATION : 900;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'rate_limit_' . md5($key . '_' . $ip);

    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['attempts' => 0, 'lockout_until' => 0];
    }

    $data = $_SESSION[$rate_key];
    if ($data['lockout_until'] > time()) {
        $remaining = ceil(($data['lockout_until'] - time()) / 60);
        log_activity("Rate limit locked for key: $key from IP: $ip");
        set_flash("Terlalu banyak percobaan. Coba lagi dalam $remaining menit.", 'danger');
        return false;
    }

    if ($data['attempts'] >= $max_attempts) {
        $_SESSION[$rate_key]['lockout_until'] = time() + $lockout_duration;
        log_activity("Rate limit triggered for key: $key from IP: $ip");
        set_flash("Terlalu banyak percobaan. Coba lagi dalam " . ceil($lockout_duration/60) . " menit.", 'danger');
        return false;
    }

    return true;
}

function rate_limit_increment($key) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'rate_limit_' . md5($key . '_' . $ip);
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['attempts' => 0, 'lockout_until' => 0];
    }
    $_SESSION[$rate_key]['attempts']++;
}

function rate_limit_reset($key) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'rate_limit_' . md5($key . '_' . $ip);
    unset($_SESSION[$rate_key]);
}

function secure_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; connect-src 'self'; frame-src 'none'; object-src 'none'");
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function validate_upload_file($file, $allowed_types = null) {
    if ($allowed_types === null) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        log_activity("Upload error: code {$file['error']} for file {$file['name']}");
        return 'Upload gagal (error ' . $file['error'] . ')';
    }

    $max_size = defined('UPLOAD_MAX_SIZE') ? (int)UPLOAD_MAX_SIZE : 5 * 1024 * 1024;
    if ($file['size'] > $max_size) return 'File terlalu besar. Maksimal ' . round($max_size/1024/1024, 1) . 'MB.';

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) return 'Tipe file tidak diizinkan: ' . implode(', ', $allowed_types);

    // MIME validation via finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $mime_map = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
    ];

    if (isset($mime_map[$ext]) && !in_array($mime, $mime_map[$ext])) {
        log_activity("MIME mismatch: extension=$ext, detected=$mime for file {$file['name']}");
        return 'MIME type tidak sesuai dengan ekstensi file';
    }

    return null;
}

function save_upload_file($file, $subdir = 'bukti') {
    $upload_dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Generate unique filename with hash
    $file_hash = hash_file('sha256', $file['tmp_name']);
    $filename = date('Ymd') . '_' . substr($file_hash, 0, 16) . '.' . $ext;

    // Avoid overwrite
    $dest = $upload_dir . '/' . $filename;
    $counter = 1;
    while (file_exists($dest)) {
        $filename = date('Ymd') . '_' . substr($file_hash, 0, 12) . '_' . $counter . '.' . $ext;
        $dest = $upload_dir . '/' . $filename;
        $counter++;
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/' . $subdir . '/' . $filename;
    }

    log_activity("Failed to save uploaded file: {$file['name']}");
    return null;
}

function delete_upload_file($path) {
    if ($path) {
        $full = __DIR__ . '/../' . $path;
        if (file_exists($full)) unlink($full);
    }
}

function sanitize_filename($name) {
    return preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $name);
}

function log_activity($description) {
    try {
        $pdo = get_connection();
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, tabel, detail) VALUES (?, 'system', 'system', ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? 0,
            $description
        ]);
    } catch (\Exception $e) {
        // Silently fail - don't break app for logging
    }
}

function log_login_attempt($username, $success) {
    try {
        $pdo = get_connection();
        $desc = $success ? 'Login berhasil: ' . $username : 'Login gagal: ' . $username;
        $user_id = 0;
        if ($success) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $u = $stmt->fetch();
            if ($u) $user_id = $u['id'];
        }
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, aksi, tabel, detail) VALUES (?, 'login', 'users', ?)");
        $stmt->execute([$user_id, $desc]);
    } catch (\Exception $e) {
        // Silently fail
    }
}

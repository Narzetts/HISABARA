<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        if (!rate_limit_check('login_' . $username)) {
            $error = 'Terlalu banyak percobaan. Silakan tunggu beberapa menit.';
        } else {
            $pdo = get_connection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                rate_limit_reset('login_' . $username);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['created'] = time();

                log_login_attempt($username, true);
                header('Location: index.php');
                exit;
            } else {
                rate_limit_increment('login_' . $username);
                log_login_attempt($username, false);
                $remaining = max(0, 5 - ($_SESSION['rate_limit_login_' . md5('login_' . $username . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'))]['attempts'] ?? 0));
                if ($remaining <= 0) {
                    $error = 'Terlalu banyak percobaan. Silakan tunggu beberapa menit.';
                } else {
                    $error = "Username atau password salah! ($remaining percobaan tersisa)";
                }
            }
        }
    } else {
        $error = 'Silakan isi username dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hisabara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="images/logo/logo.png">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">

    <div class="login-card">
        <div class="login-icon">
            <img src="images/logo/logo.png" alt="Hisabara">
        </div>
        <div class="login-title">Hisabara</div>
        <div class="login-subtitle"><?= sanitize(get_setting('nama_masjid') ?: 'Masjid') ?></div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-accent w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="public/index.php" class="text-decoration-none small text-secondary">
                <i class="bi bi-globe"></i> Halaman Publik
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>

<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (isset($_SESSION['user_id'])) {
    audit_log('Logout', 'users', $_SESSION['user_id'], 'Logout berhasil');
}

session_destroy();
header('Location: login.php');
exit;

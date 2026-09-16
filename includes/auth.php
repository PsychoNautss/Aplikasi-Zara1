<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function current_user()
{
    global $pdo;
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = $row;
    return $user;
}

function require_login()
{
    if (!current_user()) {
        flash_set('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

function require_admin()
{
    require_login();
    if (current_user()['role'] !== 'admin') {
        http_response_code(403);
        die('Hanya admin yang boleh mengakses halaman ini.');
    }
}

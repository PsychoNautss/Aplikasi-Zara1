<?php
require_once __DIR__ . '/includes/auth.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
csrf_verify();

$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$role = (string) ($_POST['role'] ?? 'user');

$errors = [];
if ($name === '' || $email === '' || $password === '') {
    $errors[] = 'Nama, email, dan password wajib diisi.';
}
if (mb_strlen($name) > 100) {
    $errors[] = 'Nama maksimal 100 karakter.';
}
if ($email !== '' && !is_valid_email($email)) {
    $errors[] = 'Format email tidak valid.';
}
if (strlen($password) < 6) {
    $errors[] = 'Password minimal 6 karakter.';
}
if (!in_array($role, ['user', 'admin'], true)) {
    $errors[] = 'Peran tidak valid.';
}

if (!$errors) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email sudah terdaftar.';
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('admin.php');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
    ->execute([$name, $email, $passwordHash, $role]);

flash_set('success', 'Akun pengguna berhasil dibuat.');
redirect('admin.php');

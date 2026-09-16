<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $old = ['name' => $name, 'email' => $email];

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

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')"
        );
        $insert->execute([$name, $email, $passwordHash]);

        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        flash_set('success', 'Akun berhasil dibuat. Selamat datang di JARA!');
        redirect('dashboard.php');
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar · JARA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-hero">
        <div class="auth-hero-content">
            <div class="auth-logo">JARA</div>
            <h1>Mulai kelola tugasmu hari ini.</h1>
            <p class="lead">Buat akun gratis, susun daftar tugas pertamamu, dan ajak rekan kerja atau teman satu tim untuk berkolaborasi.</p>
            <ul class="auth-features">
                <li><span class="tick">&#10003;</span> Prioritas rendah, sedang, hingga tinggi</li>
                <li><span class="tick">&#10003;</span> Tenggat waktu untuk tiap tugas</li>
                <li><span class="tick">&#10003;</span> Kolaborasi tim dalam satu daftar</li>
            </ul>
        </div>
    </div>

    <div class="auth-form-side">
        <div class="auth-card">
            <div class="auth-card-logo">JARA</div>
            <h1>Buat Akun</h1>
            <p class="subtitle">Kelola tugas pribadi maupun tim dengan JARA.</p>

            <?php if ($errors): ?>
                <div class="auth-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="error-text"><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php">
                <?= csrf_field() ?>
                <input type="text" name="name" placeholder="Nama lengkap" value="<?= e($old['name']) ?>" required maxlength="100" autofocus>
                <input type="email" name="email" placeholder="Email" value="<?= e($old['email']) ?>" required>
                <input type="password" name="password" placeholder="Password (min. 6 karakter)" required minlength="6">
                <button type="submit">Daftar</button>
            </form>

            <p class="auth-footer-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
        </div>
    </div>
</div>
</body>
</html>

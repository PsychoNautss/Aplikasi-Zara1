<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $old = ['email' => $email];

    if ($email === '' || $password === '') {
        $errors[] = 'Email dan password wajib diisi.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email atau password salah.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            redirect('dashboard.php');
        }
    }
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · JARA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-hero">
        <div class="auth-hero-content">
            <div class="auth-logo">JARA</div>
            <h1>Kelola tugasmu, sendiri atau bersama tim.</h1>
            <p class="lead">Buat daftar tugas, atur prioritas dan tenggat waktu, lalu pantau progres penyelesaiannya di satu tempat.</p>
            <ul class="auth-features">
                <li><span class="tick">&#10003;</span> Kelompokkan tugas ke dalam beberapa daftar</li>
                <li><span class="tick">&#10003;</span> Undang kolaborator lewat email</li>
                <li><span class="tick">&#10003;</span> Pantau progres penyelesaian secara real-time</li>
            </ul>
        </div>
    </div>

    <div class="auth-form-side">
        <div class="auth-card">
            <div class="auth-card-logo">JARA</div>
            <h1>Selamat datang kembali</h1>
            <p class="subtitle">Masuk untuk melanjutkan mengelola daftar tugasmu.</p>

            <?php if ($flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="auth-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="error-text"><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <?= csrf_field() ?>
                <input type="email" name="email" placeholder="Email" value="<?= e($old['email']) ?>" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Masuk</button>
            </form>

            <p class="auth-footer-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>
    </div>
</div>
</body>
</html>

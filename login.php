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

$pageTitle = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:420px;margin:0 auto;">
    <h1>Masuk</h1>
    <p class="subtitle">Lanjutkan mengelola daftar tugasmu.</p>

    <?php foreach ($errors as $error): ?>
        <p class="error-text"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="post" action="login.php">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Email" value="<?= e($old['email']) ?>" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Masuk</button>
    </form>

    <p class="subtitle">Belum punya akun? <a href="register.php">Daftar di sini</a>.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

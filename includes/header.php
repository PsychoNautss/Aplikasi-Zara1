<?php
$user = current_user();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' · JARA' : 'JARA' ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="<?= $user ? 'dashboard.php' : 'login.php' ?>" class="brand">JARA</a>
        <nav>
            <?php if ($user): ?>
                <span class="nav-user">Halo, <?= e($user['name']) ?></span>
                <a href="dashboard.php">Daftar Tugas</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin.php">Panel Admin</a>
                <?php endif; ?>
                <a href="logout.php">Keluar</a>
            <?php else: ?>
                <a href="login.php">Masuk</a>
                <a href="register.php">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="page">
    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

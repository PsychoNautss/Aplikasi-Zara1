<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_login();
$user = current_user();

$NAME_MAX_LENGTH = 100;
$DESCRIPTION_MAX_LENGTH = 500;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '') {
        flash_set('error', 'Nama daftar wajib diisi.');
    } elseif (mb_strlen($name) > $NAME_MAX_LENGTH) {
        flash_set('error', "Nama daftar maksimal {$NAME_MAX_LENGTH} karakter.");
    } elseif (mb_strlen($description) > $DESCRIPTION_MAX_LENGTH) {
        flash_set('error', "Deskripsi maksimal {$DESCRIPTION_MAX_LENGTH} karakter.");
    } else {
        $stmt = $pdo->prepare('INSERT INTO lists (name, description, owner_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $description !== '' ? $description : null, $user['id']]);
        flash_set('success', 'Daftar baru berhasil dibuat.');
    }

    redirect('dashboard.php');
}

$stmt = $pdo->prepare(
    "SELECT DISTINCT l.*, (l.owner_id = ?) AS is_owner_flag
     FROM lists l
     LEFT JOIN list_members lm ON lm.list_id = l.id
     WHERE l.owner_id = ? OR lm.user_id = ?
     ORDER BY l.created_at DESC"
);
$stmt->execute([$user['id'], $user['id'], $user['id']]);
$lists = $stmt->fetchAll();

$pageTitle = 'Daftar Tugas';
require __DIR__ . '/includes/header.php';
?>

<h1>Daftar Tugasmu</h1>
<p class="subtitle">Kelompokkan tugas pribadi atau tim ke dalam beberapa daftar, lalu pantau progresnya.</p>

<div class="card">
    <h2>Buat Daftar Baru</h2>
    <form method="post" action="dashboard.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <input type="text" name="name" placeholder="Nama daftar, misal: Proyek Skripsi" maxlength="100" required>
        </div>
        <textarea name="description" placeholder="Deskripsi (opsional)" rows="2" maxlength="500"></textarea>
        <div><button type="submit">Buat Daftar</button></div>
    </form>
</div>

<?php if (!$lists): ?>
    <div class="empty-state">Belum ada daftar tugas. Buat daftar pertamamu di atas.</div>
<?php else: ?>
    <?php foreach ($lists as $list): ?>
        <?php $progress = list_progress($pdo, $list['id']); ?>
        <a class="list-item" href="list_detail.php?id=<?= (int) $list['id'] ?>">
            <div class="list-item-head">
                <span style="font-weight:600;"><?= e($list['name']) ?></span>
                <span class="role"><?= $list['is_owner_flag'] ? 'Pemilik' : 'Kolaborator' ?></span>
            </div>
            <div class="progress-track"><div class="progress-fill" style="width:<?= $progress['progress'] ?>%"></div></div>
            <div class="progress-label"><?= $progress['doneCount'] ?>/<?= $progress['taskCount'] ?> tugas selesai (<?= $progress['progress'] ?>%)</div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

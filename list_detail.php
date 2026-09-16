<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_login();
$user = current_user();

$listId = (int) ($_GET['id'] ?? 0);
$access = get_list_access($pdo, $listId, $user['id']);

if (!$access['list']) {
    http_response_code(404);
    $pageTitle = 'Tidak ditemukan';
    require __DIR__ . '/includes/header.php';
    echo '<p class="error-text">Daftar tidak ditemukan.</p><a href="dashboard.php">Kembali ke daftar tugas</a>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
if (!$access['isMember']) {
    http_response_code(403);
    $pageTitle = 'Tidak diizinkan';
    require __DIR__ . '/includes/header.php';
    echo '<p class="error-text">Kamu tidak punya akses ke daftar ini.</p><a href="dashboard.php">Kembali ke daftar tugas</a>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$list = $access['list'];
$isOwner = $access['isOwner'];
$progress = list_progress($pdo, $listId);

$ownerStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
$ownerStmt->execute([$list['owner_id']]);
$owner = $ownerStmt->fetch();

$membersStmt = $pdo->prepare(
    'SELECT u.id, u.name, u.email
     FROM list_members lm JOIN users u ON u.id = lm.user_id
     WHERE lm.list_id = ?'
);
$membersStmt->execute([$listId]);
$members = $membersStmt->fetchAll();

$tasksStmt = $pdo->prepare(
    "SELECT t.*, u.name AS assignee_name
     FROM tasks t LEFT JOIN users u ON u.id = t.assigned_to
     WHERE t.list_id = ?
     ORDER BY (t.status = 'done'), t.due_date IS NULL, t.due_date ASC, t.priority DESC"
);
$tasksStmt->execute([$listId]);
$tasks = $tasksStmt->fetchAll();

$pageTitle = $list['name'];
require __DIR__ . '/includes/header.php';
?>

<a href="dashboard.php">&larr; Semua daftar</a>

<div class="list-item-head" style="margin-top:8px;">
    <div>
        <h1><?= e($list['name']) ?></h1>
        <p class="subtitle">Pemilik: <?= e($owner['name']) ?></p>
    </div>
    <?php if ($isOwner): ?>
        <form method="post" action="list_delete.php" onsubmit="return confirm('Hapus daftar ini beserta semua tugas di dalamnya?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $list['id'] ?>">
            <button type="submit" class="btn-link">Hapus Daftar</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Progres Penyelesaian</h2>
    <div class="progress-track"><div class="progress-fill" style="width:<?= $progress['progress'] ?>%"></div></div>
    <div class="progress-label"><?= $progress['doneCount'] ?>/<?= $progress['taskCount'] ?> tugas selesai (<?= $progress['progress'] ?>%)</div>
</div>

<?php if ($isOwner): ?>
    <div class="card">
        <h2>Kolaborator</h2>
        <div>
            <?php if (!$members): ?>
                <span class="subtitle">Belum ada kolaborator.</span>
            <?php endif; ?>
            <?php foreach ($members as $member): ?>
                <span class="member-chip">
                    <?= e($member['name']) ?>
                    <form method="post" action="member_remove.php" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="list_id" value="<?= (int) $list['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                        <button type="submit" title="Hapus kolaborator">&times;</button>
                    </form>
                </span>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($_GET['member_error'])): ?>
            <p class="error-text"><?= e($_GET['member_error']) ?></p>
        <?php endif; ?>
        <form method="post" action="member_add.php" class="form-row" style="margin-top:8px;">
            <?= csrf_field() ?>
            <input type="hidden" name="list_id" value="<?= (int) $list['id'] ?>">
            <input type="email" name="email" placeholder="Email kolaborator" required>
            <button type="submit" class="btn-muted">Undang</button>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Tambah Tugas</h2>
    <form method="post" action="task_create.php">
        <?= csrf_field() ?>
        <input type="hidden" name="list_id" value="<?= (int) $list['id'] ?>">
        <div class="form-row">
            <input type="text" name="title" placeholder="Judul tugas..." maxlength="150" required>
            <select name="priority">
                <option value="low">Prioritas Rendah</option>
                <option value="medium" selected>Prioritas Sedang</option>
                <option value="high">Prioritas Tinggi</option>
            </select>
            <input type="date" name="due_date">
        </div>
        <textarea name="description" placeholder="Deskripsi (opsional)" rows="2" maxlength="1000"></textarea>
        <div><button type="submit">Tambah Tugas</button></div>
    </form>
</div>

<h2>Tugas</h2>
<?php if (!$tasks): ?>
    <div class="empty-state">Belum ada tugas di daftar ini.</div>
<?php endif; ?>
<?php foreach ($tasks as $task): ?>
    <div class="task-item">
        <div class="task-main">
            <form method="post" action="task_toggle.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                <input type="checkbox" onchange="this.form.submit()" <?= $task['status'] === 'done' ? 'checked' : '' ?>>
            </form>
            <div>
                <span class="task-title <?= $task['status'] === 'done' ? 'done' : '' ?>"><?= e($task['title']) ?></span>
                <span class="<?= priority_class($task['priority']) ?>"><?= priority_label($task['priority']) ?></span>
                <?php if ($task['due_date']): ?>
                    <div class="task-meta">Jatuh tempo: <?= e($task['due_date']) ?></div>
                <?php endif; ?>
                <?php if ($task['assignee_name']): ?>
                    <div class="task-meta">Ditugaskan ke: <?= e($task['assignee_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" action="task_delete.php" onsubmit="return confirm('Hapus tugas ini?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
            <button type="submit" class="btn-link">Hapus</button>
        </form>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

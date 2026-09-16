<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_admin();
$admin = current_user();

$usersStmt = $pdo->query(
    "SELECT u.id, u.name, u.email, u.role, u.created_at,
            (SELECT COUNT(*) FROM tasks t
               JOIN lists l ON l.id = t.list_id
               LEFT JOIN list_members lm ON lm.list_id = l.id AND lm.user_id = u.id
              WHERE (l.owner_id = u.id OR lm.user_id = u.id)) AS task_count
     FROM users u
     ORDER BY u.created_at DESC"
);
$users = $usersStmt->fetchAll();

$selectedUserId = isset($_GET['user']) ? (int) $_GET['user'] : null;
$selectedUser = null;
$userLists = [];
$userTasks = [];

if ($selectedUserId) {
    foreach ($users as $u) {
        if ((int) $u['id'] === $selectedUserId) {
            $selectedUser = $u;
            break;
        }
    }
}

if ($selectedUser) {
    $listsStmt = $pdo->prepare(
        "SELECT DISTINCT l.id, l.name, (l.owner_id = ?) AS is_owner_flag
         FROM lists l
         LEFT JOIN list_members lm ON lm.list_id = l.id
         WHERE l.owner_id = ? OR lm.user_id = ?
         ORDER BY l.created_at DESC"
    );
    $listsStmt->execute([$selectedUserId, $selectedUserId, $selectedUserId]);
    $userLists = $listsStmt->fetchAll();

    $tasksStmt = $pdo->prepare(
        "SELECT DISTINCT t.*, l.name AS list_name
         FROM tasks t
         JOIN lists l ON l.id = t.list_id
         LEFT JOIN list_members lm ON lm.list_id = l.id
         WHERE l.owner_id = ? OR lm.user_id = ?
         ORDER BY (t.status = 'done'), t.due_date IS NULL, t.due_date ASC"
    );
    $tasksStmt->execute([$selectedUserId, $selectedUserId]);
    $userTasks = $tasksStmt->fetchAll();
}

$pageTitle = 'Panel Admin';
require __DIR__ . '/includes/header.php';
?>

<h1>Panel Admin</h1>
<p class="subtitle">Kelola akun dan tugas seluruh pengguna.</p>

<div class="admin-grid">
    <div>
        <div class="card">
            <h2>Pengguna</h2>
            <?php foreach ($users as $u): ?>
                <div class="user-row">
                    <a class="user-link <?= $selectedUserId === (int) $u['id'] ? 'active' : '' ?>"
                       href="admin.php?user=<?= (int) $u['id'] ?>">
                        <?= e($u['name']) ?> <?php if ($u['role'] === 'admin'): ?><span style="color:var(--brand-500);font-size:0.75rem;">(admin)</span><?php endif; ?>
                        <small><?= e($u['email']) ?> &middot; <?= (int) $u['task_count'] ?> tugas</small>
                    </a>
                    <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                        <?php $confirmMsg = json_encode("Hapus akun \"{$u['name']}\" beserta seluruh daftar yang ia miliki?", JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                        <form method="post" action="admin_user_delete.php" onsubmit="return confirm(<?= $confirmMsg ?>);">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn-link">Hapus</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h2>Tambah Akun Pengguna</h2>
            <form method="post" action="admin_user_create.php">
                <?= csrf_field() ?>
                <input type="text" name="name" placeholder="Nama" maxlength="100" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password (min. 6 karakter)" minlength="6" required>
                <select name="role">
                    <option value="user">Pengguna</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit">Tambah Akun</button>
            </form>
        </div>
    </div>

    <div>
        <?php if (!$selectedUser): ?>
            <div class="empty-state">Pilih pengguna untuk melihat dan mengelola tugasnya.</div>
        <?php else: ?>
            <div class="card">
                <h2>Tambah Tugas untuk <?= e($selectedUser['name']) ?></h2>
                <?php if (!$userLists): ?>
                    <p class="subtitle">Pengguna ini belum punya daftar tugas apa pun, jadi admin belum bisa menambahkan tugas.</p>
                <?php else: ?>
                    <form method="post" action="admin_task_create.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $selectedUser['id'] ?>">
                        <div class="form-row">
                            <select name="list_id">
                                <?php foreach ($userLists as $l): ?>
                                    <option value="<?= (int) $l['id'] ?>"><?= e($l['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="title" placeholder="Judul tugas..." maxlength="150" required>
                        </div>
                        <div class="form-row">
                            <select name="priority">
                                <option value="low">Prioritas Rendah</option>
                                <option value="medium" selected>Prioritas Sedang</option>
                                <option value="high">Prioritas Tinggi</option>
                            </select>
                            <input type="date" name="due_date">
                            <button type="submit">Tambah</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Semua Tugas <?= e($selectedUser['name']) ?></h2>
                <?php if (!$userTasks): ?>
                    <p class="subtitle">Belum ada tugas.</p>
                <?php endif; ?>
                <?php foreach ($userTasks as $task): ?>
                    <div class="task-item">
                        <div>
                            <span class="task-title <?= $task['status'] === 'done' ? 'done' : '' ?>"><?= e($task['title']) ?></span>
                            <span class="<?= priority_class($task['priority']) ?>"><?= priority_label($task['priority']) ?></span>
                            <span class="task-meta">di <?= e($task['list_name']) ?></span>
                        </div>
                        <form method="post" action="admin_task_delete.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $selectedUser['id'] ?>">
                            <button type="submit" class="btn-link">Hapus</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

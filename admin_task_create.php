<?php
require_once __DIR__ . '/includes/auth.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
csrf_verify();

$VALID_PRIORITIES = ['low', 'medium', 'high'];
$admin = current_user();

$userId = (int) ($_POST['user_id'] ?? 0);
$listId = (int) ($_POST['list_id'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$priority = (string) ($_POST['priority'] ?? 'medium');
$dueDate = trim((string) ($_POST['due_date'] ?? ''));

if ($title === '') {
    flash_set('error', 'Judul tugas wajib diisi.');
    redirect('admin.php?user=' . $userId);
}
if (mb_strlen($title) > 150) {
    flash_set('error', 'Judul tugas maksimal 150 karakter.');
    redirect('admin.php?user=' . $userId);
}
if (!in_array($priority, $VALID_PRIORITIES, true)) {
    flash_set('error', 'Prioritas tidak valid.');
    redirect('admin.php?user=' . $userId);
}
if (!is_valid_date($dueDate)) {
    flash_set('error', 'Tanggal jatuh tempo tidak valid.');
    redirect('admin.php?user=' . $userId);
}

$listStmt = $pdo->prepare(
    "SELECT l.* FROM lists l
     LEFT JOIN list_members lm ON lm.list_id = l.id
     WHERE l.id = ? AND (l.owner_id = ? OR lm.user_id = ?)"
);
$listStmt->execute([$listId, $userId, $userId]);
$list = $listStmt->fetch();

if (!$list) {
    flash_set('error', 'Daftar tidak ditemukan untuk pengguna ini.');
    redirect('admin.php?user=' . $userId);
}

$pdo->prepare(
    'INSERT INTO tasks (list_id, title, description, priority, due_date, created_by, assigned_to)
     VALUES (?, ?, NULL, ?, ?, ?, ?)'
)->execute([$list['id'], $title, $priority, $dueDate !== '' ? $dueDate : null, $admin['id'], $userId]);

redirect('admin.php?user=' . $userId);

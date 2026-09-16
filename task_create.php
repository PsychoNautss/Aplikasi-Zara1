<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$VALID_PRIORITIES = ['low', 'medium', 'high'];
$TITLE_MAX_LENGTH = 150;
$DESCRIPTION_MAX_LENGTH = 1000;

$user = current_user();
$listId = (int) ($_POST['list_id'] ?? 0);

$access = get_list_access($pdo, $listId, $user['id']);
if (!$access['list']) {
    flash_set('error', 'Daftar tidak ditemukan.');
    redirect('dashboard.php');
}
if (!$access['isMember']) {
    flash_set('error', 'Kamu tidak punya akses ke daftar ini.');
    redirect('dashboard.php');
}

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$priority = (string) ($_POST['priority'] ?? 'medium');
$dueDate = trim((string) ($_POST['due_date'] ?? ''));

$errors = [];
if ($title === '') {
    $errors[] = 'Judul tugas wajib diisi.';
} elseif (mb_strlen($title) > $TITLE_MAX_LENGTH) {
    $errors[] = "Judul tugas maksimal {$TITLE_MAX_LENGTH} karakter.";
}
if ($description !== '' && mb_strlen($description) > $DESCRIPTION_MAX_LENGTH) {
    $errors[] = "Deskripsi maksimal {$DESCRIPTION_MAX_LENGTH} karakter.";
}
if (!in_array($priority, $VALID_PRIORITIES, true)) {
    $errors[] = 'Prioritas tidak valid.';
}
if (!is_valid_date($dueDate)) {
    $errors[] = 'Tanggal jatuh tempo tidak valid.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('list_detail.php?id=' . $listId);
}

$pdo->prepare(
    'INSERT INTO tasks (list_id, title, description, priority, due_date, created_by, assigned_to)
     VALUES (?, ?, ?, ?, ?, ?, NULL)'
)->execute([
    $listId,
    $title,
    $description !== '' ? $description : null,
    $priority,
    $dueDate !== '' ? $dueDate : null,
    $user['id'],
]);

redirect('list_detail.php?id=' . $listId);

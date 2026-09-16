<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$user = current_user();
$listId = (int) ($_POST['list_id'] ?? 0);
$targetUserId = (int) ($_POST['user_id'] ?? 0);

$access = get_list_access($pdo, $listId, $user['id']);
if (!$access['list']) {
    flash_set('error', 'Daftar tidak ditemukan.');
    redirect('dashboard.php');
}
if (!$access['isOwner']) {
    flash_set('error', 'Hanya pemilik daftar yang bisa menghapus anggota.');
    redirect('list_detail.php?id=' . $listId);
}

$pdo->prepare('DELETE FROM list_members WHERE list_id = ? AND user_id = ?')
    ->execute([$listId, $targetUserId]);

redirect('list_detail.php?id=' . $listId);

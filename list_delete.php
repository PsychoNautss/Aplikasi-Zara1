<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$user = current_user();
$listId = (int) ($_POST['id'] ?? 0);

$access = get_list_access($pdo, $listId, $user['id']);
if (!$access['list']) {
    flash_set('error', 'Daftar tidak ditemukan.');
    redirect('dashboard.php');
}
if (!$access['isOwner']) {
    flash_set('error', 'Hanya pemilik daftar yang bisa menghapus ini.');
    redirect('dashboard.php');
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM tasks WHERE list_id = ?')->execute([$listId]);
    $pdo->prepare('DELETE FROM list_members WHERE list_id = ?')->execute([$listId]);
    $pdo->prepare('DELETE FROM lists WHERE id = ?')->execute([$listId]);
    $pdo->commit();
    flash_set('success', 'Daftar berhasil dihapus.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Gagal menghapus daftar, seluruh perubahan dibatalkan.');
}

redirect('dashboard.php');

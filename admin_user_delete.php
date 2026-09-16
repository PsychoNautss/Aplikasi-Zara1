<?php
require_once __DIR__ . '/includes/auth.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
csrf_verify();

$admin = current_user();
$targetId = (int) ($_POST['id'] ?? 0);

if ($targetId === (int) $admin['id']) {
    flash_set('error', 'Admin tidak bisa menghapus akunnya sendiri.');
    redirect('admin.php');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$targetId]);
if (!$stmt->fetch()) {
    flash_set('error', 'Pengguna tidak ditemukan.');
    redirect('admin.php');
}

try {
    $pdo->beginTransaction();

    $listsStmt = $pdo->prepare('SELECT id FROM lists WHERE owner_id = ?');
    $listsStmt->execute([$targetId]);
    $ownedLists = $listsStmt->fetchAll();

    foreach ($ownedLists as $list) {
        $pdo->prepare('DELETE FROM tasks WHERE list_id = ?')->execute([$list['id']]);
        $pdo->prepare('DELETE FROM list_members WHERE list_id = ?')->execute([$list['id']]);
    }
    $pdo->prepare('DELETE FROM lists WHERE owner_id = ?')->execute([$targetId]);

    // Tugas yang dibuat pengguna ini di daftar milik orang lain dialihkan ke admin
    // yang melakukan penghapusan, supaya kolom created_by (NOT NULL) tetap valid.
    $pdo->prepare('UPDATE tasks SET created_by = ? WHERE created_by = ?')
        ->execute([$admin['id'], $targetId]);

    $pdo->prepare('DELETE FROM list_members WHERE user_id = ?')->execute([$targetId]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);

    $pdo->commit();
    flash_set('success', 'Pengguna berhasil dihapus.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Gagal menghapus pengguna, seluruh perubahan dibatalkan.');
}

redirect('admin.php');

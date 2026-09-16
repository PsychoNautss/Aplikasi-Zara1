<?php
require_once __DIR__ . '/includes/auth.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
csrf_verify();

$taskId = (int) ($_POST['id'] ?? 0);
$userId = (int) ($_POST['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ?');
$stmt->execute([$taskId]);
if (!$stmt->fetch()) {
    flash_set('error', 'Tugas tidak ditemukan.');
    redirect('admin.php?user=' . $userId);
}

$pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$taskId]);

redirect('admin.php?user=' . $userId);

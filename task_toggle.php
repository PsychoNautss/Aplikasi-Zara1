<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lists.php';

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$user = current_user();
$taskId = (int) ($_POST['id'] ?? 0);

$access = get_task_access($pdo, $taskId, $user['id']);
if (!$access['task']) {
    flash_set('error', 'Tugas tidak ditemukan.');
    redirect('dashboard.php');
}
if (!$access['isMember']) {
    flash_set('error', 'Kamu tidak punya akses ke tugas ini.');
    redirect('dashboard.php');
}

$task = $access['task'];
$nextStatus = $task['status'] === 'done' ? 'pending' : 'done';
$completedAt = $nextStatus === 'done' ? date('c') : null;

$pdo->prepare('UPDATE tasks SET status = ?, completed_at = ? WHERE id = ?')
    ->execute([$nextStatus, $completedAt, $taskId]);

redirect('list_detail.php?id=' . (int) $task['list_id']);

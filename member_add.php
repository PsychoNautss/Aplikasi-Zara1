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
$email = strtolower(trim((string) ($_POST['email'] ?? '')));

$access = get_list_access($pdo, $listId, $user['id']);
if (!$access['list']) {
    flash_set('error', 'Daftar tidak ditemukan.');
    redirect('dashboard.php');
}
if (!$access['isOwner']) {
    flash_set('error', 'Hanya pemilik daftar yang bisa menambah anggota.');
    redirect('list_detail.php?id=' . $listId);
}

if (!is_valid_email($email)) {
    redirect('list_detail.php?id=' . $listId . '&member_error=' . urlencode('Format email tidak valid.'));
}

$inviteeStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ?');
$inviteeStmt->execute([$email]);
$invitee = $inviteeStmt->fetch();

if (!$invitee) {
    redirect('list_detail.php?id=' . $listId . '&member_error=' . urlencode('Pengguna dengan email itu tidak ditemukan.'));
}
if ((int) $invitee['id'] === (int) $access['list']['owner_id']) {
    redirect('list_detail.php?id=' . $listId . '&member_error=' . urlencode('Pemilik sudah otomatis menjadi anggota daftar.'));
}

$pdo->prepare('INSERT OR IGNORE INTO list_members (list_id, user_id) VALUES (?, ?)')
    ->execute([$listId, $invitee['id']]);

flash_set('success', 'Kolaborator berhasil ditambahkan.');
redirect('list_detail.php?id=' . $listId);

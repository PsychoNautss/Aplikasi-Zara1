<?php

function get_list_access($pdo, $listId, $userId)
{
    $stmt = $pdo->prepare('SELECT * FROM lists WHERE id = ?');
    $stmt->execute([$listId]);
    $list = $stmt->fetch();

    if (!$list) {
        return ['list' => null, 'isMember' => false, 'isOwner' => false];
    }

    $isOwner = (int) $list['owner_id'] === (int) $userId;
    $isMember = $isOwner;
    if (!$isMember) {
        $memberStmt = $pdo->prepare('SELECT 1 FROM list_members WHERE list_id = ? AND user_id = ?');
        $memberStmt->execute([$listId, $userId]);
        $isMember = (bool) $memberStmt->fetch();
    }

    return ['list' => $list, 'isMember' => $isMember, 'isOwner' => $isOwner];
}

function list_progress($pdo, $listId)
{
    $totalStmt = $pdo->prepare('SELECT COUNT(*) AS n FROM tasks WHERE list_id = ?');
    $totalStmt->execute([$listId]);
    $total = (int) $totalStmt->fetch()['n'];

    $doneStmt = $pdo->prepare("SELECT COUNT(*) AS n FROM tasks WHERE list_id = ? AND status = 'done'");
    $doneStmt->execute([$listId]);
    $done = (int) $doneStmt->fetch()['n'];

    $progress = $total === 0 ? 0 : (int) round(($done / $total) * 100);

    return ['taskCount' => $total, 'doneCount' => $done, 'progress' => $progress];
}

function get_task_access($pdo, $taskId, $userId)
{
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        return ['task' => null, 'isMember' => false, 'isOwner' => false];
    }

    $access = get_list_access($pdo, $task['list_id'], $userId);
    return ['task' => $task, 'isMember' => $access['isMember'], 'isOwner' => $access['isOwner']];
}

function is_valid_assignee($pdo, $listId, $ownerId, $assignedTo)
{
    if (empty($assignedTo)) {
        return true;
    }
    $id = (int) $assignedTo;
    if ($id === (int) $ownerId) {
        return true;
    }
    $stmt = $pdo->prepare('SELECT 1 FROM list_members WHERE list_id = ? AND user_id = ?');
    $stmt->execute([$listId, $id]);
    return (bool) $stmt->fetch();
}

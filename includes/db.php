<?php

$dbPath = __DIR__ . '/../data/jara.sqlite';

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin')),
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS lists (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        owner_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS list_members (
        list_id INTEGER NOT NULL REFERENCES lists(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        added_at TEXT NOT NULL DEFAULT (datetime('now')),
        PRIMARY KEY (list_id, user_id)
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        list_id INTEGER NOT NULL REFERENCES lists(id) ON DELETE CASCADE,
        title TEXT NOT NULL,
        description TEXT,
        priority TEXT NOT NULL DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
        due_date TEXT,
        status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'done')),
        created_by INTEGER NOT NULL REFERENCES users(id),
        assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        completed_at TEXT
    )"
);

$pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_list_id ON tasks(list_id)');
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_list_members_user_id ON list_members(user_id)');

// Seed default admin so there is always a way into the admin panel on a fresh database.
$adminEmail = 'admin@jara.app';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$adminEmail]);
if (!$stmt->fetch()) {
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $insert = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')"
    );
    $insert->execute(['Admin JARA', $adminEmail, $passwordHash]);
}

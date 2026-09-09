import { Router } from "express";
import { db } from "../db.js";
import { requireAuth, requireAdmin } from "../middleware/auth.js";

export const adminRouter = Router();
adminRouter.use(requireAuth, requireAdmin);

const VALID_PRIORITIES = ["low", "medium", "high"];

// Semua pengguna beserta ringkasan tugasnya
adminRouter.get("/users", (req, res) => {
  const users = db
    .prepare(
      `SELECT u.id, u.name, u.email, u.role, u.created_at,
              (SELECT COUNT(*) FROM tasks t
                 JOIN lists l ON l.id = t.list_id
                 LEFT JOIN list_members lm ON lm.list_id = l.id AND lm.user_id = u.id
                WHERE (l.owner_id = u.id OR lm.user_id = u.id)) AS task_count
       FROM users u
       ORDER BY u.created_at DESC`
    )
    .all();
  res.json({ users });
});

// Daftar yang bisa diakses seorang pengguna (dimiliki atau diikuti), untuk dipilih admin saat menambah tugas
adminRouter.get("/users/:userId/lists", (req, res) => {
  const userId = Number(req.params.userId);
  const lists = db
    .prepare(
      `SELECT DISTINCT l.id, l.name, (l.owner_id = ?) AS is_owner_flag
       FROM lists l
       LEFT JOIN list_members lm ON lm.list_id = l.id
       WHERE l.owner_id = ? OR lm.user_id = ?
       ORDER BY l.created_at DESC`
    )
    .all(userId, userId, userId);
  res.json({ lists: lists.map((l) => ({ ...l, isOwner: Boolean(l.is_owner_flag) })) });
});

// Semua tugas milik pengguna tertentu, lintas daftar
adminRouter.get("/users/:userId/tasks", (req, res) => {
  const userId = Number(req.params.userId);
  const tasks = db
    .prepare(
      `SELECT DISTINCT t.*, l.name AS list_name
       FROM tasks t
       JOIN lists l ON l.id = t.list_id
       LEFT JOIN list_members lm ON lm.list_id = l.id
       WHERE l.owner_id = ? OR lm.user_id = ?
       ORDER BY (t.status = 'done'), t.due_date IS NULL, t.due_date ASC`
    )
    .all(userId, userId);
  res.json({ tasks });
});

// Admin menambahkan tugas baru untuk seorang pengguna di salah satu daftarnya
adminRouter.post("/users/:userId/tasks", (req, res) => {
  const userId = Number(req.params.userId);
  const { listId, title, description, priority, dueDate } = req.body;

  if (!title?.trim()) return res.status(400).json({ error: "Judul tugas wajib diisi." });
  if (priority && !VALID_PRIORITIES.includes(priority)) {
    return res.status(400).json({ error: "Prioritas tidak valid." });
  }

  const list = db
    .prepare(
      `SELECT l.* FROM lists l
       LEFT JOIN list_members lm ON lm.list_id = l.id
       WHERE l.id = ? AND (l.owner_id = ? OR lm.user_id = ?)`
    )
    .get(listId, userId, userId);
  if (!list) {
    return res.status(404).json({ error: "Daftar tidak ditemukan untuk pengguna ini." });
  }

  const result = db
    .prepare(
      `INSERT INTO tasks (list_id, title, description, priority, due_date, created_by, assigned_to)
       VALUES (?, ?, ?, ?, ?, ?, ?)`
    )
    .run(list.id, title.trim(), description?.trim() || null, priority || "medium", dueDate || null, req.user.id, userId);

  const task = db.prepare("SELECT * FROM tasks WHERE id = ?").get(result.lastInsertRowid);
  res.status(201).json({ task });
});

// Admin menghapus (mengurangi) tugas milik pengguna manapun
adminRouter.delete("/tasks/:id", (req, res) => {
  const task = db.prepare("SELECT * FROM tasks WHERE id = ?").get(Number(req.params.id));
  if (!task) return res.status(404).json({ error: "Tugas tidak ditemukan." });

  db.prepare("DELETE FROM tasks WHERE id = ?").run(task.id);
  res.status(204).end();
});

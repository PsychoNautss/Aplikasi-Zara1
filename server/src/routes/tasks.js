import { Router } from "express";
import { db } from "../db.js";
import { requireAuth } from "../middleware/auth.js";
import { getListWithAccessCheck } from "./lists.js";

export const tasksRouter = Router();
tasksRouter.use(requireAuth);

const VALID_PRIORITIES = ["low", "medium", "high"];

function getTaskWithAccessCheck(taskId, userId) {
  const task = db.prepare("SELECT * FROM tasks WHERE id = ?").get(taskId);
  if (!task) return { task: null, isMember: false };

  const { isMember, isOwner } = getListWithAccessCheck(task.list_id, userId);
  return { task, isMember, isOwner };
}

// Tugas dalam satu daftar
tasksRouter.get("/list/:listId", (req, res) => {
  const { list, isMember } = getListWithAccessCheck(Number(req.params.listId), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isMember) return res.status(403).json({ error: "Kamu tidak punya akses ke daftar ini." });

  const tasks = db
    .prepare(
      `SELECT t.*, u.name AS assignee_name
       FROM tasks t LEFT JOIN users u ON u.id = t.assigned_to
       WHERE t.list_id = ?
       ORDER BY (t.status = 'done'), t.due_date IS NULL, t.due_date ASC, t.priority DESC`
    )
    .all(list.id);

  res.json({ tasks });
});

tasksRouter.post("/list/:listId", (req, res) => {
  const { list, isMember } = getListWithAccessCheck(Number(req.params.listId), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isMember) return res.status(403).json({ error: "Kamu tidak punya akses ke daftar ini." });

  const { title, description, priority, dueDate, assignedTo } = req.body;
  if (!title?.trim()) return res.status(400).json({ error: "Judul tugas wajib diisi." });
  if (priority && !VALID_PRIORITIES.includes(priority)) {
    return res.status(400).json({ error: "Prioritas tidak valid." });
  }

  const result = db
    .prepare(
      `INSERT INTO tasks (list_id, title, description, priority, due_date, created_by, assigned_to)
       VALUES (?, ?, ?, ?, ?, ?, ?)`
    )
    .run(
      list.id,
      title.trim(),
      description?.trim() || null,
      priority || "medium",
      dueDate || null,
      req.user.id,
      assignedTo || null
    );

  const task = db.prepare("SELECT * FROM tasks WHERE id = ?").get(result.lastInsertRowid);
  res.status(201).json({ task });
});

tasksRouter.patch("/:id", (req, res) => {
  const { task, isMember } = getTaskWithAccessCheck(Number(req.params.id), req.user.id);
  if (!task) return res.status(404).json({ error: "Tugas tidak ditemukan." });
  if (!isMember) return res.status(403).json({ error: "Kamu tidak punya akses ke tugas ini." });

  const { title, description, priority, dueDate, status, assignedTo } = req.body;
  if (priority && !VALID_PRIORITIES.includes(priority)) {
    return res.status(400).json({ error: "Prioritas tidak valid." });
  }
  if (status && !["pending", "done"].includes(status)) {
    return res.status(400).json({ error: "Status tidak valid." });
  }

  const next = {
    title: title?.trim() || task.title,
    description: description !== undefined ? description?.trim() || null : task.description,
    priority: priority || task.priority,
    due_date: dueDate !== undefined ? dueDate || null : task.due_date,
    status: status || task.status,
    assigned_to: assignedTo !== undefined ? assignedTo || null : task.assigned_to,
    completed_at:
      status === "done" && task.status !== "done"
        ? new Date().toISOString()
        : status === "pending"
        ? null
        : task.completed_at,
  };

  db.prepare(
    `UPDATE tasks SET title = ?, description = ?, priority = ?, due_date = ?, status = ?, assigned_to = ?, completed_at = ?
     WHERE id = ?`
  ).run(
    next.title,
    next.description,
    next.priority,
    next.due_date,
    next.status,
    next.assigned_to,
    next.completed_at,
    task.id
  );

  const updated = db.prepare("SELECT * FROM tasks WHERE id = ?").get(task.id);
  res.json({ task: updated });
});

tasksRouter.delete("/:id", (req, res) => {
  const { task, isMember } = getTaskWithAccessCheck(Number(req.params.id), req.user.id);
  if (!task) return res.status(404).json({ error: "Tugas tidak ditemukan." });
  if (!isMember) return res.status(403).json({ error: "Kamu tidak punya akses ke tugas ini." });

  db.prepare("DELETE FROM tasks WHERE id = ?").run(task.id);
  res.status(204).end();
});

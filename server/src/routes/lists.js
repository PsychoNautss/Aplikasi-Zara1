import { Router } from "express";
import { db } from "../db.js";
import { requireAuth } from "../middleware/auth.js";

export const listsRouter = Router();
listsRouter.use(requireAuth);

function getListWithAccessCheck(listId, userId) {
  const list = db.prepare("SELECT * FROM lists WHERE id = ?").get(listId);
  if (!list) return { list: null, isMember: false, isOwner: false };

  const isOwner = list.owner_id === userId;
  const isMember =
    isOwner ||
    Boolean(
      db.prepare("SELECT 1 FROM list_members WHERE list_id = ? AND user_id = ?").get(listId, userId)
    );

  return { list, isMember, isOwner };
}

function withProgress(list) {
  const total = db.prepare("SELECT COUNT(*) AS n FROM tasks WHERE list_id = ?").get(list.id).n;
  const done = db
    .prepare("SELECT COUNT(*) AS n FROM tasks WHERE list_id = ? AND status = 'done'")
    .get(list.id).n;

  return {
    ...list,
    taskCount: total,
    doneCount: done,
    progress: total === 0 ? 0 : Math.round((done / total) * 100),
  };
}

// Daftar semua list yang dimiliki atau diikuti user
listsRouter.get("/", (req, res) => {
  const lists = db
    .prepare(
      `SELECT DISTINCT l.*, (l.owner_id = ?) AS is_owner_flag
       FROM lists l
       LEFT JOIN list_members lm ON lm.list_id = l.id
       WHERE l.owner_id = ? OR lm.user_id = ?
       ORDER BY l.created_at DESC`
    )
    .all(req.user.id, req.user.id, req.user.id);

  res.json({ lists: lists.map((l) => ({ ...withProgress(l), isOwner: Boolean(l.is_owner_flag) })) });
});

listsRouter.post("/", (req, res) => {
  const { name, description } = req.body;
  if (!name?.trim()) {
    return res.status(400).json({ error: "Nama daftar wajib diisi." });
  }

  const result = db
    .prepare("INSERT INTO lists (name, description, owner_id) VALUES (?, ?, ?)")
    .run(name.trim(), description?.trim() || null, req.user.id);

  const list = db.prepare("SELECT * FROM lists WHERE id = ?").get(result.lastInsertRowid);
  res.status(201).json({ list: { ...withProgress(list), isOwner: true } });
});

listsRouter.get("/:id", (req, res) => {
  const { list, isMember, isOwner } = getListWithAccessCheck(Number(req.params.id), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isMember) return res.status(403).json({ error: "Kamu tidak punya akses ke daftar ini." });

  const members = db
    .prepare(
      `SELECT u.id, u.name, u.email, 'collaborator' AS role
       FROM list_members lm JOIN users u ON u.id = lm.user_id
       WHERE lm.list_id = ?`
    )
    .all(list.id);
  const owner = db.prepare("SELECT id, name, email FROM users WHERE id = ?").get(list.owner_id);

  res.json({ list: { ...withProgress(list), isOwner }, owner, members });
});

listsRouter.patch("/:id", (req, res) => {
  const { list, isOwner } = getListWithAccessCheck(Number(req.params.id), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isOwner) return res.status(403).json({ error: "Hanya pemilik daftar yang bisa mengubah ini." });

  const { name, description } = req.body;
  db.prepare("UPDATE lists SET name = ?, description = ? WHERE id = ?").run(
    name?.trim() || list.name,
    description?.trim() ?? list.description,
    list.id
  );

  const updated = db.prepare("SELECT * FROM lists WHERE id = ?").get(list.id);
  res.json({ list: { ...withProgress(updated), isOwner: true } });
});

listsRouter.delete("/:id", (req, res) => {
  const { list, isOwner } = getListWithAccessCheck(Number(req.params.id), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isOwner) return res.status(403).json({ error: "Hanya pemilik daftar yang bisa menghapus ini." });

  db.prepare("DELETE FROM lists WHERE id = ?").run(list.id);
  res.status(204).end();
});

// Kolaborasi: pemilik menambahkan anggota lewat email
listsRouter.post("/:id/members", (req, res) => {
  const { list, isOwner } = getListWithAccessCheck(Number(req.params.id), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isOwner) return res.status(403).json({ error: "Hanya pemilik daftar yang bisa menambah anggota." });

  const { email } = req.body;
  const invitee = db.prepare("SELECT id, name, email FROM users WHERE email = ?").get(
    email?.trim().toLowerCase()
  );
  if (!invitee) return res.status(404).json({ error: "Pengguna dengan email itu tidak ditemukan." });
  if (invitee.id === list.owner_id) {
    return res.status(400).json({ error: "Pemilik sudah otomatis menjadi anggota daftar." });
  }

  db.prepare("INSERT OR IGNORE INTO list_members (list_id, user_id) VALUES (?, ?)").run(
    list.id,
    invitee.id
  );
  res.status(201).json({ member: invitee });
});

listsRouter.delete("/:id/members/:userId", (req, res) => {
  const { list, isOwner } = getListWithAccessCheck(Number(req.params.id), req.user.id);
  if (!list) return res.status(404).json({ error: "Daftar tidak ditemukan." });
  if (!isOwner) return res.status(403).json({ error: "Hanya pemilik daftar yang bisa menghapus anggota." });

  db.prepare("DELETE FROM list_members WHERE list_id = ? AND user_id = ?").run(
    list.id,
    Number(req.params.userId)
  );
  res.status(204).end();
});

export { getListWithAccessCheck };

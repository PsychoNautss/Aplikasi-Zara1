import { Router } from "express";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import { db } from "../db.js";
import { requireAuth } from "../middleware/auth.js";

export const authRouter = Router();

function signToken(user) {
  return jwt.sign({ userId: user.id }, process.env.JWT_SECRET, { expiresIn: "7d" });
}

function publicUser(user) {
  return { id: user.id, name: user.name, email: user.email, role: user.role };
}

authRouter.post("/register", (req, res) => {
  const { name, email, password } = req.body;

  if (!name?.trim() || !email?.trim() || !password) {
    return res.status(400).json({ error: "Nama, email, dan password wajib diisi." });
  }
  if (password.length < 6) {
    return res.status(400).json({ error: "Password minimal 6 karakter." });
  }

  const normalizedEmail = email.trim().toLowerCase();
  const existing = db.prepare("SELECT id FROM users WHERE email = ?").get(normalizedEmail);
  if (existing) {
    return res.status(409).json({ error: "Email sudah terdaftar." });
  }

  const passwordHash = bcrypt.hashSync(password, 10);
  const result = db
    .prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')")
    .run(name.trim(), normalizedEmail, passwordHash);

  const user = db.prepare("SELECT id, name, email, role FROM users WHERE id = ?").get(result.lastInsertRowid);
  res.status(201).json({ token: signToken(user), user: publicUser(user) });
});

authRouter.post("/login", (req, res) => {
  const { email, password } = req.body;
  if (!email?.trim() || !password) {
    return res.status(400).json({ error: "Email dan password wajib diisi." });
  }

  const user = db
    .prepare("SELECT * FROM users WHERE email = ?")
    .get(email.trim().toLowerCase());

  if (!user || !bcrypt.compareSync(password, user.password_hash)) {
    return res.status(401).json({ error: "Email atau password salah." });
  }

  res.json({ token: signToken(user), user: publicUser(user) });
});

authRouter.get("/me", requireAuth, (req, res) => {
  res.json({ user: req.user });
});

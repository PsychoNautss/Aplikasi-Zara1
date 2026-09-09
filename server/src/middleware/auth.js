import jwt from "jsonwebtoken";
import { db } from "../db.js";

export function requireAuth(req, res, next) {
  const header = req.headers.authorization || "";
  const token = header.startsWith("Bearer ") ? header.slice(7) : null;

  if (!token) {
    return res.status(401).json({ error: "Token tidak ditemukan, silakan login." });
  }

  try {
    const payload = jwt.verify(token, process.env.JWT_SECRET);
    const user = db
      .prepare("SELECT id, name, email, role FROM users WHERE id = ?")
      .get(payload.userId);

    if (!user) {
      return res.status(401).json({ error: "Akun tidak ditemukan." });
    }

    req.user = user;
    next();
  } catch {
    return res.status(401).json({ error: "Token tidak valid atau sudah kedaluwarsa." });
  }
}

export function requireAdmin(req, res, next) {
  if (req.user?.role !== "admin") {
    return res.status(403).json({ error: "Hanya admin yang boleh mengakses fitur ini." });
  }
  next();
}

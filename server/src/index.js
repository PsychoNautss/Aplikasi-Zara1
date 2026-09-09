import "dotenv/config";
import express from "express";
import cors from "cors";
import "./db.js";
import { authRouter } from "./routes/auth.js";
import { listsRouter } from "./routes/lists.js";
import { tasksRouter } from "./routes/tasks.js";
import { adminRouter } from "./routes/admin.js";

if (!process.env.JWT_SECRET) {
  console.error("JWT_SECRET belum diatur. Salin server/.env.example menjadi server/.env dan isi nilainya.");
  process.exit(1);
}

const app = express();
app.use(cors({ origin: process.env.CLIENT_ORIGIN || "http://localhost:5173" }));
app.use(express.json());

app.get("/api/health", (_req, res) => res.json({ ok: true }));
app.use("/api/auth", authRouter);
app.use("/api/lists", listsRouter);
app.use("/api/tasks", tasksRouter);
app.use("/api/admin", adminRouter);

app.use((err, _req, res, _next) => {
  console.error(err);
  res.status(500).json({ error: "Terjadi kesalahan pada server." });
});

const port = process.env.PORT || 4000;
app.listen(port, () => console.log(`Zara API berjalan di http://localhost:${port}`));

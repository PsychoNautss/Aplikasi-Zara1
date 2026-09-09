# Zara

Aplikasi web untuk mengelola tugas pribadi dan tim. Pengguna dapat membuat tugas, mengelompokkannya ke dalam beberapa daftar, menetapkan prioritas dan tenggat waktu, menandai tugas selesai, serta berkolaborasi dengan pemilik daftar. Pemilik daftar dapat memantau progres penyelesaian, dan admin dapat menambah maupun menghapus tugas milik pengguna mana pun.

## Struktur Proyek

- `server/` — REST API (Node.js, Express, SQLite via better-sqlite3, autentikasi JWT)
- `client/` — Aplikasi web (React, Vite, Tailwind CSS)

## Fitur

- Registrasi & login dengan email/password
- Buat, ubah, dan hapus daftar tugas
- Tambah tugas dengan prioritas (rendah/sedang/tinggi) dan tenggat waktu
- Tandai tugas selesai/belum selesai
- Undang kolaborator ke daftar lewat email untuk kerja bersama
- Bilah progres penyelesaian tugas per daftar
- Panel admin untuk melihat semua pengguna serta menambah/menghapus tugas mereka

## Menjalankan Secara Lokal

### 1. Backend

```bash
cd server
npm install
cp .env.example .env   # sesuaikan JWT_SECRET jika perlu
npm run dev
```

API akan berjalan di `http://localhost:4000`. Database SQLite (`data.sqlite`) dibuat otomatis, lengkap dengan akun admin default:

- Email: `admin@zara.app`
- Password: `admin123`

**Segera ganti password admin default ini setelah login pertama kali.**

### 2. Frontend

```bash
cd client
npm install
npm run dev
```

Buka `http://localhost:5173` di browser. Permintaan ke `/api` otomatis diteruskan (proxy) ke backend.

## Alur Peran

- **Pengguna** membuat daftar tugas, menjadi pemiliknya, dan dapat mengundang pengguna lain sebagai kolaborator lewat email.
- **Kolaborator** dapat menambah, mengubah, menandai selesai, dan menghapus tugas di daftar yang mereka ikuti.
- **Pemilik daftar** memantau progres lewat bilah persentase penyelesaian dan mengelola keanggotaan daftar.
- **Admin** memiliki panel khusus (`/admin`) untuk melihat seluruh pengguna beserta tugasnya, menambahkan tugas baru atas nama pengguna, dan menghapus tugas siapa pun.

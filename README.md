# JARA

Aplikasi web todo list untuk mengelola tugas pribadi maupun tim. Pengguna dapat membuat daftar tugas, menjadi pemiliknya secara otomatis, mengelompokkan tugas, menetapkan prioritas dan tenggat waktu, menandai tugas selesai, serta mengundang kolaborator. Admin dapat menambah dan menghapus akun pengguna, serta mengelola tugas siapa pun.

Ditulis full PHP (plain PHP, tanpa framework) dengan SQLite lewat PDO.

## Struktur Proyek

- `*.php` di root — halaman (dashboard, daftar tugas, panel admin, dll) dan action handler (create/update/delete)
- `includes/` — koneksi database (`db.php`), sesi & autentikasi (`auth.php`), helper (`functions.php`, `lists.php`), layout (`header.php`, `footer.php`)
- `assets/style.css` — styling
- `data/` — tempat file database SQLite dibuat otomatis saat aplikasi pertama dijalankan (diabaikan git)

## Fitur

- Registrasi & login dengan email/password (session PHP, password di-hash dengan `password_hash`)
- Buat daftar tugas baru — otomatis menjadi pemiliknya
- Hapus daftar beserta seluruh tugas dan keanggotaannya secara atomik (transaksi dibatalkan total bila ada langkah yang gagal)
- Tambah tugas dengan prioritas (rendah/sedang/tinggi) dan tenggat waktu
- Tandai tugas selesai/belum selesai
- Undang kolaborator ke daftar lewat email untuk kerja bersama
- Bilah progres penyelesaian tugas per daftar
- Panel admin: menambah/menghapus akun pengguna (atomik, termasuk membersihkan daftar & tugas miliknya), serta menambah/menghapus tugas pengguna mana pun

Seluruh input divalidasi di server dan seluruh query database memakai prepared statement (PDO) untuk mencegah SQL injection. Setiap form POST dilindungi CSRF token, dan setiap aksi memeriksa kepemilikan/keanggotaan sebelum mengizinkan perubahan.

## Menjalankan Secara Lokal

Butuh PHP 8+ dengan ekstensi `pdo_sqlite` (biasanya sudah aktif secara default).

```bash
php -S localhost:8000
```

Buka `http://localhost:8000` di browser. Database SQLite (`data/jara.sqlite`) dibuat otomatis saat pertama kali diakses, lengkap dengan akun admin default:

- Email: `admin@jara.app`
- Password: `admin123`

**Segera ganti password admin default ini setelah login pertama kali.**

Alternatif: taruh folder proyek ini di `htdocs` XAMPP/Laragon lalu akses lewat Apache.

## Alur Peran

- **Pengguna** membuat daftar tugas, menjadi pemiliknya, dan dapat mengundang pengguna lain sebagai kolaborator lewat email.
- **Kolaborator** dapat menambah, menandai selesai, dan menghapus tugas di daftar yang mereka ikuti.
- **Pemilik daftar** memantau progres lewat bilah persentase penyelesaian dan mengelola keanggotaan daftar.
- **Admin** memiliki panel khusus (`admin.php`) untuk menambah/menghapus akun pengguna, melihat tugas siapa pun, menambahkan tugas baru atas nama pengguna, dan menghapus tugas siapa pun.

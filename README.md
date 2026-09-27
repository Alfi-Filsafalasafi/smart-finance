# Smart Finance

Website pencatatan keuangan siswa berbasis **PHP native + Bootstrap 5 + MySQL**.
Siswa mencatat pemasukan/pengeluaran lengkap dengan bukti, guru memantau keuangan siswa
dan bisa memberi catatan per transaksi, admin mengelola seluruh akun & kategori.

## Fitur

- **Landing page** — statistik jumlah siswa, guru, dan transaksi (real-time dari database)
- **Autentikasi** — login satu pintu, registrasi mandiri (siswa wajib memilih guru pemantau)
- **Role: Siswa**
  - Dashboard ringkasan saldo, pemasukan, pengeluaran
  - CRUD transaksi + upload bukti (JPG/PNG/PDF, maks 2MB) — tabel dengan DataTables
  - Lihat catatan dari guru di tiap transaksi
  - Kelola kategori custom milik sendiri (selain kategori default sistem)
  - Edit profil & ganti password
- **Role: Guru**
  - Dashboard ringkasan siswa pantauan, transaksi minggu ini, siswa saldo negatif
  - Daftar siswa pantauan (DataTables) + detail transaksi per siswa
  - Memberi catatan/komentar di transaksi siswa
  - Edit profil & ganti password
- **Role: Admin**
  - Dashboard ringkasan seluruh sistem
  - CRUD akun siswa & guru (DataTables), termasuk atur guru pemantau siswa
  - CRUD kategori default sistem
- Alert sukses/gagal (dismissible) di setiap aksi tambah/edit/hapus

## Tech Stack

- PHP native (PDO MySQL)
- Bootstrap 5.3
- Font Awesome 6
- Google Fonts — Inter
- DataTables (jQuery) untuk tabel data

## Struktur Folder

```
smart-finance/
├── admin/              # Dashboard, manajemen akun & kategori (role admin)
├── auth/                # login.php, register.php, logout.php
├── assets/
│   ├── css/             # style.css (tema), app.css (layout dashboard)
│   └── uploads/bukti/   # file bukti transaksi ter-upload
├── config/
│   └── database.php     # koneksi PDO ke MySQL
├── guru/                # Dashboard, siswa pantauan & detail (role guru)
├── includes/            # header, footer, sidebar per role, auth.php (helper)
├── siswa/               # Dashboard, transaksi, kategori, profil (role siswa)
├── index.php            # Landing page
└── smart_finance.sql    # Skema database + data awal
```

## Instalasi

1. **Import database**
   Import `smart_finance.sql` ke MySQL (lewat Navicat, phpMyAdmin, atau CLI). File ini otomatis:
   - Membuat database `smart_finance`
   - Membuat seluruh tabel (`users`, `guru_siswa`, `kategori`, `transaksi`, `catatan_guru`)
   - Mengisi kategori default sistem
   - Mengisi akun admin & akun dummy (lihat bagian **Akun Default** di bawah)

2. **Atur koneksi database**
   Buka `config/database.php`, sesuaikan dengan environment lokal kamu:
   ```php
   $host = 'localhost';
   $dbname = 'smart_finance';
   $username = 'root';
   $password = '';
   ```

3. **Taruh project di web server**
   Pastikan project berada di root domain/virtual host (misal `smart-finance.test`), karena
   sebagian path CSS/asset di dalam layout dashboard (`includes/header.php`) menggunakan
   **absolute path** (`/assets/css/...`, `/auth/login.php`, dst). Kalau project ditaruh di
   subfolder (misal `localhost/smart-finance/`), path tersebut perlu disesuaikan atau pakai
   virtual host terpisah.

4. **Pastikan folder upload bisa ditulis**
   Folder `assets/uploads/bukti/` akan dibuat otomatis oleh `siswa/transaksi.php` saat pertama
   kali upload bukti, tapi pastikan permission folder `assets/uploads/` writable oleh web server.

5. **Buka `index.php`** di browser.

## Akun Default

| Role  | Email                   | Password      |
|-------|--------------------------|---------------|
| Admin | admin@smart.com          | smart101      |
| Guru  | budi.guru@smart.com      | password123   |
| Siswa | ani.siswa@smart.com      | password123   |
| Siswa | rizky.siswa@smart.com    | password123   |


## Catatan Teknis

- Semua password disimpan ter-hash dengan `password_hash()` (bcrypt).
- Kategori punya 2 jenis: **default sistem** (`siswa_id` NULL, diatur admin) dan **custom**
  (`siswa_id` terisi, milik siswa masing-masing).
- Kategori yang sudah dipakai di transaksi tidak bisa dihapus (dicegah di level aplikasi).
- Satu siswa hanya bisa punya 1 bukti file per transaksi (kolom `bukti_file` di tabel `transaksi`).
- Guru bisa memberi lebih dari 1 catatan per transaksi (tabel `catatan_guru`, one-to-many).

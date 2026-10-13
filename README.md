# BrandKicks - Aplikasi Manajemen Sepatu Branded (Mini Project 2)

Aplikasi web manajemen produk (Product Manager) berbasis PHP Native dan MySQL, dibangun untuk memenuhi tugas praktikum Pemrograman Web (Pertemuan 3).

---

## 1. Deskripsi Proyek

BrandKicks Vault adalah aplikasi katalog dan inventaris produk sepatu branded original (Nike, Adidas, New Balance, Converse, Puma, Vans, Asics, Reebok). Sistem dirancang dengan memperhatikan standar keamanan, validasi server-side yang ketat, pencegahan duplikasi data melalui pola PRG (Post-Redirect-Get), serta antarmuka pengguna yang responsif dan terstruktur.

---

## 2. Fitur dan Implementasi Teknis

### A. Operasi CRUD Lengkap
- Create (public/create.php):
  - Formulir penambahan data produk sepatu baru.
  - Mendukung pemilihan kategori dari relasi database, pengisian kode SKU, harga, stok, dan upload foto produk (JPG, PNG, WEBP).
  - Menerapkan pola PRG (Post-Redirect-Get) untuk mencegah pengiriman formulir ganda saat halaman di-refresh.
- Read (public/index.php):
  - Menampilkan katalog inventaris sepatu dalam layout kartu responsif (CSS Grid dan Flexbox).
  - Menampilkan foto produk asli, badge kategori, kode SKU, harga dalam format mata uang rupiah, dan status ketersediaan stok.
  - Dilengkapi fitur pencarian nama atau SKU serta filter berdasarkan kategori.
- Update (public/edit.php):
  - Formulir pembaruan data produk berdasarkan ID.
  - Mengambil data lama menggunakan PDO Prepared Statements dan mengisi kembali input form secara otomatis.
  - Mendukung pembaruan gambar produk dan preview gambar lama.
- Delete (public/delete.php):
  - Menangani aksi penghapusan produk secara aman.
  - Mewajibkan request dengan metode HTTP POST.
  - Dilindungi oleh verifikasi CSRF Token.

### B. Validasi Sisi Server (Server-Side Validation)
- Nama produk wajib diisi dan minimal memiliki panjang 3 karakter.
- Nama produk harus unik dan tidak boleh duplikat dengan produk lain.
- Kategori wajib dipilih dari kategori yang valid.
- Harga produk wajib berupa nilai numerik dan lebih besar dari 0.
- Stok produk berupa bilangan bulat dan tidak boleh bernilai negatif (minimal 0).
- Validasi file foto mencakup pembatasan ekstensi file (JPG, PNG, WEBP) dan batasan ukuran berkas (maksimal 3MB).

### C. Standar Keamanan
- Pencegahan SQL Injection: Seluruh interaksi basis data menggunakan PDO Prepared Statements dengan parameter binding.
- Pencegahan Cross-Site Scripting (XSS): Setiap variabel yang dicetak ke layar di-escape menggunakan fungsi htmlspecialchars dengan flag ENT_QUOTES dan encoding UTF-8.
- Proteksi Cross-Site Request Forgery (CSRF): Pembuatan token acak berbasis kriptografi melalui fungsi random_bytes() dan validasi dengan hash_equals().

---

## 3. Struktur Direktori

```text
mini-project_2/
├── config/
│   └── db.php                  # Koneksi database menggunakan PDO
├── database/
│   └── store_db.sql            # Skema tabel dan data awal produk sepatu
├── public/
│   ├── assets/
│   │   └── style.css           # Berkas styling CSS responsif
│   ├── uploads/                # Direktori penyimpanan foto produk
│   ├── index.php               # Halaman utama (Katalog & Pencarian)
│   ├── create.php              # Halaman formulir tambah produk
│   ├── edit.php                # Halaman formulir ubah data produk
│   └── delete.php              # Handler aksi penghapusan data
└── README.md                   # Dokumentasi panduan proyek
```

---

## 4. Panduan Menjalankan Aplikasi

### Persyaratan Sistem
- Web Server: Apache (XAMPP / Laragon) atau PHP Built-in Server
- PHP: Versi 8.0 ke atas dengan ekstensi PDO MySQL aktif
- Basis Data: MySQL atau MariaDB

### Langkah 1: Persiapan Basis Data
1. Buka XAMPP Control Panel dan aktifkan layanan Apache serta MySQL.
2. Akses phpMyAdmin melalui peramban web pada alamat: http://localhost/phpmyadmin.
3. Buat basis data bernama store_db atau impor berkas:
   database/store_db.sql

### Langkah 2: Konfigurasi Koneksi
Pastikan pengaturan kredensial pada berkas config/db.php sesuai dengan lingkungan lokal Anda:
- Host: 127.0.0.1
- Port: 3306
- Database: store_db
- User: root
- Password: (kosong secara default pada XAMPP)

### Langkah 3: Menjalankan Server Lokal
Buka terminal (PowerShell atau Command Prompt) pada direktori proyek ini, lalu jalankan perintah:

```powershell
php -S localhost:8000 -t public
```

Buka peramban web dan akses alamat:
http://localhost:8000

---

## 5. Matriks Pengujian Fungsional dan Keamanan

| No | Skenario Uji | Hasil yang Diharapkan | Status |
|---|---|---|---|
| 1 | Input data produk valid | Data tersimpan ke database, redirect ke index.php dengan notifikasi sukses | Terpenuhi |
| 2 | Input nama kurang dari 3 karakter | Form ditolak, muncul pesan kesalahan validasi | Terpenuhi |
| 3 | Input nama produk yang sudah terdaftar | Form ditolak, muncul peringatan nama produk sudah digunakan | Terpenuhi |
| 4 | Input harga <= 0 atau stok negatif | Form ditolak oleh validasi server-side | Terpenuhi |
| 5 | Refresh halaman setelah submit create | Tidak terjadi duplikasi data berkat implementasi PRG | Terpenuhi |
| 6 | Uji input karakter khusus (XSS payload) | Ditampilkan secara aman sebagai teks biasa tanpa eksekusi skrip | Terpenuhi |
| 7 | Hapus data tanpa CSRF token valid | Request ditolak dengan kode respon 403 Forbidden | Terpenuhi |
| 8 | Pencarian dan filter kategori | Data difilter secara akurat melalui query prepared statements | Terpenuhi |
| 9 | Tampilan pada layar smartphone | Tata letak kartu produk menyesuaikan layar tanpa horizontal scroll | Terpenuhi |

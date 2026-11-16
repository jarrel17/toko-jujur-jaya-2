# Toko Jujur Jaya 2 — Sistem Manajemen Stok & Keuangan

Aplikasi web untuk mengelola produk, penjualan, pembelian, hutang/piutang, retur,
dan laporan keuangan toko bangunan. Dibuat untuk **Tugas Project Cyber Security**
(Pengembangan & Pengamanan Aplikasi Web).

- **URL Aplikasi:** `https://ervinjarrel.my.id` 
- **Repository:** `https://github.com/jarrel17/toko-jujur-jaya-2`

## Fitur

| Kategori | Fitur |
|---|---|
| Autentikasi | Registrasi, login, logout, sesi dengan timeout, persetujuan akun oleh pemilik |
| Otorisasi | 3 role: **admin**, **gudang**, **pemilik** (+ `pending` untuk akun baru) |
| Pengelolaan data (CRUD) | Produk (tambah, lihat, ubah, hapus), user, penjualan, pembelian, retur, bayar hutang/piutang |
| Laporan | Laporan stok, penjualan, pembelian, keuangan — filter tanggal, **ekspor CSV**, cetak |
| Keamanan tambahan | Log aktivitas, rate limiting login, proteksi CSRF, security headers |

### Hak akses per role

| Fitur | Admin (kasir) | Gudang | Pemilik |
|---|---|---|---|
| Dashboard | ✅ | ✅ | ✅ |
| Produk | lihat, tambah, ubah, hapus | lihat, tambah, ubah | lihat |
| Penjualan | lihat, tambah | ❌ | lihat |
| Pembelian | lihat, tambah | lihat, tambah | lihat |
| Retur penjualan | lihat, tambah | ❌ | lihat |
| Retur pembelian | ❌ | lihat, tambah | lihat |
| Hutang / Piutang | ❌ | ❌ | lihat, bayar |
| Laporan stok | ❌ | ✅ | ✅ |
| Laporan penjualan / pembelian | ❌ | ❌ | ✅ |
| Laporan keuangan | ❌ | ❌ | ✅ |
| Kelola user | ❌ | ❌ | ✅ |
| Log aktivitas | ❌ | ❌ | ✅ |
| Logout | ✅ | ✅ | ✅ |

Aturan ada di satu tempat: `htdocs/config/akses.php` (`$HAK_AKSES`).

## Teknologi

- PHP 8.x (tanpa framework, tanpa dependency pihak ketiga / Composer)
- MySQL / MariaDB (ekstensi `mysqli` + `mysqlnd`)
- HTML + CSS murni
- Hosting: InfinityFree (dapat dipindah ke hosting PHP mana pun)

## Struktur

```
htdocs/
├── index.php, register.php, logout.php     # autentikasi
├── config/        keamanan.php, db.php, akses.php, koneksi.php
├── templates/     sidebar, header_page, footer_page
├── pages/         dashboard, produk, penjualan, pembelian, retur_*, hutang, piutang, laporan, user, log
└── assets/        css, js, img
database/skema.sql
docs/PANDUAN_SELF_TESTING.md
```

## Instalasi & menjalankan lokal

1. Siapkan PHP 8+ dan MySQL/MariaDB (mis. XAMPP).
2. Buat database dan impor struktur tabel toko (produk, penjualan, pembelian, dst.).
3. Salin `htdocs/config/config.local.php.example` menjadi `htdocs/config/config.local.php`, isi kredensial database Anda.
4. Jalankan: `php -S localhost:8000 -t htdocs` lalu buka `http://localhost:8000`.
5. Tabel tambahan (`log_aktivitas`, `percobaan_login`) dan penyesuaian tabel `users` dibuat **otomatis** saat login/registrasi pertama. Password lama (plaintext) otomatis diubah ke bcrypt.
6. Daftar akun lewat `register.php`, lalu ubah role akun pertama menjadi `pemilik` langsung di database:
   `UPDATE users SET role='pemilik' WHERE username='NAMA_ANDA';`
   Setelah itu akun lain dikelola dari menu **Kelola User**.

## Konfigurasi environment variable

Kredensial database **tidak ada di source code**. Sumbernya, urutan prioritas:

| Variabel | Keterangan |
|---|---|
| `DB_HOST` | host database |
| `DB_USER` | username database |
| `DB_PASS` | password database |
| `DB_NAME` | nama database |

Bila hosting tidak mendukung environment variable (mis. InfinityFree), pakai file
`htdocs/config/config.local.php` (masuk `.gitignore`, tidak ikut repository).

## Penerapan keamanan

| Kontrol | Implementasi | Lokasi |
|---|---|---|
| Hash password | bcrypt cost 12 (`password_hash` / `password_verify`), migrasi otomatis dari plaintext | `index.php`, `register.php`, `config/db.php` |
| Anti SQL Injection | Prepared statement di seluruh query yang memakai input | `config/db.php` + semua halaman |
| Anti XSS | Semua output di-escape (`e()`), CSP ketat **tanpa `unsafe-inline`** (semua CSS/JS di `assets/`), cookie HttpOnly | `config/keamanan.php`, `assets/` |
| Validasi input | Validasi tipe/rentang di server (angka, tanggal, whitelist metode & role, panjang string) | semua form |
| Sesi aman | Cookie `HttpOnly`, `Secure`, `SameSite=Lax`, strict mode, `session_regenerate_id` saat login, timeout 30 menit idle / 8 jam total, destroy saat logout | `config/keamanan.php`, `config/akses.php` |
| Kontrol akses | RBAC per halaman di sisi server (`cek_akses`) + menu/tombol disembunyikan | `config/akses.php` |
| CSRF | Token per sesi pada semua form POST, hapus lewat POST | `config/keamanan.php` |
| Rate limiting | 5 login gagal / (IP+username) dan 30 / IP dalam 15 menit; 5 registrasi / IP / jam | `config/db.php` |
| Security headers | CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS, COOP, CORP | `config/keamanan.php` |
| Role dibaca ulang dari database | Perubahan role atau penghapusan akun berlaku seketika di setiap request | `config/akses.php` (`cek_akses`) |
| Secret | Kredensial DB lewat env var / file lokal yang di-gitignore | `config/koneksi.php` |
| HTTPS | Redirect HTTP→HTTPS, folder `config/` & `templates/` ditutup dari akses web | `.htaccess` |
| Log aktivitas | Login/gagal/blokir, CRUD, perubahan role, akses ditolak, ekspor | `config/db.php`, halaman Log |
| Integritas data | Transaksi database + validasi stok sebelum simpan | halaman transaksi |

## Penggunaan AI agent

- **Alat:** Claude (Anthropic).
- **Bagian yang dibantu:** perancangan tampilan (UI), pembagian hak akses per role, penulisan ulang kode agar aman (prepared statement, hashing, sesi, CSRF, rate limiting, log), fitur registrasi, ekspor CSV, dan penyusunan dokumentasi ini.
- **Contoh prompt utama:**
  1. "perbaiki isi kode agar berikan batasan akses untuk admin, gudang, dan pemilik; rapikan tampilan semua halaman"
  2. "tolong perbaiki file zip agar bisa memenuhi kriteria tugas yang diberikan" (disertai PDF ketentuan tugas)
- **Verifikasi:** kode hasil AI saya baca dan jalankan sendiri, terutama login, hashing, dan query; hasil uji ada di bawah. `[ISI: jelaskan cara Anda meninjau kode]`
- Tidak ada API key/password nyata yang dimasukkan ke prompt. `[PASTIKAN benar — jika password DB pernah terkirim ke AI, ganti sekarang]`

## Hasil self-testing keamanan

Panduan langkah uji dan template tabel lengkap ada di
[`docs/PANDUAN_SELF_TESTING.md`](docs/PANDUAN_SELF_TESTING.md).

Temuan pada versi awal (dari peninjauan kode) dan perbaikannya:

| # | Temuan awal | Perbaikan |
|---|---|---|
| 1 | Password disimpan plaintext | bcrypt + migrasi otomatis |
| 2 | Query menyisipkan input langsung (SQL Injection, termasuk login) | prepared statement |
| 3 | Kredensial DB tertulis di source code | env var / `config.local.php` ter-gitignore |
| 4 | Tanpa CSRF; hapus produk lewat link GET | token CSRF; hapus via POST |
| 5 | Tanpa rate limiting login | pembatasan percobaan gagal |
| 6 | Sesi tanpa flag cookie/timeout, tidak regenerate ID | sesi diperkeras |
| 7 | Pembelian selalu tercatat user #1 | memakai user yang login |

Hasil pengujian **setelah** perbaikan (isi dengan bukti nyata dari pengujian Anda): `[ISI — lihat panduan]`

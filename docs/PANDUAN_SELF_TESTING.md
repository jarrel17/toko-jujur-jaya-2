# Panduan Self-Testing Keamanan

Ganti `DOMAIN` dengan domain aplikasi Anda. **Kerjakan sendiri dan simpan bukti nyata**
(tangkapan layar / keluaran perintah) untuk setiap skenario. Template tabel ada di bagian akhir.

> Tips nilai: kalau ingin menunjukkan "sebelum dan sesudah", jalankan skenario 1 dan 2
> pada versi lama (zip awal) di server lokal, simpan buktinya, lalu ulangi pada versi baru.

## 1. SQL Injection (form + parameter URL)

- **Login (browser):** username `' OR '1'='1' -- ` , password bebas → harus **gagal** ("Username atau Password salah").
- **Parameter URL:** buka `https://DOMAIN/pages/produk/edit.php?id=1'` dan `...?id=1 OR 1=1` → harus muncul "Data Tidak Ditemukan", bukan error SQL / data bocor.
- **Laporan:** `https://DOMAIN/pages/laporan/penjualan.php?tgl1=2026-01-01'&tgl2=x` → pesan "Format tanggal tidak valid".
- **Opsional (alat):** OWASP ZAP *Automated Scan* atau `sqlmap` pada URL di atas (hanya pada aplikasi milik sendiri).

## 2. XSS

- Tambah produk dengan nama `<script>alert(1)</script>` dan `"><img src=x onerror=alert(2)>`.
- Buka Data Produk, Laporan Stok, dan form Penjualan/Pembelian → teks tampil apa adanya, **tidak ada popup**.
- Cek CSP: DevTools → Network → pilih dokumen → header `Content-Security-Policy` ada.

## 3. Kontrol akses & IDOR

Siapkan 3 akun (admin, gudang, pemilik). Login sebagai **gudang**, lalu buka langsung:

| URL | Hasil yang diharapkan |
|---|---|
| `/pages/penjualan/` | 403 Akses Ditolak |
| `/pages/laporan/keuangan.php` | 403 |
| `/pages/user/` | 403 |
| `/pages/produk/hapus.php` | 403 |

Login sebagai **pemilik**: buka `/pages/produk/tambah.php` → 403. Tanpa login: buka `/pages/produk/` → redirect ke login.
IDOR: `/pages/penjualan/detail.php?id=999999` → 404; ubah `id` pada `edit.php`/`bayar.php` sebagai role tidak berhak → 403.
Lihat halaman **Log Aktivitas** — percobaan di atas tercatat sebagai `AKSES_DITOLAK`.

## 4. Autentikasi & sesi

- **Password lemah ditolak:** registrasi dengan `12345678`, `password`, `abcdefgh` → ditolak.
- **Hash password:** di phpMyAdmin, kolom `password` tabel `users` berawalan `$2y$` (screenshot).
- **Rate limiting:** salah login 6x berturut-turut dengan username sama → muncul "Terlalu banyak percobaan gagal" (HTTP 429).
- **Cookie sesi:** DevTools → Application → Cookies → `TJJSESSID` memiliki `HttpOnly`, `Secure`, `SameSite=Lax`.
- **Logout:** klik Logout, tekan tombol *Back* → diarahkan ke login; cookie sesi lama tidak berlaku.
- **Timeout:** diamkan 30 menit lalu klik menu → kembali ke login dengan pesan "Sesi Anda berakhir".
- **Session fixation:** catat nilai `TJJSESSID` sebelum login; setelah login nilainya **berubah**.

## 5. CSRF

- Buat file HTML lokal berisi form `POST` ke `https://DOMAIN/pages/produk/hapus.php` dengan field `id=1` (tanpa `csrf_token`), buka saat sedang login admin → hasil **HTTP 419 "Permintaan tidak valid"**, data tidak terhapus.
- Buka `https://DOMAIN/pages/produk/hapus.php?id=1` (GET) → tidak menghapus apa pun.

## 6. HTTPS & security headers

- `http://DOMAIN` otomatis berpindah ke `https://`.
- Buka <https://securityheaders.com> dan <https://www.ssllabs.com/ssltest/> → screenshot skor.
- Atau: `curl -sI https://DOMAIN | grep -i -E "strict-transport|content-security|x-frame|x-content-type|referrer|permissions"`.
- Ikon gembok → detail sertifikat (screenshot untuk laporan).
- `https://DOMAIN/config/koneksi.php` dan `/templates/sidebar.php` → **403** (folder tertutup).

## 7. Secret & dependency

- Cari secret di riwayat Git: `git log -p | grep -i -E "password|passwd|secret|api[_-]?key"` — tidak boleh ada kredensial asli. Alat: `gitleaks detect` atau `trufflehog git file://.`
- Pastikan `config.local.php` tidak terlacak: `git ls-files | grep config.local` → kosong.
- Dependency: aplikasi **tidak memakai library pihak ketiga** (tanpa Composer/npm), jadi `npm audit`/`pip-audit` tidak berlaku — jelaskan hal ini di laporan. Sebutkan juga versi PHP yang dipakai hosting.
- **Jika password database pernah ter-commit atau terkirim ke AI, GANTI password tersebut** lalu perbarui `config.local.php`.

## 8. Fungsional yang berkaitan dengan keamanan

- Penjualan melebihi stok → ditolak, stok tidak berubah.
- Bayar hutang/piutang melebihi sisa → ditolak.
- Retur melebihi jumlah transaksi → ditolak.
- Ekspor CSV: nama produk berawalan `=1+1` tampil sebagai teks (`'=1+1`), bukan rumus.

## 9. Pengujian tambahan (perubahan terbaru)

- **CSP tanpa `unsafe-inline`:** DevTools → Network → `index.php` → Response Headers → `content-security-policy` tidak memuat `unsafe-inline`. Buka tiap halaman dengan tab **Console** terbuka: tidak boleh ada pesan "Refused to execute inline script/style". Cek juga tombol Lihat password, Tambah Produk, Cetak, dan dialog konfirmasi hapus masih berfungsi.
- **Admin (kasir) dibatasi:** login sebagai admin lalu buka `/pages/hutang/`, `/pages/piutang/`, `/pages/laporan/`, `/pages/user/`, `/pages/log/`, `/pages/retur_pembelian/` → semuanya **403**. Dashboard, Produk, Penjualan, Pembelian, Retur Penjualan tetap terbuka. (Pada versi lama, halaman-halaman itu terbuka untuk admin: simpan sebagai bukti "sebelum".)
- **Role dibaca dari database:** login sebagai gudang di satu browser; di browser lain, pemilik mengubah role akun itu menjadi `pending`; muat ulang halaman di browser pertama → langsung berubah menjadi "Menunggu Persetujuan" tanpa login ulang. (Versi lama: hak lama bertahan sampai login ulang.)
- **Akun dihapus:** hapus akun yang sedang login di browser lain → request berikutnya diarahkan ke login.

---

## Template tabel hasil (salin ke laporan PDF)

| No | Skenario uji | Cara uji | Hasil (Lolos/Gagal) | Bukti | Perbaikan |
|---|---|---|---|---|---|
| 1 | SQL Injection login | username `' OR '1'='1' -- ` | `[ISI]` | `[screenshot]` | Prepared statement |
| 2 | SQL Injection parameter URL | `edit.php?id=1 OR 1=1` | `[ISI]` | `[screenshot]` | Validasi int + prepared statement |
| 3 | XSS stored | nama produk `<script>…` | `[ISI]` | `[screenshot]` | `e()` + CSP |
| 4 | Akses role (gudang → penjualan) | buka URL langsung | `[ISI]` | `[screenshot]` | `cek_akses()` |
| 5 | IDOR / ID tidak valid | `detail.php?id=999999` | `[ISI]` | `[screenshot]` | 404 + validasi |
| 6 | Password lemah | registrasi `12345678` | `[ISI]` | `[screenshot]` | Aturan password |
| 7 | Rate limiting login | 6x salah | `[ISI]` | `[screenshot]` | `login_terkunci()` |
| 8 | CSRF | form tanpa token | `[ISI]` | `[screenshot]` | Token CSRF |
| 9 | Security headers / HTTPS | securityheaders.com, SSL Labs | `[ISI]` | `[screenshot]` | Header di `keamanan.php`, `.htaccess` |
| 10 | Secret di repository | `gitleaks` / `git log -p` | `[ISI]` | `[keluaran]` | env var / `.gitignore` |
| 11 | Admin mengakses halaman milik pemilik/gudang | buka URL langsung sebagai admin | `[ISI]` | `[screenshot]` | `$HAK_AKSES` dipersempit |
| 12 | Role berubah saat sesi aktif | ubah role dari akun lain | `[ISI]` | `[screenshot]` | role dibaca ulang dari DB |
| 13 | CSP tanpa inline | cek header + Console | `[ISI]` | `[screenshot]` | CSS/JS dipindah ke `assets/` |

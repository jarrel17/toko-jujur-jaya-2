-- =====================================================================
-- Skema tambahan untuk Toko Jujur Jaya 2
-- Tabel-tabel ini DIBUAT OTOMATIS oleh aplikasi saat pertama kali dipakai.
-- File ini disediakan sebagai dokumentasi / bila ingin dijalankan manual.
-- =====================================================================

-- Log aktivitas (audit trail)
CREATE TABLE IF NOT EXISTS log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    waktu DATETIME NOT NULL,
    id_user INT NULL,
    username VARCHAR(50) NOT NULL,
    aksi VARCHAR(40) NOT NULL,
    detail VARCHAR(255) NOT NULL DEFAULT '',
    ip VARCHAR(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pembatasan percobaan login / registrasi (rate limiting)
CREATE TABLE IF NOT EXISTS percobaan_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aksi VARCHAR(20) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(50) NOT NULL DEFAULT '',
    waktu DATETIME NOT NULL,
    KEY idx_aksi_ip_waktu (aksi, ip, waktu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Penyesuaian tabel users (juga dijalankan otomatis saat login/registrasi):
--  * kolom password harus >= 60 karakter agar muat hash bcrypt
--  * kolom role berupa VARCHAR agar bisa berisi 'pending'
ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;
ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'pending';

-- Password lama (plaintext) otomatis diubah menjadi hash bcrypt oleh aplikasi
-- pada login pertama setelah update ini.

-- Nama pembeli pada penjualan kredit (juga dibuat otomatis oleh aplikasi)
ALTER TABLE piutang ADD COLUMN nama_pembeli VARCHAR(100) NULL;

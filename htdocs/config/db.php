<?php
/* =====================================================================
   HELPER DATABASE
   - prepared statement (anti SQL Injection)
   - transaksi
   - log aktivitas
   - pembatasan percobaan login (rate limiting)
   - migrasi skema & password lama (plaintext -> bcrypt)
===================================================================== */

/* ---------- prepared statement ---------- */

function db_run($sql, $types = '', $params = [])
{
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log('DB prepare gagal: ' . mysqli_error($conn));
        return false;
    }

    if ($types !== '' && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log('DB execute gagal: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

/* semua baris (array asosiatif) */
function db_all($sql, $types = '', $params = [])
{
    $stmt = db_run($sql, $types, $params);

    if (!$stmt) {
        return [];
    }

    $res  = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

    mysqli_stmt_close($stmt);

    return $rows;
}

/* satu baris atau null */
function db_one($sql, $types = '', $params = [])
{
    $rows = db_all($sql, $types, $params);

    return $rows[0] ?? null;
}

/* INSERT / UPDATE / DELETE -> true bila berhasil */
function db_exec($sql, $types = '', $params = [])
{
    $stmt = db_run($sql, $types, $params);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_close($stmt);

    return true;
}

function db_last_id()
{
    global $conn;
    return mysqli_insert_id($conn);
}

function db_begin()
{
    global $conn;
    mysqli_begin_transaction($conn);
}

function db_commit()
{
    global $conn;
    mysqli_commit($conn);
}

function db_rollback()
{
    global $conn;
    mysqli_rollback($conn);
}

/* ---------- tabel tambahan (dibuat otomatis bila belum ada) ---------- */

function pastikan_tabel_tambahan()
{
    global $conn;

    if (!empty($_SESSION['tabel_tambahan_ok'])) {
        return;
    }

    mysqli_query($conn,
        "CREATE TABLE IF NOT EXISTS log_aktivitas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            waktu DATETIME NOT NULL,
            id_user INT NULL,
            username VARCHAR(50) NOT NULL,
            aksi VARCHAR(40) NOT NULL,
            detail VARCHAR(255) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    mysqli_query($conn,
        "CREATE TABLE IF NOT EXISTS percobaan_login (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aksi VARCHAR(20) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            username VARCHAR(50) NOT NULL DEFAULT '',
            waktu DATETIME NOT NULL,
            KEY idx_aksi_ip_waktu (aksi, ip, waktu)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $_SESSION['tabel_tambahan_ok'] = true;
}

/* ---------- log aktivitas ---------- */

function catat_log($aksi, $detail = '', $username = null)
{
    pastikan_tabel_tambahan();

    $id_user = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : null;
    $user    = $username ?? ($_SESSION['username'] ?? '-');

    db_exec(
        "INSERT INTO log_aktivitas (waktu, id_user, username, aksi, detail, ip)
         VALUES (NOW(), ?, ?, ?, ?, ?)",
        'issss',
        [
            $id_user,
            substr((string)$user, 0, 50),
            substr((string)$aksi, 0, 40),
            substr((string)$detail, 0, 255),
            klien_ip(),
        ]
    );
}

/* ---------- rate limiting ---------- */

function rl_hitung($aksi, $username, $menit)
{
    pastikan_tabel_tambahan();

    $sql = "SELECT COUNT(*) AS c FROM percobaan_login
            WHERE aksi = ? AND ip = ?
            AND waktu > DATE_SUB(NOW(), INTERVAL " . (int)$menit . " MINUTE)";

    $types  = 'ss';
    $params = [$aksi, klien_ip()];

    if ($username !== null) {
        $sql    .= " AND username = ?";
        $types  .= 's';
        $params[] = $username;
    }

    $r = db_one($sql, $types, $params);

    return $r ? (int)$r['c'] : 0;
}

function rl_catat($aksi, $username = '')
{
    pastikan_tabel_tambahan();

    db_exec(
        "INSERT INTO percobaan_login (aksi, ip, username, waktu) VALUES (?, ?, ?, NOW())",
        'sss',
        [$aksi, klien_ip(), substr((string)$username, 0, 50)]
    );
}

function rl_bersihkan($aksi, $username)
{
    db_exec(
        "DELETE FROM percobaan_login WHERE aksi = ? AND ip = ? AND username = ?",
        'sss',
        [$aksi, klien_ip(), substr((string)$username, 0, 50)]
    );

    /* buang catatan lama (> 1 hari) */
    db_exec("DELETE FROM percobaan_login WHERE waktu < DATE_SUB(NOW(), INTERVAL 1 DAY)");
}

/* maks 5 gagal per (IP + username) atau 30 gagal per IP dalam 15 menit */
function login_terkunci($username)
{
    return rl_hitung('login', substr($username, 0, 50), 15) >= 5
        || rl_hitung('login', null, 15) >= 30;
}

/* ---------- migrasi skema users & password lama ---------- */

function pastikan_skema_users()
{
    global $conn;

    if (!empty($_SESSION['skema_users_ok'])) {
        return;
    }

    /* 1. kolom password harus cukup panjang untuk hash bcrypt (60 karakter) */
    $kolom_pw = function () use ($conn) {
        $r = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'password'");
        return $r ? mysqli_fetch_assoc($r) : null;
    };

    $c = $kolom_pw();

    if ($c && preg_match('/^(var)?char\((\d+)\)/i', $c['Type'], $m) && (int)$m[2] < 255) {
        mysqli_query($conn, "ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL");
        $c = $kolom_pw();
    }

    $pw_ok = true;

    if ($c && preg_match('/^(var)?char\((\d+)\)/i', $c['Type'], $m) && (int)$m[2] < 60) {
        $pw_ok = false;
    }

    /* 2. kolom role: ubah ENUM menjadi VARCHAR agar bisa memuat role 'pending' */
    $r = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    $c_role = $r ? mysqli_fetch_assoc($r) : null;

    if ($c_role && stripos($c_role['Type'], 'enum(') === 0) {
        mysqli_query($conn, "ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    /* 3. ubah password plaintext lama menjadi hash bcrypt (sekali jalan) */
    if ($pw_ok) {

        $lama = db_all(
            "SELECT id_user, password FROM users WHERE password NOT LIKE ?",
            's',
            ['$2y$%']
        );

        foreach ($lama as $u) {
            db_exec(
                "UPDATE users SET password = ? WHERE id_user = ?",
                'si',
                [password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]), (int)$u['id_user']]
            );
        }

        $_SESSION['skema_users_ok'] = true;
    }
}

/* ---------- kolom nama pembeli pada piutang (dibuat otomatis bila belum ada) ---------- */

function pastikan_kolom_pembeli()
{
    global $conn;

    if (!empty($_SESSION['kolom_pembeli_ok'])) {
        return;
    }

    $cek = function () use ($conn) {
        $r = mysqli_query($conn, "SHOW COLUMNS FROM piutang LIKE 'nama_pembeli'");
        return $r && mysqli_num_rows($r) > 0;
    };

    if (!$cek()) {
        mysqli_query($conn, "ALTER TABLE piutang ADD COLUMN nama_pembeli VARCHAR(100) NULL");
    }

    if ($cek()) {
        $_SESSION['kolom_pembeli_ok'] = true;
    }
}

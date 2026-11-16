<?php

require_once "config/keamanan.php";
require_once "config/koneksi.php";
require_once "config/db.php";

/* sudah login -> langsung ke dashboard */
if (!empty($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: pages/dashboard/");
    exit;
}

$error = "";
$info  = "";

if (isset($_GET['expired']))    { $info = "Sesi Anda berakhir. Silakan login kembali."; }
if (isset($_GET['logout']))     { $info = "Anda telah logout."; }
if (isset($_GET['registered'])) { $info = "Registrasi berhasil. Akun menunggu persetujuan pemilik."; }

/* hash palsu untuk menyamakan waktu respons bila username tidak ada */
const HASH_PALSU = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (isset($_POST['login'])) {

    csrf_wajib();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    pastikan_tabel_tambahan();
    pastikan_skema_users();

    if (login_terkunci($username)) {

        http_response_code(429);
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.";
        catat_log('LOGIN_DIBLOKIR', 'username=' . $username, substr($username, 0, 50));

    } else {

        $data = null;

        if ($username !== '' && panjang($username) <= 50 && strlen($password) <= 200) {
            $data = db_one("SELECT * FROM users WHERE username = ?", 's', [$username]);
        }

        $hash = $data['password'] ?? HASH_PALSU;
        $ok   = false;

        if (strpos($hash, '$2y$') === 0) {
            $ok = password_verify($password, $hash);
        } elseif ($data) {
            /* cadangan: password lama yang belum sempat dimigrasi */
            $ok = hash_equals($hash, $password);
        } else {
            password_verify($password, HASH_PALSU);
        }

        if ($ok && $data) {

            rl_bersihkan('login', substr($username, 0, 50));

            /* hash ulang bila perlu */
            if (strpos($hash, '$2y$') !== 0 || password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
                db_exec(
                    "UPDATE users SET password = ? WHERE id_user = ?",
                    'si',
                    [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), (int)$data['id_user']]
                );
            }

            /* cegah session fixation */
            session_regenerate_id(true);

            $_SESSION['login']          = true;
            $_SESSION['id_user']        = $data['id_user'];
            $_SESSION['username']       = $data['username'];
            $_SESSION['role']           = $data['role'];
            $_SESSION['waktu_login']    = time();
            $_SESSION['aktif_terakhir'] = time();

            catat_log('LOGIN', 'berhasil');

            header("Location: pages/dashboard/");
            exit;

        } else {

            rl_catat('login', substr($username, 0, 50));
            catat_log('LOGIN_GAGAL', 'username=' . $username, substr($username, 0, 50));

            $error = "Username atau Password salah";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Jujur Jaya 2</title>

    <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">

    <link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . '/assets/css/login.css'); ?>">

</head>

<body>

<div class="login-container">

    <div class="login-header">

        <div class="logo"><img src="assets/img/logo.svg" alt="Logo"></div>

        <h1>TOKO JUJUR JAYA 2</h1>

        <p>Sistem Manajemen Stok &amp; Keuangan</p>

    </div>

    <?php if ($error !== "") { ?>

        <div class="error">

            <?= e($error); ?>

        </div>

    <?php } ?>

    <?php if ($info !== "") { ?>

        <div class="info">

            <?= e($info); ?>

        </div>

    <?php } ?>

    <form method="POST" autocomplete="off">

        <?= csrf_field(); ?>

        <div class="input-group">

            <label>Username</label>

            <input type="text"
                   name="username"
                   placeholder="Masukkan username"
                   required>

        </div>

        <div class="input-group">

            <label>Password</label>

            <div class="input-wrap">

                <input type="password"
                       name="password"
                       id="password"
                       placeholder="Masukkan password"
                       required>

                <button type="button"
                        class="toggle-pass">Lihat</button>

            </div>

        </div>

        <button type="submit"
                name="login"
                class="login-btn">

            Masuk

        </button>

    </form>

    <div class="link-daftar">
        Belum punya akun? <a href="register.php">Daftar</a>
    </div>

    <div class="footer-text">

        © <?= date('Y'); ?> Sistem Manajemen Stok

    </div>

</div>

<script src="assets/js/login.js?v=<?= filemtime(__DIR__ . '/assets/js/login.js'); ?>"></script>

</body>
</html>

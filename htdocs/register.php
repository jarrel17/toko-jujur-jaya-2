<?php

require_once "config/keamanan.php";
require_once "config/koneksi.php";
require_once "config/db.php";

if (!empty($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: pages/dashboard/");
    exit;
}

$error = "";

/* password yang terlalu umum */
const PASSWORD_LEMAH = [
    'password', 'password1', 'passw0rd', '12345678', '123456789', '1234567890',
    'qwerty123', 'qwertyuiop', 'abcd1234', 'admin123', 'admin1234', 'iloveyou1',
    'welcome1', 'letmein123',
];

if (isset($_POST['daftar'])) {

    csrf_wajib();

    $username  = trim((string)($_POST['username'] ?? ''));
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    pastikan_tabel_tambahan();
    pastikan_skema_users();

    if (rl_hitung('register', null, 60) >= 5) {

        http_response_code(429);
        $error = "Terlalu banyak registrasi dari alamat ini. Coba lagi nanti.";

    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {

        $error = "Username 3-30 karakter, hanya huruf, angka, titik, dan garis bawah.";

    } elseif (strlen($password) < 8 || strlen($password) > 72) {

        $error = "Password harus 8-72 karakter.";

    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {

        $error = "Password harus mengandung huruf dan angka.";

    } elseif (in_array(strtolower($password), PASSWORD_LEMAH, true)
              || strtolower($password) === strtolower($username)) {

        $error = "Password terlalu mudah ditebak. Gunakan kombinasi yang lebih unik.";

    } elseif ($password !== $password2) {

        $error = "Konfirmasi password tidak sama.";

    } elseif (db_one("SELECT id_user FROM users WHERE username = ?", 's', [$username])) {

        $error = "Username sudah dipakai.";

    } else {

        $ok = db_exec(
            "INSERT INTO users (username, password, role) VALUES (?, ?, 'pending')",
            'ss',
            [$username, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])]
        );

        rl_catat('register', $username);

        if ($ok) {
            catat_log('REGISTRASI', 'akun baru, menunggu persetujuan', $username);
            header("Location: index.php?registered=1");
            exit;
        }

        $error = "Registrasi gagal. Silakan coba lagi atau hubungi pemilik toko.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Toko Jujur Jaya 2</title>

    <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">

    <link rel="stylesheet" href="assets/css/register.css?v=<?= filemtime(__DIR__ . '/assets/css/register.css'); ?>">

</head>

<body>

<div class="login-container">

    <div class="login-header">

        <div class="logo"><img src="assets/img/logo.svg" alt="Logo"></div>

        <h1>REGISTRASI AKUN</h1>

        <p>Toko Jujur Jaya 2 &middot; Sistem Manajemen Stok</p>

    </div>

    <?php if ($error !== "") { ?>
        <div class="error"><?= e($error); ?></div>
    <?php } ?>

    <form method="POST" autocomplete="off">

        <?= csrf_field(); ?>

        <div class="input-group">
            <label>Username</label>
            <input type="text"
                   name="username"
                   placeholder="3-30 karakter: huruf, angka, titik, _"
                   value="<?= e($_POST['username'] ?? ''); ?>"
                   maxlength="30"
                   required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password"
                   name="password"
                   placeholder="Minimal 8 karakter, ada huruf dan angka"
                   maxlength="72"
                   required>
        </div>

        <div class="input-group">
            <label>Ulangi Password</label>
            <input type="password"
                   name="password2"
                   placeholder="Ketik ulang password"
                   maxlength="72"
                   required>
        </div>

        <button type="submit" name="daftar" class="login-btn">Daftar</button>

    </form>

    <div class="link-daftar">
        Sudah punya akun? <a href="index.php">Login</a>
    </div>

    <div class="footer-text">
        Akun baru perlu disetujui pemilik sebelum dapat dipakai.
    </div>

</div>

</body>
</html>

<?php
/* Kerangka atas untuk semua halaman dalam (selain dashboard).
   Set $judul_halaman sebelum meng-include file ini. */

$judul_halaman = $judul_halaman ?? 'Halaman';
$hp_username   = e($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($judul_halaman); ?> - Toko Jujur Jaya 2</title>

    <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">

    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="../../assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css'); ?>">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">

    <div class="navbar">

        <div class="navbar-left">
            <img src="../../assets/img/logo.svg" alt="Logo Toko Jujur Jaya 2" class="nav-logo">
            <h2>Sistem Manajemen Stok &amp; Keuangan</h2>
        </div>

        <div class="navbar-right">

            <div class="date-box">
                <?= date('d M Y'); ?>
            </div>

            <div class="profile-box">

                <div class="avatar">
                    <?= e(strtoupper(substr($_SESSION['username'] ?? 'User', 0, 2))); ?>
                </div>

                <div class="profile-info">
                    <p class="username"><?= $hp_username; ?></p>
                </div>

            </div>

            <a href="../../logout.php" class="logout-btn">Logout</a>

        </div>

    </div>

    <div class="content">

<?= flash_tampil(); ?>

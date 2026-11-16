<?php
include "../../config/akses.php";

cek_akses('dashboard');

include "../../config/koneksi.php";
include "../../templates/header.php";
include "../../templates/sidebar.php";

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$role     = htmlspecialchars($_SESSION['role'] ?? 'Staff');
?>

<link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../../assets/css/dashboard.css'); ?>">

<div class="main">

   <div class="navbar">

    <div class="navbar-left">

        <img src="../../assets/img/logo.svg" alt="Logo Toko Jujur Jaya 2" class="nav-logo">

        <h2>
            Sistem Manajemen Stok & Keuangan
        </h2>

    </div>

    <div class="navbar-right">

        <!-- WAKTU -->

        <div class="date-box">

            <?= date('d M Y'); ?>

        </div>

        <!-- PROFILE -->

        <div class="profile-box">

            <div class="avatar">

                <?= strtoupper(substr($username, 0, 2)); ?>

            </div>

            <div class="profile-info">

                <p class="username">

                    <?= $username; ?>

                </p>

            </div>

        </div>

        <!-- LOGOUT -->

        <a href="../../logout.php"
           class="logout-btn">

            Logout

        </a>

    </div>

</div>
    <!-- CARD MENU -->
    <div class="card-grid">

        <?php if (bisa('produk.lihat')) { ?>
        <a href="../produk/" class="menu-card fade-in">
            <h3>📦 Produk</h3>
            <p>
                Kelola data produk dan stok barang.
            </p>
        </a>
        <?php } ?>

        <?php if (bisa('penjualan.lihat')) { ?>
        <a href="../penjualan/" class="menu-card fade-in">
            <h3>💰 Penjualan</h3>
            <p>
                Transaksi penjualan produk toko.
            </p>
        </a>
        <?php } ?>

        <?php if (bisa('pembelian.lihat')) { ?>
        <a href="../pembelian/" class="menu-card fade-in">
            <h3>🛒 Pembelian</h3>
            <p>
                Restock dan pembelian barang.
            </p>
        </a>
        <?php } ?>

        <?php if (bisa('hutang.lihat')) { ?>
        <a href="../hutang/" class="menu-card fade-in">
            <h3>💳 Hutang</h3>
            <p>
                Kelola hutang pembelian kredit.
            </p>
        </a>
        <?php } ?>

        <?php if (bisa('piutang.lihat')) { ?>
        <a href="../piutang/" class="menu-card fade-in">
            <h3>💵 Piutang</h3>
            <p>
                Kelola piutang penjualan kredit.
            </p>
        </a>
        <?php } ?>

        <?php if (bisa('laporan.menu')) { ?>
        <a href="../laporan/" class="menu-card fade-in">
            <h3>📈 Laporan</h3>
            <p>
                Lihat laporan stok dan keuangan.
            </p>
        </a>
        <?php } ?>

    </div>

</div>

<script src="../../assets/js/dashboard.js"></script>

<?php
include "../../templates/footer.php";
?>
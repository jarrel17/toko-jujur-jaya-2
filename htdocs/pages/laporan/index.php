<?php
include "../../config/akses.php";

cek_akses('laporan.menu');

$judul_halaman = "Laporan";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>📈 Laporan</h1>
        <p>Pilih laporan yang ingin ditampilkan.</p>
    </div>

    <div class="page-actions">
        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>
    </div>

</div>

<div class="card-grid">

    <?php if (bisa('laporan.stok')) { ?>
    <a href="stok.php" class="menu-card">
        <h3>📋 Laporan Stok</h3>
        <p>Stok barang dan total modal persediaan.</p>
    </a>
    <?php } ?>

    <?php if (bisa('laporan.penjualan')) { ?>
    <a href="penjualan.php" class="menu-card">
        <h3>📈 Laporan Penjualan</h3>
        <p>Rekap pendapatan penjualan per periode.</p>
    </a>
    <?php } ?>

    <?php if (bisa('laporan.pembelian')) { ?>
    <a href="pembelian.php" class="menu-card">
        <h3>📉 Laporan Pembelian</h3>
        <p>Rekap pengeluaran pembelian per periode.</p>
    </a>
    <?php } ?>

    <?php if (bisa('laporan.keuangan')) { ?>
    <a href="keuangan.php" class="menu-card">
        <h3>🏦 Laporan Keuangan</h3>
        <p>Uang masuk, uang keluar, dan saldo akhir.</p>
    </a>
    <?php } ?>

</div>

<?php include "../../templates/footer_page.php"; ?>

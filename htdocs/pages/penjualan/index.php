<?php
include "../../config/akses.php";

cek_akses('penjualan.lihat');

$judul_halaman = "Data Penjualan";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>💰 Data Penjualan</h1>
        <p>Riwayat transaksi penjualan toko.</p>
    </div>

    <div class="page-actions">

        <a class="btn btn-secondary" href="../dashboard/">← Kembali</a>

        <?php if (bisa('penjualan.tambah')) { ?>
            <a class="btn btn-success" href="tambah.php">+ Tambah Penjualan</a>
        <?php } ?>

    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

    <table class="data">

        <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Metode</th>
            <th>Aksi</th>
        </tr>
        </thead>

        <tbody>

        <?php
        $no = 1;

        $data = db_all("SELECT * FROM penjualan ORDER BY tanggal DESC, id_penjualan DESC");

        if (count($data) == 0) {
        ?>
        <tr class="empty-row">
            <td colspan="5">Belum ada data penjualan.</td>
        </tr>
        <?php
        }

        foreach ($data as $d) {
        ?>

        <tr>
            <td><?= $no++; ?></td>
            <td class="nowrap"><?= e($d['tanggal']); ?></td>
            <td class="nowrap"><strong><?= rp($d['total']); ?></strong></td>
            <td>
                <span class="badge <?= $d['metode'] == 'Tunai' ? 'badge-success' : 'badge-warning'; ?>">
                    <?= e($d['metode']); ?>
                </span>
            </td>
            <td>
                <a class="btn btn-primary btn-sm" href="detail.php?id=<?= e($d['id_penjualan']); ?>">
                    Detail
                </a>
            </td>
        </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

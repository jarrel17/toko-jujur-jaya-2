<?php
include "../../config/akses.php";

cek_akses('retur_pembelian.lihat');

$judul_halaman = "Retur Pembelian";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>↪️ Data Retur Pembelian</h1>
        <p>Barang yang dikembalikan ke supplier.</p>
    </div>

    <div class="page-actions">

        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>

        <?php if (bisa('retur_pembelian.tambah')) { ?>
            <a href="tambah.php" class="btn btn-success">+ Tambah Retur</a>
        <?php } ?>

    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>No</th>
    <th>ID Pembelian</th>
    <th>Produk</th>
    <th class="text-center">Jumlah</th>
    <th>Tanggal</th>
</tr>
</thead>

<tbody>

<?php

$no = 1;

$data = db_all(
    "SELECT *
     FROM retur_pembelian
     JOIN produk
     ON retur_pembelian.id_produk =
        produk.id_produk
     ORDER BY tanggal DESC"
);

if (count($data) == 0) {
?>
<tr class="empty-row">
    <td colspan="5">Belum ada data retur pembelian.</td>
</tr>
<?php
}

foreach ($data as $d) {

?>

<tr>

<td><?= $no++; ?></td>

<td>#<?= e($d['id_pembelian']); ?></td>

<td><strong><?= e($d['nama_produk']); ?></strong></td>

<td class="text-center"><?= e($d['jumlah']); ?></td>

<td class="nowrap"><?= e($d['tanggal']); ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

<?php
include "../../config/akses.php";

cek_akses('pembelian.lihat');

$id = get_int('id');

$pembelian = $id
    ? db_one("SELECT * FROM pembelian WHERE id_pembelian = ?", 'i', [$id])
    : null;

if (!$pembelian) {
    halaman_error(404, 'Data Tidak Ditemukan', 'Transaksi pembelian yang Anda cari tidak ada.');
}

$data = db_all(
    "SELECT *

     FROM detail_pembelian

     JOIN produk
     ON detail_pembelian.id_produk =
        produk.id_produk

     WHERE id_pembelian = ?",
    'i',
    [$id]
);

$judul_halaman = "Detail Pembelian";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>🧾 Detail Pembelian</h1>
        <p>Rincian produk pada pembelian #<?= e($id); ?>.</p>
    </div>

    <div class="page-actions">
        <a class="btn btn-secondary" href="index.php">← Kembali</a>
    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>

    <th>Produk</th>
    <th class="text-center">Jumlah</th>
    <th class="text-right">Harga</th>
    <th class="text-right">Total</th>

</tr>
</thead>

<tbody>

<?php
$total_semua = 0;

foreach ($data as $d) {

    $total_semua += $d['jumlah'] * $d['harga'];
?>

<tr>

<td><strong><?= e($d['nama_produk']); ?></strong></td>

<td class="text-center"><?= e($d['jumlah']); ?></td>

<td class="text-right nowrap"><?= rp($d['harga']); ?></td>

<td class="text-right nowrap"><?= rp($d['jumlah'] * $d['harga']); ?></td>

</tr>

<?php } ?>

</tbody>

<tfoot>
<tr>
    <td colspan="3">Total Keseluruhan</td>
    <td class="text-right nowrap"><?= rp($total_semua); ?></td>
</tr>
</tfoot>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

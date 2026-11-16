<?php
include "../../config/akses.php";

cek_akses('penjualan.lihat');

/* =========================
   VALIDASI ID
========================= */
$id_penjualan = get_int('id');

$penjualan = $id_penjualan
    ? db_one("SELECT * FROM penjualan WHERE id_penjualan = ?", 'i', [$id_penjualan])
    : null;

if (!$penjualan) {
    halaman_error(404, 'Data Tidak Ditemukan', 'Transaksi penjualan yang Anda cari tidak ada.');
}

/* =========================
   QUERY DETAIL
========================= */
$data = db_all(
    "SELECT dp.*, p.nama_produk
     FROM detail_penjualan dp
     JOIN produk p ON dp.id_produk = p.id_produk
     WHERE dp.id_penjualan = ?",
    'i',
    [$id_penjualan]
);

$judul_halaman = "Detail Penjualan";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>🧾 Detail Penjualan</h1>
        <p>Rincian produk pada transaksi #<?= e($id_penjualan); ?>.</p>
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

    $subtotal = $d['jumlah'] * $d['harga'];
    $total_semua += $subtotal;
?>

<tr>
    <td><strong><?= e($d['nama_produk']); ?></strong></td>
    <td class="text-center"><?= e($d['jumlah']); ?></td>
    <td class="text-right nowrap"><?= rp($d['harga']); ?></td>
    <td class="text-right nowrap"><?= rp($subtotal); ?></td>
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

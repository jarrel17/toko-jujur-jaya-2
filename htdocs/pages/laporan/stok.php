<?php
include "../../config/akses.php";

cek_akses('laporan.stok');

$data = db_all("SELECT * FROM produk ORDER BY nama_produk");

$total_modal = 0;

foreach ($data as $d) {
    $total_modal += $d['harga_beli'] * $d['stok'];
}

/* ---------- EKSPOR CSV ---------- */

if (($_GET['export'] ?? '') === 'csv') {

    $baris = [];
    $no = 1;

    foreach ($data as $d) {
        $baris[] = [$no++, $d['nama_produk'], $d['harga_beli'], $d['stok'], $d['harga_beli'] * $d['stok']];
    }

    $baris[] = ['', 'TOTAL MODAL PERSEDIAAN', '', '', $total_modal];

    catat_log('EKSPOR_CSV', 'laporan stok');

    csv_kirim('laporan_stok.csv', ['No', 'Produk', 'Harga Beli', 'Stok', 'Total Modal'], $baris);
}

$judul_halaman = "Laporan Stok";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>📋 Laporan Stok Barang</h1>
        <p>Posisi stok dan nilai modal persediaan saat ini.</p>
    </div>

    <div class="page-actions no-print">
        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>
        <a href="?export=csv" class="btn btn-success">⬇️ Ekspor CSV</a>
        <button type="button" data-cetak class="btn btn-primary">🖨️ Cetak</button>
    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>No</th>
    <th>Produk</th>
    <th class="text-right">Harga Beli</th>
    <th class="text-center">Stok</th>
    <th class="text-right">Total Modal</th>
</tr>
</thead>

<tbody>

<?php

$no = 1;

foreach ($data as $d) {

    $modal = $d['harga_beli'] * $d['stok'];

?>

<tr>

<td><?= $no++; ?></td>

<td><strong><?= e($d['nama_produk']); ?></strong></td>

<td class="text-right nowrap"><?= rp($d['harga_beli']); ?></td>

<td class="text-center">
    <?php if ($d['stok'] <= 5) { ?>
        <span class="badge badge-danger"><?= e($d['stok']); ?> · Menipis</span>
    <?php } else { ?>
        <span class="badge badge-success"><?= e($d['stok']); ?></span>
    <?php } ?>
</td>

<td class="text-right nowrap"><?= rp($modal); ?></td>

</tr>

<?php } ?>

<?php if (count($data) == 0) { ?>
<tr class="empty-row">
    <td colspan="5">Belum ada data produk.</td>
</tr>
<?php } ?>

</tbody>

<tfoot>
<tr>

<td colspan="4">Total Modal Persediaan</td>

<td class="text-right nowrap"><?= rp($total_modal); ?></td>

</tr>
</tfoot>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

<?php
include "../../config/akses.php";

cek_akses('piutang.lihat');

pastikan_kolom_pembeli();

$judul_halaman = "Data Piutang";

include "../../templates/header_page.php";

$boleh_bayar = bisa('piutang.bayar');
?>

<div class="page-head">

    <div>
        <h1>💵 Data Piutang</h1>
        <p>Piutang dari penjualan secara kredit.</p>
    </div>

    <div class="page-actions">
        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>
    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>No</th>
    <th>ID Penjualan</th>
    <th>Nama Pembeli</th>
    <th>Total</th>
    <th>Sisa</th>
    <th>Status</th>
    <?php if ($boleh_bayar) { ?>
        <th>Aksi</th>
    <?php } ?>
</tr>
</thead>

<tbody>

<?php

$no = 1;

$data = db_all("SELECT * FROM piutang ORDER BY id_piutang DESC");

if (count($data) == 0) {
?>
<tr class="empty-row">
    <td colspan="<?= $boleh_bayar ? 7 : 6; ?>">Belum ada data piutang.</td>
</tr>
<?php
}

foreach ($data as $d) {

?>

<tr>

<td><?= $no++; ?></td>

<td>#<?= e($d['id_penjualan']); ?></td>

<td><strong><?= ($d['nama_pembeli'] ?? '') !== '' ? e($d['nama_pembeli']) : '-'; ?></strong></td>

<td class="nowrap"><?= rp($d['total']); ?></td>

<td class="nowrap"><strong><?= rp($d['sisa']); ?></strong></td>

<td>
    <span class="badge <?= $d['status'] == 'Lunas' ? 'badge-success' : 'badge-warning'; ?>">
        <?= e($d['status']); ?>
    </span>
</td>

<?php if ($boleh_bayar) { ?>
<td>

<a class="btn btn-success btn-sm" href="bayar.php?id=<?= e($d['id_piutang']); ?>">
    Bayar
</a>

</td>
<?php } ?>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

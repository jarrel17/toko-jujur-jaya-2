<?php
include "../../config/akses.php";

cek_akses('laporan.pembelian');

/* ---------- AMBIL DATA ---------- */

list($tgl1, $tgl2, $error) = rentang_tanggal();

if ($error === "" && $tgl1 !== null) {

    $data = db_all(
        "SELECT * FROM pembelian
         WHERE tanggal >= ? AND tanggal < DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY tanggal",
        'ss',
        [$tgl1, $tgl2]
    );

} elseif ($error === "") {

    $data = db_all("SELECT * FROM pembelian ORDER BY tanggal");

} else {

    $data = [];
}

$total_pembelian = 0;

foreach ($data as $d) {
    $total_pembelian += $d['total'];
}

/* ---------- EKSPOR CSV ---------- */

if (($_GET['export'] ?? '') === 'csv' && $error === "") {

    $baris = [];
    $no = 1;

    foreach ($data as $d) {
        $baris[] = [$no++, $d['tanggal'], $d['total'], $d['metode']];
    }

    $baris[] = ['', 'TOTAL', $total_pembelian, ''];

    catat_log('EKSPOR_CSV', 'laporan pembelian');

    csv_kirim('laporan_pembelian.csv', ['No', 'Tanggal', 'Total', 'Metode'], $baris);
}

$judul_halaman = "Laporan Pembelian";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>📉 Laporan Pembelian</h1>
        <p>Rekap pengeluaran dari pembelian barang.</p>
    </div>

    <div class="page-actions no-print">
        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>
        <a href="?<?= e(http_build_query(['tgl1' => $tgl1 ?? '', 'tgl2' => $tgl2 ?? '', 'export' => 'csv'])); ?>"
           class="btn btn-success">⬇️ Ekspor CSV</a>
        <button type="button" data-cetak class="btn btn-primary">🖨️ Cetak</button>
    </div>

</div>

<div class="card no-print">

<?= alert_html($error); ?>

<form method="GET" class="filter-form">

    <div class="form-group">
        <label>Tanggal Awal</label>
        <input type="date" name="tgl1" value="<?= e($tgl1 ?? ''); ?>">
    </div>

    <div class="form-group">
        <label>Tanggal Akhir</label>
        <input type="date" name="tgl2" value="<?= e($tgl2 ?? ''); ?>">
    </div>

    <button type="submit" class="btn btn-primary">Tampilkan</button>

    <a href="pembelian.php" class="btn btn-secondary">Reset</a>

</form>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>No</th>
    <th>Tanggal</th>
    <th class="text-right">Total</th>
    <th>Metode</th>
</tr>
</thead>

<tbody>

<?php

$no = 1;

foreach ($data as $d) {

?>

<tr>

<td><?= $no++; ?></td>

<td class="nowrap"><?= e($d['tanggal']); ?></td>

<td class="text-right nowrap"><?= rp($d['total']); ?></td>

<td>
    <span class="badge <?= $d['metode'] == 'Tunai' ? 'badge-success' : 'badge-warning'; ?>">
        <?= e($d['metode']); ?>
    </span>
</td>

</tr>

<?php } ?>

<?php if (count($data) == 0) { ?>
<tr class="empty-row">
    <td colspan="4">Tidak ada data pada periode ini.</td>
</tr>
<?php } ?>

</tbody>

<tfoot>
<tr>

<td colspan="2">Total Pengeluaran</td>

<td class="text-right nowrap"><?= rp($total_pembelian); ?></td>

<td></td>

</tr>
</tfoot>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

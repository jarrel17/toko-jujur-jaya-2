<?php
include "../../config/akses.php";

cek_akses('laporan.keuangan');

/* ---------- AMBIL DATA ---------- */

list($tgl1, $tgl2, $error) = rentang_tanggal();

if ($error === "" && $tgl1 !== null) {

    $baris = db_all(
        "SELECT * FROM keuangan
         WHERE tanggal >= ? AND tanggal < DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY tanggal",
        'ss',
        [$tgl1, $tgl2]
    );

} elseif ($error === "") {

    $baris = db_all("SELECT * FROM keuangan ORDER BY tanggal");

} else {

    $baris = [];
}

$total_masuk  = 0;
$total_keluar = 0;

foreach ($baris as $d) {

    if ($d['jenis'] == 'masuk') {

        $total_masuk += $d['jumlah'];

    } else {

        $total_keluar += $d['jumlah'];
    }
}

/* ---------- EKSPOR CSV ---------- */

if (($_GET['export'] ?? '') === 'csv' && $error === "") {

    $out = [];
    $no = 1;

    foreach ($baris as $d) {
        $out[] = [$no++, $d['tanggal'], $d['jenis'], $d['jumlah'], $d['keterangan']];
    }

    $out[] = ['', 'TOTAL MASUK', '', $total_masuk, ''];
    $out[] = ['', 'TOTAL KELUAR', '', $total_keluar, ''];
    $out[] = ['', 'SALDO AKHIR', '', $total_masuk - $total_keluar, ''];

    catat_log('EKSPOR_CSV', 'laporan keuangan');

    csv_kirim('laporan_keuangan.csv', ['No', 'Tanggal', 'Jenis', 'Jumlah', 'Keterangan'], $out);
}

$judul_halaman = "Laporan Keuangan";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>🏦 Laporan Keuangan</h1>
        <p>Arus uang masuk dan keluar toko.</p>
    </div>

    <div class="page-actions no-print">
        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>
        <a href="?<?= e(http_build_query(['tgl1' => $tgl1 ?? '', 'tgl2' => $tgl2 ?? '', 'export' => 'csv'])); ?>"
           class="btn btn-success">⬇️ Ekspor CSV</a>
        <button type="button" data-cetak class="btn btn-primary">🖨️ Cetak</button>
    </div>

</div>

<div class="stats">

    <div class="stat-card green">
        <span>Total Uang Masuk</span>
        <strong><?= rp($total_masuk); ?></strong>
    </div>

    <div class="stat-card red">
        <span>Total Uang Keluar</span>
        <strong><?= rp($total_keluar); ?></strong>
    </div>

    <div class="stat-card">
        <span>Saldo Akhir</span>
        <strong><?= rp($total_masuk - $total_keluar); ?></strong>
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

    <a href="keuangan.php" class="btn btn-secondary">Reset</a>

</form>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>No</th>
    <th>Tanggal</th>
    <th>Jenis</th>
    <th class="text-right">Jumlah</th>
    <th>Keterangan</th>
</tr>
</thead>

<tbody>

<?php

$no = 1;

foreach ($baris as $d) {

?>

<tr>

<td><?= $no++; ?></td>

<td class="nowrap"><?= e($d['tanggal']); ?></td>

<td>
    <span class="badge <?= $d['jenis'] == 'masuk' ? 'badge-success' : 'badge-danger'; ?>">
        <?= $d['jenis'] == 'masuk' ? 'Masuk' : 'Keluar'; ?>
    </span>
</td>

<td class="text-right nowrap"><?= rp($d['jumlah']); ?></td>

<td><?= e($d['keterangan']); ?></td>

</tr>

<?php } ?>

<?php if (count($baris) == 0) { ?>
<tr class="empty-row">
    <td colspan="5">Tidak ada data pada periode ini.</td>
</tr>
<?php } ?>

</tbody>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

<?php
include "../../config/akses.php";

cek_akses('log.lihat');

pastikan_tabel_tambahan();

$data = db_all("SELECT * FROM log_aktivitas ORDER BY id DESC LIMIT 300");

$judul_halaman = "Log Aktivitas";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>🧾 Log Aktivitas</h1>
        <p>300 aktivitas terakhir: login, perubahan data, dan akses yang ditolak.</p>
    </div>

    <div class="page-actions">
        <a class="btn btn-secondary" href="../dashboard/">← Kembali</a>
    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>Waktu</th>
    <th>User</th>
    <th>Aksi</th>
    <th>Detail</th>
    <th>IP</th>
</tr>
</thead>

<tbody>

<?php

if (count($data) == 0) {
?>
<tr class="empty-row">
    <td colspan="5">Belum ada aktivitas tercatat.</td>
</tr>
<?php
}

foreach ($data as $d) {

    $gagal = (strpos($d['aksi'], 'GAGAL') !== false
           || strpos($d['aksi'], 'DITOLAK') !== false
           || strpos($d['aksi'], 'DIBLOKIR') !== false);

?>

<tr>

<td class="nowrap"><?= e($d['waktu']); ?></td>

<td><strong><?= e($d['username']); ?></strong></td>

<td>
    <span class="badge <?= $gagal ? 'badge-danger' : 'badge-info'; ?>">
        <?= e($d['aksi']); ?>
    </span>
</td>

<td><?= e($d['detail']); ?></td>

<td class="nowrap"><?= e($d['ip']); ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

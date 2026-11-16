<?php

include "../../config/akses.php";

cek_akses('produk.lihat');

$judul_halaman = "Data Produk";

include "../../templates/header_page.php";

$boleh_tambah = bisa('produk.tambah');
$boleh_edit   = bisa('produk.edit');
$boleh_hapus  = bisa('produk.hapus');
$ada_aksi     = $boleh_edit || $boleh_hapus;

?>

<div class="page-head">

    <div>
        <h1>📦 Data Produk</h1>
        <p>Daftar produk beserta harga dan stok barang.</p>
    </div>

    <div class="page-actions">

        <a class="btn btn-secondary" href="../dashboard/">← Kembali</a>

        <?php if ($boleh_tambah) { ?>
            <a class="btn btn-success" href="tambah.php">+ Tambah Produk</a>
        <?php } ?>

    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>

    <th>No</th>
    <th>Nama Produk</th>
    <th>Harga Beli</th>
    <th>Harga Jual</th>
    <th class="text-center">Stok</th>
    <?php if ($ada_aksi) { ?>
        <th>Aksi</th>
    <?php } ?>

</tr>
</thead>

<tbody>

<?php

$no = 1;

$data = db_all("SELECT * FROM produk ORDER BY id_produk");

if (count($data) == 0) {
?>

<tr class="empty-row">
    <td colspan="<?= $ada_aksi ? 6 : 5; ?>">Belum ada data produk.</td>
</tr>

<?php
}

foreach ($data as $d) {

?>

<tr>

<td><?= $no++; ?></td>

<td><strong><?= e($d['nama_produk']); ?></strong></td>

<td class="nowrap"><?= rp($d['harga_beli']); ?></td>

<td class="nowrap"><?= rp($d['harga_jual']); ?></td>

<td class="text-center">
    <?php if ($d['stok'] <= 5) { ?>
        <span class="badge badge-danger"><?= e($d['stok']); ?> · Menipis</span>
    <?php } else { ?>
        <span class="badge badge-success"><?= e($d['stok']); ?></span>
    <?php } ?>
</td>

<?php if ($ada_aksi) { ?>
<td>

<div class="btn-group">

    <?php if ($boleh_edit) { ?>
        <a class="btn btn-primary btn-sm" href="edit.php?id=<?= e($d['id_produk']); ?>">Edit</a>
    <?php } ?>

    <?php if ($boleh_hapus) { ?>
        <form method="POST" action="hapus.php" class="inline-form"
              data-konfirmasi="Yakin ingin menghapus produk ini?">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" value="<?= e($d['id_produk']); ?>">
            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
        </form>
    <?php } ?>

</div>

</td>
<?php } ?>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php

include "../../templates/footer_page.php";

?>

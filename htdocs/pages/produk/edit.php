<?php
include "../../config/akses.php";

cek_akses('produk.edit');

csrf_wajib();

$id = get_int('id');

$data = $id ? db_one("SELECT * FROM produk WHERE id_produk = ?", 'i', [$id]) : null;

if (!$data) {
    halaman_error(404, 'Data Tidak Ditemukan', 'Produk yang Anda cari tidak ada.');
}

$error = "";

if (isset($_POST['update'])) {

    $nama = post_str('nama', 100);
    $beli = post_int('beli', 1);
    $jual = post_int('jual', 1);
    $stok = post_int('stok', 0);

    if ($nama === '') {

        $error = "Nama produk wajib diisi (maksimal 100 karakter).";

    } elseif ($beli === null || $jual === null) {

        $error = "Harga beli dan harga jual harus berupa angka lebih dari 0.";

    } elseif ($stok === null) {

        $error = "Stok harus berupa angka 0 atau lebih.";

    } elseif (db_exec(
        "UPDATE produk SET nama_produk = ?, harga_beli = ?, harga_jual = ?, stok = ? WHERE id_produk = ?",
        'siiii',
        [$nama, $beli, $jual, $stok, $id]
    )) {

        catat_log('PRODUK_EDIT', 'id=' . $id . ' ' . $nama);
        flash_set("Produk berhasil diperbarui.");
        header("Location: index.php");
        exit;

    } else {

        $error = "Gagal memperbarui produk. Silakan coba lagi.";
    }

    /* tampilkan kembali isian terakhir */
    $data['nama_produk'] = $_POST['nama'] ?? $data['nama_produk'];
    $data['harga_beli']  = $_POST['beli'] ?? $data['harga_beli'];
    $data['harga_jual']  = $_POST['jual'] ?? $data['harga_jual'];
    $data['stok']        = $_POST['stok'] ?? $data['stok'];
}

$judul_halaman = "Edit Produk";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>✏️ Edit Produk</h1>
        <p>Perbarui data produk yang dipilih.</p>
    </div>

</div>

<div class="card form-card">

<?= alert_html($error); ?>

<form method="POST">

<?= csrf_field(); ?>

<div class="form-group">
    <label>Nama Produk</label>
    <input type="text" name="nama" maxlength="100" value="<?= e($data['nama_produk']); ?>" required>
</div>

<div class="form-group">
    <label>Harga Beli</label>
    <input type="number" name="beli" min="1" value="<?= e($data['harga_beli']); ?>" required>
</div>

<div class="form-group">
    <label>Harga Jual</label>
    <input type="number" name="jual" min="1" value="<?= e($data['harga_jual']); ?>" required>
</div>

<div class="form-group">
    <label>Stok</label>
    <input type="number" name="stok" min="0" value="<?= e($data['stok']); ?>" required>
</div>

<div class="form-actions">

    <button type="submit" name="update" class="btn btn-primary">Update</button>

    <a href="index.php" class="btn btn-secondary">Batal</a>

</div>

</form>

</div>

<?php include "../../templates/footer_page.php"; ?>

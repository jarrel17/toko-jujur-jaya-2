<?php
include "../../config/akses.php";

cek_akses('produk.tambah');

csrf_wajib();

$error = "";

if (isset($_POST['simpan'])) {

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
        "INSERT INTO produk (nama_produk, harga_beli, harga_jual, stok) VALUES (?, ?, ?, ?)",
        'siii',
        [$nama, $beli, $jual, $stok]
    )) {

        catat_log('PRODUK_TAMBAH', $nama);
        flash_set("Produk berhasil ditambahkan.");
        header("Location: index.php");
        exit;

    } else {

        $error = "Gagal menyimpan produk. Silakan coba lagi.";
    }
}

$judul_halaman = "Tambah Produk";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>➕ Tambah Produk</h1>
        <p>Isi data produk baru dengan lengkap.</p>
    </div>

</div>

<div class="card form-card">

<?= alert_html($error); ?>

<form method="POST">

<?= csrf_field(); ?>

<div class="form-group">
    <label>Nama Produk</label>
    <input type="text" name="nama" maxlength="100" placeholder="Contoh: Semen 50 kg"
           value="<?= e($_POST['nama'] ?? ''); ?>" required>
</div>

<div class="form-group">
    <label>Harga Beli</label>
    <input type="number" name="beli" min="1" placeholder="0"
           value="<?= e($_POST['beli'] ?? ''); ?>" required>
</div>

<div class="form-group">
    <label>Harga Jual</label>
    <input type="number" name="jual" min="1" placeholder="0"
           value="<?= e($_POST['jual'] ?? ''); ?>" required>
</div>

<div class="form-group">
    <label>Stok</label>
    <input type="number" name="stok" min="0" placeholder="0"
           value="<?= e($_POST['stok'] ?? ''); ?>" required>
</div>

<div class="form-actions">

    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>

    <a href="index.php" class="btn btn-secondary">Batal</a>

</div>

</form>

</div>

<?php include "../../templates/footer_page.php"; ?>

<?php
include "../../config/akses.php";

cek_akses('pembelian.tambah');

csrf_wajib();

$id_user = (int)($_SESSION['id_user'] ?? 0);

if ($id_user <= 0) {
    halaman_error(403, 'Sesi Tidak Valid', 'Silakan login ulang.');
}

$error = "";

$daftar_produk = db_all("SELECT id_produk, nama_produk FROM produk ORDER BY nama_produk");

if (isset($_POST['simpan'])) {

    $id_produk = post_int('produk');
    $jumlah    = post_int('jumlah', 1, 1000000);
    $metode    = $_POST['metode'] ?? '';

    if ($id_produk === null || $jumlah === null) {

        $error = "Produk dan jumlah harus diisi dengan benar (jumlah minimal 1).";

    } elseif (!in_array($metode, ['Tunai', 'Kredit'], true)) {

        $error = "Metode pembayaran tidak valid.";

    } else {

        db_begin();

        $produk = db_one("SELECT * FROM produk WHERE id_produk = ? FOR UPDATE", 'i', [$id_produk]);

        if (!$produk) {

            db_rollback();
            $error = "Produk tidak ditemukan.";

        } else {

            $harga = $produk['harga_beli'];
            $total = $harga * $jumlah;

            // SIMPAN PEMBELIAN (id_user = user yang login)
            $ok = db_exec(
                "INSERT INTO pembelian VALUES(NULL, ?, NOW(), ?, ?)",
                'iss',
                [$id_user, (string)$total, $metode]
            );

            // DETAIL PEMBELIAN
            if ($ok) {

                $id_pembelian = db_last_id();

                $ok = db_exec(
                    "INSERT INTO detail_pembelian VALUES(NULL, ?, ?, ?, ?)",
                    'iiis',
                    [$id_pembelian, $id_produk, $jumlah, (string)$harga]
                );
            }

            // UPDATE STOK
            if ($ok) {
                $ok = db_exec(
                    "UPDATE produk SET stok = stok + ? WHERE id_produk = ?",
                    'ii',
                    [$jumlah, $id_produk]
                );
            }

            // PEMBELIAN TUNAI / KREDIT
            if ($ok) {

                if ($metode === 'Tunai') {

                    $ok = db_exec(
                        "INSERT INTO keuangan (tanggal, jenis, jumlah, keterangan) VALUES (NOW(), 'keluar', ?, 'Pembelian')",
                        's',
                        [(string)$total]
                    );

                } else {

                    $ok = db_exec(
                        "INSERT INTO hutang (id_pembelian, total, sisa, status) VALUES (?, ?, ?, 'Belum Lunas')",
                        'iss',
                        [$id_pembelian, (string)$total, (string)$total]
                    );
                }
            }

            if ($ok) {

                db_commit();

                catat_log('PEMBELIAN_TAMBAH', 'id=' . $id_pembelian . ' total=' . $total . ' ' . $metode);
                flash_set("Pembelian berhasil disimpan.");
                header("Location: index.php");
                exit;
            }

            db_rollback();
            $error = "Gagal menyimpan pembelian. Silakan coba lagi.";
        }
    }
}

$judul_halaman = "Tambah Pembelian";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>📥 Tambah Pembelian</h1>
        <p>Catat barang masuk / restock dari supplier.</p>
    </div>

</div>

<div class="card form-card">

<?= alert_html($error); ?>

<form method="POST">

<?= csrf_field(); ?>

<div class="form-group">

    <label>Produk</label>

    <select name="produk">

    <?php foreach ($daftar_produk as $d) { ?>

    <option value="<?= e($d['id_produk']); ?>"
        <?= ((string)($_POST['produk'] ?? '') === (string)$d['id_produk']) ? 'selected' : ''; ?>>

    <?= e($d['nama_produk']); ?>

    </option>

    <?php } ?>

    </select>

</div>

<div class="form-group">

    <label>Jumlah</label>

    <input type="number" name="jumlah" min="1" placeholder="0"
           value="<?= e($_POST['jumlah'] ?? ''); ?>" required>

</div>

<div class="form-group">

    <label>Metode</label>

    <select name="metode">

        <option value="Tunai">Tunai</option>

        <option value="Kredit">Kredit</option>

    </select>

</div>

<div class="form-actions">

    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>

    <a href="index.php" class="btn btn-secondary">Batal</a>

</div>

</form>

</div>

<?php include "../../templates/footer_page.php"; ?>

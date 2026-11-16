<?php
include "../../config/akses.php";

cek_akses('retur_penjualan.tambah');

csrf_wajib();

$error = "";

$daftar_produk = db_all("SELECT id_produk, nama_produk FROM produk ORDER BY nama_produk");

if (isset($_POST['simpan'])) {

    $id_trx     = post_int('penjualan');
    $id_produk  = post_int('produk');
    $jumlah     = post_int('jumlah', 1, 1000000);
    $keterangan = post_str('keterangan', 255);

    if ($id_trx === null || $id_produk === null || $jumlah === null) {

        $error = "ID penjualan, produk, dan jumlah retur harus diisi dengan benar.";

    } else {

        db_begin();

        $trx = db_one("SELECT id_penjualan FROM penjualan WHERE id_penjualan = ?", 'i', [$id_trx]);

        $produk = db_one("SELECT * FROM produk WHERE id_produk = ? FOR UPDATE", 'i', [$id_produk]);

        if (!$trx) {

            $error = "Transaksi penjualan dengan ID tersebut tidak ditemukan.";

        } elseif (!$produk) {

            $error = "Produk tidak ditemukan.";

        } else {

            /* jumlah yang boleh diretur = jumlah pada transaksi - retur sebelumnya */
            $awal = db_one(
                "SELECT COALESCE(SUM(jumlah), 0) AS n FROM detail_penjualan WHERE id_penjualan = ? AND id_produk = ?",
                'ii',
                [$id_trx, $id_produk]
            );

            $sudah = db_one(
                "SELECT COALESCE(SUM(jumlah), 0) AS n FROM retur_penjualan WHERE id_penjualan = ? AND id_produk = ?",
                'ii',
                [$id_trx, $id_produk]
            );

            $boleh = (int)$awal['n'] - (int)$sudah['n'];

            if ($boleh <= 0) {

                $error = "Produk ini tidak ada pada transaksi tersebut atau sudah seluruhnya diretur.";

            } elseif ($jumlah > $boleh) {

                $error = "Jumlah retur melebihi batas. Maksimal yang bisa diretur: " . $boleh . ".";
            }
        }

        if ($error === "") {

            // STOK BARU
            $stok_baru = $produk['stok'] + $jumlah;

            // TOTAL RETUR
            $total = $produk['harga_jual'] * $jumlah;

            // SIMPAN RETUR
            $ok = db_exec(
                "INSERT INTO retur_penjualan VALUES(NULL, ?, NOW(), ?, ?, ?)",
                'iiis',
                [$id_trx, $id_produk, $jumlah, $keterangan]
            );

            // UPDATE STOK
            if ($ok) {
                $ok = db_exec(
                    "UPDATE produk SET stok = ? WHERE id_produk = ?",
                    'ii',
                    [$stok_baru, $id_produk]
                );
            }

            // KEUANGAN
            if ($ok) {
                $ok = db_exec(
                    "INSERT INTO keuangan (tanggal, jenis, jumlah, keterangan) VALUES (NOW(), 'keluar', ?, 'Retur Penjualan')",
                    's',
                    [(string)$total]
                );
            }

            // CEK PIUTANG
            if ($ok) {

                $ref = db_one("SELECT * FROM piutang WHERE id_penjualan = ? FOR UPDATE", 'i', [$id_trx]);

                if ($ref) {

                    $sisa_baru = $ref['sisa'] - $total;

                    if ($sisa_baru < 0) {

                        $sisa_baru = 0;
                    }

                    $ok = db_exec(
                        "UPDATE piutang SET sisa = ? WHERE id_penjualan = ?",
                        'si',
                        [(string)$sisa_baru, $id_trx]
                    );
                }
            }

            if ($ok) {

                db_commit();

                catat_log('RETUR_PENJUALAN', 'penjualan=' . $id_trx . ' produk=' . $id_produk . ' jumlah=' . $jumlah);
                flash_set("Retur berhasil disimpan.");
                header("Location: index.php");
                exit;
            }

            $error = "Gagal menyimpan retur. Silakan coba lagi.";
        }

        db_rollback();
    }
}

$judul_halaman = "Tambah Retur Penjualan";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>↩️ Tambah Retur Penjualan</h1>
        <p>Catat barang yang dikembalikan oleh pelanggan.</p>
    </div>

</div>

<div class="card form-card">

<?= alert_html($error); ?>

<form method="POST">

<?= csrf_field(); ?>

<div class="form-group">
    <label>ID Penjualan</label>
    <input type="number" name="penjualan" min="1" placeholder="Nomor ID penjualan"
           value="<?= e($_POST['penjualan'] ?? ''); ?>" required>
</div>

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
    <label>Jumlah Retur</label>
    <input type="number" name="jumlah" min="1" placeholder="0"
           value="<?= e($_POST['jumlah'] ?? ''); ?>" required>
</div>

<div class="form-group">
    <label>Keterangan</label>
    <textarea name="keterangan" maxlength="255" placeholder="Alasan retur"><?= e($_POST['keterangan'] ?? ''); ?></textarea>
</div>

<div class="form-actions">

    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>

    <a href="index.php" class="btn btn-secondary">Batal</a>

</div>

</form>

</div>

<?php include "../../templates/footer_page.php"; ?>

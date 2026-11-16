<?php
include "../../config/akses.php";

cek_akses('penjualan.tambah');

csrf_wajib();

$id_user = (int)($_SESSION['id_user'] ?? 0);

if ($id_user <= 0) {
    halaman_error(403, 'Sesi Tidak Valid', 'Silakan login ulang.');
}

$error = "";

pastikan_kolom_pembeli();

/* daftar produk untuk pilihan (dipakai form + baris tambahan JS) */
$daftar_produk = db_all("SELECT id_produk, nama_produk, stok FROM produk ORDER BY nama_produk");

/* =========================
   SIMPAN TRANSAKSI
========================= */
if (isset($_POST['simpan'])) {

    $produk_post = $_POST['produk'] ?? [];
    $jumlah_post = $_POST['jumlah'] ?? [];
    $metode      = $_POST['metode'] ?? '';
    $nama_pembeli = post_str('nama_pembeli', 100);

    $kebutuhan = [];   /* id_produk => total jumlah */

    if (!is_array($produk_post) || !is_array($jumlah_post)
        || count($produk_post) == 0 || count($produk_post) > 50
        || count($produk_post) != count($jumlah_post)) {

        $error = "Data produk tidak valid.";

    } elseif (!in_array($metode, ['Tunai', 'Kredit'], true)) {

        $error = "Metode pembayaran tidak valid.";

    } elseif ($metode === 'Kredit' && $nama_pembeli === '') {

        $error = "Nama pembeli wajib diisi untuk penjualan kredit (maksimal 100 karakter).";

    } else {

        foreach ($produk_post as $i => $idp) {

            $qty = $jumlah_post[$i] ?? '';

            if (!is_string($idp) || !ctype_digit($idp)
                || !is_string($qty) || !ctype_digit($qty)
                || (int)$qty < 1 || (int)$qty > 1000000) {

                $error = "Produk atau jumlah tidak valid.";
                break;
            }

            $idp = (int)$idp;
            $kebutuhan[$idp] = ($kebutuhan[$idp] ?? 0) + (int)$qty;
        }
    }

    if ($error === "") {

        db_begin();

        $total = 0;
        $baris = [];

        /* VALIDASI STOK + HITUNG TOTAL (harga selalu dari database) */
        foreach ($kebutuhan as $idp => $qty) {

            $p = db_one("SELECT * FROM produk WHERE id_produk = ? FOR UPDATE", 'i', [$idp]);

            if (!$p) {
                $error = "Produk tidak ditemukan.";
                break;
            }

            if ((int)$p['stok'] < $qty) {
                $error = "Stok " . $p['nama_produk'] . " tidak cukup (tersisa " . $p['stok'] . ").";
                break;
            }

            $total += $p['harga_jual'] * $qty;

            $baris[] = ['id' => $idp, 'qty' => $qty, 'harga' => $p['harga_jual']];
        }

        $ok = ($error === "");

        /* INSERT PENJUALAN */
        if ($ok) {
            $ok = db_exec(
                "INSERT INTO penjualan (id_user, tanggal, total, metode) VALUES (?, NOW(), ?, ?)",
                'iss',
                [$id_user, (string)$total, $metode]
            );
        }

        /* DETAIL + STOK */
        if ($ok) {

            $id_penjualan = db_last_id();

            foreach ($baris as $b) {

                $ok = db_exec(
                        "INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, harga) VALUES (?, ?, ?, ?)",
                        'iiis',
                        [$id_penjualan, $b['id'], $b['qty'], (string)$b['harga']]
                    )
                    && db_exec(
                        "UPDATE produk SET stok = stok - ? WHERE id_produk = ?",
                        'ii',
                        [$b['qty'], $b['id']]
                    );

                if (!$ok) {
                    break;
                }
            }
        }

        /* KEUANGAN / PIUTANG */
        if ($ok) {

            if ($metode === 'Tunai') {

                $ok = db_exec(
                    "INSERT INTO keuangan (tanggal, jenis, jumlah, keterangan) VALUES (NOW(), 'masuk', ?, 'Penjualan')",
                    's',
                    [(string)$total]
                );

            } else {

                $ok = db_exec(
                    "INSERT INTO piutang (id_penjualan, total, sisa, status, nama_pembeli) VALUES (?, ?, ?, 'Belum Lunas', ?)",
                    'isss',
                    [$id_penjualan, (string)$total, (string)$total, $nama_pembeli]
                );
            }
        }

        if ($ok) {

            db_commit();

            catat_log('PENJUALAN_TAMBAH', 'id=' . $id_penjualan . ' total=' . $total . ' ' . $metode
                . ($metode === 'Kredit' ? ' pembeli=' . $nama_pembeli : ''));
            flash_set("Penjualan berhasil disimpan.");
            header("Location: index.php");
            exit;
        }

        db_rollback();

        if ($error === "") {
            $error = "Gagal menyimpan transaksi. Silakan coba lagi.";
        }
    }
}

$judul_halaman = "Tambah Penjualan";

include "../../templates/header_page.php";
?>

<!-- =========================
     FORM TAMBAH PENJUALAN
========================= -->

<div class="page-head">

    <div>
        <h1>🛍️ Tambah Penjualan</h1>
        <p>Pilih produk dan jumlah yang dijual, lalu simpan transaksi.</p>
    </div>

</div>

<div class="card form-card">

<?= alert_html($error); ?>

<form method="POST">

<?= csrf_field(); ?>

<div class="form-group">

<label>Produk yang dijual</label>

<div id="produk-area">

    <div class="row-item">
        <select name="produk[]">
            <?php foreach ($daftar_produk as $d) { ?>
                <option value="<?= e($d['id_produk']); ?>">
                    <?= e($d['nama_produk']); ?> (stok: <?= e($d['stok']); ?>)
                </option>
            <?php } ?>
        </select>

        <input type="number" name="jumlah[]" min="1" placeholder="Jumlah" required>
    </div>

</div>

<button type="button" class="btn btn-secondary btn-sm" id="btn-tambah-produk">
    + Tambah Produk
</button>

</div>

<div class="form-group">

<label>Metode Pembayaran</label>

<select name="metode" id="metode">
    <option value="Tunai">Tunai</option>
    <option value="Kredit" <?= ($_POST['metode'] ?? '') === 'Kredit' ? 'selected' : ''; ?>>Kredit (hutang)</option>
</select>

</div>

<div class="form-group sembunyi" id="grup-pembeli">

<label>Nama Pembeli</label>

<input type="text" name="nama_pembeli" id="nama_pembeli" maxlength="100"
       placeholder="Nama pembeli yang berhutang"
       value="<?= e($_POST['nama_pembeli'] ?? ''); ?>">

</div>

<div class="form-actions">

    <button type="submit" name="simpan" class="btn btn-success">
        Simpan
    </button>

    <a href="index.php" class="btn btn-secondary">Batal</a>

</div>

</form>

</div>

<script src="../../assets/js/penjualan-tambah.js?v=<?= filemtime(__DIR__ . '/../../assets/js/penjualan-tambah.js'); ?>"></script>

<?php include "../../templates/footer_page.php"; ?>

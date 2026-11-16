<?php
include "../../config/akses.php";

cek_akses('piutang.bayar');

csrf_wajib();

pastikan_kolom_pembeli();

$id = get_int('id');

$data = $id ? db_one("SELECT * FROM piutang WHERE id_piutang = ?", 'i', [$id]) : null;

if (!$data) {
    halaman_error(404, 'Data Tidak Ditemukan', 'Data piutang yang Anda cari tidak ada.');
}

$error = "";

if (isset($_POST['bayar'])) {

    $bayar = post_int('bayar_piutang');

    if ($data['status'] === 'Lunas' || $data['sisa'] <= 0) {

        $error = "Piutang ini sudah lunas.";

    } elseif ($bayar === null) {

        $error = "Jumlah bayar harus berupa angka lebih dari 0.";

    } elseif ($bayar > $data['sisa']) {

        $error = "Jumlah bayar melebihi sisa piutang (" . rp($data['sisa']) . ").";

    } else {

        $sisa_baru = $data['sisa'] - $bayar;

        // STATUS

        if ($sisa_baru <= 0) {

            $status = "Lunas";
            $sisa_baru = 0;

        } else {

            $status = "Belum Lunas";
        }

        db_begin();

        // UPDATE Piutang

        $ok = db_exec(
            "UPDATE piutang SET sisa = ?, status = ? WHERE id_piutang = ?",
            'ssi',
            [(string)$sisa_baru, $status, $id]
        );

        // MASUK KE KEUANGAN

        if ($ok) {
            $ok = db_exec(
                "INSERT INTO keuangan (tanggal, jenis, jumlah, keterangan) VALUES (NOW(), 'masuk', ?, 'Bayar Piutang')",
                's',
                [(string)$bayar]
            );
        }

        if ($ok) {

            db_commit();

            catat_log('PIUTANG_BAYAR', 'id=' . $id . ' bayar=' . $bayar . ' pembeli=' . ($data['nama_pembeli'] ?? '-'));
            flash_set("Pembayaran piutang berhasil dicatat.");
            header("Location: index.php");
            exit;
        }

        db_rollback();
        $error = "Gagal mencatat pembayaran. Silakan coba lagi.";
    }
}

$judul_halaman = "Bayar Piutang";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>💵 Bayar Piutang</h1>
        <p>Catat pembayaran piutang dari pelanggan.</p>
    </div>

</div>

<div class="card form-card">

<?= alert_html($error); ?>

<div class="info-row">

    <div>
        <span>Nama Pembeli</span>
        <strong><?= ($data['nama_pembeli'] ?? '') !== '' ? e($data['nama_pembeli']) : '-'; ?></strong>
    </div>

    <div>
        <span>Total Piutang</span>
        <strong><?= rp($data['total']); ?></strong>
    </div>

    <div>
        <span>Sisa Piutang</span>
        <strong><?= rp($data['sisa']); ?></strong>
    </div>

</div>

<form method="POST">

<?= csrf_field(); ?>

<div class="form-group">
    <label>Jumlah Bayar</label>
    <input type="number" name="bayar_piutang" min="1" max="<?= e((int)$data['sisa']); ?>" placeholder="0" required>
</div>

<div class="form-actions">

    <button type="submit" name="bayar" class="btn btn-success">Bayar</button>

    <a href="index.php" class="btn btn-secondary">Batal</a>

</div>

</form>

</div>

<?php include "../../templates/footer_page.php"; ?>

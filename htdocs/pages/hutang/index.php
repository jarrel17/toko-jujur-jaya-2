<?php
include "../../config/akses.php";

cek_akses('hutang.lihat');

$judul_halaman = "Data Hutang";

include "../../templates/header_page.php";

$boleh_bayar = bisa('hutang.bayar');
?>

<div class="page-head">

    <div>
        <h1>💳 Data Hutang</h1>
        <p>Hutang dari pembelian secara kredit.</p>
    </div>

    <div class="page-actions">
        <a href="../dashboard/" class="btn btn-secondary">← Kembali</a>
    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

    <table class="data">

        <thead>
        <tr>
            <th>No</th>
            <th>ID Pembelian</th>
            <th>Total</th>
            <th>Sisa</th>
            <th>Status</th>
            <?php if ($boleh_bayar) { ?>
                <th>Aksi</th>
            <?php } ?>
        </tr>
        </thead>

        <tbody>

        <?php
        $no = 1;

        $data = db_all("SELECT * FROM hutang ORDER BY id_hutang DESC");

        if (count($data) == 0) {
        ?>
        <tr class="empty-row">
            <td colspan="<?= $boleh_bayar ? 6 : 5; ?>">Belum ada data hutang.</td>
        </tr>
        <?php
        }

        foreach ($data as $d) {
        ?>

        <tr>

            <td><?= $no++; ?></td>

            <td>#<?= e($d['id_pembelian']); ?></td>

            <td class="nowrap"><?= rp($d['total']); ?></td>

            <td class="nowrap"><strong><?= rp($d['sisa']); ?></strong></td>

            <td>
                <span class="badge <?= $d['status'] == 'Lunas' ? 'badge-success' : 'badge-warning'; ?>">
                    <?= e($d['status']); ?>
                </span>
            </td>

            <?php if ($boleh_bayar) { ?>
            <td>
                <a class="btn btn-success btn-sm"
                   href="bayar.php?id=<?= e($d['id_hutang']); ?>">
                    Bayar
                </a>
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

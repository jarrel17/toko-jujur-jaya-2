<?php
/* Menu sidebar - hanya menampilkan menu yang boleh diakses role yang login */

$sb_uri = $_SERVER['SCRIPT_NAME'] ?? '';

$sb_menu = [

    '' => [
        ['dashboard',        '../dashboard/',                 '/pages/dashboard/',              '📊 Dashboard'],
        ['produk.lihat',     '../produk/',                    '/pages/produk/',                 '📦 Produk'],
    ],

    'Transaksi' => [
        ['penjualan.lihat',        '../penjualan/',           '/pages/penjualan/',              '💰 Penjualan'],
        ['pembelian.lihat',        '../pembelian/',           '/pages/pembelian/',              '🛒 Pembelian'],
        ['retur_penjualan.lihat',  '../retur_penjualan/',     '/pages/retur_penjualan/',        '↩️ Retur Penjualan'],
        ['retur_pembelian.lihat',  '../retur_pembelian/',     '/pages/retur_pembelian/',        '↪️ Retur Pembelian'],
    ],

    'Keuangan' => [
        ['hutang.lihat',     '../hutang/',                    '/pages/hutang/',                 '💳 Hutang'],
        ['piutang.lihat',    '../piutang/',                   '/pages/piutang/',                '💵 Piutang'],
    ],

    'Laporan' => [
        ['laporan.stok',       '../laporan/stok.php',         '/laporan/stok.php',              '📋 Laporan Stok'],
        ['laporan.penjualan',  '../laporan/penjualan.php',    '/laporan/penjualan.php',         '📈 Laporan Penjualan'],
        ['laporan.pembelian',  '../laporan/pembelian.php',    '/laporan/pembelian.php',         '📉 Laporan Pembelian'],
        ['laporan.keuangan',   '../laporan/keuangan.php',     '/laporan/keuangan.php',          '🏦 Laporan Keuangan'],
    ],

    'Administrasi' => [
        ['user.kelola',      '../user/',                      '/pages/user/',                   '👥 Kelola User'],
        ['log.lihat',        '../log/',                       '/pages/log/',                    '🧾 Log Aktivitas'],
    ],
];
?>
<div class="sidebar">

<?php foreach ($sb_menu as $sb_judul => $sb_items) {

    /* ambil hanya item yang boleh diakses */
    $sb_boleh = [];
    foreach ($sb_items as $sb_item) {
        if (bisa($sb_item[0])) {
            $sb_boleh[] = $sb_item;
        }
    }

    if (count($sb_boleh) == 0) {
        continue;
    }

    if ($sb_judul !== '') { ?>
        <p class="menu-label"><?= e($sb_judul); ?></p>
    <?php }

    foreach ($sb_boleh as $sb_item) {
        $sb_class = (strpos($sb_uri, $sb_item[2]) !== false) ? ' class="active"' : '';
        ?>
        <a href="<?= $sb_item[1]; ?>"<?= $sb_class; ?>><?= $sb_item[3]; ?></a>
    <?php }
} ?>

<p class="menu-label">Akun</p>

<a href="../../logout.php" class="logout-link">🚪 Logout</a>

</div>

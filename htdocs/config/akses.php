<?php
/* =====================================================================
   PUSAT HAK AKSES
   Ubah daftar role di bawah ini bila ingin menyesuaikan batasan akses.
   Role yang dikenali: admin, gudang, pemilik
   (role 'pending' = akun baru yang belum disetujui pemilik, tanpa akses)
===================================================================== */

require_once __DIR__ . '/keamanan.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/db.php';

/* ---------- peta hak akses ---------- */

$HAK_AKSES = [

    'dashboard'          => ['admin', 'gudang', 'pemilik'],

    /* Produk */
    'produk.lihat'       => ['admin', 'gudang', 'pemilik'],
    'produk.tambah'      => ['admin', 'gudang'],
    'produk.edit'        => ['admin', 'gudang'],
    'produk.hapus'       => ['admin'],

    /* Penjualan */
    'penjualan.lihat'    => ['admin', 'pemilik'],
    'penjualan.tambah'   => ['admin'],

    /* Pembelian */
    'pembelian.lihat'    => ['admin', 'gudang', 'pemilik'],
    'pembelian.tambah'   => ['admin', 'gudang'],

    /* Retur */
    'retur_penjualan.lihat'  => ['admin', 'pemilik'],
    'retur_penjualan.tambah' => ['admin'],
    'retur_pembelian.lihat'  => ['gudang', 'pemilik'],
    'retur_pembelian.tambah' => ['gudang'],

    /* Hutang & Piutang */
    'hutang.lihat'       => ['pemilik'],
    'hutang.bayar'       => ['pemilik'],
    'piutang.lihat'      => ['pemilik'],
    'piutang.bayar'      => ['pemilik'],

    /* Laporan */
    'laporan.menu'       => ['gudang', 'pemilik'],
    'laporan.stok'       => ['gudang', 'pemilik'],
    'laporan.penjualan'  => ['pemilik'],
    'laporan.pembelian'  => ['pemilik'],
    'laporan.keuangan'   => ['pemilik'],

    /* Administrasi */
    'user.kelola'        => ['pemilik'],
    'log.lihat'          => ['pemilik'],
];

/* ---------- role user yang sedang login ---------- */

function role_user()
{
    $r = strtolower(trim($_SESSION['role'] ?? ''));

    $alias = [
        'administrator' => 'admin',
        'owner'         => 'pemilik',
        'staff gudang'  => 'gudang',
        'staf gudang'   => 'gudang',
    ];

    return $alias[$r] ?? $r;
}

/* ---------- apakah role saat ini boleh? ---------- */

function bisa($izin)
{
    global $HAK_AKSES;

    return isset($HAK_AKSES[$izin])
        && in_array(role_user(), $HAK_AKSES[$izin], true);
}

/* ---------- halaman pesan error dengan layout ---------- */

function halaman_error($kode, $judul, $pesan, $tombol_dashboard = true)
{
    http_response_code($kode);

    $judul_halaman = $judul;

    include __DIR__ . '/../templates/header_page.php';
    ?>
    <div class="card denied">
        <div class="denied-icon">🔒</div>
        <h2><?= e($judul); ?></h2>
        <p><?= $pesan; ?></p>
        <?php if ($tombol_dashboard) { ?>
            <a href="../dashboard/" class="btn btn-primary">← Kembali ke Dashboard</a>
        <?php } else { ?>
            <a href="../../logout.php" class="btn btn-danger">Logout</a>
        <?php } ?>
    </div>
    <?php
    include __DIR__ . '/../templates/footer_page.php';
    exit;
}

/* ---------- penjaga halaman (taruh paling atas tiap halaman) ---------- */

function cek_akses($izin, $login_url = '../../index.php')
{
    /* belum login */
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        header("Location: " . $login_url);
        exit;
    }

    /* sesi kedaluwarsa (tidak aktif terlalu lama / terlalu lama sejak login) */
    $sekarang = time();

    if (($sekarang - ($_SESSION['aktif_terakhir'] ?? 0)) > SESI_IDLE_MAKS
        || ($sekarang - ($_SESSION['waktu_login'] ?? 0)) > SESI_TOTAL_MAKS) {

        sesi_hancurkan();
        header("Location: " . $login_url . "?expired=1");
        exit;
    }

    $_SESSION['aktif_terakhir'] = $sekarang;

    /* role selalu dibaca ulang dari database: perubahan role atau penghapusan akun
       berlaku seketika, tidak menunggu pengguna login ulang */
    $akun = db_one("SELECT role FROM users WHERE id_user = ?", 'i', [(int)($_SESSION['id_user'] ?? 0)]);

    if (!$akun) {
        sesi_hancurkan();
        header("Location: " . $login_url . "?expired=1");
        exit;
    }

    $_SESSION['role'] = $akun['role'];

    /* akun belum disetujui pemilik */
    if (role_user() === 'pending') {
        halaman_error(
            403,
            'Menunggu Persetujuan',
            'Akun Anda sudah terdaftar, namun belum diberi hak akses oleh pemilik toko.<br>Silakan hubungi pemilik toko.',
            false
        );
    }

    /* role tidak punya izin */
    if (!bisa($izin)) {

        catat_log('AKSES_DITOLAK', $izin);

        halaman_error(
            403,
            'Akses Ditolak',
            'Akun Anda dengan role <strong>'
                . e(role_user() !== '' ? role_user() : 'tidak dikenal')
                . '</strong> tidak memiliki izin untuk membuka halaman ini.'
        );
    }
}

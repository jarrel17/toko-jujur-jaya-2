<?php
include "../../config/akses.php";

cek_akses('produk.hapus');

/* hapus hanya lewat POST + token CSRF */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: index.php");
    exit;
}

csrf_wajib();

$id = post_int('id');

if ($id === null) {
    flash_set("Permintaan tidak valid.", "danger");
    header("Location: index.php");
    exit;
}

$produk = db_one("SELECT nama_produk FROM produk WHERE id_produk = ?", 'i', [$id]);

if (!$produk) {

    flash_set("Produk tidak ditemukan.", "danger");

} elseif (db_exec("DELETE FROM produk WHERE id_produk = ?", 'i', [$id])) {

    catat_log('PRODUK_HAPUS', 'id=' . $id . ' ' . $produk['nama_produk']);
    flash_set("Produk berhasil dihapus.");

} else {

    flash_set("Produk tidak dapat dihapus karena sudah dipakai pada transaksi.", "danger");
}

header("Location: index.php");
exit;

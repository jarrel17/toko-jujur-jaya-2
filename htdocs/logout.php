<?php

require_once "config/keamanan.php";
require_once "config/koneksi.php";
require_once "config/db.php";

if (!empty($_SESSION['login'])) {
    catat_log('LOGOUT', 'keluar');
}

sesi_hancurkan();

header("Location: index.php?logout=1");
exit;

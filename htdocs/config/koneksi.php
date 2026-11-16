<?php
/* =====================================================================
   KONEKSI DATABASE
   Kredensial TIDAK ditulis di sini. Sumbernya (urutan prioritas):
     1. environment variable: DB_HOST, DB_USER, DB_PASS, DB_NAME
     2. file config/config.local.php (tidak ikut repository, lihat .gitignore)
===================================================================== */

if (isset($conn) && $conn instanceof mysqli) {
    return;
}

mysqli_report(MYSQLI_REPORT_OFF);

$cfg = [
    'host' => getenv('DB_HOST') ?: '',
    'user' => getenv('DB_USER') ?: '',
    'pass' => getenv('DB_PASS') ?: '',
    'name' => getenv('DB_NAME') ?: '',
];

if ($cfg['host'] === '' && is_file(__DIR__ . '/config.local.php')) {

    $lokal = require __DIR__ . '/config.local.php';

    if (is_array($lokal)) {
        $cfg = array_merge($cfg, $lokal);
    }
}

$conn = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);

if (!$conn) {

    error_log('Koneksi database gagal: ' . mysqli_connect_error());

    http_response_code(500);

    die('Layanan sedang bermasalah. Silakan coba beberapa saat lagi.');
}

mysqli_set_charset($conn, 'utf8mb4');

unset($cfg, $lokal);

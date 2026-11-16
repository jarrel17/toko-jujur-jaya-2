<?php
/* =====================================================================
   KEAMANAN DASAR
   - security headers
   - sesi aman (HttpOnly, Secure, SameSite, strict mode, timeout)
   - proteksi CSRF
   - fungsi bantu escape & validasi input
   Di-include otomatis oleh config/akses.php (dan halaman login/registrasi).
===================================================================== */

@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

header_remove('X-Powered-By');

function https_aktif()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/* ---------- security headers ---------- */

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");
/* tanpa 'unsafe-inline': semua CSS/JS ada di file terpisah (assets/css, assets/js) */
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
     . "style-src 'self'; script-src 'self'; "
     . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

if (https_aktif()) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

/* ---------- sesi aman ---------- */

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name('TJJSESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => https_aktif(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

define('SESI_IDLE_MAKS', 1800);    /* 30 menit tidak aktif  */
define('SESI_TOTAL_MAKS', 28800);  /* 8 jam sejak login     */

function sesi_hancurkan()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/* ---------- escape & format ---------- */

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rp($n)
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function klien_ip()
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function panjang($s)
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

/* ---------- pesan (alert) ---------- */

function alert_html($pesan, $tipe = 'danger')
{
    if ($pesan === '' || $pesan === null) {
        return '';
    }
    return '<div class="alert alert-' . e($tipe) . '">' . e($pesan) . '</div>';
}

function flash_set($pesan, $tipe = 'success')
{
    $_SESSION['flash'] = ['pesan' => $pesan, 'tipe' => $tipe];
}

function flash_tampil()
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return alert_html($f['pesan'], $f['tipe']);
}

/* ---------- CSRF ---------- */

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid()
{
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function tolak_csrf()
{
    http_response_code(419);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Permintaan Tidak Valid</title>'
       . '<link rel="stylesheet" href="/assets/css/error.css">'
       . '</head><body class="halaman-sederhana">'
       . '<h2>Permintaan tidak valid</h2>'
       . '<p>Token keamanan (CSRF) tidak cocok atau sudah kedaluwarsa.<br>Muat ulang halaman lalu coba lagi.</p>'
       . '<p><a href="/" data-kembali>&larr; Kembali</a></p>'
       . '<script src="/assets/js/app.js"></script>'
       . '</body></html>';
    exit;
}

/* Panggil di halaman yang menerima form: otomatis memeriksa token bila request POST */
function csrf_wajib()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !csrf_valid()) {
        tolak_csrf();
    }
}

/* ---------- validasi input ---------- */

/* ambil bilangan bulat positif dari $_POST / $_GET; null bila tidak valid */
function ambil_int($sumber, $kunci, $min = 1, $maks = 1000000000)
{
    $v = $sumber[$kunci] ?? null;

    if (!is_string($v) || !preg_match('/^\d{1,12}$/', trim($v))) {
        return null;
    }

    $n = (int)trim($v);

    return ($n >= $min && $n <= $maks) ? $n : null;
}

function post_int($kunci, $min = 1, $maks = 1000000000)
{
    return ambil_int($_POST, $kunci, $min, $maks);
}

function get_int($kunci, $min = 1, $maks = 1000000000)
{
    return ambil_int($_GET, $kunci, $min, $maks);
}

function post_str($kunci, $maks = 100)
{
    $v = $_POST[$kunci] ?? '';

    if (!is_string($v)) {
        return '';
    }

    $v = trim($v);

    return panjang($v) > $maks ? '' : $v;
}

/* rentang tanggal laporan dari $_GET['tgl1'], $_GET['tgl2'] -> [tgl1, tgl2, error] */
function rentang_tanggal()
{
    $a = $_GET['tgl1'] ?? '';
    $b = $_GET['tgl2'] ?? '';

    if (!is_string($a) || !is_string($b)) {
        return [null, null, 'Format tanggal tidak valid.'];
    }

    if ($a === '' && $b === '') {
        return [null, null, ''];
    }

    if ($a === '' || $b === '') {
        return [null, null, 'Isi tanggal awal dan tanggal akhir.'];
    }

    foreach ([$a, $b] as $t) {
        $d = DateTime::createFromFormat('Y-m-d', $t);
        if (!$d || $d->format('Y-m-d') !== $t) {
            return [null, null, 'Format tanggal tidak valid.'];
        }
    }

    if ($a > $b) {
        return [null, null, 'Tanggal awal tidak boleh melebihi tanggal akhir.'];
    }

    return [$a, $b, ''];
}

/* ---------- ekspor CSV ---------- */

function csv_aman($v)
{
    $v = (string)$v;

    /* cegah CSV/formula injection pada sel teks */
    if ($v !== '' && !is_numeric($v) && strpos("=+-@\t\r", $v[0]) !== false) {
        return "'" . $v;
    }

    return $v;
}

/* pemisah titik koma (;): Excel dengan pengaturan regional Indonesia langsung membagi kolom */
function csv_kirim($namaFile, $kepala, $baris)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');

    echo "\xEF\xBB\xBF";

    $o = fopen('php://output', 'w');

    fputcsv($o, $kepala, ';', '"', '\\');

    foreach ($baris as $b) {
        fputcsv($o, array_map('csv_aman', $b), ';', '"', '\\');
    }

    fclose($o);
    exit;
}

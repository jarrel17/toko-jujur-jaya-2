<?php
include "../../config/akses.php";

cek_akses('user.kelola');

csrf_wajib();

const ROLE_VALID = ['admin', 'gudang', 'pemilik', 'pending'];

$id_saya = (int)($_SESSION['id_user'] ?? 0);

/* ---------- PROSES AKSI (POST + CSRF) ---------- */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $aksi = $_POST['aksi'] ?? '';
    $id   = post_int('id');

    $target = $id ? db_one("SELECT id_user, username, role FROM users WHERE id_user = ?", 'i', [$id]) : null;

    if (!$target) {

        flash_set("User tidak ditemukan.", "danger");

    } elseif ($id === $id_saya) {

        flash_set("Anda tidak dapat mengubah atau menghapus akun Anda sendiri.", "danger");

    } elseif ($aksi === 'ubah_role') {

        $role_baru = $_POST['role'] ?? '';

        if (!in_array($role_baru, ROLE_VALID, true)) {

            flash_set("Role tidak valid.", "danger");

        } elseif (db_exec("UPDATE users SET role = ? WHERE id_user = ?", 'si', [$role_baru, $id])) {

            catat_log('USER_ROLE', $target['username'] . ': ' . $target['role'] . ' -> ' . $role_baru);
            flash_set("Role " . $target['username'] . " diubah menjadi " . $role_baru . ".");

        } else {

            flash_set("Gagal mengubah role.", "danger");
        }

    } elseif ($aksi === 'hapus') {

        if (db_exec("DELETE FROM users WHERE id_user = ?", 'i', [$id])) {

            catat_log('USER_HAPUS', $target['username']);
            flash_set("User " . $target['username'] . " dihapus.");

        } else {

            flash_set("User tidak dapat dihapus karena sudah dipakai pada transaksi.", "danger");
        }

    } else {

        flash_set("Aksi tidak dikenal.", "danger");
    }

    header("Location: index.php");
    exit;
}

$users = db_all("SELECT id_user, username, role FROM users ORDER BY (role = 'pending') DESC, username");

$judul_halaman = "Kelola User";

include "../../templates/header_page.php";
?>

<div class="page-head">

    <div>
        <h1>👥 Kelola User</h1>
        <p>Setujui akun baru dan atur hak akses (role) setiap pengguna.</p>
    </div>

    <div class="page-actions">
        <a class="btn btn-secondary" href="../dashboard/">← Kembali</a>
    </div>

</div>

<div class="card card-flush">

<div class="table-wrap">

<table class="data">

<thead>
<tr>
    <th>No</th>
    <th>Username</th>
    <th>Role</th>
    <th>Ubah Role</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>

<?php

$no = 1;

foreach ($users as $u) {

    $saya = ((int)$u['id_user'] === $id_saya);

    $badge = [
        'admin'   => 'badge-danger',
        'gudang'  => 'badge-info',
        'pemilik' => 'badge-success',
        'pending' => 'badge-warning',
    ][strtolower($u['role'])] ?? 'badge-warning';

?>

<tr>

<td><?= $no++; ?></td>

<td>
    <strong><?= e($u['username']); ?></strong>
    <?php if ($saya) { ?><span class="muted"> (Anda)</span><?php } ?>
</td>

<td><span class="badge <?= $badge; ?>"><?= e($u['role']); ?></span></td>

<td>
    <?php if (!$saya) { ?>
    <form method="POST" class="inline-form">
        <?= csrf_field(); ?>
        <input type="hidden" name="aksi" value="ubah_role">
        <input type="hidden" name="id" value="<?= e($u['id_user']); ?>">
        <select name="role" class="select-sm">
            <?php foreach (ROLE_VALID as $r) { ?>
                <option value="<?= e($r); ?>" <?= strtolower($u['role']) === $r ? 'selected' : ''; ?>>
                    <?= e($r); ?>
                </option>
            <?php } ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
    </form>
    <?php } else { ?>
        <span class="muted">-</span>
    <?php } ?>
</td>

<td>
    <?php if (!$saya) { ?>
    <form method="POST" class="inline-form"
          data-konfirmasi="Hapus user ini?">
        <?= csrf_field(); ?>
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" value="<?= e($u['id_user']); ?>">
        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
    </form>
    <?php } else { ?>
        <span class="muted">-</span>
    <?php } ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php include "../../templates/footer_page.php"; ?>

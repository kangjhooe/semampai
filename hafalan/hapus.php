<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Permintaan tidak valid.');
    redirect('hafalan/riwayat.php');
}

$id = (int) ($_POST['id'] ?? 0);
$returnTo = (string) ($_POST['return'] ?? 'riwayat');
$hafalan = Hafalan::findForUser($id, $userId);

if (!$hafalan) {
    flash('error', 'Setoran tidak ditemukan.');
    redirect('hafalan/riwayat.php');
}

$siswaId = (int) $hafalan['siswa_id'];
$kelasId = (int) $hafalan['kelas_id'];

if (Hafalan::delete($id, $userId)) {
    flash('success', 'Setoran dihapus.');
} else {
    flash('error', 'Gagal menghapus setoran.');
}

if ($returnTo === 'siswa') {
    redirect('hafalan/siswa.php?siswa_id=' . $siswaId);
}

redirect('hafalan/riwayat.php?kelas_id=' . $kelasId);

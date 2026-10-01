<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Permintaan tidak valid.');
    redirect('siswa/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$kelasId = (int) ($_POST['kelas_id'] ?? 0);
$siswa = Siswa::findForUser($id, $userId);

if ($siswa && Siswa::delete($id, $userId)) {
    flash('success', 'Siswa dihapus.');
    redirect('siswa/index.php?kelas_id=' . (int) $siswa['kelas_id']);
}

flash('error', 'Siswa tidak ditemukan.');
redirect($kelasId ? 'siswa/index.php?kelas_id=' . $kelasId : 'siswa/index.php');

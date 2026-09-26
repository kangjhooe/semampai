<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$daftarKelas = Kelas::allForUser($userId);

$kelasId = (int) ($_GET['kelas_id'] ?? 0);
if ($kelasId <= 0 && $daftarKelas !== []) {
    $kelasId = (int) $daftarKelas[0]['id'];
}

$kelas = $kelasId > 0 ? Kelas::findForUser($kelasId, $userId) : null;
if ($kelasId > 0 && !$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('hafalan_rekap.php');
}

$view = (string) ($_GET['view'] ?? 'siswa');
if (!in_array($view, ['siswa', 'surat'], true)) {
    $view = 'siswa';
}

$matrix = $kelas ? HafalanProgress::matrixForKelas((int) $kelas['id'], $userId) : null;

view('hafalan/rekap', [
    'title' => 'Rekap Target Hafalan',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'kelas' => $kelas,
    'matrix' => $matrix,
    'viewMode' => $view,
], 'app');

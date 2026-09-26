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
    redirect('hafalan_riwayat.php');
}

$siswaId = (int) ($_GET['siswa_id'] ?? 0);
$status = (string) ($_GET['status'] ?? '');
if (!in_array($status, ['', 'lancar', 'ulang'], true)) {
    $status = '';
}

$siswaList = $kelas ? Siswa::allForKelas((int) $kelas['id']) : [];
if ($siswaId > 0) {
    $owned = false;
    foreach ($siswaList as $s) {
        if ((int) $s['id'] === $siswaId) {
            $owned = true;
            break;
        }
    }
    if (!$owned) {
        $siswaId = 0;
    }
}

$ringkas = $kelas ? Hafalan::ringkasForKelas((int) $kelas['id'], $userId) : [];
$histori = $kelas ? Hafalan::historiForKelas((int) $kelas['id'], $userId, [
    'siswa_id' => $siswaId,
    'status' => $status,
    'limit' => 200,
]) : [];

view('hafalan/riwayat', [
    'title' => 'Riwayat Hafalan',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'kelas' => $kelas,
    'siswaList' => $siswaList,
    'ringkas' => $ringkas,
    'histori' => $histori,
    'filterSiswaId' => $siswaId,
    'filterStatus' => $status,
], 'app');

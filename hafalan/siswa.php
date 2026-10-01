<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$siswaId = (int) ($_GET['siswa_id'] ?? 0);

$siswa = $siswaId > 0 ? Siswa::findForUser($siswaId, $userId) : null;
if (!$siswa) {
    flash('error', 'Siswa tidak ditemukan.');
    redirect('hafalan/riwayat.php');
}

$histori = Hafalan::historiForSiswa($siswaId, $userId);
$totalLancar = 0;
$totalUlang = 0;
foreach ($histori as $row) {
    if ($row['status'] === 'lancar') {
        $totalLancar++;
    } else {
        $totalUlang++;
    }
}

view('hafalan/siswa', [
    'title' => 'Riwayat · ' . $siswa['nama'],
    'user' => $user,
    'siswa' => $siswa,
    'histori' => $histori,
    'totalLancar' => $totalLancar,
    'totalUlang' => $totalUlang,
], 'app');

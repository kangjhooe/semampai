<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();

$user = Auth::user();
$userId = (int) $user['id'];

$daftarKelas = Kelas::allForUser($userId);
$jumlahKelas = count($daftarKelas);
$jumlahSiswa = 0;
foreach ($daftarKelas as $kelas) {
    $jumlahSiswa += (int) $kelas['jumlah_siswa'];
}

view('dashboard', [
    'title' => 'Beranda',
    'user' => $user,
    'jumlahKelas' => $jumlahKelas,
    'jumlahSiswa' => $jumlahSiswa,
    'setorHariIni' => Hafalan::countTodayForUser($userId),
    'recent' => Hafalan::recentForUser($userId, 6),
    'daftarKelas' => $daftarKelas,
], 'app');

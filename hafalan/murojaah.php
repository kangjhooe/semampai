<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

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
    redirect('hafalan/murojaah.php');
}

$interval = (int) ($_GET['hari'] ?? HafalanMurojaah::DEFAULT_INTERVAL_HARI);
if ($interval < 1 || $interval > 90) {
    $interval = HafalanMurojaah::DEFAULT_INTERVAL_HARI;
}

$due = $kelas
    ? HafalanMurojaah::dueForKelas((int) $kelas['id'], $userId, $interval)
    : [];

view('hafalan/murojaah', [
    'title' => 'Muroja\'ah',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'kelas' => $kelas,
    'due' => $due,
    'interval' => $interval,
], 'app');

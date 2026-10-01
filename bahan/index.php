<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$kelasId = (int) ($_GET['kelas_id'] ?? 0);

if ($kelasId <= 0) {
    $daftarKelas = Kelas::allForUser($userId);
    view('bahan/pilih_kelas', [
        'title' => 'Bahan Ajar',
        'user' => $user,
        'daftarKelas' => $daftarKelas,
    ], 'app');
    exit;
}

$kelas = Kelas::findForUser($kelasId, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('bahan/index.php');
}

$daftar = BahanAjar::allForKelas($kelasId, $userId);

view('bahan/index', [
    'title' => 'Bahan Ajar · ' . $kelas['nama'],
    'user' => $user,
    'kelas' => $kelas,
    'daftar' => $daftar,
], 'app');

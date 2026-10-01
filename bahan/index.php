<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];

$daftarKelas = Kelas::allForUser($userId);
$filterKelasId = (int) ($_GET['kelas_id'] ?? 0);
$filterSumber = (string) ($_GET['sumber'] ?? '');
$filterQ = trim((string) ($_GET['q'] ?? ''));

if ($filterKelasId > 0 && !Kelas::findForUser($filterKelasId, $userId)) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('bahan/index.php');
}

if ($filterSumber !== '' && !in_array($filterSumber, BahanAjar::sumberList(), true)) {
    $filterSumber = '';
}

$daftar = BahanAjar::allForUser($userId, [
    'kelas_id' => $filterKelasId,
    'sumber' => $filterSumber,
    'q' => $filterQ,
]);

view('bahan/index', [
    'title' => 'Bahan Ajar',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'daftar' => $daftar,
    'filterKelasId' => $filterKelasId,
    'filterSumber' => $filterSumber,
    'filterQ' => $filterQ,
], 'app');

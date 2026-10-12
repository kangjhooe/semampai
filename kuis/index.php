<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];

$daftarKelas = Kelas::allForUser($userId);
$filterKelasId = (int) ($_GET['kelas_id'] ?? 0);
$filterQ = trim((string) ($_GET['q'] ?? ''));

if ($filterKelasId > 0 && !Kelas::findForUser($filterKelasId, $userId)) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('kuis/index.php');
}

$daftar = Kuis::allForUser($userId, [
    'kelas_id' => $filterKelasId,
    'q' => $filterQ,
]);

view('kuis/index', [
    'title' => 'Kuis',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'daftar' => $daftar,
    'filterKelasId' => $filterKelasId,
    'filterQ' => $filterQ,
], 'app');

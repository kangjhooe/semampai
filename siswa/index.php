<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$kelasId = (int) ($_GET['kelas_id'] ?? 0);

if ($kelasId <= 0) {
    $daftarKelas = Kelas::allForUser($userId);
    view('siswa/pilih_kelas', [
        'title' => 'Siswa',
        'user' => $user,
        'daftarKelas' => $daftarKelas,
    ], 'app');
    exit;
}

$kelas = Kelas::findForUser($kelasId, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('siswa/index.php');
}

$siswa = Siswa::allForKelas($kelasId);
$importErrors = $_SESSION['_import_errors'] ?? [];
unset($_SESSION['_import_errors']);

view('siswa/index', [
    'title' => 'Siswa · ' . $kelas['nama'],
    'user' => $user,
    'kelas' => $kelas,
    'siswa' => $siswa,
    'importErrors' => $importErrors,
], 'app');

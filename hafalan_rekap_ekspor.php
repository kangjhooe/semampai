<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];
$kelasId = (int) ($_GET['kelas_id'] ?? 0);
$view = (string) ($_GET['view'] ?? 'siswa');
if (!in_array($view, ['siswa', 'surat'], true)) {
    $view = 'siswa';
}

$kelas = Kelas::findForUser($kelasId, $userId);
if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('hafalan_rekap.php');
}

$matrix = HafalanProgress::matrixForKelas($kelasId, $userId);
if (!$matrix) {
    flash('error', 'Target hafalan belum diatur untuk kelas ini.');
    redirect('hafalan_target.php?kelas_id=' . $kelasId);
}

HafalanRekapExporter::download($kelas, $matrix, $view);

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];
$kelasId = (int) ($_GET['kelas_id'] ?? 0);
$kelas = Kelas::findForUser($kelasId, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('hafalan_riwayat.php');
}

$ringkas = Hafalan::ringkasForKelas($kelasId, $userId);
$histori = Hafalan::historiForKelas($kelasId, $userId);

HafalanExporter::downloadKelas($kelas, $ringkas, $histori);

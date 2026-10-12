<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$hasilId = (int) ($_GET['hasil_id'] ?? 0);

$hasil = Kuis::findHasilForUser($hasilId, $userId);
if (!$hasil) {
    flash('error', 'Hasil tidak ditemukan.');
    redirect('kuis/index.php');
}

$kuis = Kuis::findForUser((int) $hasil['kuis_id'], $userId);

view('kuis/hasil_detail', [
    'title' => 'Detail · ' . ($hasil['nama_peserta'] ?? 'Peserta'),
    'user' => $user,
    'kuis' => $kuis,
    'hasil' => $hasil,
], 'app');

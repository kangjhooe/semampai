<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$id = (int) ($_GET['id'] ?? 0);
$sort = (string) ($_GET['sort'] ?? 'peringkat');
if (!in_array($sort, ['peringkat', 'terbaru'], true)) {
    $sort = 'peringkat';
}

$kuis = Kuis::findForUser($id, $userId);
if (!$kuis) {
    flash('error', 'Kuis tidak ditemukan.');
    redirect('kuis/index.php');
}

$hasil = Kuis::hasilForKuis($id, $userId, $sort);
$statistik = Kuis::statistikSoal($id, $userId);

view('kuis/hasil', [
    'title' => 'Hasil · ' . $kuis['judul'],
    'user' => $user,
    'kuis' => $kuis,
    'hasil' => $hasil,
    'sort' => $sort,
    'statistik' => $statistik,
], 'app');

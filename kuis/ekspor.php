<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];
$id = (int) ($_GET['id'] ?? 0);

$kuis = Kuis::findForUser($id, $userId);
if (!$kuis) {
    flash('error', 'Kuis tidak ditemukan.');
    redirect('kuis/index.php');
}

$hasil = Kuis::hasilForKuis($id, $userId, 'peringkat');
KuisExporter::downloadHasil($kuis, $hasil);

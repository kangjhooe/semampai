<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Permintaan tidak valid.');
    redirect('kuis/index.php');
}

$hasilId = (int) ($_POST['hasil_id'] ?? 0);
$kuisId = (int) ($_POST['kuis_id'] ?? 0);

if ($hasilId > 0 && Kuis::deleteHasil($hasilId, $userId)) {
    flash('success', 'Hasil peserta dihapus. Siswa bisa mengerjakan lagi (jika kuis aktif).');
} else {
    flash('error', 'Hasil tidak ditemukan.');
}

redirect($kuisId > 0 ? 'kuis/hasil.php?id=' . $kuisId : 'kuis/index.php');

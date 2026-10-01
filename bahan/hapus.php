<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Permintaan tidak valid.');
    redirect('bahan/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$kelasId = (int) ($_POST['kelas_id'] ?? 0);
$bahan = BahanAjar::findForUser($id, $userId);

if ($bahan && BahanAjar::delete($id, $userId)) {
    flash('success', 'Bahan ajar dihapus.');
    redirect('bahan/index.php?kelas_id=' . (int) $bahan['kelas_id']);
}

flash('error', 'Bahan ajar tidak ditemukan.');
redirect($kelasId ? 'bahan/index.php?kelas_id=' . $kelasId : 'bahan/index.php');

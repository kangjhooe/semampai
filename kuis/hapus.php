<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Permintaan tidak valid.');
    redirect('kuis/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0 && Kuis::delete($id, $userId)) {
    flash('success', 'Kuis dihapus.');
    redirect('kuis/index.php');
}

flash('error', 'Kuis tidak ditemukan.');
redirect('kuis/index.php');

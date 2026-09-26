<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Permintaan tidak valid.');
    redirect('kelas.php');
}

$id = (int) ($_POST['id'] ?? 0);

if (Kelas::delete($id, $userId)) {
    flash('success', 'Kelas dan seluruh siswanya dihapus.');
} else {
    flash('error', 'Kelas tidak ditemukan.');
}

redirect('kelas.php');

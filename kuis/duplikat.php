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
$kelasRaw = trim((string) ($_POST['kelas_id'] ?? ''));
$kelasId = $kelasRaw === '' ? null : (int) $kelasRaw;

$result = Kuis::duplicate($id, $userId, $kelasId);
if ($result['ok']) {
    flash('success', 'Kuis diduplikasi. Kode baru: ' . $result['kode'] . '. Status nonaktif — aktifkan setelah dicek.');
    redirect('kuis/form.php?id=' . $result['id']);
}

flash('error', $result['errors']['_form'] ?? 'Gagal menduplikasi.');
redirect('kuis/index.php');

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$id = (int) ($_GET['id'] ?? 0);
$kelas = Kelas::findForUser($id, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('kelas/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('kelas/edit.php?id=' . $id);
    }

    store_old($_POST);
    $result = Kelas::update($id, $userId, (string) ($_POST['nama'] ?? ''), (string) ($_POST['tahun_ajaran'] ?? ''));

    if ($result['ok']) {
        clear_old();
        flash('success', 'Kelas berhasil diperbarui.');
        redirect('kelas/index.php');
    }

    $errors = $result['errors'];
}

view('kelas/edit', [
    'title' => 'Edit Kelas',
    'user' => $user,
    'kelas' => $kelas,
    'errors' => $errors,
], 'app');

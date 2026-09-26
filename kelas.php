<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('kelas.php');
    }

    store_old($_POST);
    $result = Kelas::create($userId, (string) ($_POST['nama'] ?? ''), (string) ($_POST['tahun_ajaran'] ?? ''));

    if ($result['ok']) {
        clear_old();
        flash('success', 'Kelas berhasil ditambahkan.');
        redirect('kelas.php');
    }

    $errors = $result['errors'];
}

$daftarKelas = Kelas::allForUser($userId);

view('kelas/index', [
    'title' => 'Pengelola Kelas',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'errors' => $errors,
], 'app');

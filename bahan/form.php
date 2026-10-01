<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$kelasId = (int) ($_GET['kelas_id'] ?? $_POST['kelas_id'] ?? 0);
$kelas = Kelas::findForUser($kelasId, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('bahan/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('bahan/form.php?kelas_id=' . $kelasId);
    }

    store_old($_POST);
    $payload = [
        'judul' => (string) ($_POST['judul'] ?? ''),
        'sumber' => (string) ($_POST['sumber'] ?? ''),
        'original_url' => (string) ($_POST['original_url'] ?? ''),
    ];
    $file = $_FILES['file'] ?? null;

    $result = BahanAjar::create($kelasId, $userId, $payload, $file);

    if ($result['ok']) {
        clear_old();
        flash('success', 'Bahan ajar berhasil ditambahkan.');
        redirect('bahan/index.php?kelas_id=' . $kelasId);
    }

    $errors = $result['errors'];
}

view('bahan/form', [
    'title' => 'Tambah Bahan Ajar',
    'user' => $user,
    'kelas' => $kelas,
    'errors' => $errors,
], 'app');

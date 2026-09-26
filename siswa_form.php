<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$kelasId = (int) ($_GET['kelas_id'] ?? $_POST['kelas_id'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);
$kelas = Kelas::findForUser($kelasId, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('siswa.php');
}

$siswa = null;
if ($id > 0) {
    $siswa = Siswa::findForUser($id, $userId);
    if (!$siswa || (int) $siswa['kelas_id'] !== $kelasId) {
        flash('error', 'Siswa tidak ditemukan.');
        redirect('siswa.php?kelas_id=' . $kelasId);
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('siswa_form.php?kelas_id=' . $kelasId . ($id ? '&id=' . $id : ''));
    }

    store_old($_POST);
    $payload = [
        'nama' => (string) ($_POST['nama'] ?? ''),
        'nisn' => (string) ($_POST['nisn'] ?? ''),
        'tempat_lahir' => (string) ($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => (string) ($_POST['tanggal_lahir'] ?? ''),
    ];

    $result = $siswa
        ? Siswa::update($id, $userId, $payload)
        : Siswa::create($kelasId, $payload);

    if ($result['ok']) {
        clear_old();
        flash('success', $siswa ? 'Siswa berhasil diperbarui.' : 'Siswa berhasil ditambahkan.');
        redirect('siswa.php?kelas_id=' . $kelasId);
    }

    $errors = $result['errors'];
}

view('siswa/form', [
    'title' => $siswa ? 'Edit Siswa' : 'Tambah Siswa',
    'user' => $user,
    'kelas' => $kelas,
    'siswa' => $siswa,
    'errors' => $errors,
], 'app');

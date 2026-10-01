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
    redirect('siswa/index.php');
}

if (isset($_GET['template'])) {
    SiswaImporter::downloadTemplate($kelas['nama']);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('siswa/import.php?kelas_id=' . $kelasId);
    }

    $file = $_FILES['file'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors['_form'] = 'Pilih file Excel terlebih dahulu.';
    } else {
        $result = SiswaImporter::import($kelasId, $userId, $file['tmp_name'], $file['name']);
        $_SESSION['_import_errors'] = array_slice($result['errors'], 0, 20);

        if ($result['imported'] > 0) {
            $msg = $result['imported'] . ' siswa berhasil diimpor.';
            if ($result['skipped'] > 0) {
                $msg .= ' ' . $result['skipped'] . ' baris dilewati.';
            }
            flash('success', $msg);
            redirect('siswa/index.php?kelas_id=' . $kelasId);
        }

        $errors['_form'] = $result['errors'][0] ?? 'Tidak ada siswa yang diimpor.';
        $_SESSION['_import_errors'] = array_slice($result['errors'], 0, 20);
    }
}

view('siswa/import', [
    'title' => 'Import Siswa',
    'user' => $user,
    'kelas' => $kelas,
    'errors' => $errors,
], 'app');

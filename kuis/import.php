<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$kuis = Kuis::findForUser($id, $userId);
if (!$kuis) {
    flash('error', 'Kuis tidak ditemukan.');
    redirect('kuis/index.php');
}

if (isset($_GET['template'])) {
    KuisSoalImporter::downloadTemplate((string) $kuis['judul']);
}

$errors = [];
$importErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('kuis/import.php?id=' . $id);
    }

    $file = $_FILES['file'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors['_form'] = 'Pilih file Excel terlebih dahulu.';
    } else {
        $result = KuisSoalImporter::import($id, $userId, (string) $file['tmp_name'], (string) $file['name']);
        $importErrors = array_slice($result['errors'], 0, 30);

        if ($result['imported'] > 0) {
            $msg = $result['imported'] . ' soal berhasil diimpor.';
            if ($result['skipped'] > 0) {
                $msg .= ' ' . $result['skipped'] . ' baris dilewati.';
            }
            flash('success', $msg);
            if ($importErrors !== []) {
                $_SESSION['_kuis_import_errors'] = $importErrors;
            }
            redirect('kuis/form.php?id=' . $id);
        }

        $errors['_form'] = $result['errors'][0] ?? 'Tidak ada soal yang diimpor.';
        $importErrors = array_slice($result['errors'], 0, 30);
    }
}

view('kuis/import', [
    'title' => 'Impor Soal',
    'user' => $user,
    'kuis' => $kuis,
    'errors' => $errors,
    'importErrors' => $importErrors,
], 'app');

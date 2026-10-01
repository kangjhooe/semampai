<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$daftarKelas = Kelas::allForUser($userId);

if ($daftarKelas === []) {
    flash('error', 'Buat kelas terlebih dahulu.');
    redirect('kelas/index.php');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bahan = null;
if ($id > 0) {
    $bahan = BahanAjar::findForUser($id, $userId);
    if (!$bahan) {
        flash('error', 'Bahan ajar tidak ditemukan.');
        redirect('bahan/index.php');
    }
}

$kelasId = (int) ($_GET['kelas_id'] ?? $_POST['kelas_id'] ?? 0);
if ($kelasId <= 0) {
    $kelasId = $bahan ? (int) $bahan['kelas_id'] : (int) $daftarKelas[0]['id'];
}

$kelas = Kelas::findForUser($kelasId, $userId);
if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('bahan/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('bahan/form.php' . ($bahan ? '?id=' . $id : '?kelas_id=' . $kelasId));
    }

    store_old($_POST);
    $kelasId = (int) ($_POST['kelas_id'] ?? 0);
    $kelas = Kelas::findForUser($kelasId, $userId);

    if (!$kelas) {
        $errors['_form'] = 'Kelas tidak valid.';
        $kelas = Kelas::findForUser(
            $bahan ? (int) $bahan['kelas_id'] : (int) $daftarKelas[0]['id'],
            $userId
        );
        $kelasId = (int) ($kelas['id'] ?? 0);
    } else {
        $payload = [
            'kelas_id' => $kelasId,
            'judul' => (string) ($_POST['judul'] ?? ''),
            'sumber' => (string) ($_POST['sumber'] ?? ''),
            'original_url' => (string) ($_POST['original_url'] ?? ''),
        ];
        $file = $_FILES['file'] ?? null;

        $result = $bahan
            ? BahanAjar::update($id, $userId, $payload, $file)
            : BahanAjar::create($kelasId, $userId, $payload, $file);

        if ($result['ok']) {
            clear_old();
            flash('success', $bahan ? 'Bahan ajar berhasil diperbarui.' : 'Bahan ajar berhasil ditambahkan.');
            redirect('bahan/index.php');
        }

        $errors = $result['errors'];
    }
}

view('bahan/form', [
    'title' => $bahan ? 'Edit Bahan Ajar' : 'Tambah Bahan Ajar',
    'user' => $user,
    'kelas' => $kelas,
    'daftarKelas' => $daftarKelas,
    'bahan' => $bahan,
    'errors' => $errors,
], 'app');

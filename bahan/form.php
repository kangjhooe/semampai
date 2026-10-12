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

/** @var list<int> $selectedKelasIds */
$selectedKelasIds = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = $_POST['kelas_ids'] ?? [];
    if (is_array($raw)) {
        foreach ($raw as $value) {
            $kid = (int) $value;
            if ($kid > 0) {
                $selectedKelasIds[] = $kid;
            }
        }
    }
} elseif ($bahan) {
    $selectedKelasIds = array_map('intval', $bahan['kelas_ids'] ?? []);
} else {
    $prefill = (int) ($_GET['kelas_id'] ?? 0);
    if ($prefill > 0 && Kelas::findForUser($prefill, $userId)) {
        $selectedKelasIds = [$prefill];
    } elseif ($daftarKelas !== []) {
        $selectedKelasIds = [(int) $daftarKelas[0]['id']];
    }
}
$selectedKelasIds = array_values(array_unique($selectedKelasIds));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('bahan/form.php' . ($bahan ? '?id=' . $id : ''));
    }

    store_old($_POST);

    $payload = [
        'kelas_ids' => $selectedKelasIds,
        'judul' => (string) ($_POST['judul'] ?? ''),
        'sumber' => (string) ($_POST['sumber'] ?? ''),
        'original_url' => (string) ($_POST['original_url'] ?? ''),
        'kuis_id' => (int) ($_POST['kuis_id'] ?? 0),
    ];
    $file = $_FILES['file'] ?? null;

    $result = $bahan
        ? BahanAjar::update($id, $userId, $payload, $file)
        : BahanAjar::create($userId, $payload, $file);

    if ($result['ok']) {
        clear_old();
        flash('success', $bahan ? 'Bahan ajar berhasil diperbarui.' : 'Bahan ajar berhasil ditambahkan.');
        redirect('bahan/index.php');
    }

    $errors = $result['errors'];
}

view('bahan/form', [
    'title' => $bahan ? 'Edit Bahan Ajar' : 'Tambah Bahan Ajar',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'daftarKuis' => Kuis::allForUser($userId),
    'selectedKelasIds' => $selectedKelasIds,
    'bahan' => $bahan,
    'errors' => $errors,
], 'app');

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$daftarKelas = Kelas::allForUser($userId);
$errors = [];

$kelasId = (int) ($_GET['kelas_id'] ?? $_POST['kelas_id'] ?? 0);
if ($kelasId <= 0 && $daftarKelas !== []) {
    $kelasId = (int) $daftarKelas[0]['id'];
}

$kelas = $kelasId > 0 ? Kelas::findForUser($kelasId, $userId) : null;
if ($kelasId > 0 && !$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('hafalan_target.php');
}

$target = $kelas ? HafalanTarget::findByKelas((int) $kelas['id'], $userId) : null;
$selected = $target ? array_map(static fn ($i) => (int) $i['surat_nomor'], $target['items']) : [];
$judul = $target['judul'] ?? 'Target hafalan';

if (isset($_GET['template']) && $_GET['template'] === 'juz30' && $kelas) {
    $selected = HafalanTarget::templateJuzAmmaPendek(10);
    $judul = 'Juz 30 (10 surat pendek)';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kelas) {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('hafalan_target.php?kelas_id=' . $kelasId);
    }

    $judul = (string) ($_POST['judul'] ?? 'Target hafalan');
    $selected = array_map('intval', $_POST['surat'] ?? []);
    $result = HafalanTarget::saveForKelas($kelasId, $userId, $judul, $selected);

    if ($result['ok']) {
        flash('success', 'Target hafalan kelas disimpan.');
        redirect('hafalan_rekap.php?kelas_id=' . $kelasId);
    }

    $errors = $result['errors'];
}

view('hafalan/target', [
    'title' => 'Target Hafalan',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'kelas' => $kelas,
    'surahList' => Surah::all(),
    'selected' => $selected,
    'judul' => $judul,
    'errors' => $errors,
], 'app');

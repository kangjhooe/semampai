<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$hafalan = $id > 0 ? Hafalan::findForUser($id, $userId) : null;
if (!$hafalan) {
    flash('error', 'Setoran tidak ditemukan.');
    redirect('hafalan/riwayat.php');
}

$kelasId = (int) $hafalan['kelas_id'];
$kelas = Kelas::findForUser($kelasId, $userId);
$siswaList = Siswa::allForKelas($kelasId, $userId);
$errors = [];

$returnTo = (string) ($_GET['return'] ?? $_POST['return'] ?? '');
if (!in_array($returnTo, ['siswa', 'riwayat'], true)) {
    $returnTo = 'riwayat';
}

if (isset($_GET['tampil']) && in_array($_GET['tampil'], ['arab', 'arab_terjemah'], true)) {
    $_SESSION['hafalan_tampil'] = $_GET['tampil'];
}
$tampilMode = $_SESSION['hafalan_tampil'] ?? 'arab_terjemah';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('hafalan/edit.php?id=' . $id . '&return=' . $returnTo);
    }

    if (isset($_POST['tampil']) && in_array($_POST['tampil'], ['arab', 'arab_terjemah'], true)) {
        $_SESSION['hafalan_tampil'] = $_POST['tampil'];
        $tampilMode = $_POST['tampil'];
    }

    store_old($_POST);
    $result = Hafalan::update($id, $userId, [
        'siswa_id' => (int) ($_POST['siswa_id'] ?? 0),
        'surat_nomor' => (int) ($_POST['surat_nomor'] ?? 0),
        'ayat' => $_POST['ayat'] ?? [],
        'status' => (string) ($_POST['status'] ?? ''),
        'catatan' => (string) ($_POST['catatan'] ?? ''),
    ]);

    if ($result['ok']) {
        clear_old();
        flash('success', 'Setoran diperbarui · ' . $result['label']);

        if ($returnTo === 'siswa') {
            redirect('hafalan/siswa.php?siswa_id=' . (int) $result['siswa']['id']);
        }

        redirect('hafalan/riwayat.php?kelas_id=' . (int) $result['siswa']['kelas_id']);
    }

    $errors = $result['errors'];
}

$selectedSurat = (int) ($_SESSION['_old']['surat_nomor'] ?? $hafalan['surat_nomor']);
if ($selectedSurat < 1 || $selectedSurat > 114) {
    $selectedSurat = (int) $hafalan['surat_nomor'];
}

$selectedSiswa = (string) ($_SESSION['_old']['siswa_id'] ?? $hafalan['siswa_id']);

$selectedAyat = [];
if (isset($_SESSION['_old']['ayat']) && is_array($_SESSION['_old']['ayat'])) {
    $selectedAyat = array_map('intval', $_SESSION['_old']['ayat']);
} else {
    for ($n = (int) $hafalan['ayat_awal']; $n <= (int) $hafalan['ayat_akhir']; $n++) {
        $selectedAyat[] = $n;
    }
}

$suratData = QuranApi::getSurat($selectedSurat);

view('hafalan/edit', [
    'title' => 'Edit Setoran',
    'user' => $user,
    'hafalan' => $hafalan,
    'kelas' => $kelas,
    'siswaList' => $siswaList,
    'surahList' => Surah::all(),
    'suratData' => $suratData,
    'selectedSurat' => $selectedSurat,
    'selectedSiswa' => $selectedSiswa,
    'selectedAyat' => $selectedAyat,
    'tampilMode' => $tampilMode,
    'apiSuratUrl' => app_url('api/surat.php'),
    'errors' => $errors,
    'returnTo' => $returnTo,
], 'app');

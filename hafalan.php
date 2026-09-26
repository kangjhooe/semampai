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
    redirect('hafalan.php');
}

$siswaList = $kelas ? Siswa::allForKelas((int) $kelas['id']) : [];

// Preferensi tampilan: arab | arab_terjemah
if (isset($_GET['tampil']) && in_array($_GET['tampil'], ['arab', 'arab_terjemah'], true)) {
    $_SESSION['hafalan_tampil'] = $_GET['tampil'];
}
$tampilMode = $_SESSION['hafalan_tampil'] ?? 'arab_terjemah';

// Prefill dari rekap / deep-link
$prefillSiswaId = (int) ($_GET['siswa_id'] ?? 0);
$prefillSurat = (int) ($_GET['surat'] ?? 0);
$returnRekap = isset($_GET['from']) && $_GET['from'] === 'rekap';

if ($prefillSiswaId > 0) {
    $validSiswa = false;
    foreach ($siswaList as $s) {
        if ((int) $s['id'] === $prefillSiswaId) {
            $validSiswa = true;
            break;
        }
    }
    if (!$validSiswa) {
        $prefillSiswaId = 0;
    }
}
if ($prefillSurat < 1 || $prefillSurat > 114 || !Surah::find($prefillSurat)) {
    $prefillSurat = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('hafalan.php?kelas_id=' . $kelasId);
    }

    if (isset($_POST['tampil']) && in_array($_POST['tampil'], ['arab', 'arab_terjemah'], true)) {
        $_SESSION['hafalan_tampil'] = $_POST['tampil'];
        $tampilMode = $_POST['tampil'];
    }

    $returnRekap = isset($_POST['from_rekap']) && $_POST['from_rekap'] === '1';

    store_old($_POST);
    $result = Hafalan::create($userId, [
        'siswa_id' => (int) ($_POST['siswa_id'] ?? 0),
        'surat_nomor' => (int) ($_POST['surat_nomor'] ?? 0),
        'ayat' => $_POST['ayat'] ?? [],
        'status' => (string) ($_POST['status'] ?? ''),
        'catatan' => (string) ($_POST['catatan'] ?? ''),
    ]);

    if ($result['ok']) {
        clear_old();
        $nama = $result['siswa']['nama'] ?? 'Siswa';
        flash('success', $nama . ' · ' . $result['label']);

        $sid = (int) ($result['siswa']['id'] ?? 0);
        $sn = (int) ($_POST['surat_nomor'] ?? 0);

        if ($returnRekap) {
            redirect('hafalan_rekap.php?kelas_id=' . $kelasId . '&view=siswa');
        }

        // Lanjut menyimak siswa & surat yang sama
        redirect('hafalan.php?kelas_id=' . $kelasId . '&siswa_id=' . $sid . '&surat=' . $sn);
    }

    $errors = $result['errors'];
}

$selectedSurat = (int) ($_SESSION['_old']['surat_nomor'] ?? 0);
if ($selectedSurat < 1) {
    $selectedSurat = $prefillSurat > 0 ? $prefillSurat : 1;
}
if ($selectedSurat < 1 || $selectedSurat > 114) {
    $selectedSurat = 1;
}

$selectedSiswa = (string) ($_SESSION['_old']['siswa_id'] ?? '');
if ($selectedSiswa === '' && $prefillSiswaId > 0) {
    $selectedSiswa = (string) $prefillSiswaId;
}

$selectedAyat = [];
if (isset($_SESSION['_old']['ayat']) && is_array($_SESSION['_old']['ayat'])) {
    $selectedAyat = array_map('intval', $_SESSION['_old']['ayat']);
} elseif ($prefillSiswaId > 0 && $prefillSurat > 0) {
    $cell = HafalanProgress::cellForSiswaSurat($prefillSiswaId, $prefillSurat, $userId);
    if ($cell && $cell['status'] === 'proses') {
        // Sudah setor 1–N: centang yang sudah lancar (bukan kosong / bukan sisa saja)
        $selectedAyat = HafalanProgress::coveredPrefix($cell['covered_ayat']);
    } elseif ($cell && $cell['status'] === 'belum') {
        $selectedAyat = HafalanProgress::firstContiguousMissing($cell['missing']);
    }
}

$suratData = QuranApi::getSurat($selectedSurat);
$recent = $kelas ? Hafalan::recentForKelas((int) $kelas['id'], $userId) : [];

view('hafalan/form', [
    'title' => 'Setoran Hafalan',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'kelas' => $kelas,
    'siswaList' => $siswaList,
    'surahList' => Surah::all(),
    'suratData' => $suratData,
    'selectedSurat' => $selectedSurat,
    'selectedSiswa' => $selectedSiswa,
    'selectedAyat' => $selectedAyat,
    'tampilMode' => $tampilMode,
    'apiSuratUrl' => app_url('api_surat.php'),
    'recent' => $recent,
    'errors' => $errors,
    'fromRekap' => $returnRekap,
    'prefillHint' => $prefillSiswaId > 0 && $prefillSurat > 0,
], 'app');

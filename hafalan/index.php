<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

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
    redirect('hafalan/index.php');
}

$siswaList = $kelas ? Siswa::allForKelas((int) $kelas['id'], $userId) : [];

// Preferensi tampilan: arab | arab_terjemah
if (isset($_GET['tampil']) && in_array($_GET['tampil'], ['arab', 'arab_terjemah'], true)) {
    $_SESSION['hafalan_tampil'] = $_GET['tampil'];
}
$tampilMode = $_SESSION['hafalan_tampil'] ?? 'arab_terjemah';

// Preferensi mode fokus
if (isset($_GET['fokus'])) {
    $_SESSION['hafalan_fokus'] = $_GET['fokus'] === '1' ? '1' : '0';
}
$fokusMode = ($_SESSION['hafalan_fokus'] ?? '0') === '1';

// Prefill dari rekap / murojaah / deep-link
$prefillSiswaId = (int) ($_GET['siswa_id'] ?? 0);
$prefillSurat = (int) ($_GET['surat'] ?? 0);
$returnRekap = isset($_GET['from']) && $_GET['from'] === 'rekap';
$jenis = Hafalan::normalizeJenis($_GET['jenis'] ?? $_POST['jenis'] ?? 'setoran');

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
        redirect('hafalan/index.php?kelas_id=' . $kelasId);
    }

    if (isset($_POST['tampil']) && in_array($_POST['tampil'], ['arab', 'arab_terjemah'], true)) {
        $_SESSION['hafalan_tampil'] = $_POST['tampil'];
        $tampilMode = $_POST['tampil'];
    }

    if (isset($_POST['fokus'])) {
        $_SESSION['hafalan_fokus'] = $_POST['fokus'] === '1' ? '1' : '0';
        $fokusMode = $_SESSION['hafalan_fokus'] === '1';
    }

    $returnRekap = isset($_POST['from_rekap']) && $_POST['from_rekap'] === '1';
    $jenis = Hafalan::normalizeJenis($_POST['jenis'] ?? 'setoran');
    $advanceNext = isset($_POST['advance_next']) && $_POST['advance_next'] === '1';

    store_old($_POST);
    $result = Hafalan::create($userId, [
        'siswa_id' => (int) ($_POST['siswa_id'] ?? 0),
        'surat_nomor' => (int) ($_POST['surat_nomor'] ?? 0),
        'ayat' => $_POST['ayat'] ?? [],
        'status' => (string) ($_POST['status'] ?? ''),
        'catatan' => (string) ($_POST['catatan'] ?? ''),
        'jenis' => $jenis,
    ]);

    if ($result['ok']) {
        clear_old();
        $nama = $result['siswa']['nama'] ?? 'Siswa';
        flash('success', $nama . ' · ' . $result['label']);

        $sid = (int) ($result['siswa']['id'] ?? 0);
        $sn = (int) ($_POST['surat_nomor'] ?? 0);

        if ($returnRekap) {
            redirect('hafalan/rekap.php?kelas_id=' . $kelasId . '&view=siswa');
        }

        $qs = 'kelas_id=' . $kelasId;
        if ($fokusMode) {
            $qs .= '&fokus=1';
        }

        if ($advanceNext && $kelas) {
            $sudah = HafalanInsight::siswaSetorHariIni((int) $kelas['id'], $userId);
            $sudah[$sid] = true;
            $nextId = Hafalan::nextSiswaId($siswaList, $sid, $sudah);
            if ($nextId > 0) {
                redirect('hafalan/index.php?' . $qs . '&siswa_id=' . $nextId . '&jenis=setoran');
            }
        }

        // Lanjut menyimak siswa & surat yang sama
        $qs .= '&siswa_id=' . $sid . '&surat=' . $sn . '&jenis=' . urlencode($jenis);
        redirect('hafalan/index.php?' . $qs);
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
} elseif ($jenis === 'murojaah' && $prefillSurat > 0) {
    $surah = Surah::find($prefillSurat);
    if ($surah) {
        $selectedAyat = range(1, (int) $surah['ayat']);
    }
} elseif ($prefillSiswaId > 0 && $prefillSurat > 0) {
    $cell = HafalanProgress::cellForSiswaSurat($prefillSiswaId, $prefillSurat, $userId);
    // Model kejadian: centang sisa yang belum lancar (bukan yang sudah).
    if ($cell && in_array($cell['status'], ['proses', 'belum'], true)) {
        $selectedAyat = HafalanProgress::firstContiguousMissing($cell['missing']);
    }
}

$suratData = QuranApi::getSurat($selectedSurat);
$recent = $kelas ? Hafalan::recentForKelas((int) $kelas['id'], $userId) : [];
$setorHariIni = $kelas ? HafalanInsight::siswaSetorHariIni((int) $kelas['id'], $userId) : [];

view('hafalan/form', [
    'title' => $jenis === 'murojaah' ? 'Muroja\'ah' : 'Setoran Hafalan',
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
    'fokusMode' => $fokusMode,
    'jenis' => $jenis,
    'setorHariIni' => $setorHariIni,
    'apiSuratUrl' => app_url('api/surat.php'),
    'recent' => $recent,
    'errors' => $errors,
    'fromRekap' => $returnRekap,
    'prefillHint' => $prefillSiswaId > 0 && $prefillSurat > 0 && $selectedAyat !== [],
], 'app');

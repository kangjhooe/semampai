<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$prefillPin = KuisLive::normalizePin((string) ($_GET['pin'] ?? ''));
$errors = [];
$previewSesi = null;
$previewKuis = null;
$roster = [];

if (isset($_GET['batal'])) {
    unset($_SESSION['kuis_live_token'], $_SESSION['kuis_live_sesi_id']);
    redirect('kuis/live_join.php' . ($prefillPin !== '' ? '?pin=' . urlencode($prefillPin) : ''));
}

if ($prefillPin !== '' && strlen($prefillPin) === 6) {
    $previewSesi = KuisLive::findByPin($prefillPin);
    if ($previewSesi) {
        $previewKuis = Kuis::findById((int) $previewSesi['kuis_id']);
        if ($previewKuis) {
            $roster = Kuis::rosterSiswa((int) $previewKuis['id']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('kuis/live_join.php' . ($prefillPin !== '' ? '?pin=' . urlencode($prefillPin) : ''));
    }

    $pin = KuisLive::normalizePin((string) ($_POST['pin'] ?? ''));
    $siswaId = (int) ($_POST['siswa_id'] ?? 0);
    $nama = trim((string) ($_POST['nama'] ?? ''));
    store_old(['pin' => $pin, 'nama' => $nama, 'siswa_id' => (string) $siswaId]);

    if (strlen($pin) !== 6) {
        $errors['pin'] = 'PIN harus 6 digit.';
    }

    $sesi = $errors === [] ? KuisLive::findByPin($pin) : null;
    if ($errors === [] && !$sesi) {
        $errors['pin'] = 'PIN tidak ditemukan atau sesi sudah berakhir.';
    }

    if ($errors === [] && $sesi) {
        $previewSesi = $sesi;
        $previewKuis = Kuis::findById((int) $sesi['kuis_id']);
        $roster = $previewKuis ? Kuis::rosterSiswa((int) $previewKuis['id']) : [];

        $result = KuisLive::join(
            (int) $sesi['id'],
            $nama,
            $roster !== [] ? $siswaId : null
        );

        if (!$result['ok']) {
            $errors = $result['errors'];
        } else {
            clear_old();
            $_SESSION['kuis_live_token'] = $result['token'];
            $_SESSION['kuis_live_sesi_id'] = (int) $sesi['id'];
            redirect('kuis/live_play.php');
        }
    }

    $prefillPin = $pin;
}

// Sudah punya token sesi aktif → langsung play
$token = (string) ($_SESSION['kuis_live_token'] ?? '');
if ($token !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $snap = KuisLive::snapshotForPlayer($token);
    if ($snap && ($snap['status'] ?? '') !== 'ended') {
        redirect('kuis/live_play.php');
    }
}

view('kuis/live_join', [
    'title' => 'Gabung Live Kuis',
    'prefillPin' => $prefillPin,
    'errors' => $errors,
    'previewSesi' => $previewSesi,
    'previewKuis' => $previewKuis,
    'roster' => $roster,
]);

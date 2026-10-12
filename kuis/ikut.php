<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$prefillKode = Kuis::normalizeKode((string) ($_GET['kode'] ?? ''));
$errors = [];
$previewKuis = null;
$roster = [];

if (isset($_GET['batal'])) {
    unset($_SESSION['kuis_play'], $_SESSION['kuis_result']);
    redirect('kuis/ikut.php' . ($prefillKode !== '' ? '?kode=' . urlencode($prefillKode) : ''));
}

if ($prefillKode !== '') {
    $previewKuis = Kuis::findByKode($prefillKode);
    if ($previewKuis && (int) $previewKuis['aktif'] === 1) {
        $roster = Kuis::rosterSiswa((int) $previewKuis['id']);
    } else {
        $previewKuis = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid. Coba lagi.');
        redirect('kuis/ikut.php' . ($prefillKode !== '' ? '?kode=' . urlencode($prefillKode) : ''));
    }

    $kode = Kuis::normalizeKode((string) ($_POST['kode'] ?? ''));
    $siswaId = (int) ($_POST['siswa_id'] ?? 0);
    $nama = trim((string) ($_POST['nama'] ?? ''));
    store_old(['nama' => $nama, 'kode' => $kode, 'siswa_id' => (string) $siswaId]);

    if ($kode === '') {
        $errors['kode'] = 'ID kuis wajib diisi.';
    }

    $kuis = $errors === [] ? Kuis::findByKode($kode) : null;
    if ($errors === [] && (!$kuis || !(int) $kuis['aktif'])) {
        $errors['kode'] = 'Kuis tidak ditemukan atau tidak aktif.';
    }

    if ($errors === [] && $kuis) {
        $roster = Kuis::rosterSiswa((int) $kuis['id']);
        $previewKuis = $kuis;
        $resolvedSiswaId = null;

        if ($roster !== []) {
            if ($siswaId <= 0) {
                $errors['siswa_id'] = 'Pilih nama Anda dari daftar kelas.';
            } else {
                $siswa = Kuis::findSiswaInKuisKelas((int) $kuis['id'], $siswaId);
                if (!$siswa) {
                    $errors['siswa_id'] = 'Siswa tidak valid untuk kuis ini.';
                } else {
                    $nama = (string) $siswa['nama'];
                    $resolvedSiswaId = (int) $siswa['id'];
                }
            }
        } else {
            if ($nama === '') {
                $errors['nama'] = 'Nama wajib diisi.';
            } elseif (mb_strlen($nama) > 120) {
                $errors['nama'] = 'Nama maksimal 120 karakter.';
            }
        }
    }

    if ($errors === [] && $kuis) {
        if (!Kuis::canAttempt((int) $kuis['id'], $nama, $resolvedSiswaId ?? null)) {
            $errors['_form'] = 'Anda sudah mengerjakan kuis ini. Pengulangan tidak diizinkan.';
        }
    }

    if ($errors === [] && $kuis) {
        $soal = Kuis::soalUntukPeserta(
            (int) $kuis['id'],
            (bool) $kuis['acak_soal'],
            (bool) ($kuis['acak_opsi'] ?? 0)
        );
        if ($soal === []) {
            $errors['kode'] = 'Kuis ini belum siap dikerjakan.';
        } else {
            clear_old();
            $startedAt = time();
            $timerMenit = isset($kuis['timer_menit']) && $kuis['timer_menit'] !== null
                ? (int) $kuis['timer_menit']
                : 0;
            $endsAt = $timerMenit > 0 ? $startedAt + ($timerMenit * 60) : 0;

            $stepMode = Kuis::needsStepMode($kuis, $soal);
            $_SESSION['kuis_play'] = [
                'kuis_id' => (int) $kuis['id'],
                'kode' => (string) $kuis['kode'],
                'judul' => (string) $kuis['judul'],
                'nama' => $nama,
                'siswa_id' => $resolvedSiswaId ?? null,
                'soal' => $soal,
                'started_at' => $startedAt,
                'timer_menit' => $timerMenit,
                'ends_at' => $endsAt,
                'step_mode' => $stepMode,
                'mode_latihan' => (int) ($kuis['mode_latihan'] ?? 0) === 1,
                'index' => 0,
                'answers' => [],
                'feedback' => null,
                'soal_ends_at' => 0,
                'soal_timer_for' => -1,
            ];
            unset($_SESSION['kuis_result']);
            redirect('kuis/main.php');
        }
    }

    $prefillKode = $kode;
    if ($kuis ?? null) {
        $previewKuis = $kuis;
        $roster = Kuis::rosterSiswa((int) $kuis['id']);
    }
}

view('kuis/ikut', [
    'title' => 'Ikuti Kuis',
    'prefillKode' => $prefillKode,
    'errors' => $errors,
    'previewKuis' => $previewKuis,
    'roster' => $roster,
]);

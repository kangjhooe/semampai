<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$play = $_SESSION['kuis_play'] ?? null;
if (!is_array($play) || empty($play['kuis_id']) || empty($play['soal']) || empty($play['nama'])) {
    flash('error', 'Mulai dari halaman join dengan nama dan ID kuis.');
    redirect('kuis/ikut.php');
}

$kuisId = (int) $play['kuis_id'];
$kuis = Kuis::findById($kuisId);
if (!$kuis || !(int) $kuis['aktif']) {
    unset($_SESSION['kuis_play']);
    flash('error', 'Kuis tidak tersedia.');
    redirect('kuis/ikut.php');
}

$errors = [];
$endsAt = (int) ($play['ends_at'] ?? 0);
$stepMode = !empty($play['step_mode']);
$modeLatihan = !empty($play['mode_latihan']);
$soalList = $play['soal'];
$totalSoal = count($soalList);

$finishPlay = static function (array $jawabanMap, array $waktuMap = []) use ($kuisId, $kuis, $play): never {
    $siswaId = isset($play['siswa_id']) && $play['siswa_id'] !== null
        ? (int) $play['siswa_id']
        : null;
    $result = Kuis::submitHasil($kuisId, (string) $play['nama'], $jawabanMap, $siswaId, $waktuMap);
    if (!$result['ok']) {
        $msg = $result['errors']['_form'] ?? 'Gagal menyimpan hasil.';
        unset($_SESSION['kuis_play']);
        flash('error', $msg);
        redirect('kuis/ikut.php?kode=' . urlencode((string) ($play['kode'] ?? '')));
    }

    $posisi = Kuis::posisiPeserta($kuisId, (int) $result['hasil_id']);
    $tampilPembahasan = (int) ($kuis['tampil_pembahasan'] ?? 1) === 1;
    $_SESSION['kuis_result'] = [
        'kuis_id' => $kuisId,
        'judul' => (string) $kuis['judul'],
        'nama' => (string) $play['nama'],
        'skor' => (int) $result['skor'],
        'total' => (int) $result['total'],
        'hasil_id' => (int) $result['hasil_id'],
        'review' => $tampilPembahasan ? Kuis::buildReview($result['detail'] ?? []) : [],
        'tampil_pembahasan' => $tampilPembahasan,
        'peringkat' => $posisi['peringkat'] ?? null,
        'total_peserta' => $posisi['total_peserta'] ?? null,
        'leaderboard' => Kuis::peringkatForKuis($kuisId, 10),
    ];
    unset($_SESSION['kuis_play']);
    redirect('kuis/selesai.php');
};

$collectAnswers = static function () use (&$play): array {
    $map = [];
    $waktu = [];
    foreach (($play['answers'] ?? []) as $sid => $ans) {
        $sid = (int) $sid;
        if (is_array($ans) && array_key_exists('value', $ans)) {
            $map[$sid] = $ans['value'];
            if (array_key_exists('ms', $ans) && $ans['ms'] !== null) {
                $waktu[$sid] = (int) $ans['ms'];
            }
        } else {
            $map[$sid] = $ans;
        }
    }

    return [$map, $waktu];
};

$elapsedMs = static function (array $play, array $current) : ?int {
    $detik = (int) ($current['timer_detik'] ?? 0);
    if ($detik < 5) {
        return null;
    }
    $started = (float) ($play['soal_started_at'] ?? 0);
    if ($started <= 0) {
        return $detik * 1000;
    }
    $ms = (int) round((microtime(true) - $started) * 1000);

    return max(0, min($detik * 1000, $ms));
};

$ensureSoalTimer = static function () use (&$play): void {
    $idx = (int) ($play['index'] ?? 0);
    $soal = $play['soal'][$idx] ?? null;
    if (!$soal) {
        return;
    }
    $detik = (int) ($soal['timer_detik'] ?? 0);
    if ($detik > 0) {
        if (empty($play['soal_ends_at']) || (int) ($play['soal_timer_for'] ?? -1) !== $idx) {
            $play['soal_started_at'] = microtime(true);
            $play['soal_ends_at'] = time() + $detik;
            $play['soal_timer_for'] = $idx;
            $_SESSION['kuis_play'] = $play;
        }
    } else {
        $play['soal_started_at'] = 0;
        $play['soal_ends_at'] = 0;
        $play['soal_timer_for'] = $idx;
        $_SESSION['kuis_play'] = $play;
    }
};

if ($stepMode) {
    $play['index'] = (int) ($play['index'] ?? 0);
    $play['answers'] = is_array($play['answers'] ?? null) ? $play['answers'] : [];
    $play['feedback'] = $play['feedback'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'Sesi formulir tidak valid.');
            redirect('kuis/main.php');
        }

        $action = (string) ($_POST['action'] ?? 'answer');

        if ($action === 'next' && is_array($play['feedback'])) {
            $play['feedback'] = null;
            $play['index'] = (int) $play['index'] + 1;
            $play['soal_ends_at'] = 0;
            $play['soal_started_at'] = 0;
            $_SESSION['kuis_play'] = $play;

            if ((int) $play['index'] >= $totalSoal) {
                [$map, $waktu] = $collectAnswers();
                $finishPlay($map, $waktu);
            }
            redirect('kuis/main.php');
        }

        $idx = (int) $play['index'];
        $current = $soalList[$idx] ?? null;
        if (!$current) {
            [$map, $waktu] = $collectAnswers();
            $finishPlay($map, $waktu);
        }

        $sid = (int) $current['id'];
        $tipe = (string) ($current['tipe'] ?? 'pilihan_ganda');
        $rawAnswer = $tipe === 'isian'
            ? trim((string) ($_POST['jawaban_teks'] ?? ''))
            : ($_POST['jawaban'] ?? '');

        $dbSoal = Kuis::findSoalById($sid);
        $graded = $dbSoal
            ? Kuis::gradeJawaban($dbSoal, $rawAnswer === '' ? null : $rawAnswer)
            : ['ok' => false, 'pilih' => -1, 'benar' => 0, 'pilih_teks' => '', 'benar_teks' => ''];

        $ms = $elapsedMs($play, $current);
        $maks = Kuis::poinSoal(is_array($dbSoal) ? ($dbSoal['poin'] ?? 1) : ($current['poin'] ?? 1));
        $earned = Kuis::poinDidapat(
            $maks,
            (bool) $graded['ok'],
            (string) ($kuis['metode_nilai'] ?? 'tetap'),
            (int) ($current['timer_detik'] ?? 0) >= 5 ? (int) $current['timer_detik'] : null,
            $ms
        );
        $play['answers'][$sid] = [
            'value' => $tipe === 'isian' ? (string) $graded['pilih'] : (int) $graded['pilih'],
            'ok' => $graded['ok'],
            'ms' => $ms,
        ];

        if ($modeLatihan) {
            $play['feedback'] = [
                'ok' => $graded['ok'],
                'pilih_teks' => $graded['pilih_teks'],
                'benar_teks' => $graded['benar_teks'],
                'tipe' => $tipe,
                'poin' => $earned,
                'poin_maks' => $maks,
            ];
            $play['soal_ends_at'] = 0;
            $_SESSION['kuis_play'] = $play;
            redirect('kuis/main.php');
        }

        $play['index'] = $idx + 1;
        $play['soal_ends_at'] = 0;
        $play['feedback'] = null;
        $_SESSION['kuis_play'] = $play;

        if ((int) $play['index'] >= $totalSoal) {
            [$map, $waktu] = $collectAnswers();
            $finishPlay($map, $waktu);
        }
        redirect('kuis/main.php');
    }

    // Timer per soal habis
    $ensureSoalTimer();
    $soalEndsAt = (int) ($play['soal_ends_at'] ?? 0);
    if ($soalEndsAt > 0 && time() >= $soalEndsAt && empty($play['feedback'])) {
        $idx = (int) $play['index'];
        $current = $soalList[$idx] ?? null;
        if ($current) {
            $sid = (int) $current['id'];
            if (!isset($play['answers'][$sid])) {
                $play['answers'][$sid] = [
                    'value' => ($current['tipe'] ?? '') === 'isian' ? '' : -1,
                    'ok' => false,
                    'ms' => $elapsedMs($play, $current),
                ];
            }
            if ($modeLatihan) {
                $dbSoal = Kuis::findSoalById($sid);
                $graded = $dbSoal
                    ? Kuis::gradeJawaban($dbSoal, null)
                    : ['ok' => false, 'pilih_teks' => '(tidak dijawab)', 'benar_teks' => '', 'tipe' => 'pilihan_ganda'];
                $maks = Kuis::poinSoal(is_array($dbSoal) ? ($dbSoal['poin'] ?? 1) : ($current['poin'] ?? 1));
                $play['feedback'] = [
                    'ok' => false,
                    'pilih_teks' => '(waktu habis)',
                    'benar_teks' => $graded['benar_teks'] ?? '',
                    'tipe' => (string) ($current['tipe'] ?? 'pilihan_ganda'),
                    'poin' => 0,
                    'poin_maks' => $maks,
                ];
                $play['soal_ends_at'] = 0;
                $_SESSION['kuis_play'] = $play;
                redirect('kuis/main.php');
            }
            $play['index'] = $idx + 1;
            $play['soal_ends_at'] = 0;
            $_SESSION['kuis_play'] = $play;
            if ((int) $play['index'] >= $totalSoal) {
                [$map, $waktu] = $collectAnswers();
                $finishPlay($map, $waktu);
            }
            redirect('kuis/main.php');
        }
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', 'Sesi formulir tidak valid.');
            redirect('kuis/main.php');
        }

        $jawabanMap = [];
        $raw = $_POST['jawaban'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $soalId => $opsiIndex) {
                $jawabanMap[(int) $soalId] = (int) $opsiIndex;
            }
        }
        $rawTeks = $_POST['jawaban_teks'] ?? [];
        if (is_array($rawTeks)) {
            foreach ($rawTeks as $soalId => $teks) {
                $jawabanMap[(int) $soalId] = trim((string) $teks);
            }
        }

        $finishPlay($jawabanMap);
    }

    if ($endsAt > 0 && time() >= $endsAt) {
        $finishPlay([]);
    }
}

// Global timer also applies in step mode
if ($endsAt > 0 && time() >= $endsAt) {
    if ($stepMode) {
        [$map, $waktu] = $collectAnswers();
        $finishPlay($map, $waktu);
    }
}

$remainingSeconds = $endsAt > 0 ? max(0, $endsAt - time()) : 0;
$currentIndex = (int) ($play['index'] ?? 0);
$currentSoal = $stepMode ? ($soalList[$currentIndex] ?? null) : null;
$soalEndsAt = (int) ($play['soal_ends_at'] ?? 0);
$soalRemaining = $soalEndsAt > 0 ? max(0, $soalEndsAt - time()) : 0;
$feedback = $play['feedback'] ?? null;

view('kuis/main', [
    'title' => (string) $play['judul'],
    'play' => $play,
    'errors' => $errors,
    'endsAt' => $endsAt,
    'remainingSeconds' => $remainingSeconds,
    'stepMode' => $stepMode,
    'modeLatihan' => $modeLatihan,
    'currentSoal' => $currentSoal,
    'currentIndex' => $currentIndex,
    'totalSoal' => $totalSoal,
    'soalEndsAt' => $soalEndsAt,
    'soalRemaining' => $soalRemaining,
    'feedback' => $feedback,
]);

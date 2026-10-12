<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$soalId = (int) ($_GET['soal_id'] ?? 0);
$soal = $soalId > 0 ? Kuis::findSoalById($soalId) : null;
$opsiIndex = isset($_GET['opsi']) ? (int) $_GET['opsi'] : -1;

if (!$soal) {
    http_response_code(404);
    echo 'Gambar tidak ditemukan.';
    exit;
}

$relative = '';
if ($opsiIndex >= 0 && $opsiIndex <= 5) {
    $opsi = Kuis::decodeOpsi($soal['opsi_json'] ?? '[]');
    $relative = (string) ($opsi[$opsiIndex]['gambar_path'] ?? '');
} else {
    $relative = (string) ($soal['gambar_path'] ?? '');
}

if ($relative === '') {
    http_response_code(404);
    echo 'Gambar tidak ditemukan.';
    exit;
}

$user = Auth::user();
$allowed = false;
if ($user && (int) $user['id'] === (int) $soal['kuis_user_id']) {
    $allowed = true;
} elseif ((int) $soal['kuis_aktif'] === 1) {
    $allowed = true;
} else {
    $play = $_SESSION['kuis_play'] ?? null;
    if (is_array($play) && (int) ($play['kuis_id'] ?? 0) === (int) $soal['kuis_id']) {
        $allowed = true;
    }
    $liveSesiId = (int) ($_SESSION['kuis_live_sesi_id'] ?? 0);
    if (!$allowed && $liveSesiId > 0) {
        $sesi = KuisLive::findSesi($liveSesiId);
        if ($sesi && (int) ($sesi['kuis_id'] ?? 0) === (int) $soal['kuis_id']) {
            $allowed = true;
        }
    }
}

if (!$allowed) {
    http_response_code(403);
    echo 'Akses ditolak.';
    exit;
}

$relative = str_replace(['\\', "\0"], ['/', ''], $relative);
if ($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'uploads/kuis/')) {
    http_response_code(404);
    echo 'Gambar tidak valid.';
    exit;
}

$full = BASE_PATH . '/' . $relative;
if (!is_file($full)) {
    http_response_code(404);
    echo 'File tidak ada.';
    exit;
}

$mime = mime_content_type($full) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($full));
header('Cache-Control: private, max-age=3600');
readfile($full);
exit;

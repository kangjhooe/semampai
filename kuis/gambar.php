<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$soalId = (int) ($_GET['soal_id'] ?? 0);
$soal = $soalId > 0 ? Kuis::findSoalById($soalId) : null;

if (!$soal || empty($soal['gambar_path'])) {
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
}

if (!$allowed) {
    http_response_code(403);
    echo 'Akses ditolak.';
    exit;
}

$relative = str_replace(['\\', "\0"], ['/', ''], (string) $soal['gambar_path']);
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

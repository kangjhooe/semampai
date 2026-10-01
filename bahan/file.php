<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    echo 'File tidak ditemukan.';
    exit;
}

$bahan = null;
$exp = (int) ($_GET['exp'] ?? 0);
$sig = (string) ($_GET['sig'] ?? '');

if ($exp > 0 && $sig !== '' && BahanAjar::verifyFileSignature($id, $exp, $sig)) {
    $bahan = BahanAjar::findById($id);
} else {
    Auth::requireLogin();
    $userId = (int) Auth::user()['id'];
    $bahan = BahanAjar::findForUser($id, $userId);
}

if (!$bahan || ($bahan['sumber'] ?? '') !== 'upload' || empty($bahan['file_path'])) {
    http_response_code(404);
    echo 'File tidak ditemukan.';
    exit;
}

$relative = str_replace(['\\', '..'], ['/', ''], (string) $bahan['file_path']);
if (!str_starts_with($relative, 'uploads/bahan/')) {
    http_response_code(404);
    echo 'File tidak ditemukan.';
    exit;
}

$full = BASE_PATH . '/' . $relative;
if (!is_file($full)) {
    http_response_code(404);
    echo 'File tidak ditemukan.';
    exit;
}

$mime = (string) ($bahan['file_mime'] ?? '');
if ($mime === '') {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($full) ?: 'application/octet-stream';
}

$ext = strtolower((string) ($bahan['file_ext'] ?? pathinfo($full, PATHINFO_EXTENSION)));
$filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) $bahan['judul']) ?: 'bahan';
if ($ext !== '' && !str_ends_with(strtolower($filename), '.' . $ext)) {
    $filename .= '.' . $ext;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($full));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

readfile($full);
exit;

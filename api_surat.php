<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$nomor = (int) ($_GET['nomor'] ?? 0);
$data = QuranApi::getSurat($nomor);

if ($data === null) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'message' => 'Surat tidak ditemukan atau gagal diambil.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => $data,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

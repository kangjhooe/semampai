<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'errors' => ['_form' => 'Method tidak diizinkan.']]);
    exit;
}

$raw = file_get_contents('php://input');
$data = [];
if (is_string($raw) && $raw !== '' && str_starts_with(ltrim($raw), '{')) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}
if ($data === []) {
    $data = $_POST;
}

if (!Csrf::verify($data['_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'errors' => ['_form' => 'CSRF tidak valid.']]);
    exit;
}

$token = (string) ($data['token'] ?? ($_SESSION['kuis_live_token'] ?? ''));
$tipe = (string) ($data['tipe'] ?? 'pilihan_ganda');
$answer = $tipe === 'isian'
    ? trim((string) ($data['jawaban_teks'] ?? ''))
    : ($data['jawaban'] ?? null);

$result = KuisLive::answer($token, $answer);
if (!$result['ok']) {
    http_response_code(400);
}

$snapshot = KuisLive::snapshotForPlayer($token);
echo json_encode([
    'ok' => (bool) $result['ok'],
    'errors' => $result['errors'] ?? [],
    'benar' => $result['benar'] ?? null,
    'poin' => $result['poin'] ?? null,
    'snapshot' => $snapshot,
], JSON_UNESCAPED_UNICODE);

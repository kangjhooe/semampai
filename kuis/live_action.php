<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$userId = (int) Auth::user()['id'];

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

$sesiId = (int) ($data['sesi_id'] ?? 0);
$action = (string) ($data['action'] ?? '');

$result = match ($action) {
    'start' => KuisLive::start($sesiId, $userId),
    'reveal' => KuisLive::reveal($sesiId, $userId),
    'leaderboard' => KuisLive::showLeaderboard($sesiId, $userId),
    'next' => KuisLive::nextQuestion($sesiId, $userId),
    'finish' => KuisLive::finish($sesiId, $userId),
    default => ['ok' => false, 'errors' => ['_form' => 'Aksi tidak dikenal.']],
};

if (!$result['ok']) {
    http_response_code(400);
}

$snapshot = KuisLive::snapshotForHost($sesiId, $userId);
echo json_encode([
    'ok' => (bool) $result['ok'],
    'errors' => $result['errors'] ?? [],
    'finished' => !empty($result['finished']),
    'snapshot' => $snapshot,
], JSON_UNESCAPED_UNICODE);

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$role = (string) ($_GET['role'] ?? '');

if ($role === 'host') {
    Auth::requireLogin();
    $userId = (int) Auth::user()['id'];
    $sesiId = (int) ($_GET['sesi_id'] ?? 0);
    $snapshot = KuisLive::snapshotForHost($sesiId, $userId);
    if (!$snapshot) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']]);
        exit;
    }
    echo json_encode(['ok' => true, 'snapshot' => $snapshot], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($role === 'player') {
    $token = (string) ($_GET['token'] ?? ($_SESSION['kuis_live_token'] ?? ''));
    $snapshot = KuisLive::snapshotForPlayer($token);
    if (!$snapshot) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'errors' => ['_form' => 'Peserta tidak valid.']]);
        exit;
    }
    echo json_encode(['ok' => true, 'snapshot' => $snapshot], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'errors' => ['_form' => 'Role tidak valid.']]);

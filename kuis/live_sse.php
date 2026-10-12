<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$role = (string) ($_GET['role'] ?? '');

if ($role === 'host') {
    Auth::requireLogin();
    $userId = (int) Auth::user()['id'];
    $sesiId = (int) ($_GET['sesi_id'] ?? 0);
    if ($sesiId <= 0 || !KuisLive::findSesiForHost($sesiId, $userId)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Sesi tidak ditemukan.';
        exit;
    }

    Sse::streamUntil(
        static fn (): int => KuisLive::stateVersion($sesiId),
        static function () use ($sesiId, $userId): array {
            $snap = KuisLive::snapshotForHost($sesiId, $userId);

            return $snap ?? ['status' => 'ended', 'state_version' => 0];
        }
    );
    exit;
}

if ($role === 'player') {
    $token = (string) ($_GET['token'] ?? ($_SESSION['kuis_live_token'] ?? ''));
    $peserta = KuisLive::findPesertaByToken($token);
    if (!$peserta) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Peserta tidak valid.';
        exit;
    }
    $sesiId = (int) $peserta['sesi_id'];

    Sse::streamUntil(
        static fn (): int => KuisLive::stateVersion($sesiId),
        static function () use ($token): array {
            $snap = KuisLive::snapshotForPlayer($token);

            return $snap ?? ['status' => 'ended', 'state_version' => 0];
        }
    );
    exit;
}

http_response_code(400);
header('Content-Type: text/plain; charset=utf-8');
echo 'Role tidak valid.';

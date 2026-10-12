<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$token = (string) ($_SESSION['kuis_live_token'] ?? '');
$snapshot = $token !== '' ? KuisLive::snapshotForPlayer($token) : null;

if (!$snapshot || ($snapshot['status'] ?? '') === 'ended') {
    unset($_SESSION['kuis_live_token'], $_SESSION['kuis_live_sesi_id']);
    flash('error', 'Gabung sesi live dengan PIN terlebih dahulu.');
    redirect('kuis/live_join.php');
}

view('kuis/live_play', [
    'title' => (string) ($snapshot['kuis']['judul'] ?? 'Live Kuis'),
    'snapshot' => $snapshot,
    'token' => $token,
    'csrfToken' => Csrf::token(),
]);

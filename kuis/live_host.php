<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];

$kuisId = (int) ($_GET['kuis_id'] ?? $_POST['kuis_id'] ?? 0);
$sesiId = (int) ($_GET['sesi_id'] ?? 0);

$kuis = $kuisId > 0 ? Kuis::findForUser($kuisId, $userId) : null;
if (!$kuis && $sesiId > 0) {
    $sesi = KuisLive::findSesiForHost($sesiId, $userId);
    if ($sesi) {
        $kuis = Kuis::findForUser((int) $sesi['kuis_id'], $userId);
        $kuisId = (int) ($kuis['id'] ?? 0);
    }
}

if (!$kuis) {
    flash('error', 'Kuis tidak ditemukan.');
    redirect('kuis/index.php');
}

$sesi = null;
if ($sesiId > 0) {
    $sesi = KuisLive::findSesiForHost($sesiId, $userId);
}

if (!$sesi) {
    $sesi = KuisLive::findActiveForKuisHost($kuisId, $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'create') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('kuis/live_host.php?kuis_id=' . $kuisId);
    }
    $result = KuisLive::createSesi($kuisId, $userId);
    if (!$result['ok']) {
        flash('error', $result['errors']['_form'] ?? 'Gagal membuat sesi.');
        redirect('kuis/live_host.php?kuis_id=' . $kuisId);
    }
    redirect('kuis/live_host.php?sesi_id=' . (int) $result['sesi_id']);
}

if (!$sesi) {
    view('kuis/live_host_start', [
        'title' => 'Live · ' . $kuis['judul'],
        'user' => $user,
        'kuis' => $kuis,
    ], 'app');
    exit;
}

$snapshot = KuisLive::snapshotForHost((int) $sesi['id'], $userId);
if (!$snapshot) {
    flash('error', 'Sesi tidak ditemukan.');
    redirect('kuis/index.php');
}

view('kuis/live_host', [
    'title' => 'Live · ' . $kuis['judul'],
    'user' => $user,
    'kuis' => $kuis,
    'sesi' => $sesi,
    'snapshot' => $snapshot,
    'csrfToken' => Csrf::token(),
], 'app');

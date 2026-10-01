<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$id = (int) ($_GET['id'] ?? 0);

$bahan = BahanAjar::findForUser($id, $userId);

if (!$bahan) {
    flash('error', 'Bahan ajar tidak ditemukan.');
    redirect('bahan/index.php');
}

$embed = BahanAjar::resolveEmbed($bahan);

view('bahan/lihat', [
    'title' => $bahan['judul'],
    'user' => $user,
    'bahan' => $bahan,
    'embed' => $embed,
], 'app');

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

if (!Csrf::verify($_POST['_token'] ?? null)) {
    flash('error', 'Sesi formulir tidak valid.');
    redirect('dashboard.php');
}

$npsn = only_digits((string) ($_POST['npsn'] ?? ''));
$redirectTo = trim((string) ($_POST['redirect'] ?? 'dashboard.php'));
if ($redirectTo === '' || str_contains($redirectTo, '://') || str_starts_with($redirectTo, '//')) {
    $redirectTo = 'dashboard.php';
}

if (!Auth::switchSchool($npsn)) {
    flash('error', 'Sekolah tidak ditemukan di akun Anda.');
    redirect($redirectTo);
}

$school = Sekolah::find($npsn);
flash('success', 'Sekolah aktif: ' . ($school['nama_sekolah'] ?? $npsn));
redirect($redirectTo);

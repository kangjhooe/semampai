<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::guestOnly();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        $errors['_form'] = 'Sesi formulir tidak valid. Coba lagi.';
    } else {
        store_old($_POST);
        $result = Auth::register($_POST);

        if ($result['ok']) {
            clear_old();
            flash('success', 'Akun berhasil dibuat.');
            redirect('dashboard.php');
        }

        $errors = $result['errors'];
    }
}

view('auth/daftar', [
    'title' => 'Daftar',
    'errors' => $errors,
]);

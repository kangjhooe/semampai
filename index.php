<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::guestOnly();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid. Coba lagi.');
        redirect('index.php');
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    store_old(['email' => $email]);

    if ($email === '' || $password === '') {
        flash('error', 'Email dan password wajib diisi.');
        redirect('index.php');
    }

    if (Auth::attempt($email, $password)) {
        clear_old();
        redirect('dashboard.php');
    }

    flash('error', 'Email atau password salah.');
    redirect('index.php');
}

view('auth/login', [
    'title' => 'Masuk',
]);

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];

$profileErrors = [];
$passwordErrors = [];
$activeTab = (string) ($_GET['tab'] ?? 'profil');
if ($activeTab !== 'password') {
    $activeTab = 'profil';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('profil.php');
    }

    $action = (string) ($_POST['action'] ?? 'profil');

    if ($action === 'password') {
        $activeTab = 'password';
        $result = Auth::changePassword($userId, [
            'current_password' => (string) ($_POST['current_password'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ]);

        if ($result['ok']) {
            flash('success', 'Password berhasil diubah.');
            redirect('profil.php');
        }

        $passwordErrors = $result['errors'];
    } else {
        $activeTab = 'profil';
        store_old($_POST);
        $result = Auth::updateProfile($userId, [
            'nama' => (string) ($_POST['nama'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'no_wa' => (string) ($_POST['no_wa'] ?? ''),
        ]);

        if ($result['ok']) {
            clear_old();
            flash('success', 'Profil berhasil diperbarui.');
            redirect('profil.php');
        }

        $profileErrors = $result['errors'];
    }

    $user = Auth::user();
}

view('profil/form', [
    'title' => 'Profil',
    'user' => $user,
    'profileErrors' => $profileErrors,
    'passwordErrors' => $passwordErrors,
    'activeTab' => $activeTab,
], 'app');

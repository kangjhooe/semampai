<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];

$profileErrors = [];
$passwordErrors = [];
$schoolErrors = [];
$activeTab = (string) ($_GET['tab'] ?? 'profil');
if (!in_array($activeTab, ['profil', 'password', 'sekolah'], true)) {
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
            redirect('profil.php?tab=password');
        }

        $passwordErrors = $result['errors'];
    } elseif ($action === 'tambah_sekolah') {
        $activeTab = 'sekolah';
        store_old($_POST);
        $result = Sekolah::attachToUser(
            $userId,
            (string) ($_POST['npsn'] ?? ''),
            (string) ($_POST['nama_sekolah'] ?? '')
        );

        if ($result['ok']) {
            clear_old();
            Auth::refreshSchoolContext(true);
            if (!empty($_POST['jadikan_aktif'])) {
                Auth::switchSchool((string) $result['npsn']);
            }
            flash('success', 'Sekolah berhasil ditambahkan.');
            redirect('profil.php?tab=sekolah');
        }

        $schoolErrors = $result['errors'];
    } elseif ($action === 'lepas_sekolah') {
        $activeTab = 'sekolah';
        $npsn = only_digits((string) ($_POST['npsn'] ?? ''));
        $wasActive = $npsn === Auth::activeNpsn();
        $result = Sekolah::detachFromUser($userId, $npsn);

        if ($result['ok']) {
            Auth::refreshSchoolContext(true);
            if ($wasActive) {
                $schools = Sekolah::forUser($userId);
                if ($schools !== []) {
                    Auth::switchSchool($schools[0]['npsn']);
                }
            }
            flash('success', 'Sekolah dilepas dari akun.');
            redirect('profil.php?tab=sekolah');
        }

        $schoolErrors = $result['errors'];
        flash('error', $schoolErrors['_form'] ?? 'Gagal melepas sekolah.');
        redirect('profil.php?tab=sekolah');
    } elseif ($action === 'aktifkan_sekolah') {
        $activeTab = 'sekolah';
        $npsn = only_digits((string) ($_POST['npsn'] ?? ''));
        if (Auth::switchSchool($npsn)) {
            flash('success', 'Sekolah aktif diganti.');
        } else {
            flash('error', 'Sekolah tidak ditemukan di akun Anda.');
        }
        redirect('profil.php?tab=sekolah');
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
    'schoolErrors' => $schoolErrors,
    'sekolahList' => Sekolah::forUser($userId),
    'activeTab' => $activeTab,
], 'app');

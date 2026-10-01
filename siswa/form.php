<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$daftarKelas = Kelas::allForUser($userId);
$kelasId = (int) ($_GET['kelas_id'] ?? $_POST['kelas_id'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);
$kelas = Kelas::findForUser($kelasId, $userId);

if (!$kelas) {
    flash('error', 'Kelas tidak ditemukan.');
    redirect('siswa/index.php');
}

$siswa = null;
if ($id > 0) {
    $siswa = Siswa::findForUser($id, $userId);
    if (!$siswa) {
        flash('error', 'Siswa tidak ditemukan.');
        redirect('siswa/index.php?kelas_id=' . $kelasId);
    }
    // Jika siswa sudah pindah kelas, arahkan ke kelas aslinya
    if ((int) $siswa['kelas_id'] !== $kelasId) {
        $kelasId = (int) $siswa['kelas_id'];
        $kelas = Kelas::findForUser($kelasId, $userId);
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('siswa/form.php?kelas_id=' . $kelasId . ($id ? '&id=' . $id : ''));
    }

    store_old($_POST);
    $payload = [
        'nama' => (string) ($_POST['nama'] ?? ''),
        'nisn' => (string) ($_POST['nisn'] ?? ''),
        'tempat_lahir' => (string) ($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => (string) ($_POST['tanggal_lahir'] ?? ''),
    ];

    if ($siswa) {
        $kelasTujuanId = (int) ($_POST['kelas_tujuan_id'] ?? $kelasId);
        $result = Siswa::update($id, $userId, $payload);

        if ($result['ok'] && $kelasTujuanId !== (int) $siswa['kelas_id']) {
            $pindah = Siswa::pindahKelas($id, $userId, $kelasTujuanId);
            if (!$pindah['ok']) {
                $errors = $pindah['errors'];
            } else {
                clear_old();
                $labelKelas = ($pindah['kelas_nama'] ?? '') . ' · ' . ($pindah['tahun_ajaran'] ?? '');
                flash('success', 'Siswa diperbarui dan dipindah ke ' . $labelKelas . '.');
                redirect('siswa/index.php?kelas_id=' . $kelasTujuanId);
            }
        } elseif ($result['ok']) {
            clear_old();
            flash('success', 'Siswa berhasil diperbarui.');
            redirect('siswa/index.php?kelas_id=' . $kelasId);
        } else {
            $errors = $result['errors'];
        }
    } else {
        $result = Siswa::create($kelasId, $userId, $payload);

        if ($result['ok']) {
            clear_old();
            flash('success', 'Siswa berhasil ditambahkan.');
            redirect('siswa/index.php?kelas_id=' . $kelasId);
        }

        $errors = $result['errors'];
    }
}

view('siswa/form', [
    'title' => $siswa ? 'Edit Siswa' : 'Tambah Siswa',
    'user' => $user,
    'kelas' => $kelas,
    'siswa' => $siswa,
    'daftarKelas' => $daftarKelas,
    'errors' => $errors,
], 'app');

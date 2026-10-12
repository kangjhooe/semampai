<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int) $user['id'];
$daftarKelas = Kelas::allForUser($userId);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$kuis = null;
if ($id > 0) {
    $kuis = Kuis::findForUser($id, $userId);
    if (!$kuis) {
        flash('error', 'Kuis tidak ditemukan.');
        redirect('kuis/index.php');
    }
}

$errors = [];
$soalErrors = [];
$editSoalId = (int) ($_GET['edit_soal'] ?? $_POST['soal_id'] ?? 0);
$editSoal = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_token'] ?? null)) {
        flash('error', 'Sesi formulir tidak valid.');
        redirect('kuis/form.php' . ($kuis ? '?id=' . $id : ''));
    }

    $action = (string) ($_POST['action'] ?? 'save_meta');

    if ($action === 'add_soal' || $action === 'update_soal') {
        if (!$kuis) {
            flash('error', 'Simpan kuis dulu sebelum mengelola soal.');
            redirect('kuis/form.php');
        }

        store_old($_POST);
        $payload = [
            'pertanyaan' => (string) ($_POST['pertanyaan'] ?? ''),
            'tipe' => (string) ($_POST['tipe'] ?? 'pilihan_ganda'),
            'opsi' => $_POST['opsi'] ?? [],
            'jawaban_index' => (int) ($_POST['jawaban_index'] ?? -1),
            'jawaban_teks' => (string) ($_POST['jawaban_teks'] ?? ''),
            'timer_detik' => (string) ($_POST['timer_detik'] ?? ''),
            'gambar_url' => (string) ($_POST['gambar_url'] ?? ''),
            'hapus_gambar' => isset($_POST['hapus_gambar']) ? 1 : 0,
        ];
        $file = $_FILES['gambar'] ?? null;

        if ($action === 'update_soal') {
            $soalId = (int) ($_POST['soal_id'] ?? 0);
            $result = Kuis::updateSoal($soalId, $userId, $payload, $file);
            if ($result['ok']) {
                clear_old();
                flash('success', 'Soal diperbarui.');
                redirect('kuis/form.php?id=' . $id);
            }
            $soalErrors = $result['errors'];
            $editSoalId = $soalId;
        } else {
            $result = Kuis::addSoal($id, $userId, $payload, $file);
            if ($result['ok']) {
                clear_old();
                flash('success', 'Soal ditambahkan.');
                redirect('kuis/form.php?id=' . $id);
            }
            $soalErrors = $result['errors'];
            $editSoalId = 0;
        }
    } elseif ($action === 'delete_soal') {
        if (!$kuis) {
            flash('error', 'Kuis tidak ditemukan.');
            redirect('kuis/index.php');
        }

        $soalId = (int) ($_POST['soal_id'] ?? 0);
        if ($soalId > 0 && Kuis::deleteSoal($soalId, $userId)) {
            flash('success', 'Soal dihapus.');
        } else {
            flash('error', 'Soal tidak ditemukan.');
        }
        redirect('kuis/form.php?id=' . $id);
    } elseif ($action === 'move_soal') {
        if (!$kuis) {
            flash('error', 'Kuis tidak ditemukan.');
            redirect('kuis/index.php');
        }
        $soalId = (int) ($_POST['soal_id'] ?? 0);
        $arah = (string) ($_POST['arah'] ?? '');
        if ($soalId > 0 && in_array($arah, ['up', 'down'], true) && Kuis::moveSoal($soalId, $userId, $arah)) {
            flash('success', 'Urutan soal diperbarui.');
        } else {
            flash('error', 'Tidak bisa memindahkan soal.');
        }
        redirect('kuis/form.php?id=' . $id);
    } else {
        store_old($_POST);
        $payload = [
            'judul' => (string) ($_POST['judul'] ?? ''),
            'kelas_id' => (int) ($_POST['kelas_id'] ?? 0),
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
            'acak_soal' => isset($_POST['acak_soal']) ? 1 : 0,
            'acak_opsi' => isset($_POST['acak_opsi']) ? 1 : 0,
            'timer_menit' => (string) ($_POST['timer_menit'] ?? ''),
            'izinkan_ulang' => isset($_POST['izinkan_ulang']) ? 1 : 0,
            'tampil_pembahasan' => isset($_POST['tampil_pembahasan']) ? 1 : 0,
            'mode_latihan' => isset($_POST['mode_latihan']) ? 1 : 0,
        ];

        if ($kuis) {
            $result = Kuis::update($id, $userId, $payload);
            if ($result['ok']) {
                clear_old();
                flash('success', 'Kuis diperbarui.');
                redirect('kuis/form.php?id=' . $id);
            }
            $errors = $result['errors'];
        } else {
            $result = Kuis::create($userId, $payload);
            if ($result['ok']) {
                clear_old();
                flash('success', 'Kuis dibuat. Kode: ' . $result['kode'] . '. Tambahkan soal di bawah.');
                redirect('kuis/form.php?id=' . $result['id']);
            }
            $errors = $result['errors'];
        }
    }
}

if ($kuis && $editSoalId > 0) {
    $editSoal = Kuis::findSoalForUser($editSoalId, $userId);
    if (!$editSoal || (int) $editSoal['kuis_id'] !== (int) $kuis['id']) {
        flash('error', 'Soal tidak ditemukan.');
        redirect('kuis/form.php?id=' . $id);
    }
}

$soalList = $kuis ? Kuis::soalForKuis((int) $kuis['id']) : [];

view('kuis/form', [
    'title' => $kuis ? 'Edit Kuis' : 'Buat Kuis',
    'user' => $user,
    'daftarKelas' => $daftarKelas,
    'kuis' => $kuis,
    'soalList' => $soalList,
    'errors' => $errors,
    'soalErrors' => $soalErrors,
    'editSoal' => $editSoal,
], 'app');

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$result = $_SESSION['kuis_result'] ?? null;
if (!is_array($result)) {
    flash('error', 'Belum ada hasil kuis.');
    redirect('kuis/ikut.php');
}

view('kuis/selesai', [
    'title' => 'Hasil Kuis',
    'result' => $result,
]);

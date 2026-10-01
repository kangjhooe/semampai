<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

$qs = $_SERVER['QUERY_STRING'] ?? '';
redirect('kelas/hapus.php' . ($qs !== '' ? '?' . $qs : ''));

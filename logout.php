<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

Auth::logout();
flash('success', 'Anda sudah keluar.');
redirect('index.php');

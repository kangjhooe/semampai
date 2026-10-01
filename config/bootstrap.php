<?php

declare(strict_types=1);

session_start();

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once BASE_PATH . '/lib/helpers.php';
require_once BASE_PATH . '/lib/Database.php';
require_once BASE_PATH . '/lib/Csrf.php';
require_once BASE_PATH . '/lib/Auth.php';
require_once BASE_PATH . '/lib/AppSettings.php';
require_once BASE_PATH . '/lib/Kelas.php';
require_once BASE_PATH . '/lib/Siswa.php';
require_once BASE_PATH . '/lib/SiswaImporter.php';
require_once BASE_PATH . '/lib/Surah.php';
require_once BASE_PATH . '/lib/Hafalan.php';
require_once BASE_PATH . '/lib/HafalanExporter.php';
require_once BASE_PATH . '/lib/HafalanTarget.php';
require_once BASE_PATH . '/lib/HafalanProgress.php';
require_once BASE_PATH . '/lib/HafalanRekapExporter.php';
require_once BASE_PATH . '/lib/QuranApi.php';
require_once BASE_PATH . '/lib/EmbedUrl.php';
require_once BASE_PATH . '/lib/BahanAjar.php';

load_env(BASE_PATH . '/.env');

date_default_timezone_set('Asia/Jakarta');

if (env('APP_DEBUG', 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

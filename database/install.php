<?php

declare(strict_types=1);

/**
 * Satu kali: buka http://localhost/semampai/database/install.php
 * Setelah sukses, hapus atau proteksi file ini.
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$name = env('DB_NAME', 'semampai');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '') ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Tidak bisa membaca schema.sql');
    }

    $pdo->exec($sql);

    $appName = env('APP_NAME', 'Semampai') ?? 'Semampai';
    $stmt = $pdo->prepare(
        "INSERT INTO settings (`key`, `value`) VALUES ('app_name', :value)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
    );
    $stmt->execute(['value' => $appName]);

    echo "OK: database '{$name}' siap.\n";
    echo "APP_NAME: {$appName}\n";
    echo "Buka: " . app_url('index.php') . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal install: ' . $e->getMessage() . "\n";
}

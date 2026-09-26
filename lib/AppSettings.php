<?php

declare(strict_types=1);

final class AppSettings
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        self::load();

        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        $stmt->execute(['key' => $key, 'value' => $value]);
        self::$cache[$key] = $value;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];

        try {
            $rows = Database::connection()->query('SELECT `key`, `value` FROM settings')->fetchAll();
            foreach ($rows as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        } catch (Throwable) {
            // Tabel settings mungkin belum ada saat install.
            self::$cache = [];
        }
    }
}

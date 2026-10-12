<?php

declare(strict_types=1);

final class Sekolah
{
    /**
     * @return list<array{npsn: string, nama_sekolah: string, created_at?: string}>
     */
    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.npsn, s.nama_sekolah, us.created_at
             FROM user_sekolah us
             INNER JOIN sekolah s ON s.npsn = us.npsn
             WHERE us.user_id = :user_id
             ORDER BY s.nama_sekolah ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function userHas(int $userId, string $npsn): bool
    {
        $npsn = only_digits($npsn);
        if ($npsn === '') {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM user_sekolah
             WHERE user_id = :user_id AND npsn = :npsn
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'npsn' => $npsn]);

        return (bool) $stmt->fetchColumn();
    }

    public static function find(string $npsn): ?array
    {
        $npsn = only_digits($npsn);
        if (strlen($npsn) !== 8) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT npsn, nama_sekolah FROM sekolah WHERE npsn = :npsn LIMIT 1'
        );
        $stmt->execute(['npsn' => $npsn]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Pastikan baris sekolah ada; kembalikan nama resmi yang tersimpan.
     *
     * @return array{ok: bool, errors: array<string, string>, npsn?: string, nama_sekolah?: string}
     */
    public static function ensure(string $npsn, string $namaSekolah): array
    {
        $errors = self::validateSchoolInput($npsn, $namaSekolah);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $npsn = only_digits($npsn);
        $namaSekolah = trim($namaSekolah);
        $pdo = Database::connection();

        $existing = self::find($npsn);
        if ($existing) {
            return [
                'ok' => true,
                'errors' => [],
                'npsn' => $existing['npsn'],
                'nama_sekolah' => $existing['nama_sekolah'],
            ];
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO sekolah (npsn, nama_sekolah) VALUES (:npsn, :nama_sekolah)'
            );
            $stmt->execute([
                'npsn' => $npsn,
                'nama_sekolah' => $namaSekolah,
            ]);

            return [
                'ok' => true,
                'errors' => [],
                'npsn' => $npsn,
                'nama_sekolah' => $namaSekolah,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan data sekolah.']];
        }
    }

    /**
     * @return array{ok: bool, errors: array<string, string>, npsn?: string, nama_sekolah?: string}
     */
    public static function attachToUser(int $userId, string $npsn, string $namaSekolah): array
    {
        $ensured = self::ensure($npsn, $namaSekolah);
        if (!$ensured['ok']) {
            return $ensured;
        }

        $npsn = (string) $ensured['npsn'];
        if (self::userHas($userId, $npsn)) {
            return ['ok' => false, 'errors' => ['npsn' => 'Sekolah ini sudah terhubung ke akun Anda.']];
        }

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO user_sekolah (user_id, npsn) VALUES (:user_id, :npsn)'
            );
            $stmt->execute(['user_id' => $userId, 'npsn' => $npsn]);

            return [
                'ok' => true,
                'errors' => [],
                'npsn' => $npsn,
                'nama_sekolah' => (string) $ensured['nama_sekolah'],
            ];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'errors' => ['npsn' => 'Sekolah ini sudah terhubung ke akun Anda.']];
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menambahkan sekolah.']];
        }
    }

    /**
     * @return array{ok: bool, errors: array<string, string>}
     */
    public static function detachFromUser(int $userId, string $npsn): array
    {
        $npsn = only_digits($npsn);
        if (!self::userHas($userId, $npsn)) {
            return ['ok' => false, 'errors' => ['_form' => 'Sekolah tidak ditemukan di akun Anda.']];
        }

        $schools = self::forUser($userId);
        if (count($schools) <= 1) {
            return ['ok' => false, 'errors' => ['_form' => 'Tidak bisa melepas sekolah terakhir.']];
        }

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM kelas WHERE user_id = :user_id AND npsn = :npsn'
        );
        $stmt->execute(['user_id' => $userId, 'npsn' => $npsn]);
        if ((int) $stmt->fetchColumn() > 0) {
            return [
                'ok' => false,
                'errors' => ['_form' => 'Masih ada kelas di sekolah ini. Hapus atau pindahkan kelas dulu.'],
            ];
        }

        $del = Database::connection()->prepare(
            'DELETE FROM user_sekolah WHERE user_id = :user_id AND npsn = :npsn'
        );
        $del->execute(['user_id' => $userId, 'npsn' => $npsn]);

        return ['ok' => true, 'errors' => []];
    }

    /** @return array<string, string> */
    public static function validateSchoolInput(string $npsn, string $namaSekolah): array
    {
        $errors = [];
        $npsn = only_digits($npsn);
        $namaSekolah = trim($namaSekolah);

        if (strlen($npsn) !== 8) {
            $errors['npsn'] = 'NPSN harus 8 digit angka.';
        }

        if ($namaSekolah === '') {
            $errors['nama_sekolah'] = 'Nama sekolah wajib diisi.';
        } elseif (mb_strlen($namaSekolah) > 255) {
            $errors['nama_sekolah'] = 'Nama sekolah terlalu panjang.';
        }

        return $errors;
    }
}

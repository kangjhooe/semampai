<?php

declare(strict_types=1);

final class Kelas
{
    public static function allForUser(int $userId, ?string $npsn = null): array
    {
        $npsn = only_digits($npsn ?? Auth::activeNpsn());
        if ($npsn === '') {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT k.*,
                    (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) AS jumlah_siswa
             FROM kelas k
             WHERE k.user_id = :user_id AND k.npsn = :npsn
             ORDER BY k.tahun_ajaran DESC, k.nama ASC'
        );
        $stmt->execute(['user_id' => $userId, 'npsn' => $npsn]);

        return $stmt->fetchAll();
    }

    public static function findForUser(int $id, int $userId, ?string $npsn = null): ?array
    {
        $npsn = only_digits($npsn ?? Auth::activeNpsn());
        if ($npsn === '') {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM kelas
             WHERE id = :id AND user_id = :user_id AND npsn = :npsn
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId, 'npsn' => $npsn]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(int $userId, string $nama, string $tahunAjaran, ?string $npsn = null): array
    {
        $npsn = only_digits($npsn ?? Auth::activeNpsn());
        if ($npsn === '' || !Sekolah::userHas($userId, $npsn)) {
            return ['ok' => false, 'errors' => ['_form' => 'Sekolah aktif tidak valid.']];
        }

        $errors = self::validate($nama, $tahunAjaran);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO kelas (user_id, npsn, nama, tahun_ajaran)
                 VALUES (:user_id, :npsn, :nama, :tahun_ajaran)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'npsn' => $npsn,
                'nama' => trim($nama),
                'tahun_ajaran' => trim($tahunAjaran),
            ]);

            return ['ok' => true, 'errors' => [], 'id' => (int) Database::connection()->lastInsertId()];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'errors' => ['_form' => 'Kelas dengan nama dan tahun ajaran itu sudah ada di sekolah ini.']];
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan kelas.']];
        }
    }

    public static function update(int $id, int $userId, string $nama, string $tahunAjaran): array
    {
        if (!self::findForUser($id, $userId)) {
            return ['ok' => false, 'errors' => ['_form' => 'Kelas tidak ditemukan.']];
        }

        $errors = self::validate($nama, $tahunAjaran);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $stmt = Database::connection()->prepare(
                'UPDATE kelas
                 SET nama = :nama, tahun_ajaran = :tahun_ajaran
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'nama' => trim($nama),
                'tahun_ajaran' => trim($tahunAjaran),
                'id' => $id,
                'user_id' => $userId,
            ]);

            return ['ok' => true, 'errors' => []];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'errors' => ['_form' => 'Kelas dengan nama dan tahun ajaran itu sudah ada di sekolah ini.']];
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal memperbarui kelas.']];
        }
    }

    public static function delete(int $id, int $userId, ?string $npsn = null): bool
    {
        $npsn = only_digits($npsn ?? Auth::activeNpsn());
        if ($npsn === '') {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE FROM kelas WHERE id = :id AND user_id = :user_id AND npsn = :npsn'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId, 'npsn' => $npsn]);
        $ok = $stmt->rowCount() > 0;

        if ($ok) {
            BahanAjar::deleteOrphansForUser($userId);
        }

        return $ok;
    }

    /** @return array<string, string> */
    private static function validate(string $nama, string $tahunAjaran): array
    {
        $errors = [];
        $nama = trim($nama);
        $tahunAjaran = trim($tahunAjaran);

        if ($nama === '') {
            $errors['nama'] = 'Nama kelas wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors['nama'] = 'Nama kelas terlalu panjang.';
        }

        if ($tahunAjaran === '') {
            $errors['tahun_ajaran'] = 'Tahun ajaran wajib diisi.';
        } elseif (!preg_match('/^\d{4}\/\d{4}$/', $tahunAjaran)) {
            $errors['tahun_ajaran'] = 'Format tahun ajaran: 2025/2026.';
        }

        return $errors;
    }
}

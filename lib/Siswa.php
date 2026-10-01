<?php

declare(strict_types=1);

final class Siswa
{
    public static function allForKelas(int $kelasId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*
             FROM siswa s
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE s.kelas_id = :kelas_id AND k.user_id = :user_id
             ORDER BY s.nama ASC'
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, k.nama AS kelas_nama, k.tahun_ajaran, k.user_id
             FROM siswa s
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE s.id = :id AND k.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(int $kelasId, int $userId, array $data): array
    {
        if (!Kelas::findForUser($kelasId, $userId)) {
            return ['ok' => false, 'errors' => ['_form' => 'Kelas tidak ditemukan.']];
        }

        $errors = self::validate($data);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $nisn = only_digits($data['nisn']);

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO siswa (kelas_id, nama, nisn, tempat_lahir, tanggal_lahir)
                 VALUES (:kelas_id, :nama, :nisn, :tempat_lahir, :tanggal_lahir)'
            );
            $stmt->execute([
                'kelas_id' => $kelasId,
                'nama' => trim($data['nama']),
                'nisn' => $nisn,
                'tempat_lahir' => trim($data['tempat_lahir']),
                'tanggal_lahir' => $data['tanggal_lahir'],
            ]);

            return ['ok' => true, 'errors' => [], 'id' => (int) Database::connection()->lastInsertId()];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'errors' => ['nisn' => 'NISN sudah terdaftar.']];
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan siswa.']];
        }
    }

    public static function update(int $id, int $userId, array $data): array
    {
        $siswa = self::findForUser($id, $userId);
        if (!$siswa) {
            return ['ok' => false, 'errors' => ['_form' => 'Siswa tidak ditemukan.']];
        }

        $errors = self::validate($data);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $nisn = only_digits($data['nisn']);

        try {
            $stmt = Database::connection()->prepare(
                'UPDATE siswa s
                 INNER JOIN kelas k ON k.id = s.kelas_id
                 SET s.nama = :nama, s.nisn = :nisn, s.tempat_lahir = :tempat_lahir, s.tanggal_lahir = :tanggal_lahir
                 WHERE s.id = :id AND k.user_id = :user_id'
            );
            $stmt->execute([
                'nama' => trim($data['nama']),
                'nisn' => $nisn,
                'tempat_lahir' => trim($data['tempat_lahir']),
                'tanggal_lahir' => $data['tanggal_lahir'],
                'id' => $id,
                'user_id' => $userId,
            ]);

            return ['ok' => true, 'errors' => []];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'errors' => ['nisn' => 'NISN sudah terdaftar.']];
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal memperbarui siswa.']];
        }
    }

    public static function pindahKelas(int $id, int $userId, int $kelasIdBaru): array
    {
        $siswa = self::findForUser($id, $userId);
        if (!$siswa) {
            return ['ok' => false, 'errors' => ['_form' => 'Siswa tidak ditemukan.']];
        }

        if ((int) $siswa['kelas_id'] === $kelasIdBaru) {
            return ['ok' => true, 'errors' => [], 'kelas_id' => $kelasIdBaru];
        }

        $kelasBaru = Kelas::findForUser($kelasIdBaru, $userId);
        if (!$kelasBaru) {
            return ['ok' => false, 'errors' => ['kelas_id' => 'Kelas tujuan tidak valid.']];
        }

        $stmt = Database::connection()->prepare(
            'UPDATE siswa s
             INNER JOIN kelas k ON k.id = s.kelas_id
             SET s.kelas_id = :kelas_id
             WHERE s.id = :id AND k.user_id = :user_id'
        );
        $stmt->execute([
            'kelas_id' => $kelasIdBaru,
            'id' => $id,
            'user_id' => $userId,
        ]);

        return [
            'ok' => true,
            'errors' => [],
            'kelas_id' => $kelasIdBaru,
            'kelas_nama' => $kelasBaru['nama'],
            'tahun_ajaran' => $kelasBaru['tahun_ajaran'],
        ];
    }

    public static function delete(int $id, int $userId): bool
    {
        $siswa = self::findForUser($id, $userId);
        if (!$siswa) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE s FROM siswa s
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE s.id = :id AND k.user_id = :user_id'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /** @return array<string, string> */
    public static function validate(array $data): array
    {
        $errors = [];
        $nama = trim((string) ($data['nama'] ?? ''));
        $nisn = only_digits((string) ($data['nisn'] ?? ''));
        $tempatLahir = trim((string) ($data['tempat_lahir'] ?? ''));
        $tanggalLahir = trim((string) ($data['tanggal_lahir'] ?? ''));

        if ($nama === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        }

        if (strlen($nisn) !== 10) {
            $errors['nisn'] = 'NISN harus 10 digit angka.';
        }

        if ($tempatLahir === '') {
            $errors['tempat_lahir'] = 'Tempat lahir wajib diisi.';
        }

        if ($tanggalLahir === '' || !self::isValidDate($tanggalLahir)) {
            $errors['tanggal_lahir'] = 'Tanggal lahir tidak valid (YYYY-MM-DD).';
        }

        return $errors;
    }

    public static function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y);
    }

    public static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        $raw = trim((string) $value);

        if (self::isValidDate($raw)) {
            return $raw;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'] as $format) {
            $dt = DateTime::createFromFormat('!' . $format, $raw);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }
}

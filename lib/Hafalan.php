<?php

declare(strict_types=1);

final class Hafalan
{
    public static function create(int $userId, array $data): array
    {
        $normalized = self::normalizeAyatSelection($data);
        $data['ayat_awal'] = $normalized['ayat_awal'];
        $data['ayat_akhir'] = $normalized['ayat_akhir'];

        $errors = self::validate($data);
        if ($normalized['error'] !== null) {
            $errors['ayat'] = $normalized['error'];
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $siswaId = (int) $data['siswa_id'];
        $siswa = Siswa::findForUser($siswaId, $userId);
        if (!$siswa) {
            return ['ok' => false, 'errors' => ['siswa_id' => 'Siswa tidak ditemukan.']];
        }

        $suratNomor = (int) $data['surat_nomor'];
        $surah = Surah::find($suratNomor);
        if (!$surah) {
            return ['ok' => false, 'errors' => ['surat_nomor' => 'Surat tidak valid.']];
        }

        $ayatAwal = (int) $data['ayat_awal'];
        $ayatAkhir = (int) $data['ayat_akhir'];
        $status = $data['status'] === 'ulang' ? 'ulang' : 'lancar';
        $catatan = trim((string) ($data['catatan'] ?? ''));
        if ($catatan === '') {
            $catatan = null;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO hafalan
                (siswa_id, user_id, surat_nomor, surat_nama, ayat_awal, ayat_akhir, status, catatan)
             VALUES
                (:siswa_id, :user_id, :surat_nomor, :surat_nama, :ayat_awal, :ayat_akhir, :status, :catatan)'
        );
        $stmt->execute([
            'siswa_id' => $siswaId,
            'user_id' => $userId,
            'surat_nomor' => $suratNomor,
            'surat_nama' => $surah['nama'],
            'ayat_awal' => $ayatAwal,
            'ayat_akhir' => $ayatAkhir,
            'status' => $status,
            'catatan' => $catatan,
        ]);

        return [
            'ok' => true,
            'errors' => [],
            'id' => (int) Database::connection()->lastInsertId(),
            'siswa' => $siswa,
            'label' => self::formatLabel($surah['nama'], $ayatAwal, $ayatAkhir, $status),
        ];
    }

    public static function recentForKelas(int $kelasId, int $userId, int $limit = 8): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, s.nama AS siswa_nama
             FROM hafalan h
             INNER JOIN siswa s ON s.id = h.siswa_id
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE s.kelas_id = :kelas_id AND k.user_id = :user_id
             ORDER BY h.created_at DESC, h.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function countTodayForUser(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS total
             FROM hafalan h
             WHERE h.user_id = :user_id
               AND DATE(h.created_at) = CURDATE()'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public static function recentForUser(int $userId, int $limit = 6): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, s.nama AS siswa_nama, k.nama AS kelas_nama
             FROM hafalan h
             INNER JOIN siswa s ON s.id = h.siswa_id
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE h.user_id = :user_id
             ORDER BY h.created_at DESC, h.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function ringkasForKelas(int $kelasId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT
                s.id,
                s.nama,
                s.nisn,
                h.surat_nomor,
                h.surat_nama,
                h.ayat_awal,
                h.ayat_akhir,
                h.created_at AS terakhir_lancar,
                (SELECT COUNT(*) FROM hafalan hx WHERE hx.siswa_id = s.id) AS total_setor,
                (SELECT COUNT(*) FROM hafalan hx WHERE hx.siswa_id = s.id AND hx.status = \'lancar\') AS total_lancar,
                (SELECT COUNT(*) FROM hafalan hx WHERE hx.siswa_id = s.id AND hx.status = \'ulang\') AS total_ulang
             FROM siswa s
             INNER JOIN kelas k ON k.id = s.kelas_id
             LEFT JOIN hafalan h ON h.id = (
                SELECT h2.id
                FROM hafalan h2
                WHERE h2.siswa_id = s.id AND h2.status = \'lancar\'
                ORDER BY h2.created_at DESC, h2.id DESC
                LIMIT 1
             )
             WHERE s.kelas_id = :kelas_id AND k.user_id = :user_id
             ORDER BY s.nama ASC'
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Histori penuh setoran di satu kelas (opsional filter siswa & status).
     *
     * @param array{siswa_id?:int, status?:string, limit?:int} $filters
     */
    public static function historiForKelas(int $kelasId, int $userId, array $filters = []): array
    {
        $sql = 'SELECT h.*, s.nama AS siswa_nama, s.nisn
                FROM hafalan h
                INNER JOIN siswa s ON s.id = h.siswa_id
                INNER JOIN kelas k ON k.id = s.kelas_id
                WHERE s.kelas_id = :kelas_id AND k.user_id = :user_id';
        $params = ['kelas_id' => $kelasId, 'user_id' => $userId];

        $siswaId = (int) ($filters['siswa_id'] ?? 0);
        if ($siswaId > 0) {
            $sql .= ' AND h.siswa_id = :siswa_id';
            $params['siswa_id'] = $siswaId;
        }

        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['lancar', 'ulang'], true)) {
            $sql .= ' AND h.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY h.created_at DESC, h.id DESC';

        $limit = (int) ($filters['limit'] ?? 0);
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function historiForSiswa(int $siswaId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, s.nama AS siswa_nama, s.nisn, s.kelas_id, k.nama AS kelas_nama, k.tahun_ajaran
             FROM hafalan h
             INNER JOIN siswa s ON s.id = h.siswa_id
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE h.siswa_id = :siswa_id AND k.user_id = :user_id
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute(['siswa_id' => $siswaId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function formatAyat(int $ayatAwal, int $ayatAkhir): string
    {
        return $ayatAwal === $ayatAkhir
            ? (string) $ayatAwal
            : $ayatAwal . '–' . $ayatAkhir;
    }

    public static function formatLabel(string $suratNama, int $ayatAwal, int $ayatAkhir, string $status): string
    {
        $statusLabel = $status === 'lancar' ? 'Lancar' : 'Ulang';

        return $suratNama . ' ' . self::formatAyat($ayatAwal, $ayatAkhir) . ' · ' . $statusLabel;
    }

    /**
     * Terima ayat[] dari checkbox, atau ayat_awal/ayat_akhir lama.
     * @return array{ayat_awal:int, ayat_akhir:int, error:?string}
     */
    public static function normalizeAyatSelection(array $data): array
    {
        $selected = $data['ayat'] ?? null;

        if (is_array($selected) && $selected !== []) {
            $nums = [];
            foreach ($selected as $value) {
                $n = (int) $value;
                if ($n > 0) {
                    $nums[$n] = $n;
                }
            }
            $nums = array_values($nums);
            sort($nums, SORT_NUMERIC);

            if ($nums === []) {
                return ['ayat_awal' => 0, 'ayat_akhir' => 0, 'error' => 'Centang minimal satu ayat.'];
            }

            // Wajib berurutan agar rentang valid.
            for ($i = 1, $c = count($nums); $i < $c; $i++) {
                if ($nums[$i] !== $nums[$i - 1] + 1) {
                    return [
                        'ayat_awal' => 0,
                        'ayat_akhir' => 0,
                        'error' => 'Centang ayat secara berurutan (tanpa loncat).',
                    ];
                }
            }

            return [
                'ayat_awal' => $nums[0],
                'ayat_akhir' => $nums[count($nums) - 1],
                'error' => null,
            ];
        }

        return [
            'ayat_awal' => (int) ($data['ayat_awal'] ?? 0),
            'ayat_akhir' => (int) ($data['ayat_akhir'] ?? 0),
            'error' => null,
        ];
    }

    /** @return array<string, string> */
    private static function validate(array $data): array
    {
        $errors = [];
        $siswaId = (int) ($data['siswa_id'] ?? 0);
        $suratNomor = (int) ($data['surat_nomor'] ?? 0);
        $ayatAwal = (int) ($data['ayat_awal'] ?? 0);
        $ayatAkhir = (int) ($data['ayat_akhir'] ?? 0);
        $status = (string) ($data['status'] ?? '');
        $catatan = trim((string) ($data['catatan'] ?? ''));

        if ($siswaId <= 0) {
            $errors['siswa_id'] = 'Pilih siswa.';
        }

        $surah = Surah::find($suratNomor);
        if (!$surah) {
            $errors['surat_nomor'] = 'Pilih surat.';
        }

        if (!in_array($status, ['lancar', 'ulang'], true)) {
            $errors['status'] = 'Pilih status Lancar atau Ulang.';
        }

        if ($surah) {
            $max = $surah['ayat'];
            if ($ayatAwal < 1 || $ayatAwal > $max) {
                $errors['ayat'] = $errors['ayat'] ?? ('Ayat harus 1–' . $max . '.');
            }
            if ($ayatAkhir < 1 || $ayatAkhir > $max) {
                $errors['ayat'] = $errors['ayat'] ?? ('Ayat harus 1–' . $max . '.');
            } elseif ($ayatAkhir < $ayatAwal) {
                $errors['ayat'] = 'Rentang ayat tidak valid.';
            }
        }

        if (mb_strlen($catatan) > 255) {
            $errors['catatan'] = 'Catatan maksimal 255 karakter.';
        }

        return $errors;
    }
}

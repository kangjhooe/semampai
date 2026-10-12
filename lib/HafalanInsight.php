<?php

declare(strict_types=1);

/**
 * Ringkasan "siapa perlu perhatian" untuk dashboard guru.
 */
final class HafalanInsight
{
    /**
     * @return array{
     *   belum_setor: list<array>,
     *   sering_ulang: list<array>,
     *   tertinggal: list<array>
     * }
     */
    public static function attentionForUser(int $userId, int $limit = 6): array
    {
        return [
            'belum_setor' => self::belumSetorMinggu($userId, $limit),
            'sering_ulang' => self::seringUlang($userId, $limit),
            'tertinggal' => self::tertinggalTarget($userId, $limit),
        ];
    }

    /**
     * Siswa yang belum ada setoran/muroja'ah sejak Senin minggu ini.
     *
     * @return list<array{siswa_id:int, nama:string, kelas_id:int, kelas_nama:string, tahun_ajaran:string}>
     */
    public static function belumSetorMinggu(int $userId, int $limit = 8, ?string $npsn = null): array
    {
        $npsn = only_digits($npsn ?? Auth::activeNpsn());
        if ($npsn === '') {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT s.id AS siswa_id, s.nama, k.id AS kelas_id, k.nama AS kelas_nama, k.tahun_ajaran
             FROM siswa s
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE k.user_id = :user_id
               AND k.npsn = :npsn
               AND NOT EXISTS (
                   SELECT 1 FROM hafalan h
                   WHERE h.siswa_id = s.id
                     AND h.created_at >= :mulai_minggu
               )
             ORDER BY k.nama ASC, s.nama ASC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([
            'user_id' => $userId,
            'npsn' => $npsn,
            'mulai_minggu' => self::startOfWeek(),
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Siswa dengan setoran status ulang terbanyak 14 hari terakhir.
     *
     * @return list<array{siswa_id:int, nama:string, kelas_id:int, kelas_nama:string, total_ulang:int}>
     */
    public static function seringUlang(int $userId, int $limit = 8, ?string $npsn = null): array
    {
        $npsn = only_digits($npsn ?? Auth::activeNpsn());
        if ($npsn === '') {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT s.id AS siswa_id, s.nama, k.id AS kelas_id, k.nama AS kelas_nama,
                    COUNT(*) AS total_ulang
             FROM hafalan h
             INNER JOIN siswa s ON s.id = h.siswa_id
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE k.user_id = :user_id
               AND k.npsn = :npsn
               AND h.status = \'ulang\'
               AND h.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
             GROUP BY s.id, s.nama, k.id, k.nama
             HAVING total_ulang >= 2
             ORDER BY total_ulang DESC, s.nama ASC
             LIMIT ' . (int) $limit
        );
        $stmt->execute(['user_id' => $userId, 'npsn' => $npsn]);

        return $stmt->fetchAll();
    }

    /**
     * Siswa paling tertinggal terhadap target kelas (persen tuntas terendah).
     *
     * @return list<array{siswa_id:int, nama:string, kelas_id:int, kelas_nama:string, tuntas:int, total:int, persen:int}>
     */
    public static function tertinggalTarget(int $userId, int $limit = 8): array
    {
        $kelasList = Kelas::allForUser($userId);
        $rows = [];

        foreach ($kelasList as $kelas) {
            $matrix = HafalanProgress::matrixForKelas((int) $kelas['id'], $userId);
            if (!$matrix || $matrix['siswa'] === []) {
                continue;
            }

            $totalSurat = count($matrix['surat']);
            if ($totalSurat <= 0) {
                continue;
            }

            foreach ($matrix['siswa'] as $siswa) {
                $sid = (int) $siswa['id'];
                $ringkas = $matrix['ringkas_siswa'][$sid] ?? null;
                if (!$ringkas) {
                    continue;
                }
                $tuntas = (int) $ringkas['tuntas'];
                $persen = (int) round(($tuntas / $totalSurat) * 100);
                if ($persen >= 100) {
                    continue;
                }

                $rows[] = [
                    'siswa_id' => $sid,
                    'nama' => $siswa['nama'],
                    'kelas_id' => (int) $kelas['id'],
                    'kelas_nama' => $kelas['nama'],
                    'tuntas' => $tuntas,
                    'total' => $totalSurat,
                    'persen' => $persen,
                ];
            }
        }

        usort($rows, static function (array $a, array $b): int {
            if ($a['persen'] !== $b['persen']) {
                return $a['persen'] <=> $b['persen'];
            }

            return strcmp((string) $a['nama'], (string) $b['nama']);
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * ID siswa di kelas yang sudah punya aktivitas hari ini.
     *
     * @return array<int, true>
     */
    public static function siswaSetorHariIni(int $kelasId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT h.siswa_id
             FROM hafalan h
             INNER JOIN siswa s ON s.id = h.siswa_id
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE s.kelas_id = :kelas_id
               AND k.user_id = :user_id
               AND DATE(h.created_at) = CURDATE()'
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['siswa_id']] = true;
        }

        return $map;
    }

    /** Senin minggu berjalan (Y-m-d H:i:s). */
    private static function startOfWeek(): string
    {
        $ts = strtotime('monday this week');
        if ($ts === false) {
            $ts = strtotime('today');
        }

        return date('Y-m-d 00:00:00', $ts ?: time());
    }
}

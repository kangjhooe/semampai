<?php

declare(strict_types=1);

/**
 * Antrian muroja'ah: surat yang sudah tuntas tapi belum diulang dalam interval hari.
 */
final class HafalanMurojaah
{
    public const DEFAULT_INTERVAL_HARI = 7;

    /**
     * @return list<array{
     *   siswa_id:int,
     *   siswa_nama:string,
     *   surat_nomor:int,
     *   surat_nama:string,
     *   jumlah_ayat:int,
     *   terakhir:string,
     *   hari_lalu:int,
     *   setor_url:string
     * }>
     */
    public static function dueForKelas(int $kelasId, int $userId, int $intervalHari = self::DEFAULT_INTERVAL_HARI): array
    {
        $intervalHari = max(1, min(90, $intervalHari));
        $matrix = HafalanProgress::matrixForKelas($kelasId, $userId);
        if (!$matrix) {
            return [];
        }

        $lastMap = self::lastActivityMap($kelasId, $userId);
        $due = [];
        $now = time();

        foreach ($matrix['siswa'] as $siswa) {
            $sid = (int) $siswa['id'];
            foreach ($matrix['surat'] as $surat) {
                $sn = (int) $surat['surat_nomor'];
                $cell = $matrix['cells'][$sid][$sn] ?? null;
                if (!$cell || $cell['status'] !== 'tuntas') {
                    continue;
                }

                $last = $lastMap[$sid][$sn] ?? null;
                if ($last === null) {
                    continue;
                }

                $lastTs = strtotime($last);
                if ($lastTs === false) {
                    continue;
                }

                $hariLalu = (int) floor(($now - $lastTs) / 86400);
                if ($hariLalu < $intervalHari) {
                    continue;
                }

                $due[] = [
                    'siswa_id' => $sid,
                    'siswa_nama' => $siswa['nama'],
                    'surat_nomor' => $sn,
                    'surat_nama' => (string) ($surat['surat_nama'] ?? ('Surat ' . $sn)),
                    'jumlah_ayat' => (int) ($surat['jumlah_ayat'] ?? 0),
                    'terakhir' => $last,
                    'hari_lalu' => $hariLalu,
                    'setor_url' => app_url(
                        'hafalan/index.php?kelas_id=' . $kelasId
                        . '&siswa_id=' . $sid
                        . '&surat=' . $sn
                        . '&jenis=murojaah'
                    ),
                ];
            }
        }

        usort($due, static function (array $a, array $b): int {
            if ($a['hari_lalu'] !== $b['hari_lalu']) {
                return $b['hari_lalu'] <=> $a['hari_lalu'];
            }

            return strcmp((string) $a['siswa_nama'], (string) $b['siswa_nama']);
        });

        return $due;
    }

    /**
     * Aktivitas terakhir (setoran/muroja'ah) per siswa+surat.
     *
     * @return array<int, array<int, string>> siswa_id => surat_nomor => datetime
     */
    private static function lastActivityMap(int $kelasId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.siswa_id, h.surat_nomor, MAX(h.created_at) AS terakhir
             FROM hafalan h
             INNER JOIN siswa s ON s.id = h.siswa_id
             INNER JOIN kelas k ON k.id = s.kelas_id
             WHERE s.kelas_id = :kelas_id AND k.user_id = :user_id
             GROUP BY h.siswa_id, h.surat_nomor'
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['siswa_id']][(int) $row['surat_nomor']] = (string) $row['terakhir'];
        }

        return $map;
    }
}

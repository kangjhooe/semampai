<?php

declare(strict_types=1);

final class HafalanProgress
{
    /**
     * Bangun matriks progres siswa × surat target.
     *
     * @return array{
     *   target: array,
     *   siswa: list<array>,
     *   surat: list<array>,
     *   cells: array<int, array<int, array>>,
     *   ringkas_siswa: array<int, array>,
     *   ringkas_surat: array<int, array>
     * }
     */
    public static function matrixForKelas(int $kelasId, int $userId): ?array
    {
        $target = HafalanTarget::findByKelas($kelasId, $userId);
        if (!$target || $target['items'] === []) {
            return null;
        }

        $siswaList = Siswa::allForKelas($kelasId, $userId);
        $suratList = $target['items'];
        $suratNomor = array_map(static fn ($s) => (int) $s['surat_nomor'], $suratList);

        $covered = self::coveredAyatMap($kelasId, $userId, $suratNomor);

        $cells = [];
        $ringkasSiswa = [];
        $ringkasSurat = [];

        foreach ($suratList as $surat) {
            $sn = (int) $surat['surat_nomor'];
            $ringkasSurat[$sn] = [
                'tuntas' => 0,
                'proses' => 0,
                'belum' => 0,
                'total_siswa' => count($siswaList),
            ];
        }

        foreach ($siswaList as $siswa) {
            $sid = (int) $siswa['id'];
            $ringkasSiswa[$sid] = [
                'tuntas' => 0,
                'proses' => 0,
                'belum' => 0,
                'total_surat' => count($suratList),
            ];

            foreach ($suratList as $surat) {
                $sn = (int) $surat['surat_nomor'];
                $max = (int) $surat['jumlah_ayat'];
                $set = $covered[$sid][$sn] ?? [];
                $cell = self::evaluateCell($set, $max);
                $cells[$sid][$sn] = $cell;

                $ringkasSiswa[$sid][$cell['status']]++;
                $ringkasSurat[$sn][$cell['status']]++;
            }
        }

        return [
            'target' => $target,
            'siswa' => $siswaList,
            'surat' => $suratList,
            'cells' => $cells,
            'ringkas_siswa' => $ringkasSiswa,
            'ringkas_surat' => $ringkasSurat,
        ];
    }

    /**
     * @param list<int> $suratNomor
     * @return array<int, array<int, array<int, true>>> siswa_id => surat_nomor => ayat => true
     */
    private static function coveredAyatMap(int $kelasId, int $userId, array $suratNomor): array
    {
        if ($suratNomor === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($suratNomor), '?'));
        $sql = "SELECT h.siswa_id, h.surat_nomor, h.ayat_awal, h.ayat_akhir
                FROM hafalan h
                INNER JOIN siswa s ON s.id = h.siswa_id
                INNER JOIN kelas k ON k.id = s.kelas_id
                WHERE s.kelas_id = ? AND k.user_id = ? AND h.status = 'lancar'
                  AND h.surat_nomor IN ({$placeholders})";

        $params = array_merge([$kelasId, $userId], $suratNomor);
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $sid = (int) $row['siswa_id'];
            $sn = (int) $row['surat_nomor'];
            $awal = (int) $row['ayat_awal'];
            $akhir = (int) $row['ayat_akhir'];
            for ($a = $awal; $a <= $akhir; $a++) {
                $map[$sid][$sn][$a] = true;
            }
        }

        return $map;
    }

    /**
     * @param array<int, true> $coveredSet
     * @return array{status:string, covered:int, total:int, kurang:int, missing:list<int>, covered_ayat:list<int>, label:string}
     */
    public static function evaluateCell(array $coveredSet, int $total): array
    {
        $coveredAyat = [];
        $missing = [];
        for ($i = 1; $i <= $total; $i++) {
            if (isset($coveredSet[$i])) {
                $coveredAyat[] = $i;
            } else {
                $missing[] = $i;
            }
        }
        $covered = count($coveredAyat);
        $kurang = count($missing);

        if ($covered <= 0) {
            $status = 'belum';
            $label = 'Belum mulai';
        } elseif ($kurang === 0) {
            $status = 'tuntas';
            $label = 'Tuntas';
        } else {
            $status = 'proses';
            $label = 'Kurang ' . $kurang . ' ayat';
        }

        return [
            'status' => $status,
            'covered' => $covered,
            'total' => $total,
            'kurang' => $kurang,
            'missing' => $missing,
            'covered_ayat' => $coveredAyat,
            'label' => $label,
        ];
    }

    public static function formatMissing(array $missing, int $limit = 8): string
    {
        if ($missing === []) {
            return '';
        }
        $show = array_slice($missing, 0, $limit);
        $text = implode(', ', $show);
        if (count($missing) > $limit) {
            $text .= '…';
        }
        return $text;
    }

    /** Ambil run berurutan pertama dari daftar ayat yang belum (untuk prefill ceklis). */
    public static function firstContiguousMissing(array $missing): array
    {
        if ($missing === []) {
            return [];
        }

        $missing = array_values($missing);
        sort($missing, SORT_NUMERIC);
        $run = [$missing[0]];
        for ($i = 1, $n = count($missing); $i < $n; $i++) {
            if ($missing[$i] !== $missing[$i - 1] + 1) {
                break;
            }
            $run[] = $missing[$i];
        }

        return $run;
    }

    /**
     * Ayat yang sudah lancar dari nomor 1 berurutan (untuk prefill ceklis dari rekap).
     *
     * @param list<int> $coveredAyat
     * @return list<int>
     */
    public static function coveredPrefix(array $coveredAyat): array
    {
        if ($coveredAyat === []) {
            return [];
        }

        $set = array_fill_keys($coveredAyat, true);
        $run = [];
        for ($i = 1; isset($set[$i]); $i++) {
            $run[] = $i;
        }

        return $run;
    }

    /**
     * Progres satu siswa pada satu surat (untuk deep-link dari rekap).
     *
     * @return array{status:string, covered:int, total:int, kurang:int, missing:list<int>, covered_ayat:list<int>, label:string}|null
     */
    public static function cellForSiswaSurat(int $siswaId, int $suratNomor, int $userId): ?array
    {
        $siswa = Siswa::findForUser($siswaId, $userId);
        $surah = Surah::find($suratNomor);
        if (!$siswa || !$surah) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            "SELECT ayat_awal, ayat_akhir
             FROM hafalan
             WHERE siswa_id = :siswa_id AND surat_nomor = :surat_nomor AND status = 'lancar'"
        );
        $stmt->execute(['siswa_id' => $siswaId, 'surat_nomor' => $suratNomor]);

        $covered = [];
        foreach ($stmt->fetchAll() as $row) {
            $awal = (int) $row['ayat_awal'];
            $akhir = (int) $row['ayat_akhir'];
            for ($a = $awal; $a <= $akhir; $a++) {
                $covered[$a] = true;
            }
        }

        return self::evaluateCell($covered, (int) $surah['ayat']);
    }

    public static function setorUrl(int $kelasId, int $siswaId, int $suratNomor, bool $fromRekap = true): string
    {
        $url = 'hafalan/index.php?kelas_id=' . $kelasId
            . '&siswa_id=' . $siswaId
            . '&surat=' . $suratNomor;
        if ($fromRekap) {
            $url .= '&from=rekap';
        }

        return app_url($url);
    }
}

<?php

declare(strict_types=1);

final class HafalanTarget
{
    public static function findByKelas(int $kelasId, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*
             FROM target_hafalan t
             INNER JOIN kelas k ON k.id = t.kelas_id
             WHERE t.kelas_id = :kelas_id AND k.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);
        $target = $stmt->fetch();
        if (!$target) {
            return null;
        }

        $target['items'] = self::items((int) $target['id']);
        return $target;
    }

    public static function items(int $targetId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM target_hafalan_item
             WHERE target_id = :target_id
             ORDER BY urutan ASC, surat_nomor ASC'
        );
        $stmt->execute(['target_id' => $targetId]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $surah = Surah::find((int) $row['surat_nomor']);
            if (!$surah) {
                continue;
            }
            $result[] = [
                'surat_nomor' => $surah['nomor'],
                'surat_nama' => $surah['nama'],
                'jumlah_ayat' => $surah['ayat'],
                'urutan' => (int) $row['urutan'],
            ];
        }

        return $result;
    }

    /**
     * @param list<int> $suratNomorList
     */
    public static function saveForKelas(int $kelasId, int $userId, string $judul, array $suratNomorList): array
    {
        $kelas = Kelas::findForUser($kelasId, $userId);
        if (!$kelas) {
            return ['ok' => false, 'errors' => ['_form' => 'Kelas tidak ditemukan.']];
        }

        $judul = trim($judul);
        if ($judul === '') {
            $judul = 'Target hafalan';
        }
        if (mb_strlen($judul) > 150) {
            return ['ok' => false, 'errors' => ['judul' => 'Judul terlalu panjang.']];
        }

        $unique = [];
        foreach ($suratNomorList as $nomor) {
            $n = (int) $nomor;
            if ($n >= 1 && $n <= 114 && Surah::find($n)) {
                $unique[$n] = $n;
            }
        }
        $unique = array_values($unique);
        sort($unique, SORT_NUMERIC);

        if ($unique === []) {
            return ['ok' => false, 'errors' => ['surat' => 'Pilih minimal satu surat target.']];
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();

            $existing = self::findByKelas($kelasId, $userId);
            if ($existing) {
                $targetId = (int) $existing['id'];
                $upd = $pdo->prepare('UPDATE target_hafalan SET judul = :judul WHERE id = :id');
                $upd->execute(['judul' => $judul, 'id' => $targetId]);
                $pdo->prepare('DELETE FROM target_hafalan_item WHERE target_id = :id')->execute(['id' => $targetId]);
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO target_hafalan (kelas_id, judul) VALUES (:kelas_id, :judul)'
                );
                $ins->execute(['kelas_id' => $kelasId, 'judul' => $judul]);
                $targetId = (int) $pdo->lastInsertId();
            }

            $item = $pdo->prepare(
                'INSERT INTO target_hafalan_item (target_id, surat_nomor, urutan)
                 VALUES (:target_id, :surat_nomor, :urutan)'
            );
            foreach ($unique as $i => $nomor) {
                $item->execute([
                    'target_id' => $targetId,
                    'surat_nomor' => $nomor,
                    'urutan' => $i + 1,
                ]);
            }

            $pdo->commit();
            return ['ok' => true, 'errors' => [], 'id' => $targetId];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan target.']];
        }
    }

    /** Template cepat: An-Nas (114) mundur N surat (Juz Amma pendek). */
    public static function templateJuzAmmaPendek(int $jumlah = 10): array
    {
        $list = [];
        for ($n = 114; $n >= 1 && count($list) < $jumlah; $n--) {
            $list[] = $n;
        }
        sort($list, SORT_NUMERIC);
        return $list;
    }
}

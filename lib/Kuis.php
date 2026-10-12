<?php

declare(strict_types=1);

final class Kuis
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public static function allForUser(int $userId, array $filters = []): array
    {
        $kelasId = (int) ($filters['kelas_id'] ?? 0);
        $q = trim((string) ($filters['q'] ?? ''));
        $npsn = only_digits((string) ($filters['npsn'] ?? Auth::activeNpsn()));

        $sql = 'SELECT k.*,
                       kl.nama AS kelas_nama,
                       kl.tahun_ajaran AS kelas_tahun,
                       (SELECT COUNT(*) FROM kuis_soal s WHERE s.kuis_id = k.id) AS jumlah_soal,
                       (SELECT COUNT(*) FROM kuis_hasil h WHERE h.kuis_id = k.id) AS jumlah_hasil
                FROM kuis k
                LEFT JOIN kelas kl ON kl.id = k.kelas_id
                WHERE k.user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($npsn !== '') {
            $sql .= ' AND (k.kelas_id IS NULL OR kl.npsn = :npsn)';
            $params['npsn'] = $npsn;
        }

        if ($kelasId > 0) {
            $sql .= ' AND k.kelas_id = :kelas_id';
            $params['kelas_id'] = $kelasId;
        }

        if ($q !== '') {
            $sql .= ' AND (k.judul LIKE :q OR k.kode LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY k.updated_at DESC, k.id DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT k.*,
                    kl.nama AS kelas_nama,
                    kl.tahun_ajaran AS kelas_tahun
             FROM kuis k
             LEFT JOIN kelas kl ON kl.id = k.kelas_id
             WHERE k.id = :id AND k.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByKode(string $kode): ?array
    {
        $kode = self::normalizeKode($kode);
        if ($kode === '') {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM kuis WHERE kode = :kode LIMIT 1'
        );
        $stmt->execute(['kode' => $kode]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(int $userId, array $data): array
    {
        $errors = self::validateMeta($data, $userId);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $pdo = Database::connection();

        try {
            $kode = self::generateUniqueKode($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO kuis (user_id, kelas_id, judul, kode, aktif, acak_soal, acak_opsi, timer_menit, izinkan_ulang, tampil_pembahasan, mode_latihan)
                 VALUES (:user_id, :kelas_id, :judul, :kode, :aktif, :acak_soal, :acak_opsi, :timer_menit, :izinkan_ulang, :tampil_pembahasan, :mode_latihan)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'kelas_id' => self::nullableKelasId($data),
                'judul' => trim((string) $data['judul']),
                'kode' => $kode,
                'aktif' => !empty($data['aktif']) ? 1 : 0,
                'acak_soal' => !empty($data['acak_soal']) ? 1 : 0,
                'acak_opsi' => !empty($data['acak_opsi']) ? 1 : 0,
                'timer_menit' => self::nullableTimerMenit($data),
                'izinkan_ulang' => !empty($data['izinkan_ulang']) ? 1 : 0,
                'tampil_pembahasan' => !empty($data['tampil_pembahasan']) ? 1 : 0,
                'mode_latihan' => !empty($data['mode_latihan']) ? 1 : 0,
            ]);

            return [
                'ok' => true,
                'errors' => [],
                'id' => (int) $pdo->lastInsertId(),
                'kode' => $kode,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['_form' => 'Gagal membuat kuis.']];
        }
    }

    public static function update(int $id, int $userId, array $data): array
    {
        if (!self::findForUser($id, $userId)) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis tidak ditemukan.']];
        }

        $errors = self::validateMeta($data, $userId);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $stmt = Database::connection()->prepare(
                'UPDATE kuis
                 SET kelas_id = :kelas_id,
                     judul = :judul,
                     aktif = :aktif,
                     acak_soal = :acak_soal,
                     acak_opsi = :acak_opsi,
                     timer_menit = :timer_menit,
                     izinkan_ulang = :izinkan_ulang,
                     tampil_pembahasan = :tampil_pembahasan,
                     mode_latihan = :mode_latihan
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'kelas_id' => self::nullableKelasId($data),
                'judul' => trim((string) $data['judul']),
                'aktif' => !empty($data['aktif']) ? 1 : 0,
                'acak_soal' => !empty($data['acak_soal']) ? 1 : 0,
                'acak_opsi' => !empty($data['acak_opsi']) ? 1 : 0,
                'timer_menit' => self::nullableTimerMenit($data),
                'izinkan_ulang' => !empty($data['izinkan_ulang']) ? 1 : 0,
                'tampil_pembahasan' => !empty($data['tampil_pembahasan']) ? 1 : 0,
                'mode_latihan' => !empty($data['mode_latihan']) ? 1 : 0,
                'id' => $id,
                'user_id' => $userId,
            ]);

            return ['ok' => true, 'errors' => []];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['_form' => 'Gagal memperbarui kuis.']];
        }
    }

    public static function delete(int $id, int $userId): bool
    {
        $kuis = self::findForUser($id, $userId);
        if (!$kuis) {
            return false;
        }

        foreach (self::soalForKuis($id) as $soal) {
            self::unlinkGambarPath((string) ($soal['gambar_path'] ?? ''));
        }

        $stmt = Database::connection()->prepare(
            'DELETE FROM kuis WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array{ok:bool, errors:array<string,string>, id?:int, kode?:string}
     */
    public static function duplicate(int $id, int $userId, ?int $kelasId = null): array
    {
        $kuis = self::findForUser($id, $userId);
        if (!$kuis) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis tidak ditemukan.']];
        }

        if ($kelasId !== null && $kelasId > 0 && !Kelas::findForUser($kelasId, $userId)) {
            return ['ok' => false, 'errors' => ['kelas_id' => 'Kelas tidak valid.']];
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $kode = self::generateUniqueKode($pdo);
            $judul = trim((string) $kuis['judul']);
            if (!str_ends_with($judul, '(salinan)')) {
                $judul .= ' (salinan)';
            }
            if (mb_strlen($judul) > 200) {
                $judul = mb_substr($judul, 0, 200);
            }

            $newKelasId = $kelasId !== null
                ? ($kelasId > 0 ? $kelasId : null)
                : (isset($kuis['kelas_id']) && $kuis['kelas_id'] !== null ? (int) $kuis['kelas_id'] : null);

            $stmt = $pdo->prepare(
                'INSERT INTO kuis (user_id, kelas_id, judul, kode, aktif, acak_soal, acak_opsi, timer_menit, izinkan_ulang, tampil_pembahasan, mode_latihan)
                 VALUES (:user_id, :kelas_id, :judul, :kode, :aktif, :acak_soal, :acak_opsi, :timer_menit, :izinkan_ulang, :tampil_pembahasan, :mode_latihan)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'kelas_id' => $newKelasId,
                'judul' => $judul,
                'kode' => $kode,
                'aktif' => 0,
                'acak_soal' => (int) $kuis['acak_soal'],
                'acak_opsi' => (int) ($kuis['acak_opsi'] ?? 0),
                'timer_menit' => $kuis['timer_menit'] !== null ? (int) $kuis['timer_menit'] : null,
                'izinkan_ulang' => (int) ($kuis['izinkan_ulang'] ?? 0),
                'tampil_pembahasan' => (int) ($kuis['tampil_pembahasan'] ?? 1),
                'mode_latihan' => (int) ($kuis['mode_latihan'] ?? 0),
            ]);
            $newId = (int) $pdo->lastInsertId();

            $insertSoal = $pdo->prepare(
                'INSERT INTO kuis_soal (kuis_id, urutan, pertanyaan, tipe, opsi_json, jawaban_index, jawaban_teks, timer_detik, gambar_path, gambar_url)
                 VALUES (:kuis_id, :urutan, :pertanyaan, :tipe, :opsi_json, :jawaban_index, :jawaban_teks, :timer_detik, :gambar_path, :gambar_url)'
            );

            foreach (self::soalForKuis($id) as $soal) {
                $newPath = null;
                $oldPath = (string) ($soal['gambar_path'] ?? '');
                if ($oldPath !== '') {
                    $newPath = self::copyGambarPath($oldPath);
                }
                $insertSoal->execute([
                    'kuis_id' => $newId,
                    'urutan' => (int) $soal['urutan'],
                    'pertanyaan' => (string) $soal['pertanyaan'],
                    'tipe' => (string) ($soal['tipe'] ?? 'pilihan_ganda'),
                    'opsi_json' => is_string($soal['opsi_json'] ?? null)
                        ? $soal['opsi_json']
                        : json_encode($soal['opsi'], JSON_UNESCAPED_UNICODE),
                    'jawaban_index' => (int) $soal['jawaban_index'],
                    'jawaban_teks' => $soal['jawaban_teks'] ?? null,
                    'timer_detik' => isset($soal['timer_detik']) && $soal['timer_detik'] !== null ? (int) $soal['timer_detik'] : null,
                    'gambar_path' => $newPath,
                    'gambar_url' => $soal['gambar_url'] ?? null,
                ]);
            }

            $pdo->commit();

            return ['ok' => true, 'errors' => [], 'id' => $newId, 'kode' => $kode];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menduplikasi kuis.']];
        }
    }

    /** @return list<array<string, mixed>> */
    public static function soalForKuis(int $kuisId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM kuis_soal WHERE kuis_id = :kuis_id ORDER BY urutan ASC, id ASC'
        );
        $stmt->execute(['kuis_id' => $kuisId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['tipe'] = (string) ($row['tipe'] ?? 'pilihan_ganda');
            $row['opsi'] = self::decodeOpsi($row['opsi_json'] ?? '[]');
            $row['jawaban_index'] = (int) $row['jawaban_index'];
            $row['timer_detik'] = isset($row['timer_detik']) && $row['timer_detik'] !== null
                ? (int) $row['timer_detik']
                : null;
        }
        unset($row);

        return $rows;
    }

    /** Soal untuk peserta: tanpa kunci jawaban. Opsi bisa berisi {i,teks} jika diacak. */
    public static function soalUntukPeserta(int $kuisId, bool $acakSoal = false, bool $acakOpsi = false): array
    {
        $soal = self::soalForKuis($kuisId);
        if ($acakSoal && $soal !== []) {
            shuffle($soal);
        }

        $out = [];
        foreach ($soal as $row) {
            $tipe = (string) ($row['tipe'] ?? 'pilihan_ganda');
            $opsiItems = [];
            if ($tipe === 'pilihan_ganda') {
                foreach ($row['opsi'] as $i => $teks) {
                    $opsiItems[] = ['i' => (int) $i, 'teks' => (string) $teks];
                }
                if ($acakOpsi && count($opsiItems) > 1) {
                    shuffle($opsiItems);
                }
            }

            $out[] = [
                'id' => (int) $row['id'],
                'pertanyaan' => (string) $row['pertanyaan'],
                'tipe' => $tipe,
                'opsi' => $opsiItems,
                'timer_detik' => $row['timer_detik'],
                'gambar' => self::gambarPublicUrl($row),
            ];
        }

        return $out;
    }

    public static function needsStepMode(array $kuis, array $soalPeserta): bool
    {
        if ((int) ($kuis['mode_latihan'] ?? 0) === 1) {
            return true;
        }
        foreach ($soalPeserta as $s) {
            if (!empty($s['timer_detik']) && (int) $s['timer_detik'] > 0) {
                return true;
            }
        }

        return false;
    }

    public static function moveSoal(int $soalId, int $userId, string $arah): bool
    {
        $soal = self::findSoalForUser($soalId, $userId);
        if (!$soal) {
            return false;
        }

        $list = self::soalForKuis((int) $soal['kuis_id']);
        $idx = -1;
        foreach ($list as $i => $row) {
            if ((int) $row['id'] === $soalId) {
                $idx = $i;
                break;
            }
        }
        if ($idx < 0) {
            return false;
        }

        $swapWith = $arah === 'up' ? $idx - 1 : $idx + 1;
        if ($swapWith < 0 || $swapWith >= count($list)) {
            return false;
        }

        $a = $list[$idx];
        $b = $list[$swapWith];
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE kuis_soal SET urutan = :urutan WHERE id = :id');
        $stmt->execute(['urutan' => (int) $b['urutan'], 'id' => (int) $a['id']]);
        $stmt->execute(['urutan' => (int) $a['urutan'], 'id' => (int) $b['id']]);

        // Jika urutan sama, pakai id sebagai penstabil dengan menormalisasi ulang.
        if ((int) $a['urutan'] === (int) $b['urutan']) {
            foreach ($list as $i => $row) {
                $stmt->execute(['urutan' => $i + 1, 'id' => (int) $row['id']]);
            }
            $list = self::soalForKuis((int) $soal['kuis_id']);
            $idx = -1;
            foreach ($list as $i => $row) {
                if ((int) $row['id'] === $soalId) {
                    $idx = $i;
                    break;
                }
            }
            $swapWith = $arah === 'up' ? $idx - 1 : $idx + 1;
            if ($idx < 0 || $swapWith < 0 || $swapWith >= count($list)) {
                return true;
            }
            $a = $list[$idx];
            $b = $list[$swapWith];
            $stmt->execute(['urutan' => (int) $b['urutan'], 'id' => (int) $a['id']]);
            $stmt->execute(['urutan' => (int) $a['urutan'], 'id' => (int) $b['id']]);
        }

        return true;
    }

    public static function deleteHasil(int $hasilId, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE h FROM kuis_hasil h
             INNER JOIN kuis k ON k.id = h.kuis_id
             WHERE h.id = :id AND k.user_id = :user_id'
        );
        $stmt->execute(['id' => $hasilId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function normalizeIsian(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    /**
     * @return array{ok:bool, pilih:int|string, benar:int|string, pilih_teks:string, benar_teks:string}
     */
    public static function gradeJawaban(array $soalRow, mixed $answer): array
    {
        $tipe = (string) ($soalRow['tipe'] ?? 'pilihan_ganda');
        if ($tipe === 'isian') {
            $pilihTeks = trim((string) $answer);
            $benarTeks = trim((string) ($soalRow['jawaban_teks'] ?? ''));
            $ok = $pilihTeks !== '' && self::normalizeIsian($pilihTeks) === self::normalizeIsian($benarTeks);

            return [
                'ok' => $ok,
                'pilih' => $pilihTeks,
                'benar' => $benarTeks,
                'pilih_teks' => $pilihTeks !== '' ? $pilihTeks : '(tidak dijawab)',
                'benar_teks' => $benarTeks,
            ];
        }

        $benar = (int) $soalRow['jawaban_index'];
        $pilih = $answer === '' || $answer === null ? -1 : (int) $answer;
        $opsi = is_array($soalRow['opsi'] ?? null) ? $soalRow['opsi'] : self::decodeOpsi($soalRow['opsi_json'] ?? '[]');
        $ok = $pilih === $benar;

        return [
            'ok' => $ok,
            'pilih' => $pilih,
            'benar' => $benar,
            'pilih_teks' => $pilih >= 0 ? (string) ($opsi[$pilih] ?? '') : '(tidak dijawab)',
            'benar_teks' => (string) ($opsi[$benar] ?? ''),
        ];
    }

    public static function gambarPublicUrl(array $soal): ?string
    {
        if (!empty($soal['gambar_path'])) {
            return app_url('kuis/gambar.php?soal_id=' . (int) $soal['id']);
        }

        $url = trim((string) ($soal['gambar_url'] ?? ''));
        return $url !== '' ? $url : null;
    }

    public static function findSoalById(int $soalId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, k.aktif AS kuis_aktif, k.user_id AS kuis_user_id
             FROM kuis_soal s
             INNER JOIN kuis k ON k.id = s.kuis_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $soalId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function addSoal(int $kuisId, int $userId, array $data, ?array $file = null): array
    {
        if (!self::findForUser($kuisId, $userId)) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis tidak ditemukan.']];
        }

        $parsed = self::parseSoalInput($data);
        if ($parsed['errors'] !== []) {
            return ['ok' => false, 'errors' => $parsed['errors']];
        }

        $gambar = self::resolveGambarInput($data, $file, null);
        if (!$gambar['ok']) {
            return ['ok' => false, 'errors' => $gambar['errors']];
        }

        $pdo = Database::connection();
        $urutanStmt = $pdo->prepare('SELECT COALESCE(MAX(urutan), 0) + 1 FROM kuis_soal WHERE kuis_id = :kuis_id');
        $urutanStmt->execute(['kuis_id' => $kuisId]);
        $urutan = (int) $urutanStmt->fetchColumn();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO kuis_soal (kuis_id, urutan, pertanyaan, tipe, opsi_json, jawaban_index, jawaban_teks, timer_detik, gambar_path, gambar_url)
                 VALUES (:kuis_id, :urutan, :pertanyaan, :tipe, :opsi_json, :jawaban_index, :jawaban_teks, :timer_detik, :gambar_path, :gambar_url)'
            );
            $stmt->execute([
                'kuis_id' => $kuisId,
                'urutan' => $urutan,
                'pertanyaan' => $parsed['pertanyaan'],
                'tipe' => $parsed['tipe'],
                'opsi_json' => json_encode($parsed['opsi'], JSON_UNESCAPED_UNICODE),
                'jawaban_index' => $parsed['jawaban_index'],
                'jawaban_teks' => $parsed['jawaban_teks'],
                'timer_detik' => $parsed['timer_detik'],
                'gambar_path' => $gambar['path'],
                'gambar_url' => $gambar['url'],
            ]);

            return ['ok' => true, 'errors' => [], 'id' => (int) $pdo->lastInsertId()];
        } catch (Throwable $e) {
            if (!empty($gambar['path'])) {
                self::unlinkGambarPath((string) $gambar['path']);
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menambah soal.']];
        }
    }

    public static function findSoalForUser(int $soalId, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*
             FROM kuis_soal s
             INNER JOIN kuis k ON k.id = s.kuis_id
             WHERE s.id = :id AND k.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $soalId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['tipe'] = (string) ($row['tipe'] ?? 'pilihan_ganda');
        $row['opsi'] = self::decodeOpsi($row['opsi_json'] ?? '[]');
        $row['jawaban_index'] = (int) $row['jawaban_index'];
        $row['timer_detik'] = isset($row['timer_detik']) && $row['timer_detik'] !== null
            ? (int) $row['timer_detik']
            : null;

        return $row;
    }

    public static function updateSoal(int $soalId, int $userId, array $data, ?array $file = null): array
    {
        $soal = self::findSoalForUser($soalId, $userId);
        if (!$soal) {
            return ['ok' => false, 'errors' => ['_form' => 'Soal tidak ditemukan.']];
        }

        $parsed = self::parseSoalInput($data);
        if ($parsed['errors'] !== []) {
            return ['ok' => false, 'errors' => $parsed['errors']];
        }

        $gambar = self::resolveGambarInput($data, $file, $soal);
        if (!$gambar['ok']) {
            return ['ok' => false, 'errors' => $gambar['errors']];
        }

        try {
            $stmt = Database::connection()->prepare(
                'UPDATE kuis_soal
                 SET pertanyaan = :pertanyaan,
                     tipe = :tipe,
                     opsi_json = :opsi_json,
                     jawaban_index = :jawaban_index,
                     jawaban_teks = :jawaban_teks,
                     timer_detik = :timer_detik,
                     gambar_path = :gambar_path,
                     gambar_url = :gambar_url
                 WHERE id = :id'
            );
            $stmt->execute([
                'pertanyaan' => $parsed['pertanyaan'],
                'tipe' => $parsed['tipe'],
                'opsi_json' => json_encode($parsed['opsi'], JSON_UNESCAPED_UNICODE),
                'jawaban_index' => $parsed['jawaban_index'],
                'jawaban_teks' => $parsed['jawaban_teks'],
                'timer_detik' => $parsed['timer_detik'],
                'gambar_path' => $gambar['path'],
                'gambar_url' => $gambar['url'],
                'id' => $soalId,
            ]);

            if (!empty($gambar['unlink_old'])) {
                self::unlinkGambarPath((string) $gambar['unlink_old']);
            }

            return ['ok' => true, 'errors' => []];
        } catch (Throwable $e) {
            if (!empty($gambar['path']) && $gambar['path'] !== ($soal['gambar_path'] ?? null)) {
                self::unlinkGambarPath((string) $gambar['path']);
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal memperbarui soal.']];
        }
    }

    public static function deleteSoal(int $soalId, int $userId): bool
    {
        $soal = self::findSoalForUser($soalId, $userId);
        if (!$soal) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE s FROM kuis_soal s
             INNER JOIN kuis k ON k.id = s.kuis_id
             WHERE s.id = :id AND k.user_id = :user_id'
        );
        $stmt->execute(['id' => $soalId, 'user_id' => $userId]);
        if ($stmt->rowCount() > 0) {
            self::unlinkGambarPath((string) ($soal['gambar_path'] ?? ''));

            return true;
        }

        return false;
    }

    /**
     * @param list<array{soal_id:int,pilih:int,benar:int,ok:bool}> $detail
     * @return list<array<string, mixed>>
     */
    public static function buildReview(array $detail): array
    {
        if ($detail === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn ($d) => (int) $d['soal_id'], $detail)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id, pertanyaan, tipe, opsi_json, jawaban_index, jawaban_teks, gambar_path, gambar_url
             FROM kuis_soal
             WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        $byId = [];
        foreach ($stmt->fetchAll() as $row) {
            $byId[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'pertanyaan' => (string) $row['pertanyaan'],
                'tipe' => (string) ($row['tipe'] ?? 'pilihan_ganda'),
                'opsi' => self::decodeOpsi($row['opsi_json'] ?? '[]'),
                'jawaban_index' => (int) $row['jawaban_index'],
                'jawaban_teks' => (string) ($row['jawaban_teks'] ?? ''),
                'gambar_path' => $row['gambar_path'] ?? null,
                'gambar_url' => $row['gambar_url'] ?? null,
            ];
        }

        $labels = ['A', 'B', 'C', 'D', 'E', 'F'];
        $review = [];
        foreach ($detail as $i => $item) {
            $sid = (int) $item['soal_id'];
            $soal = $byId[$sid] ?? null;
            if (!$soal) {
                continue;
            }
            $tipe = (string) ($item['tipe'] ?? $soal['tipe']);
            if ($tipe === 'isian') {
                $review[] = [
                    'nomor' => $i + 1,
                    'tipe' => 'isian',
                    'pertanyaan' => $soal['pertanyaan'],
                    'opsi' => [],
                    'gambar' => self::gambarPublicUrl($soal),
                    'pilih' => -1,
                    'benar' => 0,
                    'ok' => (bool) $item['ok'],
                    'pilih_label' => 'Isian',
                    'benar_label' => 'Kunci',
                    'pilih_teks' => (string) ($item['pilih_teks'] ?? $item['pilih'] ?? '(tidak dijawab)'),
                    'benar_teks' => (string) ($item['benar_teks'] ?? $soal['jawaban_teks']),
                ];
                continue;
            }

            $pilih = (int) $item['pilih'];
            $benar = (int) ($item['benar'] ?? $soal['jawaban_index']);
            $review[] = [
                'nomor' => $i + 1,
                'tipe' => 'pilihan_ganda',
                'pertanyaan' => $soal['pertanyaan'],
                'opsi' => $soal['opsi'],
                'gambar' => self::gambarPublicUrl($soal),
                'pilih' => $pilih,
                'benar' => $benar,
                'ok' => (bool) $item['ok'],
                'pilih_label' => $pilih >= 0 ? ($labels[$pilih] ?? (string) ($pilih + 1)) : '—',
                'benar_label' => $labels[$benar] ?? (string) ($benar + 1),
                'pilih_teks' => (string) ($item['pilih_teks'] ?? ($pilih >= 0 ? (string) ($soal['opsi'][$pilih] ?? '') : '(tidak dijawab)')),
                'benar_teks' => (string) ($item['benar_teks'] ?? ($soal['opsi'][$benar] ?? '')),
            ];
        }

        return $review;
    }

    /** @return list<array<string, mixed>> */
    public static function peringkatForKuis(int $kuisId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = Database::connection()->prepare(
            'SELECT id, nama_peserta, skor, total, created_at
             FROM kuis_hasil
             WHERE kuis_id = :kuis_id
             ORDER BY skor DESC, created_at ASC, id ASC
             LIMIT ' . $limit
        );
        $stmt->execute(['kuis_id' => $kuisId]);
        $rows = $stmt->fetchAll();

        $rank = 1;
        foreach ($rows as &$row) {
            $row['peringkat'] = $rank++;
            $total = max(1, (int) $row['total']);
            $row['persen'] = (int) round(((int) $row['skor'] / $total) * 100);
        }
        unset($row);

        return $rows;
    }

    /** @return array{peringkat:int, total_peserta:int}|null */
    public static function posisiPeserta(int $kuisId, int $hasilId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, skor, created_at FROM kuis_hasil WHERE id = :id AND kuis_id = :kuis_id LIMIT 1'
        );
        $stmt->execute(['id' => $hasilId, 'kuis_id' => $kuisId]);
        $mine = $stmt->fetch();
        if (!$mine) {
            return null;
        }

        $countStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM kuis_hasil WHERE kuis_id = :kuis_id'
        );
        $countStmt->execute(['kuis_id' => $kuisId]);
        $totalPeserta = (int) $countStmt->fetchColumn();

        $rankStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM kuis_hasil
             WHERE kuis_id = :kuis_id
               AND (
                 skor > :skor
                 OR (skor = :skor2 AND created_at < :created_at)
                 OR (skor = :skor3 AND created_at = :created_at2 AND id < :id)
               )'
        );
        $rankStmt->execute([
            'kuis_id' => $kuisId,
            'skor' => (int) $mine['skor'],
            'skor2' => (int) $mine['skor'],
            'skor3' => (int) $mine['skor'],
            'created_at' => $mine['created_at'],
            'created_at2' => $mine['created_at'],
            'id' => $hasilId,
        ]);

        return [
            'peringkat' => (int) $rankStmt->fetchColumn() + 1,
            'total_peserta' => $totalPeserta,
        ];
    }

    /** @return list<array{id:int, nama:string, nisn:string}> */
    public static function rosterSiswa(int $kuisId): array
    {
        $kuis = self::findById($kuisId);
        if (!$kuis || empty($kuis['kelas_id'])) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, nama, nisn
             FROM siswa
             WHERE kelas_id = :kelas_id
             ORDER BY nama ASC'
        );
        $stmt->execute(['kelas_id' => (int) $kuis['kelas_id']]);

        return $stmt->fetchAll();
    }

    public static function findSiswaInKuisKelas(int $kuisId, int $siswaId): ?array
    {
        $kuis = self::findById($kuisId);
        if (!$kuis || empty($kuis['kelas_id']) || $siswaId <= 0) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, nama, nisn, kelas_id
             FROM siswa
             WHERE id = :id AND kelas_id = :kelas_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $siswaId,
            'kelas_id' => (int) $kuis['kelas_id'],
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function normalizeNamaKey(string $nama): string
    {
        $nama = mb_strtolower(trim($nama));
        $nama = preg_replace('/\s+/u', ' ', $nama) ?? $nama;

        return $nama;
    }

    public static function canAttempt(int $kuisId, string $namaPeserta, ?int $siswaId = null): bool
    {
        $kuis = self::findById($kuisId);
        if (!$kuis) {
            return false;
        }
        if ((int) ($kuis['izinkan_ulang'] ?? 0) === 1) {
            return true;
        }

        return !self::sudahMengerjakan($kuisId, $namaPeserta, $siswaId);
    }

    public static function sudahMengerjakan(int $kuisId, string $namaPeserta, ?int $siswaId = null): bool
    {
        $pdo = Database::connection();
        $namaKey = self::normalizeNamaKey($namaPeserta);

        $stmt = $pdo->prepare(
            'SELECT nama_peserta, siswa_id FROM kuis_hasil WHERE kuis_id = :kuis_id'
        );
        $stmt->execute(['kuis_id' => $kuisId]);
        foreach ($stmt->fetchAll() as $row) {
            if ($siswaId !== null && $siswaId > 0 && (int) ($row['siswa_id'] ?? 0) === $siswaId) {
                return true;
            }
            if ($namaKey !== '' && self::normalizeNamaKey((string) $row['nama_peserta']) === $namaKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $jawabanMap soal_id => opsi index
     * @return array{ok:bool, errors:array<string,string>, skor?:int, total?:int, hasil_id?:int, detail?:list<array<string,mixed>>}
     */
    public static function submitHasil(int $kuisId, string $namaPeserta, array $jawabanMap, ?int $siswaId = null): array
    {
        $kuis = self::findById($kuisId);
        if (!$kuis || !(int) $kuis['aktif']) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis tidak tersedia.']];
        }

        $nama = trim($namaPeserta);
        if ($nama === '' || mb_strlen($nama) > 120) {
            return ['ok' => false, 'errors' => ['nama' => 'Nama wajib diisi (maks. 120 karakter).']];
        }

        if ($siswaId !== null && $siswaId > 0) {
            $siswa = self::findSiswaInKuisKelas($kuisId, $siswaId);
            if (!$siswa) {
                return ['ok' => false, 'errors' => ['siswa_id' => 'Siswa tidak valid untuk kuis ini.']];
            }
            $nama = (string) $siswa['nama'];
        } else {
            $siswaId = null;
        }

        if (!self::canAttempt($kuisId, $nama, $siswaId)) {
            return ['ok' => false, 'errors' => ['_form' => 'Anda sudah mengerjakan kuis ini. Pengulangan tidak diizinkan.']];
        }

        $soal = self::soalForKuis($kuisId);
        if ($soal === []) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis belum memiliki soal.']];
        }

        $skor = 0;
        $detail = [];
        foreach ($soal as $row) {
            $sid = (int) $row['id'];
            $raw = $jawabanMap[$sid] ?? ($jawabanMap[(string) $sid] ?? null);
            $graded = self::gradeJawaban($row, $raw);
            if ($graded['ok']) {
                $skor++;
            }
            $detail[] = [
                'soal_id' => $sid,
                'tipe' => (string) ($row['tipe'] ?? 'pilihan_ganda'),
                'pilih' => $graded['pilih'],
                'benar' => $graded['benar'],
                'pilih_teks' => $graded['pilih_teks'],
                'benar_teks' => $graded['benar_teks'],
                'ok' => $graded['ok'],
            ];
        }

        $total = count($soal);

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO kuis_hasil (kuis_id, nama_peserta, siswa_id, skor, total, jawaban_json)
                 VALUES (:kuis_id, :nama_peserta, :siswa_id, :skor, :total, :jawaban_json)'
            );
            $stmt->execute([
                'kuis_id' => $kuisId,
                'nama_peserta' => $nama,
                'siswa_id' => $siswaId,
                'skor' => $skor,
                'total' => $total,
                'jawaban_json' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            ]);

            return [
                'ok' => true,
                'errors' => [],
                'skor' => $skor,
                'total' => $total,
                'hasil_id' => (int) Database::connection()->lastInsertId(),
                'detail' => $detail,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan hasil.']];
        }
    }

    /**
     * @param 'peringkat'|'terbaru' $sort
     * @return list<array<string, mixed>>
     */
    public static function hasilForKuis(int $kuisId, int $userId, string $sort = 'peringkat'): array
    {
        if (!self::findForUser($kuisId, $userId)) {
            return [];
        }

        $order = $sort === 'terbaru'
            ? 'created_at DESC, id DESC'
            : 'skor DESC, created_at ASC, id ASC';

        $stmt = Database::connection()->prepare(
            "SELECT id, nama_peserta, skor, total, created_at
             FROM kuis_hasil
             WHERE kuis_id = :kuis_id
             ORDER BY {$order}"
        );
        $stmt->execute(['kuis_id' => $kuisId]);
        $rows = $stmt->fetchAll();

        if ($sort === 'peringkat') {
            $rank = 1;
            foreach ($rows as &$row) {
                $row['peringkat'] = $rank++;
            }
            unset($row);
        }

        return $rows;
    }

    public static function findHasilForUser(int $hasilId, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, k.judul AS kuis_judul, k.kode AS kuis_kode, k.id AS kuis_id_ref
             FROM kuis_hasil h
             INNER JOIN kuis k ON k.id = h.kuis_id
             WHERE h.id = :id AND k.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $hasilId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $detail = json_decode((string) ($row['jawaban_json'] ?? '[]'), true);
        if (!is_array($detail)) {
            $detail = [];
        }

        $row['detail'] = $detail;
        $row['review'] = self::buildReview($detail);

        return $row;
    }

    /**
     * Statistik per soal: berapa kali dijawab benar/salah.
     *
     * @return list<array<string, mixed>>
     */
    public static function statistikSoal(int $kuisId, int $userId): array
    {
        if (!self::findForUser($kuisId, $userId)) {
            return [];
        }

        $soalList = self::soalForKuis($kuisId);
        if ($soalList === []) {
            return [];
        }

        $stats = [];
        foreach ($soalList as $i => $soal) {
            $sid = (int) $soal['id'];
            $stats[$sid] = [
                'soal_id' => $sid,
                'nomor' => $i + 1,
                'pertanyaan' => (string) $soal['pertanyaan'],
                'total' => 0,
                'benar' => 0,
                'salah' => 0,
                'kosong' => 0,
            ];
        }

        $stmt = Database::connection()->prepare(
            'SELECT jawaban_json FROM kuis_hasil WHERE kuis_id = :kuis_id'
        );
        $stmt->execute(['kuis_id' => $kuisId]);

        foreach ($stmt->fetchAll() as $row) {
            $detail = json_decode((string) ($row['jawaban_json'] ?? '[]'), true);
            if (!is_array($detail)) {
                continue;
            }
            foreach ($detail as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $sid = (int) ($item['soal_id'] ?? 0);
                if (!isset($stats[$sid])) {
                    continue;
                }
                $stats[$sid]['total']++;
                $pilih = (int) ($item['pilih'] ?? -1);
                if ($pilih < 0) {
                    $stats[$sid]['kosong']++;
                    $stats[$sid]['salah']++;
                } elseif (!empty($item['ok'])) {
                    $stats[$sid]['benar']++;
                } else {
                    $stats[$sid]['salah']++;
                }
            }
        }

        $out = array_values($stats);
        usort($out, static function (array $a, array $b): int {
            $pa = $a['total'] > 0 ? ($a['salah'] / $a['total']) : 0;
            $pb = $b['total'] > 0 ? ($b['salah'] / $b['total']) : 0;
            if ($pa === $pb) {
                return $a['nomor'] <=> $b['nomor'];
            }

            return $pb <=> $pa;
        });

        foreach ($out as &$item) {
            $total = max(0, (int) $item['total']);
            $item['persen_salah'] = $total > 0
                ? (int) round(((int) $item['salah'] / $total) * 100)
                : 0;
            $item['persen_benar'] = $total > 0
                ? (int) round(((int) $item['benar'] / $total) * 100)
                : 0;
        }
        unset($item);

        return $out;
    }

    public static function normalizeKode(string $kode): string
    {
        $kode = strtoupper(trim($kode));
        $kode = preg_replace('/[^A-Z0-9]/', '', $kode) ?? '';

        return $kode;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM kuis WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, string> */
    private static function validateMeta(array $data, int $userId): array
    {
        $errors = [];
        $judul = trim((string) ($data['judul'] ?? ''));
        if ($judul === '') {
            $errors['judul'] = 'Judul wajib diisi.';
        } elseif (mb_strlen($judul) > 200) {
            $errors['judul'] = 'Judul maksimal 200 karakter.';
        }

        $kelasId = (int) ($data['kelas_id'] ?? 0);
        if ($kelasId > 0 && !Kelas::findForUser($kelasId, $userId)) {
            $errors['kelas_id'] = 'Kelas tidak valid.';
        }

        $timerRaw = trim((string) ($data['timer_menit'] ?? ''));
        if ($timerRaw !== '') {
            if (!ctype_digit($timerRaw)) {
                $errors['timer_menit'] = 'Timer harus berupa angka menit.';
            } else {
                $timer = (int) $timerRaw;
                if ($timer < 1 || $timer > 180) {
                    $errors['timer_menit'] = 'Timer antara 1–180 menit (kosongkan jika tanpa batas).';
                }
            }
        }

        return $errors;
    }

    private static function nullableKelasId(array $data): ?int
    {
        $kelasId = (int) ($data['kelas_id'] ?? 0);

        return $kelasId > 0 ? $kelasId : null;
    }

    private static function nullableTimerMenit(array $data): ?int
    {
        $timerRaw = trim((string) ($data['timer_menit'] ?? ''));
        if ($timerRaw === '' || !ctype_digit($timerRaw)) {
            return null;
        }

        $timer = (int) $timerRaw;

        return $timer >= 1 ? $timer : null;
    }

    /**
     * @return array{ok:bool, errors:array<string,string>, path?:?string, url?:?string, unlink_old?:?string}
     */
    private static function resolveGambarInput(array $data, ?array $file, ?array $existing): array
    {
        $hapus = !empty($data['hapus_gambar']);
        $url = trim((string) ($data['gambar_url'] ?? ''));
        $hasUpload = is_array($file)
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            && (string) ($file['tmp_name'] ?? '') !== '';

        $oldPath = $existing ? (string) ($existing['gambar_path'] ?? '') : '';
        $oldUrl = $existing ? trim((string) ($existing['gambar_url'] ?? '')) : '';

        if ($hasUpload) {
            $uploaded = self::storeGambarUpload($file);
            if (!$uploaded['ok']) {
                return $uploaded;
            }

            return [
                'ok' => true,
                'errors' => [],
                'path' => $uploaded['path'],
                'url' => null,
                'unlink_old' => $oldPath !== '' ? $oldPath : null,
            ];
        }

        if ($hapus) {
            return [
                'ok' => true,
                'errors' => [],
                'path' => null,
                'url' => null,
                'unlink_old' => $oldPath !== '' ? $oldPath : null,
            ];
        }

        if ($url !== '') {
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                return ['ok' => false, 'errors' => ['gambar_url' => 'URL gambar tidak valid (http/https).']];
            }

            return [
                'ok' => true,
                'errors' => [],
                'path' => null,
                'url' => $url,
                'unlink_old' => $oldPath !== '' ? $oldPath : null,
            ];
        }

        return [
            'ok' => true,
            'errors' => [],
            'path' => $oldPath !== '' ? $oldPath : null,
            'url' => $oldUrl !== '' ? $oldUrl : null,
            'unlink_old' => null,
        ];
    }

    /** @return array{ok:bool, errors:array<string,string>, path?:string} */
    private static function storeGambarUpload(array $file): array
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'errors' => ['gambar' => 'Gagal mengunggah gambar.']];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'errors' => ['gambar' => 'File upload tidak valid.']];
        }

        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['ok' => false, 'errors' => ['gambar' => 'Ukuran gambar maksimal 2 MB.']];
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::IMAGE_EXT, true)) {
            return ['ok' => false, 'errors' => ['gambar' => 'Format gambar: JPG, PNG, GIF, WEBP.']];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (!str_starts_with($mime, 'image/')) {
            return ['ok' => false, 'errors' => ['gambar' => 'File bukan gambar yang valid.']];
        }

        $dir = BASE_PATH . '/uploads/kuis';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'errors' => ['gambar' => 'Gagal membuat folder upload.']];
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'errors' => ['gambar' => 'Gagal menyimpan gambar.']];
        }

        return ['ok' => true, 'errors' => [], 'path' => 'uploads/kuis/' . $filename];
    }

    private static function unlinkGambarPath(string $relativePath): void
    {
        $relativePath = str_replace(['\\', "\0"], ['/', ''], $relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..') || !str_starts_with($relativePath, 'uploads/kuis/')) {
            return;
        }

        $full = BASE_PATH . '/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private static function copyGambarPath(string $relativePath): ?string
    {
        $relativePath = str_replace(['\\', "\0"], ['/', ''], $relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..') || !str_starts_with($relativePath, 'uploads/kuis/')) {
            return null;
        }

        $src = BASE_PATH . '/' . $relativePath;
        if (!is_file($src)) {
            return null;
        }

        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if (!in_array($ext, self::IMAGE_EXT, true)) {
            return null;
        }

        $dir = BASE_PATH . '/uploads/kuis';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destRel = 'uploads/kuis/' . $filename;
        if (!@copy($src, BASE_PATH . '/' . $destRel)) {
            return null;
        }

        return $destRel;
    }

    private static function generateUniqueKode(PDO $pdo): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $kode = '';
            for ($i = 0; $i < 6; $i++) {
                $kode .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $check = $pdo->prepare('SELECT id FROM kuis WHERE kode = :kode LIMIT 1');
            $check->execute(['kode' => $kode]);
            if (!$check->fetch()) {
                return $kode;
            }
        }

        throw new RuntimeException('Gagal membuat kode kuis unik.');
    }

    /** @return list<string> */
    private static function decodeOpsi(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_map('strval', $raw));
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_map('strval', $decoded));
    }

    /**
     * @return array{errors:array<string,string>, pertanyaan?:string, tipe?:string, opsi?:list<string>, jawaban_index?:int, jawaban_teks?:?string, timer_detik?:?int}
     */
    private static function parseSoalInput(array $data): array
    {
        $errors = [];
        $pertanyaan = trim((string) ($data['pertanyaan'] ?? ''));
        if ($pertanyaan === '') {
            $errors['pertanyaan'] = 'Pertanyaan wajib diisi.';
        }

        $tipe = (string) ($data['tipe'] ?? 'pilihan_ganda');
        if (!in_array($tipe, ['pilihan_ganda', 'isian'], true)) {
            $tipe = 'pilihan_ganda';
        }

        $timerRaw = trim((string) ($data['timer_detik'] ?? ''));
        $timerDetik = null;
        if ($timerRaw !== '') {
            if (!ctype_digit($timerRaw)) {
                $errors['timer_detik'] = 'Timer soal harus angka detik.';
            } else {
                $timerDetik = (int) $timerRaw;
                if ($timerDetik < 5 || $timerDetik > 600) {
                    $errors['timer_detik'] = 'Timer soal 5–600 detik (kosongkan jika tanpa batas per soal).';
                }
            }
        }

        $opsi = [];
        $jawabanIndex = 0;
        $jawabanTeks = null;

        if ($tipe === 'isian') {
            $jawabanTeks = trim((string) ($data['jawaban_teks'] ?? ''));
            if ($jawabanTeks === '') {
                $errors['jawaban_teks'] = 'Kunci jawaban isian wajib diisi.';
            } elseif (mb_strlen($jawabanTeks) > 255) {
                $errors['jawaban_teks'] = 'Kunci isian maksimal 255 karakter.';
            }
        } else {
            $rawOpsi = $data['opsi'] ?? [];
            if (!is_array($rawOpsi)) {
                $rawOpsi = [];
            }

            $indexMap = [];
            $slot = 0;
            foreach ($rawOpsi as $item) {
                if ($slot >= 6) {
                    break;
                }
                $text = trim((string) $item);
                if ($text !== '') {
                    $indexMap[$slot] = count($opsi);
                    $opsi[] = $text;
                }
                $slot++;
            }

            if (count($opsi) < 2) {
                $errors['opsi'] = 'Minimal 2 opsi jawaban (A–B).';
            } elseif (count($opsi) > 6) {
                $errors['opsi'] = 'Maksimal 6 opsi (A–F).';
            }

            $jawabanAsli = (int) ($data['jawaban_index'] ?? -1);
            $jawabanIndex = $indexMap[$jawabanAsli] ?? -1;
            if ($errors === [] && $jawabanIndex < 0) {
                $errors['jawaban_index'] = 'Pilih jawaban benar pada opsi yang terisi.';
            }
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        return [
            'errors' => [],
            'pertanyaan' => $pertanyaan,
            'tipe' => $tipe,
            'opsi' => $opsi,
            'jawaban_index' => $jawabanIndex,
            'jawaban_teks' => $jawabanTeks,
            'timer_detik' => $timerDetik,
        ];
    }
}

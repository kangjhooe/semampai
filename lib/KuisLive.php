<?php

declare(strict_types=1);

final class KuisLive
{
    public const DEFAULT_TIMER = 20;
    public const ACTIVE_STATUSES = ['lobby', 'question', 'reveal', 'leaderboard', 'podium'];

    public static function normalizePin(string $pin): string
    {
        return preg_replace('/\D+/', '', $pin) ?? '';
    }

    public static function createSesi(int $kuisId, int $userId): array
    {
        $kuis = Kuis::findForUser($kuisId, $userId);
        if (!$kuis) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis tidak ditemukan.']];
        }

        $soal = Kuis::soalForKuis($kuisId);
        if ($soal === []) {
            return ['ok' => false, 'errors' => ['_form' => 'Tambahkan soal sebelum memulai live.']];
        }

        $ids = array_map(static fn ($s) => (int) $s['id'], $soal);
        if ((int) ($kuis['acak_soal'] ?? 0) === 1) {
            shuffle($ids);
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // Tutup sesi lobby lama untuk kuis+guru yang sama
            $pdo->prepare(
                "UPDATE kuis_live_sesi
                 SET status = 'ended', state_version = state_version + 1
                 WHERE kuis_id = :kuis_id AND user_id = :user_id AND status = 'lobby'"
            )->execute(['kuis_id' => $kuisId, 'user_id' => $userId]);

            $pin = self::generateUniquePin($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO kuis_live_sesi
                    (kuis_id, user_id, pin, status, current_index, soal_order_json, state_version)
                 VALUES
                    (:kuis_id, :user_id, :pin, \'lobby\', 0, :soal_order_json, 1)'
            );
            $stmt->execute([
                'kuis_id' => $kuisId,
                'user_id' => $userId,
                'pin' => $pin,
                'soal_order_json' => json_encode($ids, JSON_UNESCAPED_UNICODE),
            ]);
            $sesiId = (int) $pdo->lastInsertId();
            $pdo->commit();

            return ['ok' => true, 'errors' => [], 'sesi_id' => $sesiId, 'pin' => $pin];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal membuat sesi live.']];
        }
    }

    public static function findSesi(int $sesiId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM kuis_live_sesi WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $sesiId]);
        $row = $stmt->fetch();

        return $row ? self::hydrateSesi($row) : null;
    }

    public static function findSesiForHost(int $sesiId, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM kuis_live_sesi WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['id' => $sesiId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ? self::hydrateSesi($row) : null;
    }

    public static function findActiveForKuisHost(int $kuisId, int $userId): ?array
    {
        $placeholders = implode(',', array_fill(0, count(self::ACTIVE_STATUSES), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT * FROM kuis_live_sesi
             WHERE kuis_id = ? AND user_id = ? AND status IN ({$placeholders})
             ORDER BY id DESC LIMIT 1"
        );
        $params = array_merge([$kuisId, $userId], self::ACTIVE_STATUSES);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? self::hydrateSesi($row) : null;
    }

    public static function findByPin(string $pin): ?array
    {
        $pin = self::normalizePin($pin);
        if (strlen($pin) !== 6) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM kuis_live_sesi WHERE pin = :pin LIMIT 1'
        );
        $stmt->execute(['pin' => $pin]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $sesi = self::hydrateSesi($row);
        if ($sesi['status'] === 'ended') {
            return null;
        }

        return $sesi;
    }

    public static function stateVersion(int $sesiId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT state_version FROM kuis_live_sesi WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $sesiId]);
        $v = $stmt->fetchColumn();

        return $v === false ? 0 : (int) $v;
    }

    public static function join(int $sesiId, string $nama, ?int $siswaId = null): array
    {
        $sesi = self::findSesi($sesiId);
        if (!$sesi || $sesi['status'] === 'ended') {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']];
        }
        if ($sesi['status'] !== 'lobby') {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi sudah dimulai. Tidak bisa bergabung.']];
        }

        $kuis = Kuis::findById((int) $sesi['kuis_id']);
        if (!$kuis || !(int) $kuis['aktif']) {
            return ['ok' => false, 'errors' => ['_form' => 'Kuis tidak aktif.']];
        }

        $roster = Kuis::rosterSiswa((int) $kuis['id']);
        if ($roster !== []) {
            if ($siswaId === null || $siswaId <= 0) {
                return ['ok' => false, 'errors' => ['siswa_id' => 'Pilih nama Anda dari daftar.']];
            }
            $siswa = Kuis::findSiswaInKuisKelas((int) $kuis['id'], $siswaId);
            if (!$siswa) {
                return ['ok' => false, 'errors' => ['siswa_id' => 'Siswa tidak valid.']];
            }
            $nama = (string) $siswa['nama'];

            $dup = Database::connection()->prepare(
                'SELECT id FROM kuis_live_peserta WHERE sesi_id = :sesi_id AND siswa_id = :siswa_id LIMIT 1'
            );
            $dup->execute(['sesi_id' => $sesiId, 'siswa_id' => $siswaId]);
            if ($dup->fetch()) {
                return ['ok' => false, 'errors' => ['siswa_id' => 'Siswa ini sudah bergabung.']];
            }
        } else {
            $nama = trim($nama);
            if ($nama === '') {
                return ['ok' => false, 'errors' => ['nama' => 'Nama wajib diisi.']];
            }
            if (mb_strlen($nama) > 120) {
                return ['ok' => false, 'errors' => ['nama' => 'Nama maksimal 120 karakter.']];
            }
            $siswaId = null;
        }

        $token = bin2hex(random_bytes(32));
        $stmt = Database::connection()->prepare(
            'INSERT INTO kuis_live_peserta (sesi_id, token, nama, siswa_id, skor)
             VALUES (:sesi_id, :token, :nama, :siswa_id, 0)'
        );
        $stmt->execute([
            'sesi_id' => $sesiId,
            'token' => $token,
            'nama' => $nama,
            'siswa_id' => $siswaId,
        ]);
        $pesertaId = (int) Database::connection()->lastInsertId();
        self::bumpVersion($sesiId);

        return [
            'ok' => true,
            'errors' => [],
            'peserta_id' => $pesertaId,
            'token' => $token,
            'nama' => $nama,
        ];
    }

    public static function findPesertaByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) !== 64) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM kuis_live_peserta WHERE token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function touchPeserta(int $pesertaId): void
    {
        Database::connection()->prepare(
            'UPDATE kuis_live_peserta SET last_seen_at = CURRENT_TIMESTAMP WHERE id = :id'
        )->execute(['id' => $pesertaId]);
    }

    public static function start(int $sesiId, int $userId): array
    {
        $sesi = self::findSesiForHost($sesiId, $userId);
        if (!$sesi) {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']];
        }
        if ($sesi['status'] !== 'lobby') {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi sudah dimulai.']];
        }
        if ($sesi['soal_order'] === []) {
            return ['ok' => false, 'errors' => ['_form' => 'Tidak ada soal.']];
        }

        $count = self::countPeserta($sesiId);
        if ($count < 1) {
            return ['ok' => false, 'errors' => ['_form' => 'Tunggu minimal 1 peserta.']];
        }

        return self::openQuestion($sesi, 0);
    }

    public static function reveal(int $sesiId, int $userId): array
    {
        $sesi = self::findSesiForHost($sesiId, $userId);
        if (!$sesi) {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']];
        }
        if ($sesi['status'] !== 'question') {
            return ['ok' => false, 'errors' => ['_form' => 'Tidak ada soal aktif untuk di-reveal.']];
        }

        self::updateSesi($sesiId, [
            'status' => 'reveal',
            'question_ends_at' => time(),
        ]);

        return ['ok' => true, 'errors' => []];
    }

    public static function showLeaderboard(int $sesiId, int $userId): array
    {
        $sesi = self::findSesiForHost($sesiId, $userId);
        if (!$sesi) {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']];
        }
        if (!in_array($sesi['status'], ['reveal', 'question'], true)) {
            return ['ok' => false, 'errors' => ['_form' => 'Leaderboard belum tersedia.']];
        }

        if ($sesi['status'] === 'question') {
            self::updateSesi($sesiId, [
                'status' => 'leaderboard',
                'question_ends_at' => time(),
            ]);
        } else {
            self::updateSesi($sesiId, ['status' => 'leaderboard']);
        }

        return ['ok' => true, 'errors' => []];
    }

    public static function nextQuestion(int $sesiId, int $userId): array
    {
        $sesi = self::findSesiForHost($sesiId, $userId);
        if (!$sesi) {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']];
        }
        if (!in_array($sesi['status'], ['reveal', 'leaderboard', 'question'], true)) {
            return ['ok' => false, 'errors' => ['_form' => 'Tidak bisa lanjut ke soal berikutnya.']];
        }

        $next = (int) $sesi['current_index'] + 1;
        if ($next >= count($sesi['soal_order'])) {
            self::updateSesi($sesiId, [
                'status' => 'podium',
                'question_started_at' => null,
                'question_ends_at' => null,
            ]);

            return ['ok' => true, 'errors' => [], 'finished' => true];
        }

        return self::openQuestion($sesi, $next);
    }

    public static function finish(int $sesiId, int $userId): array
    {
        $sesi = self::findSesiForHost($sesiId, $userId);
        if (!$sesi) {
            return ['ok' => false, 'errors' => ['_form' => 'Sesi tidak ditemukan.']];
        }

        self::updateSesi($sesiId, [
            'status' => 'ended',
            'question_started_at' => null,
            'question_ends_at' => null,
        ]);

        return ['ok' => true, 'errors' => []];
    }

    public static function answer(string $token, mixed $rawAnswer): array
    {
        $peserta = self::findPesertaByToken($token);
        if (!$peserta) {
            return ['ok' => false, 'errors' => ['_form' => 'Peserta tidak valid.']];
        }

        $sesi = self::findSesi((int) $peserta['sesi_id']);
        if (!$sesi || $sesi['status'] !== 'question') {
            return ['ok' => false, 'errors' => ['_form' => 'Tidak ada soal aktif.']];
        }

        $soalId = self::currentSoalId($sesi);
        if ($soalId === null) {
            return ['ok' => false, 'errors' => ['_form' => 'Soal tidak ditemukan.']];
        }

        $endsAt = (int) ($sesi['question_ends_at'] ?? 0);
        if ($endsAt > 0 && time() > $endsAt) {
            return ['ok' => false, 'errors' => ['_form' => 'Waktu soal habis.']];
        }

        $existing = Database::connection()->prepare(
            'SELECT id FROM kuis_live_jawaban
             WHERE peserta_id = :peserta_id AND soal_id = :soal_id LIMIT 1'
        );
        $existing->execute(['peserta_id' => (int) $peserta['id'], 'soal_id' => $soalId]);
        if ($existing->fetch()) {
            return ['ok' => false, 'errors' => ['_form' => 'Anda sudah menjawab.']];
        }

        $dbSoal = Kuis::findSoalById($soalId);
        if (!$dbSoal) {
            return ['ok' => false, 'errors' => ['_form' => 'Soal tidak ditemukan.']];
        }

        $started = (int) ($sesi['question_started_at'] ?? time());
        $ms = max(0, (int) ((microtime(true) - $started) * 1000));
        if ($endsAt > 0) {
            $maxMs = max(0, ($endsAt - $started) * 1000);
            $ms = min($ms, $maxMs);
        }

        $graded = Kuis::gradeJawaban($dbSoal, $rawAnswer === '' ? null : $rawAnswer);
        $poin = $graded['ok'] ? self::speedPoints($ms) : 0;

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO kuis_live_jawaban
                    (sesi_id, peserta_id, soal_id, pilih_json, benar, poin, ms_jawab)
                 VALUES
                    (:sesi_id, :peserta_id, :soal_id, :pilih_json, :benar, :poin, :ms_jawab)'
            )->execute([
                'sesi_id' => (int) $sesi['id'],
                'peserta_id' => (int) $peserta['id'],
                'soal_id' => $soalId,
                'pilih_json' => json_encode([
                    'pilih' => $graded['pilih'],
                    'pilih_teks' => $graded['pilih_teks'],
                ], JSON_UNESCAPED_UNICODE),
                'benar' => $graded['ok'] ? 1 : 0,
                'poin' => $poin,
                'ms_jawab' => $ms,
            ]);

            if ($poin > 0) {
                $pdo->prepare(
                    'UPDATE kuis_live_peserta SET skor = skor + :poin WHERE id = :id'
                )->execute(['poin' => $poin, 'id' => (int) $peserta['id']]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan jawaban.']];
        }

        self::bumpVersion((int) $sesi['id']);
        self::touchPeserta((int) $peserta['id']);

        return [
            'ok' => true,
            'errors' => [],
            'benar' => $graded['ok'],
            'poin' => $poin,
            'waiting' => true,
        ];
    }

    /** @return list<array{rank:int,id:int,nama:string,skor:int}> */
    public static function leaderboard(int $sesiId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = Database::connection()->prepare(
            'SELECT id, nama, skor
             FROM kuis_live_peserta
             WHERE sesi_id = :sesi_id
             ORDER BY skor DESC, joined_at ASC, id ASC
             LIMIT ' . $limit
        );
        $stmt->execute(['sesi_id' => $sesiId]);
        $rows = $stmt->fetchAll();
        $out = [];
        $rank = 1;
        foreach ($rows as $row) {
            $out[] = [
                'rank' => $rank++,
                'id' => (int) $row['id'],
                'nama' => (string) $row['nama'],
                'skor' => (int) $row['skor'],
            ];
        }

        return $out;
    }

    public static function snapshotForHost(int $sesiId, int $userId): ?array
    {
        $sesi = self::findSesiForHost($sesiId, $userId);
        if (!$sesi) {
            return null;
        }

        $kuis = Kuis::findById((int) $sesi['kuis_id']);
        $peserta = self::listPeserta($sesiId);
        $board = self::leaderboard($sesiId);
        $soal = self::soalAtIndex($sesi, (int) $sesi['current_index'], true);
        $answered = 0;
        if ($soal) {
            $answered = self::countAnswers($sesiId, (int) $soal['id']);
        }

        return [
            'role' => 'host',
            'sesi_id' => (int) $sesi['id'],
            'pin' => (string) $sesi['pin'],
            'status' => (string) $sesi['status'],
            'state_version' => (int) $sesi['state_version'],
            'current_index' => (int) $sesi['current_index'],
            'total_soal' => count($sesi['soal_order']),
            'question_started_at' => $sesi['question_started_at'],
            'question_ends_at' => $sesi['question_ends_at'],
            'server_now' => time(),
            'kuis' => [
                'id' => (int) ($kuis['id'] ?? 0),
                'judul' => (string) ($kuis['judul'] ?? ''),
                'kode' => (string) ($kuis['kode'] ?? ''),
            ],
            'peserta' => $peserta,
            'peserta_count' => count($peserta),
            'answered_count' => $answered,
            'soal' => $soal,
            'leaderboard' => $board,
            'join_url' => app_url('kuis/live_join.php?pin=' . urlencode((string) $sesi['pin'])),
        ];
    }

    public static function snapshotForPlayer(string $token): ?array
    {
        $peserta = self::findPesertaByToken($token);
        if (!$peserta) {
            return null;
        }
        self::touchPeserta((int) $peserta['id']);

        $sesi = self::findSesi((int) $peserta['sesi_id']);
        if (!$sesi) {
            return null;
        }

        $kuis = Kuis::findById((int) $sesi['kuis_id']);
        $status = (string) $sesi['status'];
        $revealKeys = in_array($status, ['reveal', 'leaderboard', 'podium'], true);
        $soal = null;
        $myAnswer = null;
        if (in_array($status, ['question', 'reveal', 'leaderboard'], true)) {
            $soal = self::soalAtIndex($sesi, (int) $sesi['current_index'], $revealKeys);
            if ($soal) {
                $myAnswer = self::findAnswer((int) $peserta['id'], (int) $soal['id']);
            }
        }

        $board = in_array($status, ['leaderboard', 'podium', 'reveal'], true)
            ? self::leaderboard((int) $sesi['id'])
            : [];

        $myRank = null;
        $fresh = self::findPesertaByToken($token);
        $skor = (int) ($fresh['skor'] ?? $peserta['skor']);
        if ($board !== []) {
            foreach ($board as $row) {
                if ((int) $row['id'] === (int) $peserta['id']) {
                    $myRank = (int) $row['rank'];
                    break;
                }
            }
        }

        return [
            'role' => 'player',
            'sesi_id' => (int) $sesi['id'],
            'pin' => (string) $sesi['pin'],
            'status' => $status,
            'state_version' => (int) $sesi['state_version'],
            'current_index' => (int) $sesi['current_index'],
            'total_soal' => count($sesi['soal_order']),
            'question_started_at' => $sesi['question_started_at'],
            'question_ends_at' => $sesi['question_ends_at'],
            'server_now' => time(),
            'kuis' => [
                'id' => (int) ($kuis['id'] ?? 0),
                'judul' => (string) ($kuis['judul'] ?? ''),
            ],
            'me' => [
                'id' => (int) $peserta['id'],
                'nama' => (string) $peserta['nama'],
                'skor' => $skor,
                'rank' => $myRank,
                'answered' => $myAnswer !== null,
                'last_benar' => $myAnswer ? (bool) $myAnswer['benar'] : null,
                'last_poin' => $myAnswer ? (int) $myAnswer['poin'] : null,
            ],
            'peserta_count' => self::countPeserta((int) $sesi['id']),
            'soal' => $soal,
            'leaderboard' => $board,
        ];
    }

    public static function speedPoints(int $ms): int
    {
        return max(100, 1000 - (int) floor($ms / 10));
    }

    private static function openQuestion(array $sesi, int $index): array
    {
        $soalId = $sesi['soal_order'][$index] ?? null;
        if ($soalId === null) {
            return ['ok' => false, 'errors' => ['_form' => 'Soal tidak ditemukan.']];
        }

        $dbSoal = Kuis::findSoalById((int) $soalId);
        $timer = (int) ($dbSoal['timer_detik'] ?? 0);
        if ($timer < 5) {
            $timer = self::DEFAULT_TIMER;
        }
        $now = time();

        self::updateSesi((int) $sesi['id'], [
            'status' => 'question',
            'current_index' => $index,
            'question_started_at' => $now,
            'question_ends_at' => $now + $timer,
        ]);

        return ['ok' => true, 'errors' => [], 'finished' => false];
    }

    private static function updateSesi(int $sesiId, array $fields): void
    {
        $sets = ['state_version = state_version + 1'];
        $params = ['id' => $sesiId];
        foreach ($fields as $key => $value) {
            $sets[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        $sql = 'UPDATE kuis_live_sesi SET ' . implode(', ', $sets) . ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }

    private static function bumpVersion(int $sesiId): void
    {
        Database::connection()->prepare(
            'UPDATE kuis_live_sesi SET state_version = state_version + 1 WHERE id = :id'
        )->execute(['id' => $sesiId]);
    }

    private static function hydrateSesi(array $row): array
    {
        $order = json_decode((string) ($row['soal_order_json'] ?? '[]'), true);
        if (!is_array($order)) {
            $order = [];
        }
        $row['soal_order'] = array_values(array_map('intval', $order));
        $row['question_started_at'] = isset($row['question_started_at']) && $row['question_started_at'] !== null
            ? (int) $row['question_started_at']
            : null;
        $row['question_ends_at'] = isset($row['question_ends_at']) && $row['question_ends_at'] !== null
            ? (int) $row['question_ends_at']
            : null;
        $row['state_version'] = (int) $row['state_version'];
        $row['current_index'] = (int) $row['current_index'];

        return $row;
    }

    private static function generateUniquePin(PDO $pdo): string
    {
        for ($i = 0; $i < 40; $i++) {
            $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $check = $pdo->prepare(
                "SELECT id FROM kuis_live_sesi WHERE pin = :pin AND status != 'ended' LIMIT 1"
            );
            $check->execute(['pin' => $pin]);
            if (!$check->fetch()) {
                // Free pin among active; also OK if ended reuse — unique is on pin globally
                $any = $pdo->prepare('SELECT id FROM kuis_live_sesi WHERE pin = :pin LIMIT 1');
                $any->execute(['pin' => $pin]);
                if (!$any->fetch()) {
                    return $pin;
                }
            }
        }

        // Extremely unlikely: use longer uniqueness via time suffix then truncate differently
        throw new RuntimeException('Tidak bisa membuat PIN unik.');
    }

    private static function currentSoalId(array $sesi): ?int
    {
        $idx = (int) $sesi['current_index'];
        $id = $sesi['soal_order'][$idx] ?? null;

        return $id !== null ? (int) $id : null;
    }

    private static function soalAtIndex(array $sesi, int $index, bool $includeKey): ?array
    {
        $soalId = $sesi['soal_order'][$index] ?? null;
        if ($soalId === null) {
            return null;
        }

        $row = Kuis::findSoalById((int) $soalId);
        if (!$row) {
            return null;
        }

        $tipe = (string) ($row['tipe'] ?? 'pilihan_ganda');
        $opsi = Kuis::decodeOpsi($row['opsi_json'] ?? '[]');
        $kuis = Kuis::findById((int) $sesi['kuis_id']);
        $opsiItems = [];
        if ($tipe === 'pilihan_ganda') {
            foreach ($opsi as $i => $item) {
                $opsiItems[] = [
                    'i' => (int) $i,
                    'teks' => Kuis::opsiTeks($item),
                    'gambar' => Kuis::opsiGambarSrc((int) $row['id'], $item, (int) $i),
                ];
            }
            if ((int) ($kuis['acak_opsi'] ?? 0) === 1 && $sesi['status'] === 'question') {
                // Stable shuffle per sesi+soal so all players see same order
                $seed = crc32((string) $sesi['id'] . ':' . $soalId);
                usort($opsiItems, static function ($a, $b) use ($seed) {
                    $ha = crc32($seed . ':' . $a['i']);
                    $hb = crc32($seed . ':' . $b['i']);

                    return $ha <=> $hb;
                });
            }
        }

        $out = [
            'id' => (int) $row['id'],
            'nomor' => $index + 1,
            'pertanyaan' => (string) $row['pertanyaan'],
            'tipe' => $tipe,
            'opsi' => $opsiItems,
            'gambar' => Kuis::gambarPublicUrl([
                'id' => (int) $row['id'],
                'gambar_path' => $row['gambar_path'] ?? null,
                'gambar_url' => $row['gambar_url'] ?? null,
            ]),
            'timer_detik' => (int) ($row['timer_detik'] ?? 0) >= 5
                ? (int) $row['timer_detik']
                : self::DEFAULT_TIMER,
        ];

        if ($includeKey) {
            $out['jawaban_index'] = (int) $row['jawaban_index'];
            $out['jawaban_teks'] = (string) ($row['jawaban_teks'] ?? '');
            $out['benar_teks'] = $tipe === 'isian'
                ? (string) ($row['jawaban_teks'] ?? '')
                : Kuis::opsiLabel($opsi[(int) $row['jawaban_index']] ?? null);
        }

        return $out;
    }

    /** @return list<array{id:int,nama:string,skor:int}> */
    private static function listPeserta(int $sesiId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, nama, skor FROM kuis_live_peserta
             WHERE sesi_id = :sesi_id
             ORDER BY joined_at ASC, id ASC'
        );
        $stmt->execute(['sesi_id' => $sesiId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'nama' => (string) $row['nama'],
                'skor' => (int) $row['skor'],
            ];
        }

        return $out;
    }

    private static function countPeserta(int $sesiId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM kuis_live_peserta WHERE sesi_id = :sesi_id'
        );
        $stmt->execute(['sesi_id' => $sesiId]);

        return (int) $stmt->fetchColumn();
    }

    private static function countAnswers(int $sesiId, int $soalId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM kuis_live_jawaban
             WHERE sesi_id = :sesi_id AND soal_id = :soal_id'
        );
        $stmt->execute(['sesi_id' => $sesiId, 'soal_id' => $soalId]);

        return (int) $stmt->fetchColumn();
    }

    private static function findAnswer(int $pesertaId, int $soalId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM kuis_live_jawaban
             WHERE peserta_id = :peserta_id AND soal_id = :soal_id LIMIT 1'
        );
        $stmt->execute(['peserta_id' => $pesertaId, 'soal_id' => $soalId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}

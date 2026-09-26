<?php

declare(strict_types=1);

final class QuranApi
{
    private const BASE_URL = 'https://equran.id/api/v2/surat/';
    private const CACHE_TTL = 86400 * 30; // 30 hari

    /**
     * @return array{nomor:int, nama:string, nama_latin:string, jumlah_ayat:int, ayat:list<array{nomor:int, arab:string, latin:string, indonesia:string}>}|null
     */
    public static function getSurat(int $nomor): ?array
    {
        if ($nomor < 1 || $nomor > 114) {
            return null;
        }

        $cached = self::readCache($nomor);
        if ($cached !== null) {
            return $cached;
        }

        $raw = self::fetchRemote($nomor);
        if ($raw === null) {
            return null;
        }

        $normalized = self::normalize($raw);
        self::writeCache($nomor, $normalized);

        return $normalized;
    }

    private static function cachePath(int $nomor): string
    {
        $dir = BASE_PATH . '/storage/quran';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir . '/surat_' . $nomor . '.json';
    }

    private static function readCache(int $nomor): ?array
    {
        $path = self::cachePath($nomor);
        if (!is_file($path)) {
            return null;
        }

        if (filemtime($path) !== false && (time() - (int) filemtime($path)) > self::CACHE_TTL) {
            return null;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    private static function writeCache(int $nomor, array $data): void
    {
        $path = self::cachePath($nomor);
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function fetchRemote(int $nomor): ?array
    {
        $url = self::BASE_URL . $nomor;
        $body = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: SemampaiPAI/1.0'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code >= 400) {
                return null;
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 20,
                    'header' => "Accept: application/json\r\nUser-Agent: SemampaiPAI/1.0\r\n",
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if ($body === false) {
                return null;
            }
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || (int) ($decoded['code'] ?? 0) !== 200) {
            return null;
        }

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    /** @param array<string, mixed> $data */
    private static function normalize(array $data): array
    {
        $ayat = [];
        foreach ($data['ayat'] ?? [] as $row) {
            $ayat[] = [
                'nomor' => (int) ($row['nomorAyat'] ?? 0),
                'arab' => (string) ($row['teksArab'] ?? ''),
                'latin' => (string) ($row['teksLatin'] ?? ''),
                'indonesia' => (string) ($row['teksIndonesia'] ?? ''),
            ];
        }

        return [
            'nomor' => (int) ($data['nomor'] ?? 0),
            'nama' => (string) ($data['nama'] ?? ''),
            'nama_latin' => (string) ($data['namaLatin'] ?? ''),
            'jumlah_ayat' => (int) ($data['jumlahAyat'] ?? count($ayat)),
            'ayat' => $ayat,
        ];
    }
}

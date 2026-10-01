<?php

declare(strict_types=1);

final class BahanAjar
{
    private const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const VIDEO_EXT = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v', 'wmv', 'flv', '3gp'];
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const SUMBER_ALL = ['upload', 'gdrive', 'youtube', 'onedrive', 'canva', 'vimeo'];
    private const MAX_BYTES = 20 * 1024 * 1024; // 20 MB

    private const MIME_MAP = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
    ];

    public static function allForKelas(int $kelasId, int $userId): array
    {
        return self::allForUser($userId, ['kelas_id' => $kelasId]);
    }

    /**
     * @param array{kelas_id?: int, sumber?: string, q?: string} $filters
     */
    public static function allForUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT b.*, k.nama AS kelas_nama, k.tahun_ajaran
                FROM bahan_ajar b
                INNER JOIN kelas k ON k.id = b.kelas_id
                WHERE k.user_id = :user_id';
        $params = ['user_id' => $userId];

        $kelasId = (int) ($filters['kelas_id'] ?? 0);
        if ($kelasId > 0) {
            $sql .= ' AND b.kelas_id = :kelas_id';
            $params['kelas_id'] = $kelasId;
        }

        $sumber = (string) ($filters['sumber'] ?? '');
        if (in_array($sumber, self::SUMBER_ALL, true)) {
            $sql .= ' AND b.sumber = :sumber';
            $params['sumber'] = $sumber;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $sql .= ' AND b.judul LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY b.created_at DESC, b.id DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.*, k.nama AS kelas_nama, k.tahun_ajaran
             FROM bahan_ajar b
             INNER JOIN kelas k ON k.id = b.kelas_id
             WHERE b.id = :id AND k.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Hanya untuk penyajian file setelah token/signature diverifikasi. */
    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.*, k.nama AS kelas_nama, k.tahun_ajaran
             FROM bahan_ajar b
             INNER JOIN kelas k ON k.id = b.kelas_id
             WHERE b.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * URL unduh/lihat file yang melewati cek otorisasi.
     * Pakai signed URL bila file harus di-fetch pihak ketiga (Google Docs Viewer).
     */
    public static function authorizedFileUrl(array $row, bool $signed = false, int $ttlSeconds = 3600): string
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            return '';
        }

        if (!$signed) {
            return app_url('bahan/file.php?id=' . $id);
        }

        $exp = time() + max(60, $ttlSeconds);
        $sig = self::fileSignature($id, $exp);

        return app_url('bahan/file.php?id=' . $id . '&exp=' . $exp . '&sig=' . rawurlencode($sig));
    }

    public static function fileSignature(int $id, int $exp): string
    {
        return hash_hmac('sha256', $id . '|' . $exp, app_key());
    }

    public static function verifyFileSignature(int $id, int $exp, string $sig): bool
    {
        if ($id <= 0 || $exp < time() || $sig === '') {
            return false;
        }

        $expected = self::fileSignature($id, $exp);

        return hash_equals($expected, $sig);
    }

    public static function create(int $kelasId, int $userId, array $data, ?array $file): array
    {
        if (!Kelas::findForUser($kelasId, $userId)) {
            return ['ok' => false, 'errors' => ['kelas_id' => 'Kelas tidak valid.']];
        }

        $errors = self::validate($data, $file);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $sumber = (string) $data['sumber'];
        $judul = trim((string) $data['judul']);
        $filePath = null;
        $fileMime = null;
        $fileExt = null;
        $originalUrl = null;
        $embedUrl = null;

        if ($sumber === 'upload') {
            $stored = self::storeUpload($file);
            if (!$stored['ok']) {
                return ['ok' => false, 'errors' => $stored['errors']];
            }
            $filePath = $stored['path'];
            $fileMime = $stored['mime'];
            $fileExt = $stored['ext'];
        } else {
            $resolved = self::resolveLinkEmbed($sumber, (string) $data['original_url']);
            if (!$resolved['ok']) {
                return ['ok' => false, 'errors' => $resolved['errors']];
            }
            $originalUrl = $resolved['original_url'];
            $embedUrl = $resolved['embed_url'];
        }

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO bahan_ajar
                    (kelas_id, user_id, judul, sumber, file_path, file_mime, file_ext, original_url, embed_url)
                 VALUES
                    (:kelas_id, :user_id, :judul, :sumber, :file_path, :file_mime, :file_ext, :original_url, :embed_url)'
            );
            $stmt->execute([
                'kelas_id' => $kelasId,
                'user_id' => $userId,
                'judul' => $judul,
                'sumber' => $sumber,
                'file_path' => $filePath,
                'file_mime' => $fileMime,
                'file_ext' => $fileExt,
                'original_url' => $originalUrl,
                'embed_url' => $embedUrl,
            ]);

            return ['ok' => true, 'errors' => [], 'id' => (int) Database::connection()->lastInsertId()];
        } catch (PDOException $e) {
            if ($filePath !== null) {
                self::unlinkRelative($filePath);
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal menyimpan bahan ajar.']];
        }
    }

    public static function update(int $id, int $userId, array $data, ?array $file): array
    {
        $existing = self::findForUser($id, $userId);
        if (!$existing) {
            return ['ok' => false, 'errors' => ['_form' => 'Bahan ajar tidak ditemukan.']];
        }

        $kelasId = (int) ($data['kelas_id'] ?? 0);
        $kelas = Kelas::findForUser($kelasId, $userId);
        if (!$kelas) {
            return ['ok' => false, 'errors' => ['kelas_id' => 'Kelas tidak valid.']];
        }

        $hasNewFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $errors = self::validate($data, $hasNewFile ? $file : null, true, (string) $existing['sumber']);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $sumber = (string) $data['sumber'];
        $judul = trim((string) $data['judul']);
        $filePath = $existing['file_path'];
        $fileMime = $existing['file_mime'];
        $fileExt = $existing['file_ext'];
        $originalUrl = $existing['original_url'];
        $embedUrl = $existing['embed_url'];
        $oldFilePath = (string) ($existing['file_path'] ?? '');
        $newStoredPath = null;
        $removeOldFile = false;

        if ($sumber === 'upload') {
            if ($hasNewFile) {
                $stored = self::storeUpload($file);
                if (!$stored['ok']) {
                    return ['ok' => false, 'errors' => $stored['errors']];
                }
                $filePath = $stored['path'];
                $fileMime = $stored['mime'];
                $fileExt = $stored['ext'];
                $newStoredPath = $stored['path'];
                $removeOldFile = $oldFilePath !== '';
            } elseif ((string) $existing['sumber'] !== 'upload' || empty($existing['file_path'])) {
                return ['ok' => false, 'errors' => ['file' => 'Pilih file untuk diunggah.']];
            }
            $originalUrl = null;
            $embedUrl = null;
        } else {
            $resolved = self::resolveLinkEmbed($sumber, (string) $data['original_url']);
            if (!$resolved['ok']) {
                return ['ok' => false, 'errors' => $resolved['errors']];
            }
            $originalUrl = $resolved['original_url'];
            $embedUrl = $resolved['embed_url'];
            if ((string) $existing['sumber'] === 'upload') {
                $removeOldFile = $oldFilePath !== '';
            }
            $filePath = null;
            $fileMime = null;
            $fileExt = null;
        }

        try {
            $stmt = Database::connection()->prepare(
                'UPDATE bahan_ajar b
                 INNER JOIN kelas k ON k.id = b.kelas_id
                 SET b.kelas_id = :kelas_id,
                     b.judul = :judul,
                     b.sumber = :sumber,
                     b.file_path = :file_path,
                     b.file_mime = :file_mime,
                     b.file_ext = :file_ext,
                     b.original_url = :original_url,
                     b.embed_url = :embed_url
                 WHERE b.id = :id AND k.user_id = :user_id'
            );
            $stmt->execute([
                'kelas_id' => $kelasId,
                'judul' => $judul,
                'sumber' => $sumber,
                'file_path' => $filePath,
                'file_mime' => $fileMime,
                'file_ext' => $fileExt,
                'original_url' => $originalUrl,
                'embed_url' => $embedUrl,
                'id' => $id,
                'user_id' => $userId,
            ]);

            if ($removeOldFile && $oldFilePath !== '' && $oldFilePath !== $filePath) {
                self::unlinkRelative($oldFilePath);
            }

            return ['ok' => true, 'errors' => []];
        } catch (PDOException $e) {
            if ($newStoredPath !== null) {
                self::unlinkRelative($newStoredPath);
            }

            return ['ok' => false, 'errors' => ['_form' => 'Gagal memperbarui bahan ajar.']];
        }
    }

    public static function delete(int $id, int $userId): bool
    {
        $row = self::findForUser($id, $userId);
        if (!$row) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE b FROM bahan_ajar b
             INNER JOIN kelas k ON k.id = b.kelas_id
             WHERE b.id = :id AND k.user_id = :user_id'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        if ($stmt->rowCount() > 0 && !empty($row['file_path'])) {
            self::unlinkRelative((string) $row['file_path']);
        }

        return $stmt->rowCount() > 0;
    }

    public static function resolveEmbed(array $row): string
    {
        if (($row['sumber'] ?? '') === 'upload' && !empty($row['file_path'])) {
            $ext = strtolower((string) ($row['file_ext'] ?? ''));
            $needsPublicFetch = in_array($ext, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true);
            $fileUrl = self::authorizedFileUrl($row, $needsPublicFetch, 14400);

            return EmbedUrl::forUploadedFile($fileUrl, $ext);
        }

        return (string) ($row['embed_url'] ?? '');
    }

    public static function thumbnailUrl(array $row): ?string
    {
        return EmbedUrl::thumbnailFor($row);
    }

    public static function isImage(array $row): bool
    {
        if (($row['sumber'] ?? '') !== 'upload') {
            return false;
        }

        return in_array(strtolower((string) ($row['file_ext'] ?? '')), self::IMAGE_EXT, true);
    }

    public static function labelSumber(string $sumber): string
    {
        return match ($sumber) {
            'upload' => 'Unggah Manual',
            'gdrive' => 'Google Drive',
            'youtube' => 'YouTube',
            'onedrive' => 'OneDrive',
            'canva' => 'Canva',
            'vimeo' => 'Vimeo',
            default => $sumber,
        };
    }

    /** @return list<string> */
    public static function sumberList(): array
    {
        return self::SUMBER_ALL;
    }

    /**
     * @return array{ok: true, original_url: string, embed_url: string}|array{ok: false, errors: array<string, string>}
     */
    private static function resolveLinkEmbed(string $sumber, string $rawUrl): array
    {
        $originalUrl = trim($rawUrl);

        $embedUrl = match ($sumber) {
            'gdrive' => EmbedUrl::fromGoogleDrive($originalUrl),
            'youtube' => EmbedUrl::fromYouTube($originalUrl),
            'onedrive' => EmbedUrl::fromOneDrive($originalUrl),
            'canva' => EmbedUrl::fromCanva($originalUrl),
            'vimeo' => EmbedUrl::fromVimeo($originalUrl),
            default => null,
        };

        if ($embedUrl === null) {
            $label = self::labelSumber($sumber);

            return ['ok' => false, 'errors' => ['original_url' => 'Link ' . $label . ' tidak valid.']];
        }

        return [
            'ok' => true,
            'original_url' => $originalUrl,
            'embed_url' => $embedUrl,
        ];
    }

    /** @return array<string, string> */
    public static function validate(array $data, ?array $file, bool $isEdit = false, string $existingSumber = ''): array
    {
        $errors = [];
        $judul = trim((string) ($data['judul'] ?? ''));
        $sumber = (string) ($data['sumber'] ?? '');

        if ($judul === '') {
            $errors['judul'] = 'Judul wajib diisi.';
        } elseif (mb_strlen($judul) > 200) {
            $errors['judul'] = 'Judul maksimal 200 karakter.';
        }

        if (!in_array($sumber, self::SUMBER_ALL, true)) {
            $errors['sumber'] = 'Pilih sumber file.';
            return $errors;
        }

        if ($sumber === 'upload') {
            $hasFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if (!$hasFile) {
                if (!$isEdit || $existingSumber !== 'upload') {
                    $errors['file'] = 'Pilih file untuk diunggah.';
                }
            } elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors['file'] = 'Gagal mengunggah file.';
            }
        } else {
            $url = trim((string) ($data['original_url'] ?? ''));
            if ($url === '') {
                $errors['original_url'] = 'Tempel link terlebih dahulu.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['original_url'] = 'Format link tidak valid.';
            }
        }

        return $errors;
    }

    /** @return array{ok: bool, errors?: array<string, string>, path?: string, mime?: string, ext?: string} */
    private static function storeUpload(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'errors' => ['file' => 'Pilih file untuk diunggah.']];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return ['ok' => false, 'errors' => ['file' => 'Ukuran file maksimal 20 MB.']];
        }

        $originalName = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($ext, self::VIDEO_EXT, true)) {
            return ['ok' => false, 'errors' => ['file' => 'Video tidak bisa diunggah. Gunakan sumber YouTube atau Vimeo.']];
        }

        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return ['ok' => false, 'errors' => ['file' => 'Format diizinkan: PDF, DOC, DOCX, PPT, PPTX, JPG, PNG, GIF, WEBP.']];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'errors' => ['file' => 'File upload tidak valid.']];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: 'application/octet-stream';

        if (str_starts_with($mime, 'video/')) {
            return ['ok' => false, 'errors' => ['file' => 'Video tidak bisa diunggah. Gunakan sumber YouTube atau Vimeo.']];
        }

        $allowedMimes = self::MIME_MAP[$ext] ?? [];
        // Beberapa server mengembalikan mime generik untuk Office
        if ($allowedMimes !== [] && !in_array($mime, $allowedMimes, true) && $mime !== 'application/octet-stream' && $mime !== 'application/zip') {
            return ['ok' => false, 'errors' => ['file' => 'Tipe file tidak sesuai ekstensi.']];
        }

        $dir = BASE_PATH . '/uploads/bahan';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'errors' => ['file' => 'Gagal membuat folder upload.']];
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'errors' => ['file' => 'Gagal menyimpan file.']];
        }

        return [
            'ok' => true,
            'path' => 'uploads/bahan/' . $filename,
            'mime' => $mime,
            'ext' => $ext,
        ];
    }

    private static function unlinkRelative(string $relativePath): void
    {
        $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
        if (!str_starts_with($relativePath, 'uploads/bahan/')) {
            return;
        }

        $full = BASE_PATH . '/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

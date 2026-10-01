<?php

declare(strict_types=1);

final class BahanAjar
{
    private const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    private const MAX_BYTES = 20 * 1024 * 1024; // 20 MB

    private const MIME_MAP = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    ];

    public static function allForKelas(int $kelasId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.*
             FROM bahan_ajar b
             INNER JOIN kelas k ON k.id = b.kelas_id
             WHERE b.kelas_id = :kelas_id AND k.user_id = :user_id
             ORDER BY b.created_at DESC, b.id DESC'
        );
        $stmt->execute(['kelas_id' => $kelasId, 'user_id' => $userId]);

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

    public static function create(int $kelasId, int $userId, array $data, ?array $file): array
    {
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
        } elseif ($sumber === 'gdrive') {
            $originalUrl = trim((string) $data['original_url']);
            $embedUrl = EmbedUrl::fromGoogleDrive($originalUrl);
            if ($embedUrl === null) {
                return ['ok' => false, 'errors' => ['original_url' => 'Link Google Drive tidak valid.']];
            }
        } else {
            $originalUrl = trim((string) $data['original_url']);
            $embedUrl = EmbedUrl::fromYouTube($originalUrl);
            if ($embedUrl === null) {
                return ['ok' => false, 'errors' => ['original_url' => 'Link YouTube tidak valid.']];
            }
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

    public static function delete(int $id, int $userId): bool
    {
        $row = self::findForUser($id, $userId);
        if (!$row) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM bahan_ajar WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0 && !empty($row['file_path'])) {
            self::unlinkRelative((string) $row['file_path']);
        }

        return $stmt->rowCount() > 0;
    }

    public static function resolveEmbed(array $row): string
    {
        if (($row['sumber'] ?? '') === 'upload' && !empty($row['file_path'])) {
            return EmbedUrl::forUploadedFile(app_url((string) $row['file_path']), (string) ($row['file_ext'] ?? ''));
        }

        return (string) ($row['embed_url'] ?? '');
    }

    public static function labelSumber(string $sumber): string
    {
        return match ($sumber) {
            'upload' => 'Unggah Manual',
            'gdrive' => 'Google Drive',
            'youtube' => 'YouTube',
            default => $sumber,
        };
    }

    /** @return array<string, string> */
    public static function validate(array $data, ?array $file): array
    {
        $errors = [];
        $judul = trim((string) ($data['judul'] ?? ''));
        $sumber = (string) ($data['sumber'] ?? '');

        if ($judul === '') {
            $errors['judul'] = 'Judul wajib diisi.';
        } elseif (mb_strlen($judul) > 200) {
            $errors['judul'] = 'Judul maksimal 200 karakter.';
        }

        if (!in_array($sumber, ['upload', 'gdrive', 'youtube'], true)) {
            $errors['sumber'] = 'Pilih sumber file.';
            return $errors;
        }

        if ($sumber === 'upload') {
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                $errors['file'] = 'Pilih file untuk diunggah.';
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
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return ['ok' => false, 'errors' => ['file' => 'Format diizinkan: PDF, DOC, DOCX, PPT, PPTX.']];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'errors' => ['file' => 'File upload tidak valid.']];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: 'application/octet-stream';
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

<?php

declare(strict_types=1);

final class EmbedUrl
{
    public static function fromGoogleDrive(string $url): ?string
    {
        $url = trim($url);

        if (preg_match('#drive\.google\.com/file/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }

        if (preg_match('#drive\.google\.com/(?:open|uc)\?(?:[^#]*&)?id=([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }

        if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $url, $m) && str_contains($url, 'drive.google.com')) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }

        return null;
    }

    public static function fromYouTube(string $url): ?string
    {
        $url = trim($url);
        $id = null;

        if (preg_match('#(?:youtube\.com/watch\?(?:[^#]*&)?v=|youtu\.be/|youtube\.com/embed/|youtube\.com/shorts/)([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $id = $m[1];
        }

        return $id !== null ? 'https://www.youtube.com/embed/' . $id : null;
    }

    public static function forUploadedFile(string $publicUrl, string $ext): string
    {
        $ext = strtolower($ext);

        if (in_array($ext, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true)) {
            return 'https://docs.google.com/gview?url=' . rawurlencode($publicUrl) . '&embedded=true';
        }

        return $publicUrl;
    }
}

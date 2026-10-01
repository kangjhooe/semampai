<?php

declare(strict_types=1);

final class EmbedUrl
{
    public static function googleDriveId(string $url): ?string
    {
        $url = trim($url);

        if (preg_match('~drive\.google\.com/file/d/([a-zA-Z0-9_-]+)~', $url, $m)) {
            return $m[1];
        }

        if (preg_match('~drive\.google\.com/(?:open|uc)\?(?:[^#]*&)?id=([a-zA-Z0-9_-]+)~', $url, $m)) {
            return $m[1];
        }

        if (str_contains($url, 'drive.google.com') && preg_match('~[?&]id=([a-zA-Z0-9_-]+)~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function fromGoogleDrive(string $url): ?string
    {
        $id = self::googleDriveId($url);

        return $id !== null ? 'https://drive.google.com/file/d/' . $id . '/preview' : null;
    }

    public static function youtubeId(string $url): ?string
    {
        $url = trim($url);

        if (preg_match(
            '~(?:youtube\.com/watch\?(?:[^#]*&)?v=|youtu\.be/|youtube\.com/embed/|youtube\.com/shorts/)([a-zA-Z0-9_-]{11})~',
            $url,
            $m
        )) {
            return $m[1];
        }

        return null;
    }

    public static function fromYouTube(string $url): ?string
    {
        $id = self::youtubeId($url);

        return $id !== null ? 'https://www.youtube.com/embed/' . $id : null;
    }

    public static function isOneDriveUrl(string $url): bool
    {
        $host = parse_url(trim($url), PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        return $host === '1drv.ms'
            || $host === 'onedrive.live.com'
            || $host === 'onedrive.com'
            || str_ends_with($host, '.onedrive.live.com')
            || str_ends_with($host, '.onedrive.com')
            || str_contains($host, 'sharepoint.com');
    }

    public static function fromOneDrive(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !self::isOneDriveUrl($url)) {
            return null;
        }

        if (preg_match('~[?&]embed=1(?:&|$)~i', $url)
            || preg_match('~/embed(?:\?|$)~i', $url)
            || preg_match('~[?&]action=embedview(?:&|$)~i', $url)
            || str_contains(strtolower($url), 'embed.aspx')
        ) {
            return $url;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '1drv.ms') {
            return self::appendQuery($url, 'embed', '1');
        }

        if ($host === 'onedrive.live.com' || str_ends_with($host, '.onedrive.live.com')) {
            $converted = preg_replace(
                '~onedrive\.live\.com/(?:redir|view\.aspx)~i',
                'onedrive.live.com/embed',
                $url,
                1
            );
            if (is_string($converted) && $converted !== $url) {
                return $converted;
            }

            if (preg_match('~[?&]resid=([^&]+)~i', $url, $m)) {
                $embed = 'https://onedrive.live.com/embed?resid=' . $m[1];
                if (preg_match('~[?&]authkey=([^&]+)~i', $url, $a)) {
                    $embed .= '&authkey=' . $a[1];
                }

                return $embed;
            }

            return self::appendQuery($url, 'embed', '1');
        }

        if (str_contains($host, 'sharepoint.com')) {
            return self::appendQuery($url, 'action', 'embedview');
        }

        return self::appendQuery($url, 'embed', '1');
    }

    public static function fromCanva(string $url): ?string
    {
        $url = trim($url);
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);
        if ($host !== 'canva.com' && $host !== 'www.canva.com') {
            return null;
        }

        if (!preg_match('~canva\.com/design/[A-Za-z0-9_-]+~i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');

        if (preg_match('~/edit/?$~i', $path)) {
            $path = (string) preg_replace('~/edit/?$~i', '/view', $path);
        } elseif (
            !preg_match('~/(?:view|watch)/?$~i', $path)
            && preg_match('~/design/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+/?$~', $path)
        ) {
            $path = rtrim($path, '/') . '/view';
        }

        $rebuild = (isset($parts['scheme']) ? $parts['scheme'] . '://' : 'https://')
            . ($parts['host'] ?? 'www.canva.com')
            . $path;
        if (!empty($parts['query'])) {
            $rebuild .= '?' . $parts['query'];
        }

        if (preg_match('~[?&]embed(?:[=&]|$)~i', $rebuild)) {
            return $rebuild;
        }

        return $rebuild . (str_contains($rebuild, '?') ? '&' : '?') . 'embed';
    }

    public static function vimeoId(string $url): ?string
    {
        $url = trim($url);

        if (preg_match('~player\.vimeo\.com/video/(\d+)~i', $url, $m)) {
            return $m[1];
        }

        if (preg_match(
            '~vimeo\.com/(?:channels/[^/]+/|groups/[^/]+/videos/|ondemand/[^/]+/|video/)?(\d+)~i',
            $url,
            $m
        )) {
            return $m[1];
        }

        return null;
    }

    public static function fromVimeo(string $url): ?string
    {
        $id = self::vimeoId($url);

        return $id !== null ? 'https://player.vimeo.com/video/' . $id : null;
    }

    public static function forUploadedFile(string $publicUrl, string $ext): string
    {
        $ext = strtolower($ext);

        if (in_array($ext, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true)) {
            return 'https://docs.google.com/gview?url=' . rawurlencode($publicUrl) . '&embedded=true';
        }

        return $publicUrl;
    }

    public static function thumbnailFor(array $row): ?string
    {
        $sumber = (string) ($row['sumber'] ?? '');

        if ($sumber === 'youtube') {
            $id = self::youtubeId((string) ($row['original_url'] ?? ''))
                ?? self::youtubeId((string) ($row['embed_url'] ?? ''));

            return $id !== null ? 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg' : null;
        }

        if ($sumber === 'gdrive') {
            $id = self::googleDriveId((string) ($row['original_url'] ?? ''))
                ?? self::googleDriveId((string) ($row['embed_url'] ?? ''));

            return $id !== null ? 'https://drive.google.com/thumbnail?id=' . rawurlencode($id) . '&sz=w800' : null;
        }

        if ($sumber === 'upload') {
            $ext = strtolower((string) ($row['file_ext'] ?? ''));
            $path = (string) ($row['file_path'] ?? '');
            $id = (int) ($row['id'] ?? 0);
            if ($path !== '' && $id > 0 && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                return app_url('bahan/file.php?id=' . $id);
            }
        }

        return null;
    }

    private static function appendQuery(string $url, string $key, string $value): string
    {
        if (preg_match('~[?&]' . preg_quote($key, '~') . '=~i', $url)) {
            return $url;
        }

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url . $sep . rawurlencode($key) . '=' . rawurlencode($value);
    }
}

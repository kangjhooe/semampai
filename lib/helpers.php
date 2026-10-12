<?php

declare(strict_types=1);

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function app_name(): string
{
    $fromEnv = env('APP_NAME');
    if (is_string($fromEnv) && trim($fromEnv) !== '') {
        return trim($fromEnv);
    }

    return AppSettings::get('app_name', 'Gurupai');
}

/** Slug aman untuk localStorage / ID teknis (bukan hardcode nama brand). */
function app_slug(): string
{
    $slug = preg_replace('/[^a-z0-9]+/i', '', strtolower(app_name())) ?? '';

    return $slug !== '' ? $slug : 'app';
}

/** Ubah path internal (*.php) menjadi URL bersih tanpa ekstensi. */
function app_pretty_path(string $path): string
{
    $path = ltrim($path, '/');
    if ($path === '') {
        return '';
    }

    $query = '';
    $fragment = '';

    if (str_contains($path, '#')) {
        [$path, $fragment] = explode('#', $path, 2);
        $fragment = '#' . $fragment;
    }

    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
        $query = '?' . $query;
    }

    if (str_ends_with(strtolower($path), '.php')) {
        $path = substr($path, 0, -4);
    }

    $isDirectoryIndex = false;
    if (strcasecmp($path, 'index') === 0) {
        $path = '';
        $isDirectoryIndex = true;
    } else {
        $stripped = preg_replace('#/index$#i', '', $path) ?? $path;
        if ($stripped !== $path) {
            $path = $stripped;
            $isDirectoryIndex = true;
        }
    }

    // /kelas (tanpa garis miring) di-redirect 301 ke /kelas/ oleh server.
    // Redirect itu mengubah POST menjadi GET, jadi simpan kelas tidak tersimpan.
    if ($isDirectoryIndex && $path !== '') {
        $path .= '/';
    }

    return $path . $query . $fragment;
}

/** Host lokal atau IP LAN. Domain publik tidak masuk sini. */
function app_host_is_local(string $host): bool
{
    $host = strtolower(trim($host, '[]'));

    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        return false;
    }

    return filter_var(
        $host,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}

/**
 * Basis URL aplikasi.
 * Kalau APP_URL di hosting masih http:// pada domain publik, naikkan ke https://.
 * Halaman HTTPS menolak CSS dan JS dari http://, jadi tampilan jatuh ke HTML polos.
 */
function app_base_url(): string
{
    $base = rtrim(env('APP_URL', '') ?? '', '/');
    if ($base === '' || preg_match('#^http://#i', $base) !== 1) {
        return $base;
    }

    $host = parse_url($base, PHP_URL_HOST);
    if (!is_string($host) || app_host_is_local($host)) {
        return $base;
    }

    return 'https://' . substr($base, 7);
}

function app_url(string $path = ''): string
{
    $base = app_base_url();
    $path = app_pretty_path($path);

    if ($base === '') {
        return $path === '' ? '/' : '/' . $path;
    }

    if ($path === '') {
        $basePath = parse_url($base, PHP_URL_PATH);
        if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
            return $base . '/';
        }

        return $base;
    }

    return $base . '/' . $path;
}

/** Kunci untuk tanda tangan URL (mis. unduhan bahan ajar). */
function app_key(): string
{
    $key = env('APP_KEY');
    if ($key !== null && $key !== '') {
        return $key;
    }

    return hash(
        'sha256',
        'semampai|' . (env('DB_NAME', 'semampai') ?? 'semampai') . '|' . (env('DB_PASS', '') ?? '')
    );
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

function store_old(array $data): void
{
    unset($data['password'], $data['password_confirmation'], $data['_token']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function view(string $name, array $data = [], string $layout = 'guest'): void
{
    extract($data, EXTR_SKIP);
    $appName = app_name();
    $contentView = BASE_PATH . '/views/' . $name . '.php';
    $layoutFile = BASE_PATH . '/views/layouts/' . $layout . '.php';

    if (!is_file($contentView) || !is_file($layoutFile)) {
        http_response_code(500);
        echo 'View tidak ditemukan.';
        exit;
    }

    require $layoutFile;
}

function only_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function icon(string $name): string
{
    $icons = [
        'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4.5L19 9.5 14.5 5 4 15.5V20Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m13.2 6.3 4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M9.5 7V5.8A1.8 1.8 0 0 1 11.3 4h1.4A1.8 1.8 0 0 1 14.5 5.8V7m-7 0 0.7 11.2A1.8 1.8 0 0 0 10 20h4a1.8 1.8 0 0 0 1.8-1.8L16.5 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v5M14 11v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>',
        'eye-off' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4.5 20 19.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.7 9.9a2.6 2.6 0 0 0 3.6 3.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6.4 7.1C4.3 8.4 2.8 10.3 2.5 12s3.5 6.5 9.5 6.5c1.6 0 3.1-.4 4.3-1.1M9.8 5.6A9.8 9.8 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.7 3.3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 12Zm-7.5 8a7.5 7.5 0 0 1 15 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M18.5 8.5a2.5 2.5 0 1 0-.3-4.98M20.8 15.2a5.2 5.2 0 0 0-3.1-2.55" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ];

    return $icons[$name] ?? '';
}

function password_toggle(): string
{
    return '<button type="button" class="password-toggle" aria-pressed="false" aria-label="Tampilkan password" title="Tampilkan password">'
        . '<span data-icon="show">' . icon('eye') . '</span>'
        . '<span data-icon="hide">' . icon('eye-off') . '</span>'
        . '</button>';
}

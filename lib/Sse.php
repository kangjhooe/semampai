<?php

declare(strict_types=1);

final class Sse
{
    public static function begin(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        ignore_user_abort(true);
        @set_time_limit(0);
    }

    public static function send(string $event, mixed $data, ?string $id = null): void
    {
        if ($id !== null) {
            echo 'id: ' . $id . "\n";
        }
        echo 'event: ' . $event . "\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (function_exists('flush')) {
            flush();
        }
    }

    public static function comment(string $text = 'ping'): void
    {
        echo ': ' . $text . "\n\n";
        if (function_exists('flush')) {
            flush();
        }
    }

    /**
     * Poll $fetchVersion until change or timeout; call $fetchPayload when version changes.
     *
     * @param callable():int $fetchVersion
     * @param callable():array $fetchPayload
     */
    public static function streamUntil(
        callable $fetchVersion,
        callable $fetchPayload,
        int $maxSeconds = 25,
        int $intervalMs = 700
    ): void {
        self::begin();

        $started = time();
        $lastVersion = -1;
        $lastPing = time();

        while (!connection_aborted() && (time() - $started) < $maxSeconds) {
            try {
                $version = (int) $fetchVersion();
            } catch (Throwable $e) {
                self::send('error', ['message' => 'Gagal membaca state.']);
                break;
            }

            if ($version !== $lastVersion) {
                $lastVersion = $version;
                try {
                    $payload = $fetchPayload();
                } catch (Throwable $e) {
                    self::send('error', ['message' => 'Gagal menyusun snapshot.']);
                    break;
                }
                self::send('state', $payload, (string) $version);
            } elseif ((time() - $lastPing) >= 10) {
                self::comment('ping');
                $lastPing = time();
            }

            usleep($intervalMs * 1000);
        }

        self::send('reconnect', ['after_ms' => 200]);
    }
}

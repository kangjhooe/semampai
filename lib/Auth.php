<?php

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, s.nama_sekolah
             FROM users u
             INNER JOIN sekolah s ON s.npsn = u.npsn
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function register(array $data): array
    {
        $errors = self::validateRegistration($data);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $nik = only_digits($data['nik']);
        $npsn = only_digits($data['npsn']);
        $pdo = Database::connection();

        $checkNik = $pdo->prepare('SELECT id FROM users WHERE nik = :nik LIMIT 1');
        $checkNik->execute(['nik' => $nik]);
        if ($checkNik->fetch()) {
            return ['ok' => false, 'errors' => ['nik' => 'NIK sudah terdaftar.']];
        }

        $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkEmail->execute(['email' => strtolower(trim($data['email']))]);
        if ($checkEmail->fetch()) {
            return ['ok' => false, 'errors' => ['email' => 'Email sudah terdaftar.']];
        }

        try {
            $pdo->beginTransaction();

            $sekolah = $pdo->prepare('SELECT npsn, nama_sekolah FROM sekolah WHERE npsn = :npsn LIMIT 1');
            $sekolah->execute(['npsn' => $npsn]);
            $existingSchool = $sekolah->fetch();

            if (!$existingSchool) {
                $insertSchool = $pdo->prepare(
                    'INSERT INTO sekolah (npsn, nama_sekolah) VALUES (:npsn, :nama_sekolah)'
                );
                $insertSchool->execute([
                    'npsn' => $npsn,
                    'nama_sekolah' => trim($data['nama_sekolah']),
                ]);
                $namaSekolah = trim($data['nama_sekolah']);
            } else {
                $namaSekolah = $existingSchool['nama_sekolah'];
            }

            $insertUser = $pdo->prepare(
                'INSERT INTO users (nik, nama, email, password_hash, npsn, no_wa, role)
                 VALUES (:nik, :nama, :email, :password_hash, :npsn, :no_wa, :role)'
            );
            $insertUser->execute([
                'nik' => $nik,
                'nama' => trim($data['nama']),
                'email' => strtolower(trim($data['email'])),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'npsn' => $npsn,
                'no_wa' => only_digits($data['no_wa']),
                'role' => 'guru',
            ]);

            $userId = (int) $pdo->lastInsertId();
            $pdo->commit();

            $user = [
                'id' => $userId,
                'nik' => $nik,
                'nama' => trim($data['nama']),
                'email' => strtolower(trim($data['email'])),
                'npsn' => $npsn,
                'no_wa' => only_digits($data['no_wa']),
                'role' => 'guru',
                'nama_sekolah' => $namaSekolah,
            ];

            self::login($user);

            return ['ok' => true, 'errors' => [], 'user' => $user];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'errors' => ['_form' => 'Pendaftaran gagal. Coba lagi.']];
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'nik' => $user['nik'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'npsn' => $user['npsn'],
            'no_wa' => $user['no_wa'],
            'role' => $user['role'],
            'nama_sekolah' => $user['nama_sekolah'] ?? '',
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['user'] : null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Silakan masuk terlebih dahulu.');
            redirect('index.php');
        }
    }

    public static function guestOnly(): void
    {
        if (self::check()) {
            redirect('dashboard.php');
        }
    }

    /** @return array<string, string> */
    private static function validateRegistration(array $data): array
    {
        $errors = [];

        $nama = trim($data['nama'] ?? '');
        $nik = only_digits($data['nik'] ?? '');
        $npsn = only_digits($data['npsn'] ?? '');
        $namaSekolah = trim($data['nama_sekolah'] ?? '');
        $noWa = only_digits($data['no_wa'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $passwordConfirmation = $data['password_confirmation'] ?? '';

        if ($nama === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        }

        if (strlen($nik) !== 16) {
            $errors['nik'] = 'NIK harus 16 digit angka.';
        }

        if (strlen($npsn) !== 8) {
            $errors['npsn'] = 'NPSN harus 8 digit angka.';
        }

        if ($namaSekolah === '') {
            $errors['nama_sekolah'] = 'Nama sekolah wajib diisi.';
        }

        if (strlen($noWa) < 10 || strlen($noWa) > 15) {
            $errors['no_wa'] = 'Nomor WA tidak valid.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak cocok.';
        }

        return $errors;
    }
}

<?php

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT *
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        try {
            self::login($user);
            return true;
        } catch (Throwable $e) {
            return false;
        }
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

            $ensured = Sekolah::ensure($npsn, (string) $data['nama_sekolah']);
            if (!$ensured['ok']) {
                $pdo->rollBack();
                return ['ok' => false, 'errors' => $ensured['errors']];
            }

            $npsn = (string) $ensured['npsn'];
            $namaSekolah = (string) $ensured['nama_sekolah'];

            $insertUser = $pdo->prepare(
                'INSERT INTO users (nik, nama, email, password_hash, no_wa, role)
                 VALUES (:nik, :nama, :email, :password_hash, :no_wa, :role)'
            );
            $insertUser->execute([
                'nik' => $nik,
                'nama' => trim($data['nama']),
                'email' => strtolower(trim($data['email'])),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'no_wa' => only_digits($data['no_wa']),
                'role' => 'guru',
            ]);

            $userId = (int) $pdo->lastInsertId();

            $link = $pdo->prepare(
                'INSERT INTO user_sekolah (user_id, npsn) VALUES (:user_id, :npsn)'
            );
            $link->execute(['user_id' => $userId, 'npsn' => $npsn]);

            $pdo->commit();

            $user = [
                'id' => $userId,
                'nik' => $nik,
                'nama' => trim($data['nama']),
                'email' => strtolower(trim($data['email'])),
                'no_wa' => only_digits($data['no_wa']),
                'role' => 'guru',
            ];

            self::login($user, $npsn);

            return ['ok' => true, 'errors' => [], 'user' => self::user()];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'errors' => ['_form' => 'Pendaftaran gagal. Coba lagi.']];
        }
    }

    public static function login(array $user, ?string $preferredNpsn = null): void
    {
        session_regenerate_id(true);

        $userId = (int) $user['id'];
        $schools = Sekolah::forUser($userId);
        if ($schools === []) {
            // Akun lama tanpa pivot — jangan biarkan session setengah jalan.
            $_SESSION = [];
            throw new RuntimeException('Akun belum terhubung ke sekolah. Jalankan migrasi multi-sekolah.');
        }

        $activeNpsn = only_digits((string) ($preferredNpsn ?? ''));
        $activeName = '';
        foreach ($schools as $school) {
            if ($school['npsn'] === $activeNpsn) {
                $activeName = $school['nama_sekolah'];
                break;
            }
        }

        if ($activeName === '') {
            $activeNpsn = $schools[0]['npsn'];
            $activeName = $schools[0]['nama_sekolah'];
        }

        $_SESSION['user'] = [
            'id' => $userId,
            'nik' => $user['nik'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'no_wa' => $user['no_wa'],
            'role' => $user['role'],
            'npsn' => $activeNpsn,
            'nama_sekolah' => $activeName,
            'sekolah_list' => $schools,
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
        if (!self::check()) {
            return null;
        }

        self::refreshSchoolContext();

        return $_SESSION['user'];
    }

    public static function activeNpsn(): string
    {
        $user = self::user();
        return only_digits((string) ($user['npsn'] ?? ''));
    }

    /**
     * @return list<array{npsn: string, nama_sekolah: string}>
     */
    public static function schools(): array
    {
        $user = self::user();
        if (!$user) {
            return [];
        }

        return $user['sekolah_list'] ?? [];
    }

    public static function switchSchool(string $npsn): bool
    {
        if (!self::check()) {
            return false;
        }

        $npsn = only_digits($npsn);
        $userId = (int) $_SESSION['user']['id'];
        if (!Sekolah::userHas($userId, $npsn)) {
            return false;
        }

        $school = Sekolah::find($npsn);
        if (!$school) {
            return false;
        }

        $_SESSION['user']['npsn'] = $school['npsn'];
        $_SESSION['user']['nama_sekolah'] = $school['nama_sekolah'];
        self::refreshSchoolContext(true);

        return true;
    }

    public static function refreshSchoolContext(bool $forceReloadList = false): void
    {
        if (!isset($_SESSION['user']['id'])) {
            return;
        }

        $userId = (int) $_SESSION['user']['id'];
        if ($forceReloadList || !isset($_SESSION['user']['sekolah_list']) || !is_array($_SESSION['user']['sekolah_list'])) {
            $_SESSION['user']['sekolah_list'] = Sekolah::forUser($userId);
        }

        $schools = $_SESSION['user']['sekolah_list'];
        if ($schools === []) {
            return;
        }

        $active = only_digits((string) ($_SESSION['user']['npsn'] ?? ''));
        foreach ($schools as $school) {
            if ($school['npsn'] === $active) {
                $_SESSION['user']['nama_sekolah'] = $school['nama_sekolah'];
                return;
            }
        }

        $_SESSION['user']['npsn'] = $schools[0]['npsn'];
        $_SESSION['user']['nama_sekolah'] = $schools[0]['nama_sekolah'];
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Silakan masuk terlebih dahulu.');
            redirect('index.php');
        }

        self::refreshSchoolContext();
        if (self::activeNpsn() === '') {
            flash('error', 'Akun belum terhubung ke sekolah. Hubungi admin atau jalankan migrasi.');
            self::logout();
            redirect('index.php');
        }
    }

    public static function guestOnly(): void
    {
        if (self::check()) {
            redirect('dashboard.php');
        }
    }

    public static function updateProfile(int $userId, array $data): array
    {
        $errors = self::validateProfile($data);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $nama = trim((string) $data['nama']);
        $email = strtolower(trim((string) $data['email']));
        $noWa = only_digits((string) $data['no_wa']);
        $pdo = Database::connection();

        $checkEmail = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
        );
        $checkEmail->execute(['email' => $email, 'id' => $userId]);
        if ($checkEmail->fetch()) {
            return ['ok' => false, 'errors' => ['email' => 'Email sudah dipakai akun lain.']];
        }

        try {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET nama = :nama, email = :email, no_wa = :no_wa
                 WHERE id = :id'
            );
            $stmt->execute([
                'nama' => $nama,
                'email' => $email,
                'no_wa' => $noWa,
                'id' => $userId,
            ]);

            if (isset($_SESSION['user']) && (int) $_SESSION['user']['id'] === $userId) {
                $_SESSION['user']['nama'] = $nama;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['no_wa'] = $noWa;
            }

            return ['ok' => true, 'errors' => []];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['_form' => 'Gagal memperbarui profil.']];
        }
    }

    public static function changePassword(int $userId, array $data): array
    {
        $errors = [];
        $current = (string) ($data['current_password'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $confirmation = (string) ($data['password_confirmation'] ?? '');

        if ($current === '') {
            $errors['current_password'] = 'Isi password saat ini.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password baru minimal 8 karakter.';
        }
        if ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak cocok.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            return ['ok' => false, 'errors' => ['current_password' => 'Password saat ini salah.']];
        }

        try {
            $update = $pdo->prepare(
                'UPDATE users SET password_hash = :password_hash WHERE id = :id'
            );
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $userId,
            ]);

            return ['ok' => true, 'errors' => []];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['_form' => 'Gagal mengubah password.']];
        }
    }

    /** @return array<string, string> */
    private static function validateProfile(array $data): array
    {
        $errors = [];
        $nama = trim((string) ($data['nama'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $noWa = only_digits((string) ($data['no_wa'] ?? ''));

        if ($nama === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid.';
        }

        if (strlen($noWa) < 10 || strlen($noWa) > 15) {
            $errors['no_wa'] = 'Nomor WA tidak valid.';
        }

        return $errors;
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

        $schoolErrors = Sekolah::validateSchoolInput($npsn, $namaSekolah);
        $errors = array_merge($errors, $schoolErrors);

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

<?php
/** @var string $appName */
$initial = mb_strtoupper(mb_substr($appName, 0, 1));
$error = flash('error');
$success = flash('success');
?>
<div class="auth-landing">
  <aside class="auth-intro">
    <div class="brand">
      <span class="brand-mark"><?= e($initial) ?></span>
      <h1><?= e($appName) ?></h1>
      <p>Alat kerja harian guru PAI — kelola kelas dan siswa dengan rapi, tanpa spreadsheet berantakan.</p>
    </div>

    <ul class="auth-benefits">
      <li>
        <span class="auth-benefit-icon" aria-hidden="true">1</span>
        <div>
          <strong>Kelola kelas</strong>
          <span>Susun rombel dan daftar siswa per kelas dengan cepat.</span>
        </div>
      </li>
      <li>
        <span class="auth-benefit-icon" aria-hidden="true">2</span>
        <div>
          <strong>Impor data siswa</strong>
          <span>Masukkan data dari berkas, siap dipakai untuk kerja harian.</span>
        </div>
      </li>
      <li>
        <span class="auth-benefit-icon" aria-hidden="true">3</span>
        <div>
          <strong>Terikat sekolah Anda</strong>
          <span>Akun guru terhubung NPSN — data tetap di konteks sekolah.</span>
        </div>
      </li>
    </ul>

    <div class="auth-intro-cta">
      <p class="muted">Belum punya akun? Daftar sekali, lalu mulai dari kelas.</p>
      <a class="btn btn-ghost" href="<?= e(app_url('daftar.php')) ?>">Buat akun guru</a>
    </div>
  </aside>

  <div class="panel auth-form-panel">
    <div class="brand brand-compact">
      <h2>Masuk</h2>
      <p>Gunakan email dan password akun guru Anda.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert success"><?= e($success) ?></div>
    <?php endif; ?>

    <form class="stack" method="post" action="<?= e(app_url('index.php')) ?>" autocomplete="on">
      <?= Csrf::field() ?>

      <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= old('email') ?>" required autofocus>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
      </div>

      <button class="btn btn-primary" type="submit">Masuk</button>
    </form>

    <p class="auth-switch">
      Belum punya akun?
      <a href="<?= e(app_url('daftar.php')) ?>">Daftar guru</a>
    </p>
  </div>
</div>

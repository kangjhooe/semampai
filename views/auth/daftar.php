<?php
/** @var string $appName */
/** @var array<string, string> $errors */
$initial = mb_strtoupper(mb_substr($appName, 0, 1));
$errors = $errors ?? [];
?>
<div class="panel wide">
  <div class="brand">
    <span class="brand-mark"><?= e($initial) ?></span>
    <h1>Daftar di <?= e($appName) ?></h1>
    <p>Isi data guru dan sekolah. NIK unik per guru; NPSN unik per sekolah.</p>
  </div>

  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <form class="stack" method="post" action="<?= e(app_url('daftar.php')) ?>" autocomplete="on">
    <?= Csrf::field() ?>

    <div class="field">
      <label for="nama">Nama lengkap</label>
      <input id="nama" type="text" name="nama" value="<?= old('nama') ?>" required>
      <?php if (!empty($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="nik">NIK</label>
        <input id="nik" type="text" name="nik" inputmode="numeric" maxlength="16" value="<?= old('nik') ?>" required>
        <div class="hint">16 digit, identitas unik guru</div>
        <?php if (!empty($errors['nik'])): ?><div class="error"><?= e($errors['nik']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="no_wa">No. WhatsApp</label>
        <input id="no_wa" type="text" name="no_wa" inputmode="numeric" value="<?= old('no_wa') ?>" required>
        <?php if (!empty($errors['no_wa'])): ?><div class="error"><?= e($errors['no_wa']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="npsn">NPSN sekolah</label>
        <input id="npsn" type="text" name="npsn" inputmode="numeric" maxlength="8" value="<?= old('npsn') ?>" required>
        <div class="hint">8 digit. Jika sudah ada, sekolah dipakai bersama</div>
        <?php if (!empty($errors['npsn'])): ?><div class="error"><?= e($errors['npsn']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="nama_sekolah">Nama sekolah</label>
        <input id="nama_sekolah" type="text" name="nama_sekolah" value="<?= old('nama_sekolah') ?>" required>
        <div class="hint">Diabaikan jika NPSN sudah terdaftar</div>
        <?php if (!empty($errors['nama_sekolah'])): ?><div class="error"><?= e($errors['nama_sekolah']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= old('email') ?>" required>
      <?php if (!empty($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" minlength="8" required>
        <?php if (!empty($errors['password'])): ?><div class="error"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="password_confirmation">Ulangi password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
        <?php if (!empty($errors['password_confirmation'])): ?><div class="error"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
      </div>
    </div>

    <button class="btn btn-primary" type="submit">Buat akun</button>
  </form>

  <p class="auth-switch">
    Sudah punya akun?
    <a href="<?= e(app_url('index.php')) ?>">Masuk</a>
  </p>
</div>

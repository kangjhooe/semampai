<?php
/** @var array $user */
/** @var array<string, string> $profileErrors */
/** @var array<string, string> $passwordErrors */
/** @var string $activeTab */
$success = flash('success');
$error = flash('error');
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$activeTab = ($activeTab ?? 'profil') === 'password' ? 'password' : 'profil';
?>
<section class="page-head">
  <div>
    <p class="muted">Perbarui data akun atau ganti password.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="card stack" style="max-width: 560px;">
  <div class="tampil-toggle" role="tablist" aria-label="Bagian profil">
    <a
      class="tampil-btn<?= $activeTab === 'profil' ? ' is-active' : '' ?>"
      href="<?= e(app_url('profil.php')) ?>"
      role="tab"
      aria-selected="<?= $activeTab === 'profil' ? 'true' : 'false' ?>"
    >Data akun</a>
    <a
      class="tampil-btn<?= $activeTab === 'password' ? ' is-active' : '' ?>"
      href="<?= e(app_url('profil.php?tab=password')) ?>"
      role="tab"
      aria-selected="<?= $activeTab === 'password' ? 'true' : 'false' ?>"
    >Password</a>
  </div>

  <?php if ($activeTab === 'password'): ?>
    <?php if (!empty($passwordErrors['_form'])): ?>
      <div class="alert error"><?= e($passwordErrors['_form']) ?></div>
    <?php endif; ?>

    <form class="stack" method="post" action="<?= e(app_url('profil.php')) ?>" autocomplete="off">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="password">

      <div class="field">
        <label for="current_password">Password saat ini</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
        <?php if (!empty($passwordErrors['current_password'])): ?>
          <div class="error"><?= e($passwordErrors['current_password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="password">Password baru</label>
        <input id="password" type="password" name="password" minlength="8" required autocomplete="new-password">
        <div class="hint">Minimal 8 karakter</div>
        <?php if (!empty($passwordErrors['password'])): ?>
          <div class="error"><?= e($passwordErrors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="password_confirmation">Ulangi password baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
        <?php if (!empty($passwordErrors['password_confirmation'])): ?>
          <div class="error"><?= e($passwordErrors['password_confirmation']) ?></div>
        <?php endif; ?>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Ubah password</button>
      </div>
    </form>
  <?php else: ?>
    <?php if (!empty($profileErrors['_form'])): ?>
      <div class="alert error"><?= e($profileErrors['_form']) ?></div>
    <?php endif; ?>

    <form class="stack" method="post" action="<?= e(app_url('profil.php')) ?>" autocomplete="on">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="profil">

      <div class="field">
        <label for="nama">Nama lengkap</label>
        <input id="nama" type="text" name="nama" value="<?= old('nama', (string) ($user['nama'] ?? '')) ?>" required>
        <?php if (!empty($profileErrors['nama'])): ?><div class="error"><?= e($profileErrors['nama']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= old('email', (string) ($user['email'] ?? '')) ?>" required>
        <?php if (!empty($profileErrors['email'])): ?><div class="error"><?= e($profileErrors['email']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="no_wa">No. WhatsApp</label>
        <input id="no_wa" type="text" name="no_wa" inputmode="numeric" value="<?= old('no_wa', (string) ($user['no_wa'] ?? '')) ?>" required>
        <?php if (!empty($profileErrors['no_wa'])): ?><div class="error"><?= e($profileErrors['no_wa']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label>NIK</label>
        <input type="text" value="<?= e((string) ($user['nik'] ?? '')) ?>" disabled>
        <div class="hint">NIK tidak bisa diubah</div>
      </div>

      <div class="grid-2">
        <div class="field">
          <label>NPSN</label>
          <input type="text" value="<?= e((string) ($user['npsn'] ?? '')) ?>" disabled>
        </div>
        <div class="field">
          <label>Sekolah</label>
          <input type="text" value="<?= e((string) ($user['nama_sekolah'] ?? '')) ?>" disabled>
        </div>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Simpan profil</button>
      </div>
    </form>
  <?php endif; ?>
</section>

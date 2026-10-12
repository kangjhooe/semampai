<?php
/** @var array $user */
/** @var array<string, string> $profileErrors */
/** @var array<string, string> $passwordErrors */
/** @var array<string, string> $schoolErrors */
/** @var list<array{npsn:string,nama_sekolah:string}> $sekolahList */
/** @var string $activeTab */
$success = flash('success');
$error = flash('error');
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$schoolErrors = $schoolErrors ?? [];
$sekolahList = $sekolahList ?? [];
$activeTab = $activeTab ?? 'profil';
if (!in_array($activeTab, ['profil', 'password', 'sekolah'], true)) {
    $activeTab = 'profil';
}
$activeNpsn = (string) ($user['npsn'] ?? '');
?>
<section class="page-head">
  <div>
    <p class="muted">Perbarui data akun, kelola sekolah, atau ganti password.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="card stack" style="max-width: 640px;">
  <div class="tampil-toggle" role="tablist" aria-label="Bagian profil">
    <a
      class="tampil-btn<?= $activeTab === 'profil' ? ' is-active' : '' ?>"
      href="<?= e(app_url('profil.php')) ?>"
      role="tab"
      aria-selected="<?= $activeTab === 'profil' ? 'true' : 'false' ?>"
    >Data akun</a>
    <a
      class="tampil-btn<?= $activeTab === 'sekolah' ? ' is-active' : '' ?>"
      href="<?= e(app_url('profil.php?tab=sekolah')) ?>"
      role="tab"
      aria-selected="<?= $activeTab === 'sekolah' ? 'true' : 'false' ?>"
    >Sekolah</a>
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

  <?php elseif ($activeTab === 'sekolah'): ?>
    <?php if (!empty($schoolErrors['_form'])): ?>
      <div class="alert error"><?= e($schoolErrors['_form']) ?></div>
    <?php endif; ?>

    <div class="stack">
      <h2 class="card-title" style="margin:0;">Sekolah terhubung</h2>
      <p class="muted" style="margin:0;">Satu akun bisa mengajar di lebih dari satu sekolah. Data kelas mengikuti sekolah aktif.</p>

      <?php if ($sekolahList === []): ?>
        <p class="muted">Belum ada sekolah.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Sekolah</th>
                <th>NPSN</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sekolahList as $school): ?>
                <?php $isActive = $school['npsn'] === $activeNpsn; ?>
                <tr>
                  <td>
                    <strong><?= e($school['nama_sekolah']) ?></strong>
                    <?php if ($isActive): ?>
                      <div class="hint">Aktif sekarang</div>
                    <?php endif; ?>
                  </td>
                  <td><?= e($school['npsn']) ?></td>
                  <td class="table-actions">
                    <?php if (!$isActive): ?>
                      <form method="post" action="<?= e(app_url('profil.php')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="aktifkan_sekolah">
                        <input type="hidden" name="npsn" value="<?= e($school['npsn']) ?>">
                        <button class="btn btn-ghost" type="submit">Jadikan aktif</button>
                      </form>
                    <?php endif; ?>
                    <?php if (count($sekolahList) > 1): ?>
                      <form method="post" action="<?= e(app_url('profil.php')) ?>" onsubmit="return confirm('Lepas sekolah ini dari akun? Kelas di sekolah ini harus kosong.');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="lepas_sekolah">
                        <input type="hidden" name="npsn" value="<?= e($school['npsn']) ?>">
                        <button class="btn-icon danger" type="submit" title="Lepas" aria-label="Lepas sekolah">
                          <?= icon('trash') ?>
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:0.25rem 0;">

    <form class="stack" method="post" action="<?= e(app_url('profil.php')) ?>">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="tambah_sekolah">
      <h2 class="card-title" style="margin:0;">Tambah sekolah</h2>

      <div class="grid-2">
        <div class="field">
          <label for="npsn">NPSN</label>
          <input id="npsn" type="text" name="npsn" inputmode="numeric" maxlength="8" value="<?= old('npsn') ?>" required>
          <?php if (!empty($schoolErrors['npsn'])): ?><div class="error"><?= e($schoolErrors['npsn']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label for="nama_sekolah">Nama sekolah</label>
          <input id="nama_sekolah" type="text" name="nama_sekolah" value="<?= old('nama_sekolah') ?>" required>
          <div class="hint">Jika NPSN sudah ada, nama yang tersimpan dipakai</div>
          <?php if (!empty($schoolErrors['nama_sekolah'])): ?><div class="error"><?= e($schoolErrors['nama_sekolah']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label>
          <input type="checkbox" name="jadikan_aktif" value="1" checked>
          Jadikan sekolah aktif setelah ditambah
        </label>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Tambah sekolah</button>
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

      <div class="field">
        <label>Sekolah aktif</label>
        <input type="text" value="<?= e(($user['nama_sekolah'] ?? '') . ' · NPSN ' . ($user['npsn'] ?? '')) ?>" disabled>
        <div class="hint"><a href="<?= e(app_url('profil.php?tab=sekolah')) ?>">Kelola sekolah</a></div>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Simpan profil</button>
      </div>
    </form>
  <?php endif; ?>
</section>

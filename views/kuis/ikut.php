<?php
/** @var string $appName */
/** @var string $prefillKode */
/** @var array<string, string> $errors */
/** @var array|null $previewKuis */
/** @var list<array> $roster */
$errors = $errors ?? [];
$previewKuis = $previewKuis ?? null;
$roster = $roster ?? [];
$error = flash('error');
$success = flash('success');
$initial = mb_strtoupper(mb_substr($appName, 0, 1));
$hasRoster = $roster !== [];
$selectedSiswa = (int) ($_SESSION['_old']['siswa_id'] ?? 0);
?>
<div class="kuis-public">
  <div class="kuis-public-card">
    <div class="brand brand-compact">
      <span class="brand-mark"><?= e($initial) ?></span>
      <h1>Ikuti Kuis</h1>
      <p>
        <?php if ($hasRoster): ?>
          Pilih nama Anda dari daftar kelas, lalu mulai.
        <?php else: ?>
          Masukkan nama dan ID kuis dari guru Anda.
        <?php endif; ?>
      </p>
    </div>

    <?php if ($previewKuis): ?>
      <div class="kuis-join-meta">
        <strong><?= e((string) $previewKuis['judul']) ?></strong>
        <span class="muted">ID <?= e((string) $previewKuis['kode']) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($errors['_form'])): ?><div class="alert error"><?= e($errors['_form']) ?></div><?php endif; ?>

    <form class="stack" method="post" action="<?= e(app_url('kuis/ikut.php' . ($prefillKode !== '' ? '?kode=' . urlencode($prefillKode) : ''))) ?>" autocomplete="on">
      <?= Csrf::field() ?>

      <div class="field">
        <label for="kode">ID kuis</label>
        <input id="kode" type="text" name="kode" value="<?= old('kode', $prefillKode) ?>" required maxlength="8" class="kuis-kode-input" placeholder="Contoh: AB12CD" autocomplete="off" <?= $prefillKode !== '' && $previewKuis ? '' : 'autofocus' ?>>
        <?php if (!empty($errors['kode'])): ?><div class="error"><?= e($errors['kode']) ?></div><?php endif; ?>
        <?php if ($prefillKode === '' || !$previewKuis): ?>
          <p class="muted" style="margin:0.35rem 0 0;">Isi ID lalu tekan Enter / Mulai — jika kuis terikat kelas, daftar siswa akan muncul.</p>
        <?php endif; ?>
      </div>

      <?php if ($hasRoster): ?>
        <div class="field">
          <label for="siswa_id">Nama siswa</label>
          <select id="siswa_id" name="siswa_id" required autofocus>
            <option value="">— Pilih nama —</option>
            <?php foreach ($roster as $s): ?>
              <option value="<?= (int) $s['id'] ?>" <?= $selectedSiswa === (int) $s['id'] ? 'selected' : '' ?>>
                <?= e((string) $s['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['siswa_id'])): ?><div class="error"><?= e($errors['siswa_id']) ?></div><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="field">
          <label for="nama">Nama</label>
          <input id="nama" type="text" name="nama" value="<?= old('nama') ?>" <?= $hasRoster ? '' : 'required' ?> maxlength="120" placeholder="Nama lengkap">
          <?php if (!empty($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>
        </div>
      <?php endif; ?>

      <button class="btn btn-primary" type="submit">Mulai mengerjakan</button>
      <p class="muted" style="margin:0.75rem 0 0; text-align:center;">
        Ada PIN live dari guru?
        <a href="<?= e(app_url('kuis/live_join.php')) ?>">Gabung live</a>
      </p>
    </form>
  </div>
</div>
<script>
(function () {
  var el = document.getElementById('kode');
  if (!el) return;
  el.addEventListener('input', function () {
    el.value = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  });
})();
</script>

<?php
/** @var string $prefillPin */
/** @var array<string,string> $errors */
/** @var array|null $previewSesi */
/** @var array|null $previewKuis */
/** @var array $roster */
$errors = $errors ?? [];
$roster = $roster ?? [];
$oldPin = (string) ($_SESSION['_old']['pin'] ?? $prefillPin ?? '');
$oldNama = (string) ($_SESSION['_old']['nama'] ?? '');
$oldSiswa = (int) ($_SESSION['_old']['siswa_id'] ?? 0);
$error = flash('error');
?>
<div class="kuis-public live-join">
  <div class="kuis-public-card">
    <header>
      <p class="muted" style="margin:0;"><?= e($appName) ?> Live</p>
      <h1>Gabung kuis</h1>
      <p class="muted">Masukkan PIN 6 digit dari layar guru.</p>
    </header>

    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($errors['_form'])): ?><div class="alert error"><?= e($errors['_form']) ?></div><?php endif; ?>

    <?php if ($previewKuis && $previewSesi): ?>
      <div class="alert success" style="margin-bottom:1rem;">
        Siap gabung: <strong><?= e($previewKuis['judul']) ?></strong>
        <?php if (($previewSesi['status'] ?? '') !== 'lobby'): ?>
          <br><span class="muted">Sesi sudah berjalan — join hanya di lobby.</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form class="stack" method="post" action="<?= e(app_url('kuis/live_join.php')) ?>">
      <?= Csrf::field() ?>
      <div class="field">
        <label for="pin">PIN</label>
        <input id="pin" name="pin" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               value="<?= e($oldPin) ?>" required autocomplete="one-time-code" class="live-pin-input">
        <?php if (!empty($errors['pin'])): ?><div class="error"><?= e($errors['pin']) ?></div><?php endif; ?>
      </div>

      <?php if ($roster !== []): ?>
        <div class="field">
          <label for="siswa_id">Nama Anda</label>
          <select id="siswa_id" name="siswa_id" required>
            <option value="0">— Pilih —</option>
            <?php foreach ($roster as $s): ?>
              <option value="<?= (int) $s['id'] ?>" <?= $oldSiswa === (int) $s['id'] ? 'selected' : '' ?>>
                <?= e($s['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['siswa_id'])): ?><div class="error"><?= e($errors['siswa_id']) ?></div><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="field">
          <label for="nama">Nama panggilan</label>
          <input id="nama" name="nama" type="text" maxlength="120" value="<?= e($oldNama) ?>" required>
          <?php if (!empty($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Masuk lobby</button>
        <a class="btn btn-ghost" href="<?= e(app_url('kuis/ikut.php')) ?>">Mode biasa</a>
      </div>
    </form>
  </div>
</div>

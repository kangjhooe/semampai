<?php
/** @var array $kelas */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
?>
<section class="page-head">
  <div>
    <h1>Edit kelas</h1>
    <p class="muted"><a href="<?= e(app_url('kelas.php')) ?>">← Kembali ke pengelola kelas</a></p>
  </div>
</section>

<section class="card stack" style="max-width: 480px;">
  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>
  <form class="stack" method="post" action="<?= e(app_url('kelas_edit.php?id=' . $kelas['id'])) ?>">
    <?= Csrf::field() ?>
    <div class="field">
      <label for="nama">Nama kelas</label>
      <input id="nama" type="text" name="nama" value="<?= old('nama', $kelas['nama']) ?>" required>
      <?php if (!empty($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label for="tahun_ajaran">Tahun ajaran</label>
      <input id="tahun_ajaran" type="text" name="tahun_ajaran" value="<?= old('tahun_ajaran', $kelas['tahun_ajaran']) ?>" required>
      <?php if (!empty($errors['tahun_ajaran'])): ?><div class="error"><?= e($errors['tahun_ajaran']) ?></div><?php endif; ?>
    </div>
    <div class="actions">
      <button class="btn btn-primary" type="submit">Simpan</button>
      <a class="btn btn-ghost" href="<?= e(app_url('kelas.php')) ?>">Batal</a>
    </div>
  </form>
</section>

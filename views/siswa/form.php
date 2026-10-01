<?php
/** @var array $kelas */
/** @var array|null $siswa */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
$isEdit = $siswa !== null;
?>
<section class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit siswa' : 'Tambah siswa' ?></h1>
    <p class="muted">
      Kelas <?= e($kelas['nama']) ?> ·
      <a href="<?= e(app_url('siswa/index.php?kelas_id=' . $kelas['id'])) ?>">← Daftar siswa</a>
    </p>
  </div>
</section>

<section class="card stack" style="max-width: 560px;">
  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <form class="stack" method="post" action="<?= e(app_url('siswa/form.php?kelas_id=' . $kelas['id'] . ($isEdit ? '&id=' . $siswa['id'] : ''))) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">

    <div class="field">
      <label for="nama">Nama</label>
      <input id="nama" type="text" name="nama" value="<?= old('nama', $siswa['nama'] ?? '') ?>" required>
      <?php if (!empty($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="nisn">NISN</label>
      <input id="nisn" type="text" name="nisn" inputmode="numeric" maxlength="10" value="<?= old('nisn', $siswa['nisn'] ?? '') ?>" required>
      <div class="hint">10 digit, unik</div>
      <?php if (!empty($errors['nisn'])): ?><div class="error"><?= e($errors['nisn']) ?></div><?php endif; ?>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="tempat_lahir">Tempat lahir</label>
        <input id="tempat_lahir" type="text" name="tempat_lahir" value="<?= old('tempat_lahir', $siswa['tempat_lahir'] ?? '') ?>" required>
        <?php if (!empty($errors['tempat_lahir'])): ?><div class="error"><?= e($errors['tempat_lahir']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="tanggal_lahir">Tanggal lahir</label>
        <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="<?= old('tanggal_lahir', $siswa['tanggal_lahir'] ?? '') ?>" required>
        <?php if (!empty($errors['tanggal_lahir'])): ?><div class="error"><?= e($errors['tanggal_lahir']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Simpan perubahan' : 'Tambah siswa' ?></button>
      <a class="btn btn-ghost" href="<?= e(app_url('siswa/index.php?kelas_id=' . $kelas['id'])) ?>">Batal</a>
    </div>
  </form>
</section>

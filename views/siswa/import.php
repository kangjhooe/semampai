<?php
/** @var array $kelas */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
$importErrors = $_SESSION['_import_errors'] ?? [];
unset($_SESSION['_import_errors']);
?>
<section class="page-head">
  <div>
    <h1>Import siswa</h1>
    <p class="muted">
      Kelas <?= e($kelas['nama']) ?> ·
      <a href="<?= e(app_url('siswa.php?kelas_id=' . $kelas['id'])) ?>">← Daftar siswa</a>
    </p>
  </div>
</section>

<section class="card stack" style="max-width: 640px;">
  <p class="muted">
    Unduh template, isi kolom <strong>Nama, NISN, Tempat Lahir, Tanggal Lahir</strong>, lalu unggah kembali.
    Tanggal boleh format <code>YYYY-MM-DD</code> atau <code>DD/MM/YYYY</code>.
  </p>

  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('siswa_import.php?kelas_id=' . $kelas['id'] . '&template=1')) ?>">Unduh template Excel</a>
  </div>

  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <?php if ($importErrors !== []): ?>
    <div class="alert error">
      <strong>Detail:</strong>
      <ul class="compact-list">
        <?php foreach ($importErrors as $msg): ?>
          <li><?= e($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="stack" method="post" action="<?= e(app_url('siswa_import.php?kelas_id=' . $kelas['id'])) ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">
    <div class="field">
      <label for="file">File Excel (.xlsx / .xls / .csv)</label>
      <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" required>
    </div>
    <button class="btn btn-primary" type="submit">Import sekarang</button>
  </form>
</section>

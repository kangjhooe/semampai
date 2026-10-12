<?php
/** @var array $kuis */
/** @var array<string, string> $errors */
/** @var list<string> $importErrors */
$errors = $errors ?? [];
$importErrors = $importErrors ?? [];
$error = flash('error');
?>

<section class="page-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>">← Kembali ke edit kuis</a></p>
    <h2 class="card-title" style="margin:0.35rem 0 0;">Impor soal</h2>
    <p class="muted"><?= e($kuis['judul']) ?> · ID <code class="kuis-kode"><?= e($kuis['kode']) ?></code></p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/import.php?id=' . $kuis['id'] . '&template=1')) ?>">Unduh template</a>
  </div>
</section>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="card stack" style="max-width: 560px;">
  <p class="muted" style="margin:0;">
    Format kolom: <strong>Pertanyaan, Opsi A, Opsi B, Opsi C, Opsi D, Opsi E, Opsi F, Jawaban</strong>.
    Jawaban diisi huruf A–F. Minimal isi A dan B. Opsi kosong diabaikan.
  </p>

  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <?php if ($importErrors !== []): ?>
    <div class="alert error">
      <strong>Detail baris:</strong>
      <ul class="compact-list">
        <?php foreach ($importErrors as $msg): ?>
          <li><?= e($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="stack" method="post" action="<?= e(app_url('kuis/import.php?id=' . $kuis['id'])) ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $kuis['id'] ?>">
    <div class="field">
      <label for="file">File Excel / CSV</label>
      <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
    </div>
    <div class="actions">
      <button class="btn btn-primary" type="submit">Impor soal</button>
      <a class="btn btn-ghost" href="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>">Batal</a>
    </div>
  </form>
</section>

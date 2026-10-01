<?php
/** @var array $kelas */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
$sumber = old('sumber', 'upload');
if ($sumber === '') {
    $sumber = 'upload';
}
$isUpload = $sumber === 'upload';
?>
<section class="page-head">
  <div>
    <h1>Tambah bahan ajar</h1>
    <p class="muted">
      Kelas <?= e($kelas['nama']) ?> ·
      <a href="<?= e(app_url('bahan/index.php?kelas_id=' . $kelas['id'])) ?>">← Daftar bahan</a>
    </p>
  </div>
</section>

<section class="card stack" style="max-width: 560px;">
  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <form
    class="stack"
    method="post"
    enctype="multipart/form-data"
    action="<?= e(app_url('bahan/form.php?kelas_id=' . $kelas['id'])) ?>"
  >
    <?= Csrf::field() ?>
    <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">

    <div class="field">
      <label for="judul">Judul</label>
      <input id="judul" type="text" name="judul" maxlength="200" value="<?= old('judul') ?>" required>
      <?php if (!empty($errors['judul'])): ?><div class="error"><?= e($errors['judul']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="sumber">Sumber file</label>
      <select id="sumber" name="sumber" required>
        <option value="upload" <?= $sumber === 'upload' ? 'selected' : '' ?>>Unggah Manual</option>
        <option value="gdrive" <?= $sumber === 'gdrive' ? 'selected' : '' ?>>Google Drive</option>
        <option value="youtube" <?= $sumber === 'youtube' ? 'selected' : '' ?>>YouTube</option>
      </select>
      <?php if (!empty($errors['sumber'])): ?><div class="error"><?= e($errors['sumber']) ?></div><?php endif; ?>
    </div>

    <div class="field" id="block-upload"<?= $isUpload ? '' : ' hidden' ?>>
      <label for="file">File (PDF, Word, PPT)</label>
      <input
        id="file"
        type="file"
        name="file"
        accept=".pdf,.doc,.docx,.ppt,.pptx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
        <?= $isUpload ? 'required' : '' ?>
      >
      <div class="hint">Maksimal 20 MB</div>
      <?php if (!empty($errors['file'])): ?><div class="error"><?= e($errors['file']) ?></div><?php endif; ?>
    </div>

    <div class="field" id="block-link"<?= $isUpload ? ' hidden' : '' ?>>
      <label for="original_url">Link</label>
      <input
        id="original_url"
        type="url"
        name="original_url"
        value="<?= old('original_url') ?>"
        placeholder="Tempel link Google Drive atau YouTube"
        <?= $isUpload ? '' : 'required' ?>
      >
      <div class="hint" id="link-hint">
        <?= $sumber === 'youtube'
          ? 'Contoh: https://www.youtube.com/watch?v=… atau https://youtu.be/…'
          : 'Contoh: https://drive.google.com/file/d/…/view' ?>
      </div>
      <?php if (!empty($errors['original_url'])): ?><div class="error"><?= e($errors['original_url']) ?></div><?php endif; ?>
    </div>

    <div class="actions">
      <button class="btn btn-primary" type="submit">Simpan</button>
      <a class="btn btn-ghost" href="<?= e(app_url('bahan/index.php?kelas_id=' . $kelas['id'])) ?>">Batal</a>
    </div>
  </form>
</section>

<script>
(function () {
  var sumber = document.getElementById('sumber');
  var upload = document.getElementById('block-upload');
  var link = document.getElementById('block-link');
  var file = document.getElementById('file');
  var url = document.getElementById('original_url');
  var hint = document.getElementById('link-hint');

  function sync() {
    var isUpload = sumber.value === 'upload';
    upload.hidden = !isUpload;
    link.hidden = isUpload;
    file.required = isUpload;
    url.required = !isUpload;
    if (!isUpload && hint) {
      hint.textContent = sumber.value === 'youtube'
        ? 'Contoh: https://www.youtube.com/watch?v=… atau https://youtu.be/…'
        : 'Contoh: https://drive.google.com/file/d/…/view';
    }
  }

  sumber.addEventListener('change', sync);
  sync();
})();
</script>

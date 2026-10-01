<?php
/** @var array $kelas */
/** @var array $daftarKelas */
/** @var array|null $bahan */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
$daftarKelas = $daftarKelas ?? [];
$bahan = $bahan ?? null;
$isEdit = $bahan !== null;
$selectedKelasId = (int) (
    $_SERVER['REQUEST_METHOD'] === 'POST'
        ? ($_POST['kelas_id'] ?? $kelas['id'] ?? 0)
        : ($kelas['id'] ?? 0)
);
$sumber = (string) ($_SESSION['_old']['sumber'] ?? '');
if ($sumber === '' && $bahan) {
    $sumber = (string) ($bahan['sumber'] ?? 'upload');
}
if ($sumber === '') {
    $sumber = 'upload';
}
$isUpload = $sumber === 'upload';
$judulRaw = (string) ($_SESSION['_old']['judul'] ?? ($bahan['judul'] ?? ''));
$formAction = $isEdit
    ? app_url('bahan/form.php?id=' . (int) $bahan['id'])
    : app_url('bahan/form.php');
$currentFileLabel = '';
if ($isEdit && ($bahan['sumber'] ?? '') === 'upload' && !empty($bahan['file_path'])) {
    $currentFileLabel = strtoupper((string) ($bahan['file_ext'] ?? 'FILE')) . ' · file tersimpan';
}
?>
<section class="page-head bahan-form-head">
  <div>
    <p class="muted">
      <?= $isEdit
        ? 'Perbarui judul, kelas, atau sumber materi.'
        : 'Unggah file atau tautkan Drive, OneDrive, Canva, YouTube, dan Vimeo untuk kelas Anda.' ?>
    </p>
  </div>
  <a class="btn btn-ghost" href="<?= e(app_url('bahan/index.php')) ?>">← Galeri</a>
</section>

<section class="card bahan-form-card">
  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <form
    class="bahan-form"
    id="bahan-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= e($formAction) ?>"
    novalidate
  >
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int) $bahan['id'] ?>">
    <?php endif; ?>

    <div class="bahan-form-grid">
      <div class="field">
        <label for="kelas_id">Kelas tujuan</label>
        <select id="kelas_id" name="kelas_id" required>
          <?php foreach ($daftarKelas as $row): ?>
            <option value="<?= (int) $row['id'] ?>" <?= $selectedKelasId === (int) $row['id'] ? 'selected' : '' ?>>
              <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['kelas_id'])): ?><div class="error"><?= e($errors['kelas_id']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <div class="bahan-form-label-row">
          <label for="judul">Judul materi</label>
          <span class="bahan-char-count" id="judul-count" aria-live="polite"><?= mb_strlen($judulRaw) ?>/200</span>
        </div>
        <input
          id="judul"
          type="text"
          name="judul"
          maxlength="200"
          value="<?= old('judul', (string) ($bahan['judul'] ?? '')) ?>"
          placeholder="Contoh: Pengantar Aljabar Linear"
          required
          autocomplete="off"
        >
        <?php if (!empty($errors['judul'])): ?><div class="error"><?= e($errors['judul']) ?></div><?php endif; ?>
      </div>
    </div>

    <fieldset class="bahan-source-fieldset">
      <legend>Sumber materi</legend>
      <input type="hidden" id="sumber" name="sumber" value="<?= e($sumber) ?>">

      <div class="bahan-source-grid" role="radiogroup" aria-label="Sumber materi">
        <button
          type="button"
          class="bahan-source-card<?= $sumber === 'upload' ? ' is-active' : '' ?>"
          data-sumber="upload"
          aria-pressed="<?= $sumber === 'upload' ? 'true' : 'false' ?>"
        >
          <span class="bahan-source-icon bahan-type-upload" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 16V6m0 0 3.5 3.5M12 6 8.5 9.5M5 18h14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <span class="bahan-source-copy">
            <strong>Unggah file</strong>
            <span>PDF, Word, PPT, gambar</span>
          </span>
        </button>

        <button
          type="button"
          class="bahan-source-card<?= $sumber === 'gdrive' ? ' is-active' : '' ?>"
          data-sumber="gdrive"
          aria-pressed="<?= $sumber === 'gdrive' ? 'true' : 'false' ?>"
        >
          <span class="bahan-source-icon bahan-type-gdrive" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 4 4.5 17h15L12 4Zm-5.2 13L12 7.8 17.2 17H6.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          </span>
          <span class="bahan-source-copy">
            <strong>Google Drive</strong>
            <span>Tempel tautan berbagi</span>
          </span>
        </button>

        <button
          type="button"
          class="bahan-source-card<?= $sumber === 'youtube' ? ' is-active' : '' ?>"
          data-sumber="youtube"
          aria-pressed="<?= $sumber === 'youtube' ? 'true' : 'false' ?>"
        >
          <span class="bahan-source-icon bahan-type-youtube" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M8 7.5v9l8-4.5-8-4.5Z" fill="currentColor"/><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
          </span>
          <span class="bahan-source-copy">
            <strong>YouTube</strong>
            <span>Video pembelajaran</span>
          </span>
        </button>

        <button
          type="button"
          class="bahan-source-card<?= $sumber === 'onedrive' ? ' is-active' : '' ?>"
          data-sumber="onedrive"
          aria-pressed="<?= $sumber === 'onedrive' ? 'true' : 'false' ?>"
        >
          <span class="bahan-source-icon bahan-type-onedrive" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M7.5 16.5A4.5 4.5 0 0 1 9 8a5.5 5.5 0 0 1 10.4 1.7A3.8 3.8 0 0 1 18.5 17.5H8.2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 16.8a3.2 3.2 0 0 1 .4-6.4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          </span>
          <span class="bahan-source-copy">
            <strong>OneDrive</strong>
            <span>Tempel tautan berbagi</span>
          </span>
        </button>

        <button
          type="button"
          class="bahan-source-card<?= $sumber === 'canva' ? ' is-active' : '' ?>"
          data-sumber="canva"
          aria-pressed="<?= $sumber === 'canva' ? 'true' : 'false' ?>"
        >
          <span class="bahan-source-icon bahan-type-canva" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M7 5.5A2.5 2.5 0 0 1 9.5 3h5A2.5 2.5 0 0 1 17 5.5v13A2.5 2.5 0 0 1 14.5 21h-5A2.5 2.5 0 0 1 7 18.5v-13Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 8.5h4M10 12h4M10 15.5h2.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          </span>
          <span class="bahan-source-copy">
            <strong>Canva</strong>
            <span>Desain / presentasi</span>
          </span>
        </button>

        <button
          type="button"
          class="bahan-source-card<?= $sumber === 'vimeo' ? ' is-active' : '' ?>"
          data-sumber="vimeo"
          aria-pressed="<?= $sumber === 'vimeo' ? 'true' : 'false' ?>"
        >
          <span class="bahan-source-icon bahan-type-vimeo" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M8.2 8.8c1.7-2.7 3.4-4 5.2-4 1.1 0 1.9.5 2.3 1.5.5 1.3.2 3.2-.9 5.7-1.2 2.7-2.4 4-3.6 4-.5 0-1.1-.6-1.8-1.8L8.2 8.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M5.5 10.5c.7-1.2 1.5-1.8 2.3-1.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          </span>
          <span class="bahan-source-copy">
            <strong>Vimeo</strong>
            <span>Video pembelajaran</span>
          </span>
        </button>
      </div>
      <?php if (!empty($errors['sumber'])): ?><div class="error"><?= e($errors['sumber']) ?></div><?php endif; ?>
    </fieldset>

    <div class="bahan-source-panels">
      <div
        class="field bahan-panel<?= $isUpload ? ' is-visible' : '' ?>"
        id="block-upload"
        <?= $isUpload ? '' : 'hidden' ?>
      >
        <?php if ($currentFileLabel !== ''): ?>
          <p class="hint" id="current-file-hint">File saat ini: <?= e($currentFileLabel) ?>. Kosongkan jika tidak diganti.</p>
        <?php endif; ?>
        <label for="file" class="bahan-dropzone<?= !empty($errors['file']) ? ' has-error' : '' ?>" id="dropzone">
          <input
            id="file"
            type="file"
            name="file"
            accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/jpeg,image/png,image/gif,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
            <?= $isUpload && !$isEdit ? 'required' : '' ?>
          >
          <div class="bahan-dropzone-idle" id="dropzone-idle">
            <span class="bahan-dropzone-glyph" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 16V6m0 0 3.5 3.5M12 6 8.5 9.5M5 18h14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <strong><?= $isEdit ? 'Ganti file (opsional)' : 'Seret file ke sini' ?></strong>
            <span>atau klik untuk memilih · maks. 20 MB</span>
            <span class="bahan-dropzone-types">PDF · DOC · DOCX · PPT · PPTX · JPG · PNG · GIF · WEBP</span>
          </div>
          <div class="bahan-dropzone-file" id="dropzone-file" hidden>
            <span class="bahan-file-badge" id="file-ext">FILE</span>
            <div class="bahan-file-meta">
              <strong id="file-name">—</strong>
              <span id="file-size">—</span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" id="file-clear">Ganti</button>
          </div>
        </label>
        <div class="hint">Video tidak bisa diunggah — gunakan sumber YouTube atau Vimeo.</div>
        <?php if (!empty($errors['file'])): ?><div class="error"><?= e($errors['file']) ?></div><?php endif; ?>
      </div>

      <div
        class="field bahan-panel<?= !$isUpload ? ' is-visible' : '' ?>"
        id="block-link"
        <?= $isUpload ? 'hidden' : '' ?>
      >
        <label for="original_url" id="link-label"><?php
          echo match ($sumber) {
              'youtube' => 'Tautan YouTube',
              'onedrive' => 'Tautan OneDrive',
              'canva' => 'Tautan Canva',
              'vimeo' => 'Tautan Vimeo',
              default => 'Tautan Google Drive',
          };
        ?></label>
        <div class="bahan-url-wrap" data-sumber="<?= e($sumber) ?>" id="url-wrap">
          <span class="bahan-url-icon" id="url-icon" aria-hidden="true">
            <?php if ($sumber === 'youtube'): ?>
              <svg viewBox="0 0 24 24"><path d="M8 7.5v9l8-4.5-8-4.5Z" fill="currentColor"/><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
            <?php elseif ($sumber === 'onedrive'): ?>
              <svg viewBox="0 0 24 24"><path d="M7.5 16.5A4.5 4.5 0 0 1 9 8a5.5 5.5 0 0 1 10.4 1.7A3.8 3.8 0 0 1 18.5 17.5H8.2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 16.8a3.2 3.2 0 0 1 .4-6.4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <?php elseif ($sumber === 'canva'): ?>
              <svg viewBox="0 0 24 24"><path d="M7 5.5A2.5 2.5 0 0 1 9.5 3h5A2.5 2.5 0 0 1 17 5.5v13A2.5 2.5 0 0 1 14.5 21h-5A2.5 2.5 0 0 1 7 18.5v-13Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 8.5h4M10 12h4M10 15.5h2.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <?php elseif ($sumber === 'vimeo'): ?>
              <svg viewBox="0 0 24 24"><path d="M8.2 8.8c1.7-2.7 3.4-4 5.2-4 1.1 0 1.9.5 2.3 1.5.5 1.3.2 3.2-.9 5.7-1.2 2.7-2.4 4-3.6 4-.5 0-1.1-.6-1.8-1.8L8.2 8.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M5.5 10.5c.7-1.2 1.5-1.8 2.3-1.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24"><path d="M12 4 4.5 17h15L12 4Zm-5.2 13L12 7.8 17.2 17H6.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            <?php endif; ?>
          </span>
          <input
            id="original_url"
            type="url"
            name="original_url"
            value="<?= old('original_url', (string) ($bahan['original_url'] ?? '')) ?>"
            placeholder="<?php
              echo match ($sumber) {
                  'youtube' => 'https://www.youtube.com/watch?v=…',
                  'onedrive' => 'https://1drv.ms/… atau https://onedrive.live.com/…',
                  'canva' => 'https://www.canva.com/design/…/view',
                  'vimeo' => 'https://vimeo.com/…',
                  default => 'https://drive.google.com/file/d/…/view',
              };
            ?>"
            <?= $isUpload ? '' : 'required' ?>
          >
        </div>
        <div class="hint" id="link-hint">
          <?php
            echo match ($sumber) {
                'youtube' => 'Contoh: https://www.youtube.com/watch?v=… atau https://youtu.be/…',
                'onedrive' => 'Gunakan tautan berbagi publik (1drv.ms, onedrive.live.com, atau SharePoint).',
                'canva' => 'Bagikan desain sebagai “Siapa saja dengan tautan”, lalu tempel tautan view/watch.',
                'vimeo' => 'Contoh: https://vimeo.com/123456789 (video harus bisa ditonton publik).',
                default => 'Pastikan tautan bisa dibuka publik atau dibagikan ke siapa saja yang punya link.',
            };
          ?>
        </div>
        <?php if (!empty($errors['original_url'])): ?><div class="error"><?= e($errors['original_url']) ?></div><?php endif; ?>

        <div class="bahan-link-preview" id="link-preview" hidden>
          <span class="bahan-link-preview-dot" aria-hidden="true"></span>
          <span id="link-preview-text">Tautan siap digunakan</span>
        </div>
      </div>
    </div>

    <div class="bahan-form-actions">
      <button class="btn btn-primary" type="submit" id="submit-btn">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= $isEdit ? 'Simpan perubahan' : 'Simpan bahan' ?>
      </button>
      <a class="btn btn-ghost" href="<?= e(app_url('bahan/index.php')) ?>">Batal</a>
    </div>
  </form>
</section>

<script>
(function () {
  var sumberInput = document.getElementById('sumber');
  var cards = document.querySelectorAll('.bahan-source-card');
  var upload = document.getElementById('block-upload');
  var link = document.getElementById('block-link');
  var file = document.getElementById('file');
  var url = document.getElementById('original_url');
  var hint = document.getElementById('link-hint');
  var linkLabel = document.getElementById('link-label');
  var urlWrap = document.getElementById('url-wrap');
  var urlIcon = document.getElementById('url-icon');
  var dropzone = document.getElementById('dropzone');
  var dropIdle = document.getElementById('dropzone-idle');
  var dropFile = document.getElementById('dropzone-file');
  var fileName = document.getElementById('file-name');
  var fileSize = document.getElementById('file-size');
  var fileExt = document.getElementById('file-ext');
  var fileClear = document.getElementById('file-clear');
  var judul = document.getElementById('judul');
  var judulCount = document.getElementById('judul-count');
  var linkPreview = document.getElementById('link-preview');
  var linkPreviewText = document.getElementById('link-preview-text');
  var form = document.getElementById('bahan-form');
  var submitBtn = document.getElementById('submit-btn');
  var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
  var hadUpload = <?= ($isEdit && ($bahan['sumber'] ?? '') === 'upload') ? 'true' : 'false' ?>;

  var icons = {
    gdrive: '<svg viewBox="0 0 24 24"><path d="M12 4 4.5 17h15L12 4Zm-5.2 13L12 7.8 17.2 17H6.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
    youtube: '<svg viewBox="0 0 24 24"><path d="M8 7.5v9l8-4.5-8-4.5Z" fill="currentColor"/><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
    onedrive: '<svg viewBox="0 0 24 24"><path d="M7.5 16.5A4.5 4.5 0 0 1 9 8a5.5 5.5 0 0 1 10.4 1.7A3.8 3.8 0 0 1 18.5 17.5H8.2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 16.8a3.2 3.2 0 0 1 .4-6.4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    canva: '<svg viewBox="0 0 24 24"><path d="M7 5.5A2.5 2.5 0 0 1 9.5 3h5A2.5 2.5 0 0 1 17 5.5v13A2.5 2.5 0 0 1 14.5 21h-5A2.5 2.5 0 0 1 7 18.5v-13Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 8.5h4M10 12h4M10 15.5h2.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    vimeo: '<svg viewBox="0 0 24 24"><path d="M8.2 8.8c1.7-2.7 3.4-4 5.2-4 1.1 0 1.9.5 2.3 1.5.5 1.3.2 3.2-.9 5.7-1.2 2.7-2.4 4-3.6 4-.5 0-1.1-.6-1.8-1.8L8.2 8.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M5.5 10.5c.7-1.2 1.5-1.8 2.3-1.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>'
  };

  var linkMeta = {
    youtube: {
      label: 'Tautan YouTube',
      placeholder: 'https://www.youtube.com/watch?v=…',
      hint: 'Contoh: https://www.youtube.com/watch?v=… atau https://youtu.be/…',
      ok: 'Tautan YouTube terdeteksi',
      bad: 'Periksa format tautan YouTube',
      pattern: /youtu\.be\/|youtube\.com\//i
    },
    gdrive: {
      label: 'Tautan Google Drive',
      placeholder: 'https://drive.google.com/file/d/…/view',
      hint: 'Pastikan tautan bisa dibuka publik atau dibagikan ke siapa saja yang punya link.',
      ok: 'Tautan Google Drive terdeteksi',
      bad: 'Periksa format tautan Drive',
      pattern: /drive\.google\.com|docs\.google\.com/i
    },
    onedrive: {
      label: 'Tautan OneDrive',
      placeholder: 'https://1drv.ms/… atau https://onedrive.live.com/…',
      hint: 'Gunakan tautan berbagi publik (1drv.ms, onedrive.live.com, atau SharePoint).',
      ok: 'Tautan OneDrive terdeteksi',
      bad: 'Periksa format tautan OneDrive',
      pattern: /1drv\.ms|onedrive\.live\.com|onedrive\.com|sharepoint\.com/i
    },
    canva: {
      label: 'Tautan Canva',
      placeholder: 'https://www.canva.com/design/…/view',
      hint: 'Bagikan desain sebagai “Siapa saja dengan tautan”, lalu tempel tautan view/watch.',
      ok: 'Tautan Canva terdeteksi',
      bad: 'Periksa format tautan Canva',
      pattern: /canva\.com\/design\//i
    },
    vimeo: {
      label: 'Tautan Vimeo',
      placeholder: 'https://vimeo.com/…',
      hint: 'Contoh: https://vimeo.com/123456789 (video harus bisa ditonton publik).',
      ok: 'Tautan Vimeo terdeteksi',
      bad: 'Periksa format tautan Vimeo',
      pattern: /vimeo\.com\//i
    }
  };

  function formatSize(bytes) {
    if (!bytes && bytes !== 0) return '—';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  function showPanel(panel, visible) {
    if (!panel) return;
    panel.hidden = !visible;
    panel.classList.toggle('is-visible', visible);
  }

  function setSumber(value) {
    sumberInput.value = value;
    var isUpload = value === 'upload';

    cards.forEach(function (card) {
      var active = card.getAttribute('data-sumber') === value;
      card.classList.toggle('is-active', active);
      card.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    showPanel(upload, isUpload);
    showPanel(link, !isUpload);
    file.required = isUpload && !(isEdit && hadUpload);
    url.required = !isUpload;

    if (!isUpload) {
      var meta = linkMeta[value] || linkMeta.gdrive;
      if (hint) hint.textContent = meta.hint;
      if (linkLabel) linkLabel.textContent = meta.label;
      if (url) url.placeholder = meta.placeholder;
      if (urlWrap) urlWrap.setAttribute('data-sumber', value);
      if (urlIcon) urlIcon.innerHTML = icons[value] || icons.gdrive;
      updateLinkPreview();
    }
  }

  function updateFilePreview() {
    var chosen = file.files && file.files[0];
    if (!chosen) {
      dropIdle.hidden = false;
      dropFile.hidden = true;
      dropzone.classList.remove('has-file');
      return;
    }
    var name = chosen.name || 'file';
    var ext = (name.split('.').pop() || 'FILE').toUpperCase();
    fileName.textContent = name;
    fileSize.textContent = formatSize(chosen.size);
    fileExt.textContent = ext.slice(0, 5);
    dropIdle.hidden = true;
    dropFile.hidden = false;
    dropzone.classList.add('has-file');
    dropzone.classList.remove('has-error', 'is-dragover');
  }

  function clearFile(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    file.value = '';
    updateFilePreview();
    file.click();
  }

  function updateJudulCount() {
    if (!judul || !judulCount) return;
    judulCount.textContent = judul.value.length + '/200';
  }

  function updateLinkPreview() {
    if (!linkPreview || !url) return;
    var value = (url.value || '').trim();
    if (!value) {
      linkPreview.hidden = true;
      return;
    }
    var sumber = sumberInput.value;
    var meta = linkMeta[sumber] || linkMeta.gdrive;
    var ok = meta.pattern.test(value);
    linkPreviewText.textContent = ok ? meta.ok : meta.bad;
    linkPreview.hidden = false;
    linkPreview.classList.toggle('is-ok', ok);
    linkPreview.classList.toggle('is-warn', !ok);
  }

  cards.forEach(function (card) {
    card.addEventListener('click', function () {
      setSumber(card.getAttribute('data-sumber'));
    });
  });

  file.addEventListener('change', updateFilePreview);
  if (fileClear) fileClear.addEventListener('click', clearFile);

  ['dragenter', 'dragover'].forEach(function (evt) {
    dropzone.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (sumberInput.value !== 'upload') return;
      dropzone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach(function (evt) {
    dropzone.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.remove('is-dragover');
    });
  });

  dropzone.addEventListener('drop', function (e) {
    if (sumberInput.value !== 'upload') return;
    var files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) return;
    try {
      var dt = new DataTransfer();
      dt.items.add(files[0]);
      file.files = dt.files;
    } catch (err) {
      /* Safari lama: biarkan user klik pilih file */
    }
    updateFilePreview();
  });

  if (judul) {
    judul.addEventListener('input', updateJudulCount);
    updateJudulCount();
  }

  if (url) {
    url.addEventListener('input', updateLinkPreview);
    url.addEventListener('blur', updateLinkPreview);
  }

  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.classList.add('is-loading');
      submitBtn.disabled = true;
    });
  }

  setSumber(sumberInput.value || 'upload');
  updateFilePreview();
})();
</script>

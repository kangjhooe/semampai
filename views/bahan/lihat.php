<?php
/** @var array $bahan */
/** @var string $embed */
$error = flash('error');
$sumberLabel = BahanAjar::labelSumber((string) $bahan['sumber']);
$isImage = BahanAjar::isImage($bahan);
$isDokumen = !$isImage && in_array((string) ($bahan['sumber'] ?? ''), ['upload', 'gdrive', 'onedrive', 'canva'], true);
$ext = strtolower((string) ($bahan['file_ext'] ?? ''));
?>
<section class="page-head">
  <div>
    <p class="muted">
      <?= e($sumberLabel) ?> ·
      Kelas <?= e((string) $bahan['kelas_nama']) ?> ·
      <a href="<?= e(app_url('bahan/index.php')) ?>">← Galeri bahan</a>
    </p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('bahan/form.php?id=' . (int) $bahan['id'])) ?>">Edit</a>
  </div>
</section>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($embed === ''): ?>
  <section class="card">
    <p class="muted">Materi tidak dapat ditampilkan. Link embed kosong atau tidak valid.</p>
  </section>
<?php else: ?>
  <section class="card bahan-viewer<?= $isDokumen ? ' is-dokumen' : '' ?><?= $isImage ? ' is-image' : '' ?>" id="bahan-viewer" style="padding: 0; overflow: hidden;">
    <?php if ($isDokumen || $isImage): ?>
      <div class="bahan-viewer-toolbar">
        <button type="button" class="btn btn-ghost btn-sm" id="bahan-fullscreen-btn" aria-pressed="false">
          <svg class="bahan-fs-icon-enter" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 4H4v4M16 4h4v4M8 20H4v-4M16 20h4v-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg class="bahan-fs-icon-exit" viewBox="0 0 24 24" aria-hidden="true" hidden>
            <path d="M9 9H5V5M15 9h4V5M9 15H5v4M15 15h4v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="bahan-fs-label">Layar penuh</span>
        </button>
      </div>
    <?php endif; ?>

    <?php if ($isImage): ?>
      <div class="bahan-viewer-image-wrap">
        <img
          class="bahan-viewer-image"
          src="<?= e($embed) ?>"
          alt="<?= e($bahan['judul']) ?>"
          loading="lazy"
        >
      </div>
    <?php else: ?>
      <iframe
        id="bahan-embed"
        src="<?= e($embed) ?>"
        title="<?= e($bahan['judul']) ?>"
        class="bahan-viewer-frame"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
        allowfullscreen
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
      ></iframe>
    <?php endif; ?>
  </section>
  <?php if (($bahan['sumber'] ?? '') === 'upload' && in_array($ext, ['doc', 'docx', 'ppt', 'pptx'], true)): ?>
    <p class="muted" style="margin-top: 0.75rem;">
      File Office memakai Google Docs Viewer. Pastikan file dapat diakses secara publik (bukan localhost).
      <a href="<?= e(BahanAjar::authorizedFileUrl($bahan)) ?>" target="_blank" rel="noopener">Unduh file</a>
    </p>
  <?php elseif (($bahan['sumber'] ?? '') === 'upload' && !empty($bahan['file_path'])): ?>
    <p class="muted" style="margin-top: 0.75rem;">
      <a href="<?= e(BahanAjar::authorizedFileUrl($bahan)) ?>" target="_blank" rel="noopener">Buka / unduh file</a>
    </p>
  <?php endif; ?>
  <?php if ($isDokumen || $isImage): ?>
    <script>
    (function () {
      var viewer = document.getElementById('bahan-viewer');
      var btn = document.getElementById('bahan-fullscreen-btn');
      if (!viewer || !btn || !document.fullscreenEnabled) {
        if (btn) btn.hidden = true;
        return;
      }

      var label = btn.querySelector('.bahan-fs-label');
      var iconEnter = btn.querySelector('.bahan-fs-icon-enter');
      var iconExit = btn.querySelector('.bahan-fs-icon-exit');

      function isFs() {
        return document.fullscreenElement === viewer;
      }

      function sync() {
        var active = isFs();
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        btn.title = active ? 'Keluar layar penuh' : 'Layar penuh';
        if (label) label.textContent = active ? 'Keluar' : 'Layar penuh';
        if (iconEnter) iconEnter.hidden = active;
        if (iconExit) iconExit.hidden = !active;
      }

      btn.addEventListener('click', function () {
        if (isFs()) {
          document.exitFullscreen();
        } else {
          viewer.requestFullscreen().catch(function () {});
        }
      });

      document.addEventListener('fullscreenchange', sync);
      sync();
    })();
    </script>
  <?php endif; ?>
<?php endif; ?>

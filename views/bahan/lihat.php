<?php
/** @var array $bahan */
/** @var string $embed */
$error = flash('error');
$sumberLabel = BahanAjar::labelSumber((string) $bahan['sumber']);
?>
<section class="page-head">
  <div>
    <h1><?= e($bahan['judul']) ?></h1>
    <p class="muted">
      <?= e($sumberLabel) ?> ·
      Kelas <?= e((string) $bahan['kelas_nama']) ?> ·
      <a href="<?= e(app_url('bahan/index.php?kelas_id=' . $bahan['kelas_id'])) ?>">← Daftar bahan</a>
    </p>
  </div>
</section>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($embed === ''): ?>
  <section class="card">
    <p class="muted">Materi tidak dapat ditampilkan. Link embed kosong atau tidak valid.</p>
  </section>
<?php else: ?>
  <section class="card" style="padding: 0; overflow: hidden;">
    <iframe
      src="<?= e($embed) ?>"
      title="<?= e($bahan['judul']) ?>"
      style="width:100%;min-height:70vh;border:0;display:block;"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
      allowfullscreen
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
    ></iframe>
  </section>
  <?php if (($bahan['sumber'] ?? '') === 'upload' && in_array(strtolower((string) ($bahan['file_ext'] ?? '')), ['doc', 'docx', 'ppt', 'pptx'], true)): ?>
    <p class="muted" style="margin-top: 0.75rem;">
      File Office memakai Google Docs Viewer. Pastikan file dapat diakses secara publik (bukan localhost).
      <a href="<?= e(app_url((string) $bahan['file_path'])) ?>" target="_blank" rel="noopener">Unduh file</a>
    </p>
  <?php elseif (($bahan['sumber'] ?? '') === 'upload' && !empty($bahan['file_path'])): ?>
    <p class="muted" style="margin-top: 0.75rem;">
      <a href="<?= e(app_url((string) $bahan['file_path'])) ?>" target="_blank" rel="noopener">Buka / unduh file</a>
    </p>
  <?php endif; ?>
<?php endif; ?>

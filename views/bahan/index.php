<?php
/** @var array $daftarKelas */
/** @var array $daftar */
/** @var int $filterKelasId */
/** @var string $filterSumber */
/** @var string $filterQ */
$success = flash('success');
$error = flash('error');
$filterKelasId = (int) ($filterKelasId ?? 0);
$filterSumber = (string) ($filterSumber ?? '');
$filterQ = (string) ($filterQ ?? '');
$hasFilter = $filterKelasId > 0 || $filterSumber !== '' || $filterQ !== '';

$sumberMeta = [
    'upload' => ['label' => 'File', 'class' => 'bahan-type-upload'],
    'gdrive' => ['label' => 'Drive', 'class' => 'bahan-type-gdrive'],
    'youtube' => ['label' => 'YouTube', 'class' => 'bahan-type-youtube'],
    'onedrive' => ['label' => 'OneDrive', 'class' => 'bahan-type-onedrive'],
    'canva' => ['label' => 'Canva', 'class' => 'bahan-type-canva'],
    'vimeo' => ['label' => 'Vimeo', 'class' => 'bahan-type-vimeo'],
];
?>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card">
    <p class="muted">Belum ada kelas. <a href="<?= e(app_url('kelas/index.php')) ?>">Buat kelas dulu</a> sebelum menambah bahan ajar.</p>
  </section>
<?php else: ?>
  <form class="card bahan-filter" method="get" action="<?= e(app_url('bahan/index.php')) ?>">
    <select id="kelas_id" name="kelas_id" aria-label="Kelas" onchange="this.form.submit()">
      <option value="0">Semua kelas</option>
      <?php foreach ($daftarKelas as $row): ?>
        <option value="<?= (int) $row['id'] ?>" <?= $filterKelasId === (int) $row['id'] ? 'selected' : '' ?>>
          <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select id="sumber" name="sumber" aria-label="Jenis" onchange="this.form.submit()">
      <option value="">Semua jenis</option>
      <option value="upload" <?= $filterSumber === 'upload' ? 'selected' : '' ?>>Unggah Manual</option>
      <option value="gdrive" <?= $filterSumber === 'gdrive' ? 'selected' : '' ?>>Google Drive</option>
      <option value="onedrive" <?= $filterSumber === 'onedrive' ? 'selected' : '' ?>>OneDrive</option>
      <option value="canva" <?= $filterSumber === 'canva' ? 'selected' : '' ?>>Canva</option>
      <option value="youtube" <?= $filterSumber === 'youtube' ? 'selected' : '' ?>>YouTube</option>
      <option value="vimeo" <?= $filterSumber === 'vimeo' ? 'selected' : '' ?>>Vimeo</option>
    </select>
    <input id="q" type="search" name="q" value="<?= e($filterQ) ?>" placeholder="Cari judul…" aria-label="Cari judul">
    <button class="btn btn-primary" type="submit">Cari</button>
    <?php if ($hasFilter): ?>
      <a class="btn btn-ghost" href="<?= e(app_url('bahan/index.php')) ?>">Reset</a>
    <?php endif; ?>
    <a class="btn btn-primary bahan-filter-add" href="<?= e(app_url('bahan/form.php' . ($filterKelasId ? '?kelas_id=' . $filterKelasId : ''))) ?>">Tambah</a>
  </form>

  <?php if ($daftar === []): ?>
    <section class="card">
      <p class="muted">
        <?= $hasFilter
          ? 'Tidak ada bahan yang cocok dengan filter.'
          : 'Belum ada bahan ajar. Mulai dengan menambah materi.' ?>
      </p>
    </section>
  <?php else: ?>
    <p class="muted bahan-count"><?= count($daftar) ?> bahan</p>
    <div class="bahan-gallery">
      <?php foreach ($daftar as $row): ?>
        <?php
        $sumber = (string) $row['sumber'];
        $meta = $sumberMeta[$sumber] ?? ['label' => $sumber, 'class' => 'bahan-type-upload'];
        $ext = strtoupper((string) ($row['file_ext'] ?? ''));
        $thumb = BahanAjar::thumbnailUrl($row);
        $fallbackLabel = $sumber === 'upload' && $ext !== '' ? $ext : $meta['label'];
        ?>
        <article class="bahan-card">
          <a class="bahan-card-main" href="<?= e(app_url('bahan/lihat.php?id=' . $row['id'])) ?>">
            <div class="bahan-thumb <?= e($meta['class']) ?><?= $thumb ? ' has-img' : '' ?>">
              <?php if ($thumb): ?>
                <img
                  src="<?= e($thumb) ?>"
                  alt=""
                  loading="lazy"
                  referrerpolicy="no-referrer"
                  onerror="var p=this.parentElement; this.remove(); if(p) p.classList.remove('has-img');"
                >
              <?php endif; ?>
              <div class="bahan-thumb-fallback" aria-hidden="true">
                <?php if ($sumber === 'youtube'): ?>
                  <svg viewBox="0 0 24 24"><path d="M8 7.5v9l8-4.5-8-4.5Z" fill="currentColor"/><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                <?php elseif ($sumber === 'vimeo'): ?>
                  <svg viewBox="0 0 24 24"><path d="M8.2 8.8c1.7-2.7 3.4-4 5.2-4 1.1 0 1.9.5 2.3 1.5.5 1.3.2 3.2-.9 5.7-1.2 2.7-2.4 4-3.6 4-.5 0-1.1-.6-1.8-1.8L8.2 8.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M5.5 10.5c.7-1.2 1.5-1.8 2.3-1.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                <?php elseif ($sumber === 'gdrive'): ?>
                  <svg viewBox="0 0 24 24"><path d="M12 4 4.5 17h15L12 4Zm-5.2 13L12 7.8 17.2 17H6.8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                <?php elseif ($sumber === 'onedrive'): ?>
                  <svg viewBox="0 0 24 24"><path d="M7.5 16.5A4.5 4.5 0 0 1 9 8a5.5 5.5 0 0 1 10.4 1.7A3.8 3.8 0 0 1 18.5 17.5H8.2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 16.8a3.2 3.2 0 0 1 .4-6.4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                <?php elseif ($sumber === 'canva'): ?>
                  <svg viewBox="0 0 24 24"><path d="M7 5.5A2.5 2.5 0 0 1 9.5 3h5A2.5 2.5 0 0 1 17 5.5v13A2.5 2.5 0 0 1 14.5 21h-5A2.5 2.5 0 0 1 7 18.5v-13Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 8.5h4M10 12h4M10 15.5h2.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24"><path d="M7 4h7l4 4v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M14 4v4h4M9 12h6M9 15h6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                <?php endif; ?>
                <span><?= e($fallbackLabel) ?></span>
              </div>
              <?php if (in_array($sumber, ['youtube', 'vimeo'], true)): ?>
                <span class="bahan-play" aria-hidden="true">
                  <svg viewBox="0 0 24 24"><path d="M9 7.5v9l8-4.5-8-4.5Z" fill="currentColor"/></svg>
                </span>
              <?php endif; ?>
            </div>
            <div class="bahan-card-body">
              <h2><?= e($row['judul']) ?></h2>
              <p class="muted"><?= e($row['kelas_nama']) ?> · <?= e($row['tahun_ajaran']) ?></p>
              <div class="bahan-card-meta">
                <span class="badge <?= in_array($sumber, ['youtube', 'vimeo'], true) ? 'badge-warn' : 'badge-ok' ?>">
                  <?= e(BahanAjar::labelSumber($sumber)) ?>
                </span>
                <time datetime="<?= e($row['created_at']) ?>">
                  <?= e(date('d/m/Y', strtotime((string) $row['created_at']))) ?>
                </time>
              </div>
            </div>
          </a>
          <div class="bahan-card-actions">
            <a class="btn-icon primary" href="<?= e(app_url('bahan/lihat.php?id=' . $row['id'])) ?>" title="Buka" aria-label="Buka">
              <?= icon('eye') ?>
            </a>
            <a class="btn-icon" href="<?= e(app_url('bahan/form.php?id=' . $row['id'])) ?>" title="Edit" aria-label="Edit">
              <?= icon('edit') ?>
            </a>
            <form method="post" action="<?= e(app_url('bahan/hapus.php')) ?>" onsubmit="return confirm('Hapus bahan ajar ini?');">
              <?= Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="btn-icon danger" type="submit" title="Hapus" aria-label="Hapus">
                <?= icon('trash') ?>
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

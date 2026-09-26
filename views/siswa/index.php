<?php
/** @var array $kelas */
/** @var array $siswa */
/** @var list<string> $importErrors */
$success = flash('success');
$error = flash('error');
$importErrors = $importErrors ?? [];
?>
<section class="page-head">
  <div>
    <h1>Siswa · <?= e($kelas['nama']) ?></h1>
    <p class="muted">Tahun ajaran <?= e($kelas['tahun_ajaran']) ?> · <a href="<?= e(app_url('siswa.php')) ?>">← Pilih kelas</a></p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('siswa_import.php?kelas_id=' . $kelas['id'])) ?>">Import Excel</a>
    <a class="btn btn-primary" href="<?= e(app_url('siswa_form.php?kelas_id=' . $kelas['id'])) ?>">Tambah siswa</a>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($importErrors !== []): ?>
  <div class="alert error">
    <strong>Detail baris yang dilewati:</strong>
    <ul class="compact-list">
      <?php foreach ($importErrors as $msg): ?>
        <li><?= e($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<section class="card">
  <?php if ($siswa === []): ?>
    <p class="muted">Belum ada siswa. Tambah manual atau import dari Excel.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">No</th>
            <th>Nama</th>
            <th>NISN</th>
            <th>Tempat lahir</th>
            <th>Tanggal lahir</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($siswa as $i => $row): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><strong><?= e($row['nama']) ?></strong></td>
              <td><?= e($row['nisn']) ?></td>
              <td><?= e($row['tempat_lahir']) ?></td>
              <td><?= e(date('d/m/Y', strtotime($row['tanggal_lahir']))) ?></td>
              <td class="table-actions">
                <a class="btn-icon" href="<?= e(app_url('siswa_form.php?kelas_id=' . $kelas['id'] . '&id=' . $row['id'])) ?>" title="Edit" aria-label="Edit">
                  <?= icon('edit') ?>
                </a>
                <form method="post" action="<?= e(app_url('siswa_hapus.php')) ?>" onsubmit="return confirm('Hapus siswa ini?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">
                  <button class="btn-icon danger" type="submit" title="Hapus" aria-label="Hapus">
                    <?= icon('trash') ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php
/** @var array $kelas */
/** @var array $daftar */
$success = flash('success');
$error = flash('error');
?>
<section class="page-head">
  <div>
    <h1>Bahan ajar · <?= e($kelas['nama']) ?></h1>
    <p class="muted">Tahun ajaran <?= e($kelas['tahun_ajaran']) ?> · <a href="<?= e(app_url('bahan/index.php')) ?>">← Pilih kelas</a></p>
  </div>
  <div class="actions">
    <a class="btn btn-primary" href="<?= e(app_url('bahan/form.php?kelas_id=' . $kelas['id'])) ?>">Tambah bahan</a>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="card">
  <?php if ($daftar === []): ?>
    <p class="muted">Belum ada bahan ajar. Unggah file, tempel link Google Drive, atau YouTube.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">No</th>
            <th>Judul</th>
            <th>Sumber</th>
            <th>Ditambah</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $i => $row): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><strong><?= e($row['judul']) ?></strong></td>
              <td><?= e(BahanAjar::labelSumber((string) $row['sumber'])) ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime((string) $row['created_at']))) ?></td>
              <td class="table-actions">
                <a class="btn-icon primary" href="<?= e(app_url('bahan/lihat.php?id=' . $row['id'])) ?>" title="Buka" aria-label="Buka">
                  <?= icon('eye') ?>
                </a>
                <form method="post" action="<?= e(app_url('bahan/hapus.php')) ?>" onsubmit="return confirm('Hapus bahan ajar ini?');">
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

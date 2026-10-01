<?php
/** @var array $daftarKelas */
$success = flash('success');
$error = flash('error');
?>
<section class="page-head">
  <div>
    <h1>Pengelola siswa</h1>
    <p class="muted">Pilih kelas untuk melihat, menambah, atau mengimpor siswa.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="card">
  <?php if ($daftarKelas === []): ?>
    <p class="muted">
      Belum ada kelas.
      Buat kelas dulu di <a href="<?= e(app_url('kelas/index.php')) ?>">pengelola kelas</a>.
    </p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">No</th>
            <th>Kelas</th>
            <th>Tahun ajaran</th>
            <th>Jumlah siswa</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarKelas as $i => $row): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><strong><?= e($row['nama']) ?></strong></td>
              <td><?= e($row['tahun_ajaran']) ?></td>
              <td><?= (int) $row['jumlah_siswa'] ?></td>
              <td class="table-actions">
                <a class="btn-icon primary" href="<?= e(app_url('siswa/index.php?kelas_id=' . $row['id'])) ?>" title="Kelola siswa" aria-label="Kelola siswa">
                  <?= icon('users') ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

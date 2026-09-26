<?php
/** @var array $siswa */
/** @var array $histori */
/** @var int $totalLancar */
/** @var int $totalUlang */
$success = flash('success');
$error = flash('error');
?>
<section class="page-head">
  <div>
    <h1><?= e($siswa['nama']) ?></h1>
    <p class="muted">
      NISN <?= e($siswa['nisn']) ?> ·
      <?= e($siswa['kelas_nama']) ?> (<?= e($siswa['tahun_ajaran']) ?>) ·
      <a href="<?= e(app_url('hafalan_riwayat.php?kelas_id=' . $siswa['kelas_id'])) ?>">← Riwayat kelas</a>
    </p>
  </div>
  <div class="actions">
    <a class="btn btn-primary" href="<?= e(app_url('hafalan.php?kelas_id=' . $siswa['kelas_id'])) ?>">Setor lagi</a>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="stat-row">
  <div class="stat-card">
    <span class="muted">Total setor</span>
    <strong><?= count($histori) ?></strong>
  </div>
  <div class="stat-card">
    <span class="muted">Lancar</span>
    <strong><?= (int) $totalLancar ?></strong>
  </div>
  <div class="stat-card">
    <span class="muted">Ulang</span>
    <strong><?= (int) $totalUlang ?></strong>
  </div>
</section>

<section class="card">
  <h2 class="card-title">Semua setoran</h2>
  <?php if ($histori === []): ?>
    <p class="muted">Belum ada setoran untuk siswa ini.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">No</th>
            <th>Waktu</th>
            <th>Surat & ayat</th>
            <th>Status</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($histori as $i => $row): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
              <td>
                <?= e($row['surat_nama']) ?>
                <?= e(Hafalan::formatAyat((int) $row['ayat_awal'], (int) $row['ayat_akhir'])) ?>
              </td>
              <td>
                <span class="badge <?= $row['status'] === 'lancar' ? 'badge-ok' : 'badge-warn' ?>">
                  <?= $row['status'] === 'lancar' ? 'Lancar' : 'Ulang' ?>
                </span>
              </td>
              <td><?= $row['catatan'] !== null && $row['catatan'] !== '' ? e($row['catatan']) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

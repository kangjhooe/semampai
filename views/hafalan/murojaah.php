<?php
/** @var array $daftarKelas */
/** @var array|null $kelas */
/** @var list<array> $due */
/** @var int $interval */
$success = flash('success');
$error = flash('error');
?>
<section class="page-head">
  <div>
    <p class="muted">Surat yang sudah tuntas dan perlu diulang (belum diulang ≥ <?= (int) $interval ?> hari).</p>
  </div>
  <div class="actions">
    <?php if ($kelas): ?>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan/target.php?kelas_id=' . $kelas['id'])) ?>">Atur target</a>
      <a class="btn btn-primary" href="<?= e(app_url('hafalan/index.php?kelas_id=' . $kelas['id'])) ?>">Setoran baru</a>
    <?php endif; ?>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card"><p class="muted">Belum ada kelas.</p></section>
<?php else: ?>
  <form class="card filter-bar" method="get" action="<?= e(app_url('hafalan/murojaah.php')) ?>">
    <div class="grid-2">
      <div class="field">
        <label for="kelas_id">Kelas</label>
        <select id="kelas_id" name="kelas_id" onchange="this.form.submit()">
          <?php foreach ($daftarKelas as $row): ?>
            <option value="<?= (int) $row['id'] ?>" <?= $kelas && (int) $kelas['id'] === (int) $row['id'] ? 'selected' : '' ?>>
              <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="hari">Interval (hari)</label>
        <select id="hari" name="hari" onchange="this.form.submit()">
          <?php foreach ([3, 7, 14, 21, 30] as $d): ?>
            <option value="<?= $d ?>" <?= (int) $interval === $d ? 'selected' : '' ?>><?= $d ?> hari</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </form>

  <?php if (!$kelas): ?>
    <section class="card"><p class="muted">Pilih kelas.</p></section>
  <?php elseif ($due === []): ?>
    <section class="card stack">
      <h2 class="card-title">Antrian kosong</h2>
      <p class="muted">
        Belum ada surat tuntas yang lewat interval, atau kelas belum punya target surat.
        Atur target dulu, lalu setor sampai tuntas — setelah itu muroja'ah muncul di sini.
      </p>
      <div class="actions">
        <a class="btn btn-ghost" href="<?= e(app_url('hafalan/target.php?kelas_id=' . $kelas['id'])) ?>">Atur target</a>
        <a class="btn btn-primary" href="<?= e(app_url('hafalan/rekap.php?kelas_id=' . $kelas['id'])) ?>">Lihat rekap</a>
      </div>
    </section>
  <?php else: ?>
    <section class="card stack">
      <div class="dash-section-head compact">
        <h2 class="card-title"><?= count($due) ?> perlu diulang</h2>
      </div>
      <ul class="murojaah-list">
        <?php foreach ($due as $row): ?>
          <li>
            <div class="murojaah-main">
              <strong><?= e($row['siswa_nama']) ?></strong>
              <span class="muted">
                <?= e($row['surat_nama']) ?>
                <?php if ((int) $row['jumlah_ayat'] > 0): ?>
                  · <?= (int) $row['jumlah_ayat'] ?> ayat
                <?php endif; ?>
              </span>
            </div>
            <div class="murojaah-meta">
              <span class="badge badge-warn"><?= (int) $row['hari_lalu'] ?> hari lalu</span>
              <time datetime="<?= e($row['terakhir']) ?>"><?= e(date('d/m/Y', strtotime((string) $row['terakhir']))) ?></time>
              <a class="btn btn-primary btn-sm" href="<?= e($row['setor_url']) ?>">Simak</a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
<?php endif; ?>

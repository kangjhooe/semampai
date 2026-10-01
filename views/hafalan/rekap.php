<?php
/** @var array $daftarKelas */
/** @var array|null $kelas */
/** @var array|null $matrix */
/** @var string $viewMode */
$success = flash('success');
$error = flash('error');
$viewMode = $viewMode ?? 'siswa';
$qs = $kelas ? ('kelas_id=' . (int) $kelas['id']) : '';
?>
<section class="page-head">
  <div>
    <h1>Rekap target hafalan</h1>
    <p class="muted">
      <?php if ($matrix): ?>
        <?= e($matrix['target']['judul']) ?> · <?= count($matrix['surat']) ?> surat wajib
      <?php else: ?>
        Progress siswa terhadap surat wajib kelas.
      <?php endif; ?>
    </p>
  </div>
  <div class="actions">
    <?php if ($kelas): ?>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan/target.php?kelas_id=' . $kelas['id'])) ?>">Atur target</a>
      <?php if ($matrix): ?>
        <a class="btn btn-ghost" href="<?= e(app_url('hafalan/rekap_ekspor.php?' . $qs . '&view=' . urlencode($viewMode))) ?>">Ekspor Excel</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card"><p class="muted">Belum ada kelas.</p></section>
<?php else: ?>
  <form class="card filter-bar" method="get" action="<?= e(app_url('hafalan/rekap.php')) ?>">
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
        <label for="view">Tampilan tabel</label>
        <select id="view" name="view" onchange="this.form.submit()">
          <option value="siswa" <?= $viewMode === 'siswa' ? 'selected' : '' ?>>Pivot: baris siswa</option>
          <option value="surat" <?= $viewMode === 'surat' ? 'selected' : '' ?>>Pivot: baris surat</option>
        </select>
      </div>
    </div>
  </form>

  <?php if (!$matrix): ?>
    <section class="card">
      <p class="muted">
        Target belum diatur untuk kelas ini.
        <a href="<?= e(app_url('hafalan/target.php?kelas_id=' . ($kelas['id'] ?? 0))) ?>">Atur target sekarang</a>.
      </p>
    </section>
  <?php elseif ($matrix['siswa'] === []): ?>
    <section class="card"><p class="muted">Belum ada siswa di kelas ini.</p></section>
  <?php else: ?>
    <?php
      $totalSiswa = count($matrix['siswa']);
      $totalSurat = count($matrix['surat']);
      $siswaTuntasSemua = 0;
      foreach ($matrix['ringkas_siswa'] as $rs) {
          if ((int) $rs['tuntas'] === $totalSurat) {
              $siswaTuntasSemua++;
          }
      }
    ?>
    <section class="stat-row">
      <div class="stat-card">
        <span class="muted">Surat target</span>
        <strong><?= $totalSurat ?></strong>
      </div>
      <div class="stat-card">
        <span class="muted">Siswa tuntas semua</span>
        <strong><?= $siswaTuntasSemua ?>/<?= $totalSiswa ?></strong>
      </div>
      <div class="stat-card">
        <span class="muted">Arah tabel</span>
        <strong style="font-size:1rem;"><?= $viewMode === 'surat' ? 'Per surat' : 'Per siswa' ?></strong>
      </div>
    </section>

    <section class="card">
      <div class="legend-row">
        <span class="cell-pill tuntas">Tuntas</span>
        <span class="cell-pill proses">Belum tuntas (kurang ayat)</span>
        <span class="cell-pill belum">Belum mulai</span>
      </div>

      <div class="table-wrap matrix-wrap">
        <?php if ($viewMode === 'siswa'): ?>
          <table class="table matrix-table">
            <thead>
              <tr>
                <th class="col-num sticky-col sticky-num">No</th>
                <th class="sticky-col sticky-label">Siswa</th>
                <th>Progress</th>
                <?php foreach ($matrix['surat'] as $surat): ?>
                  <th title="<?= e($surat['jumlah_ayat'] . ' ayat') ?>"><?= e($surat['surat_nama']) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matrix['siswa'] as $i => $siswa): ?>
                <?php
                  $sid = (int) $siswa['id'];
                  $rs = $matrix['ringkas_siswa'][$sid];
                ?>
                <tr>
                  <td class="col-num sticky-col sticky-num"><?= $i + 1 ?></td>
                  <th class="sticky-col sticky-label">
                    <a href="<?= e(app_url('hafalan/siswa.php?siswa_id=' . $sid)) ?>"><?= e($siswa['nama']) ?></a>
                  </th>
                  <td><?= (int) $rs['tuntas'] ?>/<?= (int) $rs['total_surat'] ?></td>
                  <?php foreach ($matrix['surat'] as $surat): ?>
                    <?php
                      $sn = (int) $surat['surat_nomor'];
                      $cell = $matrix['cells'][$sid][$sn];
                      $kelasId = (int) $kelas['id'];
                      $siswaId = $sid;
                      $suratNomor = $sn;
                    ?>
                    <td>
                      <?php require BASE_PATH . '/views/hafalan/_rekap_cell.php'; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <table class="table matrix-table">
            <thead>
              <tr>
                <th class="col-num sticky-col sticky-num">No</th>
                <th class="sticky-col sticky-label">Surat</th>
                <th>Tuntas kelas</th>
                <?php foreach ($matrix['siswa'] as $siswa): ?>
                  <th><?= e($siswa['nama']) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($matrix['surat'] as $i => $surat): ?>
                <?php
                  $sn = (int) $surat['surat_nomor'];
                  $rs = $matrix['ringkas_surat'][$sn];
                ?>
                <tr>
                  <td class="col-num sticky-col sticky-num"><?= $i + 1 ?></td>
                  <th class="sticky-col sticky-label">
                    <?= e($surat['surat_nama']) ?>
                    <div class="muted" style="font-size:0.78rem;font-weight:500;"><?= (int) $surat['jumlah_ayat'] ?> ayat</div>
                  </th>
                  <td><?= (int) $rs['tuntas'] ?>/<?= (int) $rs['total_siswa'] ?></td>
                  <?php foreach ($matrix['siswa'] as $siswa): ?>
                    <?php
                      $sid = (int) $siswa['id'];
                      $cell = $matrix['cells'][$sid][$sn];
                      $kelasId = (int) $kelas['id'];
                      $siswaId = $sid;
                      $suratNomor = $sn;
                    ?>
                    <td>
                      <?php require BASE_PATH . '/views/hafalan/_rekap_cell.php'; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
      <p class="muted" style="margin-top:0.75rem;">
        Klik sel <strong>Belum</strong> / <strong>Kurang …</strong> untuk langsung membuka form setoran (siswa & surat terisi).
        Hover untuk melihat nomor ayat yang belum lancar.
      </p>
    </section>
  <?php endif; ?>
<?php endif; ?>

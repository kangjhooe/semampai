<?php
/** @var array $daftarKelas */
/** @var array|null $kelas */
/** @var array $siswaList */
/** @var array $ringkas */
/** @var array $histori */
/** @var int $filterSiswaId */
/** @var string $filterStatus */
$success = flash('success');
$error = flash('error');
$filterSiswaId = (int) ($filterSiswaId ?? 0);
$filterStatus = (string) ($filterStatus ?? '');
?>
<section class="page-head">
  <div>
    <h1>Riwayat hafalan</h1>
    <p class="muted">Ringkasan per siswa dan histori setoran lengkap.</p>
  </div>
  <div class="actions">
    <?php if ($kelas): ?>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan_ekspor.php?kelas_id=' . $kelas['id'])) ?>">Ekspor Excel</a>
      <a class="btn btn-primary" href="<?= e(app_url('hafalan.php?kelas_id=' . $kelas['id'])) ?>">Setor lagi</a>
    <?php endif; ?>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card">
    <p class="muted">Belum ada kelas. <a href="<?= e(app_url('kelas.php')) ?>">Buat kelas dulu</a>.</p>
  </section>
<?php else: ?>
  <form class="card filter-bar" method="get" action="<?= e(app_url('hafalan_riwayat.php')) ?>">
    <div class="grid-3">
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
        <label for="siswa_id">Siswa</label>
        <select id="siswa_id" name="siswa_id" onchange="this.form.submit()">
          <option value="0">Semua siswa</option>
          <?php foreach ($siswaList as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= $filterSiswaId === (int) $s['id'] ? 'selected' : '' ?>>
              <?= e($s['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" onchange="this.form.submit()">
          <option value="" <?= $filterStatus === '' ? 'selected' : '' ?>>Semua</option>
          <option value="lancar" <?= $filterStatus === 'lancar' ? 'selected' : '' ?>>Lancar</option>
          <option value="ulang" <?= $filterStatus === 'ulang' ? 'selected' : '' ?>>Ulang</option>
        </select>
      </div>
    </div>
  </form>

  <section class="card">
    <h2 class="card-title">Ringkasan per siswa</h2>
    <?php if ($ringkas === []): ?>
      <p class="muted">Belum ada siswa di kelas ini.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="col-num">No</th>
              <th>Nama</th>
              <th>Terakhir lancar</th>
              <th>Setor</th>
              <th>Lancar</th>
              <th>Ulang</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php $noRingkas = 0; ?>
            <?php foreach ($ringkas as $row): ?>
              <?php if ($filterSiswaId > 0 && (int) $row['id'] !== $filterSiswaId) {
                  continue;
              } ?>
              <?php $noRingkas++; ?>
              <tr>
                <td class="col-num"><?= $noRingkas ?></td>
                <td>
                  <strong><?= e($row['nama']) ?></strong>
                  <div class="muted" style="font-size:0.82rem;"><?= e($row['nisn']) ?></div>
                </td>
                <td>
                  <?php if ($row['surat_nama']): ?>
                    <?= e($row['surat_nama']) ?>
                    <?= e(Hafalan::formatAyat((int) $row['ayat_awal'], (int) $row['ayat_akhir'])) ?>
                    <div class="muted" style="font-size:0.82rem;">
                      <?= e(date('d/m/Y H:i', strtotime($row['terakhir_lancar']))) ?>
                    </div>
                  <?php else: ?>
                    <span class="muted">Belum ada</span>
                  <?php endif; ?>
                </td>
                <td><?= (int) $row['total_setor'] ?></td>
                <td><?= (int) $row['total_lancar'] ?></td>
                <td><?= (int) $row['total_ulang'] ?></td>
                <td class="table-actions">
                  <a class="btn-icon" href="<?= e(app_url('hafalan_siswa.php?siswa_id=' . $row['id'])) ?>" title="Detail" aria-label="Detail">
                    <?= icon('eye') ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="card" style="margin-top: 0.9rem;">
    <h2 class="card-title">Histori setoran<?= $histori !== [] ? ' (' . count($histori) . ')' : '' ?></h2>
    <?php if ($histori === []): ?>
      <p class="muted">Belum ada catatan setoran untuk filter ini.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="col-num">No</th>
              <th>Waktu</th>
              <th>Siswa</th>
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
                  <a href="<?= e(app_url('hafalan_siswa.php?siswa_id=' . $row['siswa_id'])) ?>">
                    <?= e($row['siswa_nama']) ?>
                  </a>
                </td>
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
      <?php if (count($histori) >= 200): ?>
        <p class="muted" style="margin-top:0.75rem;">Menampilkan 200 setoran terbaru. Gunakan filter siswa atau ekspor Excel untuk data lengkap.</p>
      <?php endif; ?>
    <?php endif; ?>
  </section>
<?php endif; ?>

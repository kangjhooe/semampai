<?php
/** @var array $daftarKelas */
/** @var array $daftar */
/** @var int $filterKelasId */
/** @var string $filterQ */
$success = flash('success');
$error = flash('error');
$filterKelasId = (int) ($filterKelasId ?? 0);
$filterQ = (string) ($filterQ ?? '');
$hasFilter = $filterKelasId > 0 || $filterQ !== '';
?>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="page-head">
  <div>
    <p class="muted">Bagat kuis, bagikan ID ke siswa, lihat hasilnya.</p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/live_join.php')) ?>" target="_blank" rel="noopener">Gabung live</a>
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/ikut.php')) ?>" target="_blank" rel="noopener">Halaman siswa</a>
    <a class="btn btn-primary" href="<?= e(app_url('kuis/form.php')) ?>">Buat kuis</a>
  </div>
</section>

<form class="card bahan-filter" method="get" action="<?= e(app_url('kuis/index.php')) ?>">
  <select id="kelas_id" name="kelas_id" aria-label="Kelas" onchange="this.form.submit()">
    <option value="0">Semua kelas</option>
    <?php foreach ($daftarKelas as $row): ?>
      <option value="<?= (int) $row['id'] ?>" <?= $filterKelasId === (int) $row['id'] ? 'selected' : '' ?>>
        <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input id="q" type="search" name="q" value="<?= e($filterQ) ?>" placeholder="Cari judul atau ID…" aria-label="Cari">
  <button class="btn btn-primary" type="submit">Cari</button>
  <?php if ($hasFilter): ?>
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/index.php')) ?>">Reset</a>
  <?php endif; ?>
</form>

<?php if ($daftar === []): ?>
  <section class="card">
    <p class="muted">
      <?= $hasFilter ? 'Tidak ada kuis yang cocok.' : 'Belum ada kuis. Mulai dengan membuat kuis baru.' ?>
    </p>
  </section>
<?php else: ?>
  <section class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">No</th>
            <th>Judul</th>
            <th>ID</th>
            <th>Kelas</th>
            <th>Soal</th>
            <th>Hasil</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $i => $row): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td><strong><?= e($row['judul']) ?></strong></td>
              <td><code class="kuis-kode"><?= e($row['kode']) ?></code></td>
              <td>
                <?php if (!empty($row['kelas_nama'])): ?>
                  <?= e($row['kelas_nama']) ?>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= (int) $row['jumlah_soal'] ?></td>
              <td><?= (int) $row['jumlah_hasil'] ?></td>
              <td>
                <?php if ((int) $row['aktif']): ?>
                  <span class="kuis-badge kuis-badge-on">Aktif</span>
                <?php else: ?>
                  <span class="kuis-badge kuis-badge-off">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td class="table-actions">
                <a class="btn-icon primary" href="<?= e(app_url('kuis/live_host.php?kuis_id=' . $row['id'])) ?>" title="Live" aria-label="Live">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.5" fill="currentColor"/><path d="M5.5 12a6.5 6.5 0 0 1 13 0M2.5 12a9.5 9.5 0 0 1 19 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </a>
                <a class="btn-icon" href="<?= e(app_url('kuis/hasil.php?id=' . $row['id'])) ?>" title="Hasil" aria-label="Hasil">
                  <?= icon('users') ?>
                </a>
                <a class="btn-icon" href="<?= e(app_url('kuis/form.php?id=' . $row['id'])) ?>" title="Edit" aria-label="Edit">
                  <?= icon('edit') ?>
                </a>
                <form method="post" action="<?= e(app_url('kuis/duplikat.php')) ?>" onsubmit="return confirm('Duplikasi kuis ini beserta semua soalnya?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn-icon primary" type="submit" title="Duplikasi" aria-label="Duplikasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M6.5 16H6a1.5 1.5 0 0 1-1.5-1.5V6A1.5 1.5 0 0 1 6 4.5h8.5A1.5 1.5 0 0 1 16 6v.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                  </button>
                </form>
                <form method="post" action="<?= e(app_url('kuis/hapus.php')) ?>" onsubmit="return confirm('Hapus kuis ini beserta soal dan hasilnya?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
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
  </section>
<?php endif; ?>

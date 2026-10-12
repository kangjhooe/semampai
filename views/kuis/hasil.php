<?php
/** @var array $kuis */
/** @var array $hasil */
/** @var string $sort */
/** @var list<array> $statistik */
$success = flash('success');
$error = flash('error');
$sort = $sort ?? 'peringkat';
$statistik = $statistik ?? [];

$rows = [];
$sumPersen = 0;
$tertinggi = null;
foreach ($hasil as $i => $row) {
    $total = max(1, (int) $row['total']);
    $skor = (int) $row['skor'];
    $persen = (int) round(($skor / $total) * 100);
    $sumPersen += $persen;
    if ($tertinggi === null || $persen > $tertinggi) {
        $tertinggi = $persen;
    }
    if ($persen >= 80) {
        $tone = 'is-good';
    } elseif ($persen >= 50) {
        $tone = 'is-mid';
    } else {
        $tone = 'is-low';
    }
    $rows[] = [
        'id' => (int) $row['id'],
        'nama' => (string) $row['nama_peserta'],
        'skor' => $skor,
        'total' => (int) $row['total'],
        'persen' => $persen,
        'tone' => $tone,
        'waktu' => date('d/m/Y · H.i', strtotime((string) $row['created_at'])),
        'nomor' => $sort === 'peringkat' ? (int) ($row['peringkat'] ?? ($i + 1)) : ($i + 1),
    ];
}
$jumlahHasil = count($rows);
$rata = $jumlahHasil > 0 ? (int) round($sumPersen / $jumlahHasil) : null;

$statRows = [];
foreach ($statistik as $row) {
    if ((int) $row['total'] === 0) {
        continue;
    }
    $statRows[] = $row;
}

$meta = [];
if (!empty($kuis['kelas_nama'])) {
    $meta[] = (string) $kuis['kelas_nama'];
}
$meta[] = count($statistik) . ' soal';
if (!empty($kuis['timer_menit'])) {
    $meta[] = 'Timer ' . (int) $kuis['timer_menit'] . ' menit';
}
?>

<section class="page-head kuis-hasil-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/index.php')) ?>">← Daftar kuis</a></p>
    <h2 class="kuis-hasil-title"><?= e($kuis['judul']) ?></h2>
    <p class="kuis-hasil-meta muted">
      ID <code class="kuis-kode"><?= e($kuis['kode']) ?></code>
      <?php if ($meta !== []): ?>
        · <?= e(implode(' · ', $meta)) ?>
      <?php endif; ?>
    </p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/ekspor.php?id=' . $kuis['id'])) ?>">Ekspor Excel</a>
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>">Edit kuis</a>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="kuis-hasil-summary" aria-label="Ringkasan hasil">
  <div class="stat-card">
    <span class="muted">Pengerjaan</span>
    <strong><?= $jumlahHasil ?></strong>
  </div>
  <div class="stat-card">
    <span class="muted">Rata-rata</span>
    <strong><?= $rata === null ? '—' : $rata . '%' ?></strong>
  </div>
  <div class="stat-card">
    <span class="muted">Tertinggi</span>
    <strong><?= $tertinggi === null ? '—' : $tertinggi . '%' ?></strong>
  </div>
</section>

<?php if ($statRows !== []): ?>
<section class="card stack">
  <div>
    <h2 class="card-title" style="margin:0;">Statistik soal</h2>
    <p class="muted">Diurutkan dari yang paling sering dijawab salah.</p>
  </div>
  <ul class="kuis-stat-list">
    <?php foreach ($statRows as $row): ?>
      <?php $hot = (int) $row['persen_salah'] >= 50; ?>
      <li class="kuis-stat-item<?= $hot ? ' is-hot' : '' ?>">
        <div class="kuis-stat-top">
          <span class="kuis-stat-no"><?= (int) $row['nomor'] ?></span>
          <p><?= e((string) $row['pertanyaan']) ?></p>
          <strong class="kuis-stat-pct"><?= (int) $row['persen_salah'] ?>%</strong>
        </div>
        <div class="kuis-stat-bar" title="<?= (int) $row['persen_salah'] ?>% salah">
          <span style="width: <?= (int) $row['persen_salah'] ?>%"></span>
        </div>
        <p class="muted kuis-stat-meta"><?= (int) $row['benar'] ?> benar · <?= (int) $row['salah'] ?> salah</p>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="card stack">
  <div class="kuis-hasil-toolbar">
    <h2 class="card-title" style="margin:0;">Daftar pengerjaan</h2>
    <div class="kuis-hasil-sort" role="group" aria-label="Urutkan">
      <a class="<?= $sort === 'peringkat' ? 'is-active' : '' ?>" href="<?= e(app_url('kuis/hasil.php?id=' . $kuis['id'] . '&sort=peringkat')) ?>">Peringkat</a>
      <a class="<?= $sort === 'terbaru' ? 'is-active' : '' ?>" href="<?= e(app_url('kuis/hasil.php?id=' . $kuis['id'] . '&sort=terbaru')) ?>">Terbaru</a>
    </div>
  </div>

  <?php if ($rows === []): ?>
    <div class="kuis-hasil-empty">
      <strong>Belum ada pengerjaan</strong>
      <p class="muted">Bagikan ID <code class="kuis-kode"><?= e($kuis['kode']) ?></code> supaya siswa bisa mulai mengerjakan.</p>
    </div>
  <?php else: ?>
    <div class="kuis-hasil-cards">
      <?php foreach ($rows as $row): ?>
        <article class="kuis-hasil-card">
          <div class="kuis-hasil-card-top">
            <span class="kuis-hasil-rank"><?= $sort === 'peringkat' ? '#' . $row['nomor'] : $row['nomor'] ?></span>
            <div class="kuis-hasil-who">
              <a href="<?= e(app_url('kuis/hasil_detail.php?hasil_id=' . $row['id'])) ?>">
                <strong><?= e($row['nama']) ?></strong>
              </a>
              <p class="muted"><?= e($row['waktu']) ?></p>
            </div>
            <div class="kuis-hasil-score <?= e($row['tone']) ?>">
              <strong><?= $row['persen'] ?>%</strong>
              <span><?= $row['skor'] ?> / <?= $row['total'] ?></span>
            </div>
          </div>
          <div class="kuis-hasil-card-actions">
            <a class="btn-icon" href="<?= e(app_url('kuis/hasil_detail.php?hasil_id=' . $row['id'])) ?>" title="Detail" aria-label="Detail">
              <?= icon('eye') ?>
            </a>
            <form method="post" action="<?= e(app_url('kuis/hapus_hasil.php')) ?>" onsubmit="return confirm('Hapus hasil peserta ini? Mereka bisa mengerjakan lagi.');">
              <?= Csrf::field() ?>
              <input type="hidden" name="hasil_id" value="<?= $row['id'] ?>">
              <input type="hidden" name="kuis_id" value="<?= (int) $kuis['id'] ?>">
              <button class="btn-icon danger" type="submit" title="Hapus hasil" aria-label="Hapus hasil"><?= icon('trash') ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="table-wrap kuis-hasil-table">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num"><?= $sort === 'peringkat' ? 'Rank' : 'No' ?></th>
            <th>Nama</th>
            <th>Skor</th>
            <th>Persen</th>
            <th>Waktu</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="col-num"><?= $sort === 'peringkat' ? '#' . $row['nomor'] : $row['nomor'] ?></td>
              <td>
                <a href="<?= e(app_url('kuis/hasil_detail.php?hasil_id=' . $row['id'])) ?>">
                  <strong><?= e($row['nama']) ?></strong>
                </a>
              </td>
              <td><?= $row['skor'] ?> / <?= $row['total'] ?></td>
              <td><span class="kuis-hasil-pct <?= e($row['tone']) ?>"><?= $row['persen'] ?>%</span></td>
              <td class="kuis-hasil-time"><?= e($row['waktu']) ?></td>
              <td class="table-actions">
                <a class="btn-icon" href="<?= e(app_url('kuis/hasil_detail.php?hasil_id=' . $row['id'])) ?>" title="Detail" aria-label="Detail">
                  <?= icon('eye') ?>
                </a>
                <form method="post" action="<?= e(app_url('kuis/hapus_hasil.php')) ?>" onsubmit="return confirm('Hapus hasil peserta ini? Mereka bisa mengerjakan lagi.');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="hasil_id" value="<?= $row['id'] ?>">
                  <input type="hidden" name="kuis_id" value="<?= (int) $kuis['id'] ?>">
                  <button class="btn-icon danger" type="submit" title="Hapus hasil" aria-label="Hapus hasil"><?= icon('trash') ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

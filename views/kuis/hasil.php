<?php
/** @var array $kuis */
/** @var array $hasil */
/** @var string $sort */
/** @var list<array> $statistik */
$success = flash('success');
$error = flash('error');
$sort = $sort ?? 'peringkat';
$statistik = $statistik ?? [];
?>

<section class="page-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/index.php')) ?>">← Daftar kuis</a></p>
    <h2 class="card-title" style="margin:0.35rem 0 0;"><?= e($kuis['judul']) ?></h2>
    <p class="muted">
      ID <code class="kuis-kode"><?= e($kuis['kode']) ?></code> · <?= count($hasil) ?> pengerjaan
      <?php if (!empty($kuis['timer_menit'])): ?>
        · Timer <?= (int) $kuis['timer_menit'] ?> menit
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

<?php if ($statistik !== [] && $hasil !== []): ?>
<section class="card stack">
  <h2 class="card-title" style="margin:0;">Statistik soal</h2>
  <p class="muted" style="margin:0;">Diurutkan dari yang paling sering dijawab salah.</p>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="col-num">No</th>
          <th>Pertanyaan</th>
          <th>Benar</th>
          <th>Salah</th>
          <th>% Salah</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($statistik as $row): ?>
          <?php if ((int) $row['total'] === 0) {
              continue;
          } ?>
          <tr class="<?= (int) $row['persen_salah'] >= 50 ? 'kuis-stat-hot' : '' ?>">
            <td class="col-num"><?= (int) $row['nomor'] ?></td>
            <td><?= e(mb_strimwidth((string) $row['pertanyaan'], 0, 80, '…')) ?></td>
            <td><?= (int) $row['benar'] ?></td>
            <td><?= (int) $row['salah'] ?></td>
            <td>
              <div class="kuis-stat-bar" title="<?= (int) $row['persen_salah'] ?>% salah">
                <span style="width: <?= (int) $row['persen_salah'] ?>%"></span>
                <em><?= (int) $row['persen_salah'] ?>%</em>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<section class="card stack">
  <div class="kuis-hasil-toolbar">
    <h2 class="card-title" style="margin:0;">Daftar pengerjaan</h2>
    <div class="actions" style="margin:0;">
      <a class="btn btn-sm <?= $sort === 'peringkat' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(app_url('kuis/hasil.php?id=' . $kuis['id'] . '&sort=peringkat')) ?>">Peringkat</a>
      <a class="btn btn-sm <?= $sort === 'terbaru' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(app_url('kuis/hasil.php?id=' . $kuis['id'] . '&sort=terbaru')) ?>">Terbaru</a>
    </div>
  </div>

  <?php if ($hasil === []): ?>
    <p class="muted">Belum ada siswa yang mengerjakan kuis ini.</p>
  <?php else: ?>
    <div class="table-wrap">
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
          <?php foreach ($hasil as $i => $row): ?>
            <?php
            $total = max(1, (int) $row['total']);
            $skor = (int) $row['skor'];
            $persen = (int) round(($skor / $total) * 100);
            $num = $sort === 'peringkat' ? (int) ($row['peringkat'] ?? ($i + 1)) : ($i + 1);
            ?>
            <tr>
              <td class="col-num"><?= $sort === 'peringkat' ? '#' . $num : $num ?></td>
              <td>
                <a href="<?= e(app_url('kuis/hasil_detail.php?hasil_id=' . $row['id'])) ?>">
                  <strong><?= e($row['nama_peserta']) ?></strong>
                </a>
              </td>
              <td><?= $skor ?> / <?= (int) $row['total'] ?></td>
              <td><?= $persen ?>%</td>
              <td><?= e(date('d/m/Y H:i', strtotime((string) $row['created_at']))) ?></td>
              <td class="table-actions">
                <a class="btn-icon" href="<?= e(app_url('kuis/hasil_detail.php?hasil_id=' . $row['id'])) ?>" title="Detail" aria-label="Detail">
                  <?= icon('eye') ?>
                </a>
                <form method="post" action="<?= e(app_url('kuis/hapus_hasil.php')) ?>" onsubmit="return confirm('Hapus hasil peserta ini? Mereka bisa mengerjakan lagi.');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="hasil_id" value="<?= (int) $row['id'] ?>">
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

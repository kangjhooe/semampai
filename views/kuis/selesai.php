<?php
/** @var array $result */
$skor = (int) ($result['skor'] ?? 0);
$total = max(1, (int) ($result['total'] ?? 1));
$persen = (int) round(($skor / $total) * 100);
$review = is_array($result['review'] ?? null) ? $result['review'] : [];
$leaderboard = is_array($result['leaderboard'] ?? null) ? $result['leaderboard'] : [];
$peringkat = $result['peringkat'] ?? null;
$totalPeserta = $result['total_peserta'] ?? null;
$hasilId = (int) ($result['hasil_id'] ?? 0);
$labels = ['A', 'B', 'C', 'D', 'E', 'F'];
?>
<div class="kuis-public kuis-finish">
  <div class="kuis-public-card kuis-result-card">
    <p class="muted" style="margin:0;">Selesai</p>
    <h1><?= e((string) $result['judul']) ?></h1>
    <p>Terima kasih, <strong><?= e((string) $result['nama']) ?></strong>.</p>

    <div class="kuis-score">
      <span class="kuis-score-num"><?= $skor ?> / <?= (int) $result['total'] ?></span>
      <span class="kuis-score-pct"><?= $persen ?>%</span>
      <?php if ($peringkat !== null && $totalPeserta !== null): ?>
        <span class="kuis-score-rank">Peringkat #<?= (int) $peringkat ?> dari <?= (int) $totalPeserta ?> peserta</span>
      <?php endif; ?>
    </div>

    <div class="actions" style="justify-content:center;">
      <a class="btn btn-primary" href="<?= e(app_url('kuis/ikut.php')) ?>">Kerjakan kuis lain</a>
    </div>
  </div>

  <?php if ($leaderboard !== []): ?>
    <div class="kuis-public-card">
      <h2 class="card-title" style="margin:0;">Peringkat sementara</h2>
      <ol class="kuis-leaderboard">
        <?php foreach ($leaderboard as $row): ?>
          <li class="<?= (int) $row['id'] === $hasilId ? 'is-me' : '' ?>">
            <span class="kuis-lb-rank">#<?= (int) $row['peringkat'] ?></span>
            <span class="kuis-lb-name"><?= e((string) $row['nama_peserta']) ?></span>
            <span class="kuis-lb-score"><?= (int) $row['skor'] ?>/<?= (int) $row['total'] ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  <?php endif; ?>

  <?php if ($review !== [] && !empty($result['tampil_pembahasan'])): ?>
    <div class="kuis-public-card">
      <h2 class="card-title" style="margin:0;">Pembahasan</h2>
      <ol class="kuis-review-list">
        <?php foreach ($review as $item): ?>
          <li class="kuis-review-item <?= !empty($item['ok']) ? 'is-ok' : 'is-bad' ?>">
            <div class="kuis-review-head">
              <strong><?= (int) $item['nomor'] ?>. <?= e((string) $item['pertanyaan']) ?></strong>
              <span class="kuis-review-badge"><?= !empty($item['ok']) ? 'Benar' : 'Salah' ?></span>
            </div>
            <?php if (!empty($item['gambar'])): ?>
              <img class="kuis-q-image" src="<?= e((string) $item['gambar']) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <?php if (($item['tipe'] ?? '') === 'isian'): ?>
              <p class="muted kuis-review-note">
                Jawaban Anda: <strong><?= e((string) $item['pilih_teks']) ?></strong>
                <?php if (empty($item['ok'])): ?>
                  · Kunci: <strong><?= e((string) $item['benar_teks']) ?></strong>
                <?php endif; ?>
              </p>
            <?php else: ?>
              <ul class="kuis-review-opsi">
                <?php foreach ($item['opsi'] as $oi => $teks): ?>
                  <?php
                  $cls = '';
                  if ($oi === (int) $item['benar']) {
                      $cls = 'is-correct';
                  } elseif ($oi === (int) $item['pilih'] && empty($item['ok'])) {
                      $cls = 'is-wrong';
                  }
                  ?>
                  <li class="<?= e($cls) ?>">
                    <span><?= e($labels[$oi] ?? (string) ($oi + 1)) ?>.</span>
                    <?= e((string) $teks) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (empty($item['ok'])): ?>
                <p class="muted kuis-review-note">
                  Jawaban Anda: <strong><?= e((string) $item['pilih_label']) ?></strong>
                  · Kunci: <strong><?= e((string) $item['benar_label']) ?>. <?= e((string) $item['benar_teks']) ?></strong>
                </p>
              <?php endif; ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  <?php endif; ?>
</div>

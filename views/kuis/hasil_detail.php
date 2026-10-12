<?php
/** @var array|null $kuis */
/** @var array $hasil */
$review = is_array($hasil['review'] ?? null) ? $hasil['review'] : [];
$labels = ['A', 'B', 'C', 'D', 'E', 'F'];
$total = max(1, (int) $hasil['total']);
$skor = (int) $hasil['skor'];
$persen = (int) round(($skor / $total) * 100);
$kuisId = (int) ($kuis['id'] ?? $hasil['kuis_id'] ?? 0);
?>

<section class="page-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/hasil.php?id=' . $kuisId)) ?>">← Kembali ke hasil</a></p>
    <h2 class="card-title" style="margin:0.35rem 0 0;"><?= e((string) $hasil['nama_peserta']) ?></h2>
    <p class="muted">
      <?= e((string) ($hasil['kuis_judul'] ?? $kuis['judul'] ?? '')) ?>
      · <?= e(date('d/m/Y H:i', strtotime((string) $hasil['created_at']))) ?>
    </p>
  </div>
</section>

<section class="card">
  <div class="kuis-score" style="text-align:center;">
    <span class="kuis-score-num"><?= $skor ?> / <?= (int) $hasil['total'] ?></span>
    <span class="kuis-score-pct"><?= $persen ?>%</span>
  </div>
</section>

<section class="card stack">
  <h2 class="card-title" style="margin:0;">Jawaban per soal</h2>
  <?php if ($review === []): ?>
    <p class="muted">Detail jawaban tidak tersedia.</p>
  <?php else: ?>
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
              Jawaban: <strong><?= e((string) $item['pilih_teks']) ?></strong>
              <?php if (empty($item['ok'])): ?>
                · Kunci: <strong><?= e((string) $item['benar_teks']) ?></strong>
              <?php endif; ?>
            </p>
          <?php else: ?>
            <ul class="kuis-review-opsi">
              <?php foreach ($item['opsi'] as $oi => $opt): ?>
                <?php
                $teks = is_array($opt) ? (string) ($opt['teks'] ?? '') : (string) $opt;
                $gambar = is_array($opt) ? (string) ($opt['gambar'] ?? '') : '';
                $cls = '';
                if ($oi === (int) $item['benar']) {
                    $cls = 'is-correct';
                } elseif ($oi === (int) $item['pilih'] && empty($item['ok'])) {
                    $cls = 'is-wrong';
                }
                ?>
                <li class="<?= e($cls) ?>">
                  <span><?= e($labels[$oi] ?? (string) ($oi + 1)) ?>.</span>
                  <span class="kuis-opsi-body">
                    <?php if ($gambar !== ''): ?>
                      <img class="kuis-q-option-img" src="<?= e($gambar) ?>" alt="<?= $teks === '' ? e('Gambar opsi ' . ($labels[$oi] ?? (string) ($oi + 1))) : '' ?>">
                    <?php endif; ?>
                    <?php if ($teks !== ''): ?>
                      <span class="kuis-opsi-caption"><?= e($teks) ?></span>
                    <?php endif; ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php if (empty($item['ok'])): ?>
              <p class="muted kuis-review-note">
                Jawaban peserta: <strong><?= e((string) $item['pilih_label']) ?></strong>
                · Kunci: <strong><?= e((string) $item['benar_label']) ?>. <?= e((string) $item['benar_teks']) ?></strong>
              </p>
            <?php endif; ?>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</section>

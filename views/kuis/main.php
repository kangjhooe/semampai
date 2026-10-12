<?php
/** @var array $play */
/** @var array<string, string> $errors */
/** @var int $endsAt */
/** @var int $remainingSeconds */
/** @var bool $stepMode */
/** @var bool $modeLatihan */
/** @var array|null $currentSoal */
/** @var int $currentIndex */
/** @var int $totalSoal */
/** @var int $soalEndsAt */
/** @var int $soalRemaining */
/** @var array|null $feedback */
$errors = $errors ?? [];
$endsAt = (int) ($endsAt ?? 0);
$remainingSeconds = (int) ($remainingSeconds ?? 0);
$stepMode = !empty($stepMode);
$modeLatihan = !empty($modeLatihan);
$soal = $play['soal'] ?? [];
$labels = ['A', 'B', 'C', 'D', 'E', 'F'];
$hasTimer = $endsAt > 0;
$hasSoalTimer = $stepMode && (int) ($soalEndsAt ?? 0) > 0 && empty($feedback);
$progressPct = ($stepMode && (int) $totalSoal > 0)
    ? (int) round((((int) $currentIndex + 1) / (int) $totalSoal) * 100)
    : 0;
?>
<div class="kuis-public kuis-play">
  <div class="kuis-public-card kuis-play-card">
    <header class="kuis-play-head">
      <?php if ($stepMode): ?>
        <div class="kuis-progress" role="progressbar" aria-valuemin="1" aria-valuemax="<?= (int) $totalSoal ?>" aria-valuenow="<?= (int) $currentIndex + 1 ?>" aria-label="Kemajuan soal">
          <span style="width: <?= $progressPct ?>%"></span>
        </div>
      <?php endif; ?>
      <div class="kuis-play-top">
        <div>
          <p class="muted" style="margin:0;">Halo, <strong><?= e((string) $play['nama']) ?></strong></p>
          <h1><?= e((string) $play['judul']) ?></h1>
          <p class="muted">
            <?php if ($stepMode): ?>
              Soal <?= (int) $currentIndex + 1 ?> dari <?= (int) $totalSoal ?>
              <?= $modeLatihan ? ' · Mode latihan' : '' ?>
            <?php else: ?>
              <?= count($soal) ?> soal · jawab lalu kirim<?= $hasTimer ? ' sebelum waktu habis' : '' ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="kuis-timers">
          <?php if ($hasTimer): ?>
            <div class="kuis-timer" data-kuis-timer data-ends-at="<?= (int) $endsAt ?>" aria-live="polite">
              <span class="kuis-timer-label">Total</span>
              <strong class="kuis-timer-value" data-kuis-timer-value><?= e(sprintf('%02d:%02d', intdiv($remainingSeconds, 60), $remainingSeconds % 60)) ?></strong>
            </div>
          <?php endif; ?>
          <?php if ($hasSoalTimer): ?>
            <div class="kuis-timer" data-kuis-soal-timer data-ends-at="<?= (int) $soalEndsAt ?>" aria-live="polite">
              <span class="kuis-timer-label">Soal ini</span>
              <strong class="kuis-timer-value" data-kuis-soal-timer-value><?= e(sprintf('%02d:%02d', intdiv((int) $soalRemaining, 60), ((int) $soalRemaining) % 60)) ?></strong>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <?php if (!empty($errors['_form'])): ?>
      <div class="alert error"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <?php if ($stepMode && is_array($feedback)): ?>
      <div class="kuis-feedback <?= !empty($feedback['ok']) ? 'is-ok' : 'is-bad' ?>">
        <strong><?= !empty($feedback['ok']) ? 'Benar!' : 'Belum tepat' ?></strong>
        <p>Jawaban Anda: <?= e((string) $feedback['pilih_teks']) ?></p>
        <?php if (empty($feedback['ok'])): ?>
          <p>Kunci: <strong><?= e((string) $feedback['benar_teks']) ?></strong></p>
        <?php endif; ?>
      </div>
      <form method="post" action="<?= e(app_url('kuis/main.php')) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="next">
        <button class="btn btn-primary" type="submit"><?= ((int) $currentIndex + 1) >= (int) $totalSoal ? 'Lihat hasil' : 'Soal berikutnya' ?></button>
      </form>

    <?php elseif ($stepMode && $currentSoal): ?>
      <form class="stack" method="post" action="<?= e(app_url('kuis/main.php')) ?>" data-kuis-play-form>
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="answer">
        <fieldset class="kuis-q">
          <legend>Soal <?= (int) $currentIndex + 1 ?></legend>
          <p class="kuis-q-ask"><?= e((string) $currentSoal['pertanyaan']) ?></p>
          <?php if (!empty($currentSoal['gambar'])): ?>
            <img class="kuis-q-image" src="<?= e((string) $currentSoal['gambar']) ?>" alt="" loading="lazy">
          <?php endif; ?>

          <?php if (($currentSoal['tipe'] ?? '') === 'isian'): ?>
            <div class="field" style="margin-top:0.75rem;">
              <label for="jawaban_teks">Jawaban singkat</label>
              <input id="jawaban_teks" type="text" name="jawaban_teks" required autocomplete="off" placeholder="Ketik jawaban…">
            </div>
          <?php else: ?>
            <div class="kuis-q-options">
              <?php foreach ($currentSoal['opsi'] as $pos => $opt): ?>
                <?php
                $oi = is_array($opt) ? (int) ($opt['i'] ?? $pos) : (int) $pos;
                $teks = is_array($opt) ? (string) ($opt['teks'] ?? '') : (string) $opt;
                ?>
                <label class="kuis-q-option">
                  <input type="radio" name="jawaban" value="<?= $oi ?>" required>
                  <span class="kuis-q-letter"><?= e($labels[$pos] ?? (string) ($pos + 1)) ?></span>
                  <span class="kuis-q-option-body">
                    <?php $gambar = is_array($opt) ? (string) ($opt['gambar'] ?? '') : ''; ?>
                    <?php if ($gambar !== ''): ?>
                      <img class="kuis-q-option-img" src="<?= e($gambar) ?>" alt="<?= $teks === '' ? e('Gambar opsi ' . ($labels[$pos] ?? (string) ($pos + 1))) : '' ?>">
                    <?php endif; ?>
                    <?php if ($teks !== ''): ?>
                      <span><?= e($teks) ?></span>
                    <?php endif; ?>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </fieldset>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Kirim jawaban</button>
          <a class="btn btn-ghost" href="<?= e(app_url('kuis/ikut.php?batal=1')) ?>" onclick="return confirm('Batalkan pengerjaan?');">Batal</a>
        </div>
      </form>

    <?php else: ?>
      <form class="stack" method="post" action="<?= e(app_url('kuis/main.php')) ?>" data-kuis-play-form>
        <?= Csrf::field() ?>
        <?php foreach ($soal as $i => $item): ?>
          <fieldset class="kuis-q">
            <legend>Soal <?= $i + 1 ?></legend>
            <p class="kuis-q-ask"><?= e((string) $item['pertanyaan']) ?></p>
            <?php if (!empty($item['gambar'])): ?>
              <img class="kuis-q-image" src="<?= e((string) $item['gambar']) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <?php if (($item['tipe'] ?? '') === 'isian'): ?>
              <div class="field" style="margin-top:0.75rem;">
                <label for="isian-<?= (int) $item['id'] ?>">Jawaban singkat</label>
                <input id="isian-<?= (int) $item['id'] ?>" type="text" name="jawaban_teks[<?= (int) $item['id'] ?>]" <?= $hasTimer ? '' : 'required' ?> autocomplete="off">
              </div>
            <?php else: ?>
              <div class="kuis-q-options">
                <?php foreach ($item['opsi'] as $pos => $opt): ?>
                  <?php
                  $oi = is_array($opt) ? (int) ($opt['i'] ?? $pos) : (int) $pos;
                  $teks = is_array($opt) ? (string) ($opt['teks'] ?? '') : (string) $opt;
                  ?>
                  <label class="kuis-q-option">
                    <input type="radio" name="jawaban[<?= (int) $item['id'] ?>]" value="<?= $oi ?>" <?= $hasTimer ? '' : 'required' ?>>
                    <span class="kuis-q-letter"><?= e($labels[$pos] ?? (string) ($pos + 1)) ?></span>
                    <span class="kuis-q-option-body">
                      <?php $gambar = is_array($opt) ? (string) ($opt['gambar'] ?? '') : ''; ?>
                      <?php if ($gambar !== ''): ?>
                        <img class="kuis-q-option-img" src="<?= e($gambar) ?>" alt="<?= $teks === '' ? e('Gambar opsi ' . ($labels[$pos] ?? (string) ($pos + 1))) : '' ?>">
                      <?php endif; ?>
                      <?php if ($teks !== ''): ?>
                        <span><?= e($teks) ?></span>
                      <?php endif; ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </fieldset>
        <?php endforeach; ?>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Kirim jawaban</button>
          <a class="btn btn-ghost" href="<?= e(app_url('kuis/ikut.php?batal=1')) ?>" onclick="return confirm('Batalkan pengerjaan?');">Batal</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php if ($hasTimer || $hasSoalTimer): ?>
<script>
(function () {
  function bindTimer(box, valueSel, onZero) {
    if (!box) return;
    var endsAt = parseInt(box.getAttribute('data-ends-at') || '0', 10) * 1000;
    var valueEl = box.querySelector(valueSel);
    var done = false;
    var initial = null;
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function tick() {
      var left = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
      if (initial === null) initial = Math.max(left, 1);
      var warnAt = Math.max(3, Math.min(10, Math.round(initial * 0.25)));
      if (valueEl) valueEl.textContent = pad(Math.floor(left / 60)) + ':' + pad(left % 60);
      box.classList.toggle('is-urgent', left <= warnAt);
      if (left <= 0) {
        if (!done) { done = true; onZero(); }
        return;
      }
      setTimeout(tick, 250);
    }
    tick();
  }

  var form = document.querySelector('[data-kuis-play-form]');
  bindTimer(document.querySelector('[data-kuis-timer]'), '[data-kuis-timer-value]', function () {
    if (!form) { location.reload(); return; }
    form.querySelectorAll('[required]').forEach(function (el) { el.required = false; });
    form.submit();
  });
  bindTimer(document.querySelector('[data-kuis-soal-timer]'), '[data-kuis-soal-timer-value]', function () {
    if (!form) { location.reload(); return; }
    form.querySelectorAll('[required]').forEach(function (el) { el.required = false; });
    form.submit();
  });
})();
</script>
<?php endif; ?>

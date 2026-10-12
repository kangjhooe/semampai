<?php
/**
 * @var list<string> $labels
 * @var list<string> $opsiValues
 * @var list<string> $opsiGambar
 * @var list<string> $opsiGambarUrl
 * @var list<bool> $opsiHasGambar
 * @var int $jawabanIndex
 * @var array<string, string> $fieldErrors
 */
$labels = $labels ?? ['A', 'B', 'C', 'D', 'E', 'F'];
$incoming = is_array($opsiValues ?? null) ? $opsiValues : [];
$incomingGambar = is_array($opsiGambar ?? null) ? $opsiGambar : [];
$incomingUrl = is_array($opsiGambarUrl ?? null) ? $opsiGambarUrl : [];
$incomingHas = is_array($opsiHasGambar ?? null) ? $opsiHasGambar : [];
$opsiValues = ['', '', '', '', '', ''];
$opsiGambar = ['', '', '', '', '', ''];
$opsiGambarUrl = ['', '', '', '', '', ''];
$opsiHasGambar = [false, false, false, false, false, false];
foreach ($incoming as $key => $value) {
    if (!is_int($key) && !(is_string($key) && ctype_digit((string) $key))) {
        continue;
    }
    $n = (int) $key;
    if ($n >= 0 && $n <= 5) {
        $opsiValues[$n] = (string) $value;
    }
}
foreach ($incomingGambar as $key => $value) {
    $n = (int) $key;
    if ($n >= 0 && $n <= 5) {
        $opsiGambar[$n] = (string) $value;
    }
}
foreach ($incomingUrl as $key => $value) {
    $n = (int) $key;
    if ($n >= 0 && $n <= 5) {
        $opsiGambarUrl[$n] = (string) $value;
    }
}
foreach ($incomingHas as $key => $value) {
    $n = (int) $key;
    if ($n >= 0 && $n <= 5) {
        $opsiHasGambar[$n] = (bool) $value;
    }
}
$jawabanIndex = (int) ($jawabanIndex ?? 0);
if ($jawabanIndex < 0 || $jawabanIndex > 5) {
    $jawabanIndex = 0;
}
$fieldErrors = $fieldErrors ?? [];
$lastFilled = -1;
for ($i = 0; $i < 6; $i++) {
    $has = trim($opsiValues[$i]) !== '' || $opsiGambar[$i] !== '' || trim($opsiGambarUrl[$i]) !== '';
    if ($has) {
        $lastFilled = $i;
    }
}
$visibleCount = max(4, $lastFilled + 1);
if ($jawabanIndex >= $visibleCount) {
    $visibleCount = min(6, $jawabanIndex + 1);
}
$visibleCount = min(6, max(2, $visibleCount));
$uid = 'opsi-' . bin2hex(random_bytes(3));
?>
<fieldset class="kuis-opsi-fields" data-kuis-opsi id="<?= e($uid) ?>">
  <legend>Opsi jawaban</legend>
  <p class="muted">Ketuk huruf untuk menandai jawaban benar. Ikon gambar di kanan dipakai kalau opsi berupa gambar.</p>
  <?php for ($i = 0; $i < 6; $i++): ?>
    <?php $mediaOpen = $opsiGambar[$i] !== '' || trim($opsiGambarUrl[$i]) !== ''; ?>
    <div class="kuis-opsi-row" data-opsi-row="<?= $i ?>" <?= $i >= $visibleCount ? 'hidden' : '' ?>>
      <label class="kuis-opsi-radio">
        <input type="radio" name="jawaban_index" value="<?= $i ?>" <?= $jawabanIndex === $i ? 'checked' : '' ?> <?= $i === 0 ? 'required' : '' ?> aria-label="Tandai opsi <?= e($labels[$i]) ?> sebagai jawaban benar">
        <span class="kuis-opsi-mark"><?= e($labels[$i]) ?></span>
      </label>
      <div class="kuis-opsi-main">
        <div class="kuis-opsi-editline">
          <input class="kuis-opsi-input" type="text" name="opsi[<?= $i ?>]" value="<?= e($opsiValues[$i]) ?>" placeholder="Opsi <?= e($labels[$i]) ?>" autocomplete="off">
          <button class="kuis-opsi-media-toggle<?= $mediaOpen ? ' is-open' : '' ?>" type="button" data-opsi-media-toggle aria-expanded="<?= $mediaOpen ? 'true' : 'false' ?>" aria-label="Gambar opsi <?= e($labels[$i]) ?>" title="Gambar opsi">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="9" cy="10" r="1.4" fill="currentColor"/><path d="m4 16 4.2-3.2a1 1 0 0 1 1.3 0L14 16.5l1.6-1.3a1 1 0 0 1 1.3 0L20 17.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
          </button>
        </div>
        <div class="kuis-opsi-media" data-opsi-media <?= $mediaOpen ? '' : 'hidden' ?>>
          <img class="kuis-opsi-thumb" data-opsi-preview alt="Pratinjau opsi <?= e($labels[$i]) ?>" <?= $opsiGambar[$i] !== '' ? 'src="' . e($opsiGambar[$i]) . '"' : 'hidden' ?>>
          <label class="kuis-opsi-upload">
            <input class="kuis-opsi-file" data-opsi-file type="file" name="opsi_gambar[<?= $i ?>]" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
            <span data-opsi-file-name>Unggah</span>
          </label>
          <input class="kuis-opsi-url" data-opsi-url type="url" name="opsi_gambar_url[<?= $i ?>]" value="<?= e($opsiGambarUrl[$i]) ?>" placeholder="atau URL gambar" autocomplete="off">
          <?php if (!empty($opsiHasGambar[$i])): ?>
            <label class="kuis-opsi-clear">
              <input data-opsi-hapus type="checkbox" name="opsi_hapus_gambar[<?= $i ?>]" value="1">
              <span>Hapus</span>
            </label>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endfor; ?>
  <?php /* Tombol manual disembunyikan. Opsi E dan F terbuka sendiri saat opsi sebelumnya terisi.
  <div class="kuis-opsi-toolbar">
    <button class="btn btn-ghost btn-sm" type="button" data-opsi-add>+ Tambah opsi</button>
    <button class="btn btn-ghost btn-sm" type="button" data-opsi-remove hidden>− Kurangi opsi</button>
  </div>
  */ ?>
  <?php if (!empty($fieldErrors['opsi'])): ?><div class="error"><?= e($fieldErrors['opsi']) ?></div><?php endif; ?>
  <?php if (!empty($fieldErrors['jawaban_index'])): ?><div class="error"><?= e($fieldErrors['jawaban_index']) ?></div><?php endif; ?>
</fieldset>

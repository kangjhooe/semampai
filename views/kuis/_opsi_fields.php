<?php
/**
 * @var list<string> $labels
 * @var list<string> $opsiValues
 * @var int $jawabanIndex
 * @var array<string, string> $fieldErrors
 */
$labels = $labels ?? ['A', 'B', 'C', 'D', 'E', 'F'];
$opsiValues = $opsiValues ?? ['', '', '', '', '', ''];
$opsiValues = array_values(array_slice(array_map('strval', $opsiValues), 0, 6));
while (count($opsiValues) < 6) {
    $opsiValues[] = '';
}
$jawabanIndex = (int) ($jawabanIndex ?? 0);
if ($jawabanIndex < 0 || $jawabanIndex > 5) {
    $jawabanIndex = 0;
}
$fieldErrors = $fieldErrors ?? [];
$filledCount = 0;
foreach ($opsiValues as $teksOpsi) {
    if (trim($teksOpsi) !== '') {
        $filledCount++;
    }
}
$visibleCount = max(4, min(6, max(2, $filledCount)));
if ($jawabanIndex >= $visibleCount) {
    $visibleCount = min(6, $jawabanIndex + 1);
}
$uid = 'opsi-' . bin2hex(random_bytes(3));
?>
<fieldset class="kuis-opsi-fields" data-kuis-opsi id="<?= e($uid) ?>">
  <legend>Opsi jawaban</legend>
  <p class="muted">Tersedia A–F. Isi minimal 2 opsi; kosongkan yang tidak dipakai. Radio menandai jawaban benar.</p>
  <?php for ($i = 0; $i < 6; $i++): ?>
    <div class="kuis-opsi-row" data-opsi-row="<?= $i ?>" <?= $i >= $visibleCount ? 'hidden' : '' ?>>
      <label class="kuis-opsi-radio">
        <input type="radio" name="jawaban_index" value="<?= $i ?>" <?= $jawabanIndex === $i ? 'checked' : '' ?> <?= $i === 0 ? 'required' : '' ?>>
        <span><?= e($labels[$i]) ?></span>
      </label>
      <input type="text" name="opsi[]" value="<?= e((string) ($opsiValues[$i] ?? '')) ?>" placeholder="Opsi <?= e($labels[$i]) ?>" <?= $i < 2 ? 'required' : '' ?> autocomplete="off">
    </div>
  <?php endfor; ?>
  <div class="kuis-opsi-toolbar">
    <button class="btn btn-ghost btn-sm" type="button" data-opsi-add>+ Tambah opsi</button>
    <button class="btn btn-ghost btn-sm" type="button" data-opsi-remove hidden>− Kurangi opsi</button>
  </div>
  <?php if (!empty($fieldErrors['opsi'])): ?><div class="error"><?= e($fieldErrors['opsi']) ?></div><?php endif; ?>
  <?php if (!empty($fieldErrors['jawaban_index'])): ?><div class="error"><?= e($fieldErrors['jawaban_index']) ?></div><?php endif; ?>
</fieldset>

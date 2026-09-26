<?php
/**
 * @var int $kelasId
 * @var int $siswaId
 * @var int $suratNomor
 * @var array $cell
 */
$title = $cell['status'] === 'proses'
    ? ('Kurang: ' . HafalanProgress::formatMissing($cell['missing'], 20) . ' — klik untuk setor')
    : ($cell['label'] . ($cell['status'] !== 'tuntas' ? ' — klik untuk setor' : ''));

$label = 'Tuntas';
if ($cell['status'] === 'belum') {
    $label = 'Belum';
} elseif ($cell['status'] === 'proses') {
    $label = 'Kurang ' . (int) $cell['kurang'];
}

$class = 'cell-pill ' . $cell['status'];
?>
<?php if ($cell['status'] === 'tuntas'): ?>
  <span class="<?= e($class) ?>" title="<?= e($title) ?>"><?= e($label) ?></span>
<?php else: ?>
  <a
    class="<?= e($class) ?> is-link"
    href="<?= e(HafalanProgress::setorUrl($kelasId, $siswaId, $suratNomor)) ?>"
    title="<?= e($title) ?>"
  ><?= e($label) ?></a>
<?php endif; ?>

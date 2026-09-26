<?php
/** @var array $daftarKelas */
/** @var array|null $kelas */
/** @var array $surahList */
/** @var list<int> $selected */
/** @var string $judul */
/** @var array<string, string> $errors */
$success = flash('success');
$error = flash('error');
$errors = $errors ?? [];
$selected = $selected ?? [];
$selectedMap = array_fill_keys($selected, true);
?>
<section class="page-head">
  <div>
    <h1>Target hafalan kelas</h1>
    <p class="muted">Tentukan surat wajib yang harus dituntaskan siswa di kelas ini.</p>
  </div>
  <div class="actions">
    <?php if ($kelas): ?>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan_rekap.php?kelas_id=' . $kelas['id'])) ?>">Lihat rekap</a>
    <?php endif; ?>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card"><p class="muted">Belum ada kelas.</p></section>
<?php else: ?>
  <form class="card filter-bar" method="get" action="<?= e(app_url('hafalan_target.php')) ?>">
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
  </form>

  <?php if ($kelas): ?>
    <section class="card stack">
      <?php if (!empty($errors['_form'])): ?>
        <div class="alert error"><?= e($errors['_form']) ?></div>
      <?php endif; ?>

      <div class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('hafalan_target.php?kelas_id=' . $kelas['id'] . '&template=juz30')) ?>">
          Template: 10 surat Juz 30
        </a>
      </div>

      <form class="stack" method="post" action="<?= e(app_url('hafalan_target.php?kelas_id=' . $kelas['id'])) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">

        <div class="field">
          <label for="judul">Judul target</label>
          <input id="judul" type="text" name="judul" value="<?= e($judul) ?>" maxlength="150" required>
          <?php if (!empty($errors['judul'])): ?><div class="error"><?= e($errors['judul']) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label>Pilih surat wajib</label>
          <?php if (!empty($errors['surat'])): ?><div class="error"><?= e($errors['surat']) ?></div><?php endif; ?>
          <div class="surat-pick-toolbar">
            <button type="button" class="btn btn-ghost btn-sm" id="pick-all">Centang semua</button>
            <button type="button" class="btn btn-ghost btn-sm" id="pick-none">Kosongkan</button>
            <span class="muted" id="pick-count"><?= count($selected) ?> dipilih</span>
          </div>
          <div class="surat-pick-grid">
            <?php foreach ($surahList as $surah): ?>
              <label class="surat-pick-item">
                <input
                  type="checkbox"
                  name="surat[]"
                  value="<?= (int) $surah['nomor'] ?>"
                  <?= isset($selectedMap[(int) $surah['nomor']]) ? 'checked' : '' ?>
                >
                <span><?= e($surah['nomor'] . '. ' . $surah['nama']) ?></span>
                <span class="muted"><?= (int) $surah['ayat'] ?> ayat</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Simpan target</button>
      </form>
    </section>

    <script>
      (function () {
        var boxes = Array.prototype.slice.call(document.querySelectorAll('input[name="surat[]"]'));
        var countEl = document.getElementById('pick-count');
        function sync() {
          var n = boxes.filter(function (b) { return b.checked; }).length;
          countEl.textContent = n + ' dipilih';
        }
        boxes.forEach(function (b) { b.addEventListener('change', sync); });
        document.getElementById('pick-all').addEventListener('click', function () {
          boxes.forEach(function (b) { b.checked = true; });
          sync();
        });
        document.getElementById('pick-none').addEventListener('click', function () {
          boxes.forEach(function (b) { b.checked = false; });
          sync();
        });
      })();
    </script>
  <?php endif; ?>
<?php endif; ?>

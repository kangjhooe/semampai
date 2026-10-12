<?php
/** @var array|null $kuis */
/** @var array $daftarKelas */
/** @var list<array> $soalList */
/** @var array<string, string> $errors */
/** @var array<string, string> $soalErrors */
/** @var array|null $editSoal */
$errors = $errors ?? [];
$soalErrors = $soalErrors ?? [];
$soalList = $soalList ?? [];
$editSoal = $editSoal ?? null;
$success = flash('success');
$error = flash('error');
$importNotes = $_SESSION['_kuis_import_errors'] ?? [];
unset($_SESSION['_kuis_import_errors']);
if (!is_array($importNotes)) {
    $importNotes = [];
}
$labels = ['A', 'B', 'C', 'D', 'E', 'F'];
$isEdit = $kuis !== null;
$isEditingSoal = $editSoal !== null;
$metaPosted = isset($_SESSION['_old']['judul']);
$aktifChecked = $metaPosted
    ? isset($_SESSION['_old']['aktif'])
    : ($isEdit ? (int) $kuis['aktif'] === 1 : true);
$acakChecked = $metaPosted
    ? isset($_SESSION['_old']['acak_soal'])
    : ($isEdit ? (int) $kuis['acak_soal'] === 1 : false);
$acakOpsiChecked = $metaPosted
    ? isset($_SESSION['_old']['acak_opsi'])
    : ($isEdit ? (int) ($kuis['acak_opsi'] ?? 0) === 1 : false);
$izinkanUlangChecked = $metaPosted
    ? isset($_SESSION['_old']['izinkan_ulang'])
    : ($isEdit ? (int) ($kuis['izinkan_ulang'] ?? 0) === 1 : false);
$tampilPembahasanChecked = $metaPosted
    ? isset($_SESSION['_old']['tampil_pembahasan'])
    : ($isEdit ? (int) ($kuis['tampil_pembahasan'] ?? 1) === 1 : true);
$modeLatihanChecked = $metaPosted
    ? isset($_SESSION['_old']['mode_latihan'])
    : ($isEdit ? (int) ($kuis['mode_latihan'] ?? 0) === 1 : false);
$selectedKelas = old('kelas_id', $isEdit ? (string) ($kuis['kelas_id'] ?? '') : '');
$timerValue = old(
    'timer_menit',
    $isEdit && !empty($kuis['timer_menit']) ? (string) $kuis['timer_menit'] : ''
);

$soalFormOpsi = ['', '', '', '', '', ''];
$soalFormJawaban = 0;
$soalFormPertanyaan = '';
$soalFormGambarUrl = '';
$soalFormTipe = 'pilihan_ganda';
$soalFormJawabanTeks = '';
$soalFormTimerDetik = '';
$soalOldActive = isset($_SESSION['_old']['pertanyaan']) && !isset($_SESSION['_old']['judul']);
if ($isEditingSoal) {
    if ($soalOldActive) {
        $soalFormPertanyaan = (string) $_SESSION['_old']['pertanyaan'];
        $soalFormOpsi = is_array($_SESSION['_old']['opsi'] ?? null)
            ? array_map('strval', $_SESSION['_old']['opsi'])
            : $soalFormOpsi;
        $soalFormJawaban = (int) ($_SESSION['_old']['jawaban_index'] ?? 0);
        $soalFormGambarUrl = (string) ($_SESSION['_old']['gambar_url'] ?? '');
        $soalFormTipe = (string) ($_SESSION['_old']['tipe'] ?? 'pilihan_ganda');
        $soalFormJawabanTeks = (string) ($_SESSION['_old']['jawaban_teks'] ?? '');
        $soalFormTimerDetik = (string) ($_SESSION['_old']['timer_detik'] ?? '');
    } else {
        $soalFormPertanyaan = (string) $editSoal['pertanyaan'];
        $soalFormOpsi = $editSoal['opsi'];
        $soalFormJawaban = (int) $editSoal['jawaban_index'];
        $soalFormGambarUrl = (string) ($editSoal['gambar_url'] ?? '');
        $soalFormTipe = (string) ($editSoal['tipe'] ?? 'pilihan_ganda');
        $soalFormJawabanTeks = (string) ($editSoal['jawaban_teks'] ?? '');
        $soalFormTimerDetik = !empty($editSoal['timer_detik']) ? (string) $editSoal['timer_detik'] : '';
    }
} elseif ($soalOldActive) {
    $soalFormPertanyaan = (string) $_SESSION['_old']['pertanyaan'];
    $soalFormOpsi = is_array($_SESSION['_old']['opsi'] ?? null)
        ? array_map('strval', $_SESSION['_old']['opsi'])
        : $soalFormOpsi;
    $soalFormJawaban = (int) ($_SESSION['_old']['jawaban_index'] ?? 0);
    $soalFormGambarUrl = (string) ($_SESSION['_old']['gambar_url'] ?? '');
    $soalFormTipe = (string) ($_SESSION['_old']['tipe'] ?? 'pilihan_ganda');
    $soalFormJawabanTeks = (string) ($_SESSION['_old']['jawaban_teks'] ?? '');
    $soalFormTimerDetik = (string) ($_SESSION['_old']['timer_detik'] ?? '');
}
$currentGambar = $isEditingSoal ? Kuis::gambarPublicUrl($editSoal) : null;
?>

<section class="page-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/index.php')) ?>">← Kembali ke daftar kuis</a></p>
  </div>
  <?php if ($isEdit): ?>
    <div class="actions">
      <a class="btn btn-primary" href="<?= e(app_url('kuis/live_host.php?kuis_id=' . $kuis['id'])) ?>">Live</a>
      <a class="btn btn-ghost" href="<?= e(app_url('kuis/import.php?id=' . $kuis['id'])) ?>">Impor soal</a>
      <a class="btn btn-ghost" href="<?= e(app_url('kuis/hasil.php?id=' . $kuis['id'])) ?>">Lihat hasil</a>
      <a class="btn btn-ghost" href="<?= e(app_url('kuis/ikut.php?kode=' . urlencode($kuis['kode']))) ?>" target="_blank" rel="noopener">Uji join</a>
    </div>
  <?php endif; ?>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($importNotes !== []): ?>
  <div class="alert error">
    <strong>Catatan impor:</strong>
    <ul class="compact-list">
      <?php foreach ($importNotes as $msg): ?>
        <li><?= e((string) $msg) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<section class="card stack" style="max-width: 640px;">
  <h2 class="card-title"><?= $isEdit ? 'Pengaturan kuis' : 'Buat kuis baru' ?></h2>

  <?php if ($isEdit): ?>
    <div class="kuis-share">
      <div>
        <span class="muted">ID kuis</span>
        <strong class="kuis-kode-lg"><?= e($kuis['kode']) ?></strong>
      </div>
      <p class="muted" style="margin:0;">Bagikan ID ini ke siswa, atau tautan join di bawah.</p>
      <code class="kuis-link"><?= e(app_url('kuis/ikut.php?kode=' . urlencode($kuis['kode']))) ?></code>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <form class="stack" method="post" action="<?= e(app_url('kuis/form.php' . ($isEdit ? '?id=' . $kuis['id'] : ''))) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="save_meta">
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int) $kuis['id'] ?>">
    <?php endif; ?>

    <div class="field">
      <label for="judul">Judul</label>
      <input id="judul" type="text" name="judul" value="<?= old('judul', $isEdit ? $kuis['judul'] : '') ?>" required maxlength="200">
      <?php if (!empty($errors['judul'])): ?><div class="error"><?= e($errors['judul']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="kelas_id">Kelas (opsional)</label>
      <select id="kelas_id" name="kelas_id">
        <option value="0">— Tanpa kelas —</option>
        <?php foreach ($daftarKelas as $row): ?>
          <option value="<?= (int) $row['id'] ?>" <?= (string) (int) $row['id'] === (string) (int) $selectedKelas ? 'selected' : '' ?>>
            <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['kelas_id'])): ?><div class="error"><?= e($errors['kelas_id']) ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label for="timer_menit">Batas waktu (menit, opsional)</label>
      <input id="timer_menit" type="number" name="timer_menit" min="1" max="180" step="1" value="<?= e($timerValue) ?>" placeholder="Kosongkan = tanpa batas">
      <p class="muted" style="margin:0.35rem 0 0;">Contoh: 15. Saat habis, jawaban terkirim otomatis.</p>
      <?php if (!empty($errors['timer_menit'])): ?><div class="error"><?= e($errors['timer_menit']) ?></div><?php endif; ?>
    </div>

    <div class="kuis-checks">
      <label class="kuis-check">
        <input type="checkbox" name="aktif" value="1" <?= $aktifChecked ? 'checked' : '' ?>>
        <span>Aktif (siswa bisa join)</span>
      </label>
      <label class="kuis-check">
        <input type="checkbox" name="acak_soal" value="1" <?= $acakChecked ? 'checked' : '' ?>>
        <span>Acak urutan soal</span>
      </label>
      <label class="kuis-check">
        <input type="checkbox" name="acak_opsi" value="1" <?= $acakOpsiChecked ? 'checked' : '' ?>>
        <span>Acak urutan opsi jawaban</span>
      </label>
      <label class="kuis-check">
        <input type="checkbox" name="izinkan_ulang" value="1" <?= $izinkanUlangChecked ? 'checked' : '' ?>>
        <span>Izinkan mengerjakan ulang</span>
      </label>
      <label class="kuis-check">
        <input type="checkbox" name="tampil_pembahasan" value="1" <?= $tampilPembahasanChecked ? 'checked' : '' ?>>
        <span>Tampilkan pembahasan setelah selesai</span>
      </label>
      <label class="kuis-check">
        <input type="checkbox" name="mode_latihan" value="1" <?= $modeLatihanChecked ? 'checked' : '' ?>>
        <span>Mode latihan (kunci langsung setelah tiap soal)</span>
      </label>
    </div>
    <p class="muted" style="margin:0;">Jika kuis terikat kelas, siswa join dengan memilih nama dari roster (bukan ketik bebas).</p>

    <div class="actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Simpan pengaturan' : 'Buat kuis' ?></button>
      <a class="btn btn-ghost" href="<?= e(app_url('kuis/index.php')) ?>">Batal</a>
    </div>
  </form>
</section>

<?php if ($isEdit): ?>
  <section class="card stack" style="max-width: 720px;">
    <div class="kuis-hasil-toolbar">
      <h2 class="card-title" style="margin:0;">Daftar soal (<?= count($soalList) ?>)</h2>
      <a class="btn btn-sm btn-ghost" href="<?= e(app_url('kuis/import.php?id=' . $kuis['id'])) ?>">Impor Excel</a>
    </div>

    <?php if ($soalList === []): ?>
      <p class="muted">Belum ada soal. Tambahkan manual atau <a href="<?= e(app_url('kuis/import.php?id=' . $kuis['id'])) ?>">impor dari Excel</a>.</p>
    <?php else: ?>
      <ol class="kuis-soal-list">
        <?php foreach ($soalList as $i => $soal): ?>
          <li class="kuis-soal-item<?= $isEditingSoal && (int) $editSoal['id'] === (int) $soal['id'] ? ' is-editing' : '' ?>">
            <div class="kuis-soal-head">
              <strong>
                <?= $i + 1 ?>. <?= e($soal['pertanyaan']) ?>
                <span class="kuis-badge <?= ($soal['tipe'] ?? '') === 'isian' ? 'kuis-badge-off' : 'kuis-badge-on' ?>">
                  <?= ($soal['tipe'] ?? '') === 'isian' ? 'Isian' : 'PG' ?>
                </span>
                <?php if (!empty($soal['timer_detik'])): ?>
                  <span class="muted" style="font-weight:500;"> · <?= (int) $soal['timer_detik'] ?> dtk</span>
                <?php endif; ?>
              </strong>
              <div class="table-actions">
                <form method="post" action="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="move_soal">
                  <input type="hidden" name="id" value="<?= (int) $kuis['id'] ?>">
                  <input type="hidden" name="soal_id" value="<?= (int) $soal['id'] ?>">
                  <input type="hidden" name="arah" value="up">
                  <button class="btn-icon" type="submit" title="Naik" aria-label="Naik" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                </form>
                <form method="post" action="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="move_soal">
                  <input type="hidden" name="id" value="<?= (int) $kuis['id'] ?>">
                  <input type="hidden" name="soal_id" value="<?= (int) $soal['id'] ?>">
                  <input type="hidden" name="arah" value="down">
                  <button class="btn-icon" type="submit" title="Turun" aria-label="Turun" <?= $i === count($soalList) - 1 ? 'disabled' : '' ?>>↓</button>
                </form>
                <a class="btn-icon" href="<?= e(app_url('kuis/form.php?id=' . $kuis['id'] . '&edit_soal=' . $soal['id'])) ?>" title="Edit soal" aria-label="Edit soal">
                  <?= icon('edit') ?>
                </a>
                <form method="post" action="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>" onsubmit="return confirm('Hapus soal ini?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="delete_soal">
                  <input type="hidden" name="id" value="<?= (int) $kuis['id'] ?>">
                  <input type="hidden" name="soal_id" value="<?= (int) $soal['id'] ?>">
                  <button class="btn-icon danger" type="submit" title="Hapus soal" aria-label="Hapus soal"><?= icon('trash') ?></button>
                </form>
              </div>
            </div>
            <?php $thumb = Kuis::gambarPublicUrl($soal); ?>
            <?php if ($thumb): ?>
              <img class="kuis-soal-thumb" src="<?= e($thumb) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <?php if (($soal['tipe'] ?? '') === 'isian'): ?>
              <p class="muted" style="margin:0.4rem 0 0;">Kunci: <strong><?= e((string) ($soal['jawaban_teks'] ?? '')) ?></strong></p>
            <?php else: ?>
              <ul class="kuis-opsi-preview">
                <?php foreach ($soal['opsi'] as $oi => $teks): ?>
                  <li class="<?= $oi === (int) $soal['jawaban_index'] ? 'is-correct' : '' ?>">
                    <span><?= e($labels[$oi] ?? (string) ($oi + 1)) ?>.</span>
                    <?= e($teks) ?>
                    <?php if ($oi === (int) $soal['jawaban_index']): ?>
                      <em>benar</em>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <section class="card stack" style="max-width: 720px;" id="soal-form">
    <h2 class="card-title"><?= $isEditingSoal ? 'Edit soal' : 'Tambah soal' ?></h2>

    <?php if (!empty($soalErrors['_form'])): ?>
      <div class="alert error"><?= e($soalErrors['_form']) ?></div>
    <?php endif; ?>

    <form class="stack" method="post" action="<?= e(app_url('kuis/form.php?id=' . $kuis['id'] . ($isEditingSoal ? '&edit_soal=' . $editSoal['id'] : ''))) ?>" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="<?= $isEditingSoal ? 'update_soal' : 'add_soal' ?>">
      <input type="hidden" name="id" value="<?= (int) $kuis['id'] ?>">
      <?php if ($isEditingSoal): ?>
        <input type="hidden" name="soal_id" value="<?= (int) $editSoal['id'] ?>">
      <?php endif; ?>

      <div class="field">
        <label for="pertanyaan">Pertanyaan</label>
        <textarea id="pertanyaan" name="pertanyaan" rows="3" required><?= e($soalFormPertanyaan) ?></textarea>
        <?php if (!empty($soalErrors['pertanyaan'])): ?><div class="error"><?= e($soalErrors['pertanyaan']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="tipe">Tipe soal</label>
        <select id="tipe" name="tipe" data-kuis-tipe>
          <option value="pilihan_ganda" <?= $soalFormTipe === 'pilihan_ganda' ? 'selected' : '' ?>>Pilihan ganda</option>
          <option value="isian" <?= $soalFormTipe === 'isian' ? 'selected' : '' ?>>Isian singkat</option>
        </select>
      </div>

      <div class="field">
        <label for="timer_detik">Timer per soal (detik, opsional)</label>
        <input id="timer_detik" type="number" name="timer_detik" min="5" max="600" step="1" value="<?= e($soalFormTimerDetik) ?>" placeholder="Kosongkan = tanpa batas per soal">
        <?php if (!empty($soalErrors['timer_detik'])): ?><div class="error"><?= e($soalErrors['timer_detik']) ?></div><?php endif; ?>
      </div>

      <div class="field" data-kuis-isian <?= $soalFormTipe === 'isian' ? '' : 'hidden' ?>>
        <label for="jawaban_teks">Kunci jawaban isian</label>
        <input id="jawaban_teks" type="text" name="jawaban_teks" value="<?= e($soalFormJawabanTeks) ?>" maxlength="255" placeholder="Contoh: Jakarta">
        <p class="muted" style="margin:0.35rem 0 0;">Pencocokan tanpa membedakan huruf besar/kecil.</p>
        <?php if (!empty($soalErrors['jawaban_teks'])): ?><div class="error"><?= e($soalErrors['jawaban_teks']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="gambar">Gambar soal (opsional)</label>
        <?php if ($currentGambar): ?>
          <div class="kuis-gambar-current">
            <img src="<?= e($currentGambar) ?>" alt="Gambar soal saat ini">
          </div>
        <?php endif; ?>
        <input id="gambar" type="file" name="gambar" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
        <p class="muted" style="margin:0.35rem 0 0;">JPG/PNG/GIF/WEBP, maks. 2 MB. Atau isi URL di bawah.</p>
        <?php if (!empty($soalErrors['gambar'])): ?><div class="error"><?= e($soalErrors['gambar']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="gambar_url">URL gambar (opsional)</label>
        <input id="gambar_url" type="url" name="gambar_url" value="<?= e($soalFormGambarUrl) ?>" placeholder="https://…">
        <?php if (!empty($soalErrors['gambar_url'])): ?><div class="error"><?= e($soalErrors['gambar_url']) ?></div><?php endif; ?>
      </div>

      <?php if ($currentGambar): ?>
        <label class="kuis-check">
          <input type="checkbox" name="hapus_gambar" value="1">
          <span>Hapus gambar dari soal ini</span>
        </label>
      <?php endif; ?>

      <div data-kuis-pg <?= $soalFormTipe === 'isian' ? 'hidden' : '' ?>>
        <?php
        $opsiValues = $soalFormOpsi;
        $jawabanIndex = $soalFormJawaban;
        $fieldErrors = $soalErrors;
        require BASE_PATH . '/views/kuis/_opsi_fields.php';
        ?>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit"><?= $isEditingSoal ? 'Simpan soal' : 'Tambah soal' ?></button>
        <?php if ($isEditingSoal): ?>
          <a class="btn btn-ghost" href="<?= e(app_url('kuis/form.php?id=' . $kuis['id'])) ?>">Batal edit</a>
        <?php endif; ?>
      </div>
    </form>
  </section>
  <script>
  (function () {
    var tipe = document.querySelector('[data-kuis-tipe]');
    var pg = document.querySelector('[data-kuis-pg]');
    var isian = document.querySelector('[data-kuis-isian]');
    function syncTipe() {
      if (!tipe) return;
      var isIsian = tipe.value === 'isian';
      if (pg) pg.hidden = isIsian;
      if (isian) isian.hidden = !isIsian;
      if (pg) {
        pg.querySelectorAll('input[required]').forEach(function (el) { el.required = !isIsian; });
      }
      var jt = document.getElementById('jawaban_teks');
      if (jt) jt.required = isIsian;
    }
    if (tipe) {
      tipe.addEventListener('change', syncTipe);
      syncTipe();
    }

    function bindOpsi(root) {
      var rows = Array.prototype.slice.call(root.querySelectorAll('[data-opsi-row]'));
      var btnAdd = root.querySelector('[data-opsi-add]');
      var btnRemove = root.querySelector('[data-opsi-remove]');

      function visibleCount() {
        return rows.filter(function (row) { return !row.hidden; }).length;
      }

      function sync() {
        var n = visibleCount();
        if (btnAdd) btnAdd.hidden = n >= 6;
        if (btnRemove) btnRemove.hidden = n <= 2;
        rows.forEach(function (row, i) {
          var input = row.querySelector('input[type="text"]');
          if (!input) return;
          input.required = !row.hidden && i < 2;
          if (row.hidden) {
            input.value = '';
            var radio = row.querySelector('input[type="radio"]');
            if (radio && radio.checked) {
              var first = rows[0].querySelector('input[type="radio"]');
              if (first) first.checked = true;
            }
          }
        });
      }

      if (btnAdd) {
        btnAdd.addEventListener('click', function () {
          var n = visibleCount();
          if (n >= 6) return;
          rows[n].hidden = false;
          sync();
          var input = rows[n].querySelector('input[type="text"]');
          if (input) input.focus();
        });
      }

      if (btnRemove) {
        btnRemove.addEventListener('click', function () {
          var n = visibleCount();
          if (n <= 2) return;
          rows[n - 1].hidden = true;
          sync();
        });
      }

      sync();
    }

    document.querySelectorAll('[data-kuis-opsi]').forEach(bindOpsi);
    <?php if ($isEditingSoal || $soalErrors !== []): ?>
    var form = document.getElementById('soal-form');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    <?php endif; ?>
  })();
  </script>
<?php endif; ?>

<?php
/** @var array $hafalan */
/** @var array|null $kelas */
/** @var array $siswaList */
/** @var array $surahList */
/** @var array|null $suratData */
/** @var int $selectedSurat */
/** @var list<int> $selectedAyat */
/** @var string $tampilMode */
/** @var string $apiSuratUrl */
/** @var array<string, string> $errors */
/** @var string $selectedSiswa */
/** @var string $returnTo */
$errors = $errors ?? [];
$selectedSiswa = (string) ($selectedSiswa ?? $hafalan['siswa_id']);
$catatan = old('catatan', (string) ($hafalan['catatan'] ?? ''));
$showTerjemah = ($tampilMode ?? 'arab_terjemah') === 'arab_terjemah';
$returnTo = (string) ($returnTo ?? 'riwayat');
$statusOld = (string) ($_SESSION['_old']['status'] ?? $hafalan['status']);
$backUrl = $returnTo === 'siswa'
    ? app_url('hafalan/siswa.php?siswa_id=' . (int) $hafalan['siswa_id'])
    : app_url('hafalan/riwayat.php?kelas_id=' . (int) $hafalan['kelas_id']);
?>
<section class="page-head">
  <div>
    <p class="muted">
      Koreksi setoran ·
      <?= e((string) ($kelas['nama'] ?? $hafalan['kelas_nama'])) ?> ·
      <a href="<?= e($backUrl) ?>">← Kembali</a>
    </p>
  </div>
</section>

<section class="card stack hafalan-card">
  <?php if (!empty($errors['_form'])): ?>
    <div class="alert error"><?= e($errors['_form']) ?></div>
  <?php endif; ?>

  <form class="stack" method="post" action="<?= e(app_url('hafalan/edit.php?id=' . (int) $hafalan['id'])) ?>" id="form-hafalan">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $hafalan['id'] ?>">
    <input type="hidden" name="return" value="<?= e($returnTo) ?>">
    <input type="hidden" name="tampil" id="tampil-input" value="<?= e($tampilMode) ?>">

    <div class="hafalan-step">
      <span class="step-badge">1</span>
      <div class="field">
        <label for="siswa_id">Nama siswa</label>
        <select id="siswa_id" name="siswa_id" required>
          <?php foreach ($siswaList as $siswa): ?>
            <option value="<?= (int) $siswa['id'] ?>" <?= $selectedSiswa === (string) $siswa['id'] ? 'selected' : '' ?>>
              <?= e($siswa['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['siswa_id'])): ?><div class="error"><?= e($errors['siswa_id']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="hafalan-step">
      <span class="step-badge">2</span>
      <div class="stack">
        <div class="field">
          <label for="surat_nomor">Surat</label>
          <select id="surat_nomor" name="surat_nomor" required>
            <?php foreach ($surahList as $surah): ?>
              <option
                value="<?= (int) $surah['nomor'] ?>"
                data-ayat="<?= (int) $surah['ayat'] ?>"
                <?= (int) $selectedSurat === (int) $surah['nomor'] ? 'selected' : '' ?>
              >
                <?= e($surah['nomor'] . '. ' . $surah['nama']) ?> (<?= (int) $surah['ayat'] ?> ayat)
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['surat_nomor'])): ?><div class="error"><?= e($errors['surat_nomor']) ?></div><?php endif; ?>
        </div>

        <div class="tampil-toggle" role="group" aria-label="Opsi tampilan ayat">
          <button type="button" class="tampil-btn<?= !$showTerjemah ? ' is-active' : '' ?>" data-tampil="arab">Ayat saja</button>
          <button type="button" class="tampil-btn<?= $showTerjemah ? ' is-active' : '' ?>" data-tampil="arab_terjemah">Ayat + terjemah</button>
        </div>

        <div class="ayat-toolbar">
          <button type="button" class="btn btn-ghost btn-sm" id="btn-ayat-semua">Centang semua</button>
          <button type="button" class="btn btn-ghost btn-sm" id="btn-ayat-kosong">Kosongkan</button>
          <span class="muted" id="ayat-status">Memuat ayat…</span>
        </div>

        <div id="ayat-list" class="ayat-list<?= $showTerjemah ? ' show-terjemah' : '' ?>" aria-live="polite"></div>
        <?php if (!empty($errors['ayat'])): ?><div class="error"><?= e($errors['ayat']) ?></div><?php endif; ?>
        <p class="hint">Centang ayat berurutan (tanpa loncat).</p>
      </div>
    </div>

    <div class="hafalan-step">
      <span class="step-badge">3</span>
      <div class="stack">
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status" required>
            <option value="lancar" <?= $statusOld === 'lancar' ? 'selected' : '' ?>>Lancar</option>
            <option value="ulang" <?= $statusOld === 'ulang' ? 'selected' : '' ?>>Ulang</option>
          </select>
          <?php if (!empty($errors['status'])): ?><div class="error"><?= e($errors['status']) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="catatan">Catatan <span class="muted">(opsional)</span></label>
          <input id="catatan" type="text" name="catatan" maxlength="255" value="<?= $catatan ?>" placeholder="Mis. tajwid masih lemah">
          <?php if (!empty($errors['catatan'])): ?><div class="error"><?= e($errors['catatan']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" type="submit">Simpan perubahan</button>
      <a class="btn btn-ghost" href="<?= e($backUrl) ?>">Batal</a>
    </div>
  </form>
</section>

<script>
(function () {
  var apiUrl = <?= json_encode($apiSuratUrl, JSON_UNESCAPED_SLASHES) ?>;
  var initialData = <?= json_encode($suratData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var selectedAyat = <?= json_encode(array_values($selectedAyat)) ?>;
  var suratSelect = document.getElementById('surat_nomor');
  var listEl = document.getElementById('ayat-list');
  var statusEl = document.getElementById('ayat-status');
  var tampilInput = document.getElementById('tampil-input');
  var cache = {};

  if (initialData && initialData.nomor) {
    cache[String(initialData.nomor)] = initialData;
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function currentSelected() {
    return Array.prototype.map.call(
      listEl.querySelectorAll('input[name="ayat[]"]:checked'),
      function (el) { return parseInt(el.value, 10); }
    );
  }

  function render(data, keepSelection) {
    if (!data || !data.ayat) {
      listEl.innerHTML = '<p class="muted">Teks ayat belum tersedia.</p>';
      statusEl.textContent = 'Gagal memuat';
      return;
    }

    var keep = keepSelection ? currentSelected() : selectedAyat.slice();
    var keepMap = {};
    keep.forEach(function (n) { keepMap[String(n)] = true; });
    var html = '';
    data.ayat.forEach(function (ayat) {
      var checked = keepMap[String(ayat.nomor)] ? ' checked' : '';
      html += '<label class="ayat-item">' +
        '<input type="checkbox" name="ayat[]" value="' + ayat.nomor + '"' + checked + '>' +
        '<span class="ayat-num">' + ayat.nomor + '</span>' +
        '<span class="ayat-body">' +
          '<span class="ayat-arab" dir="rtl" lang="ar">' + escapeHtml(ayat.arab) + '</span>' +
          '<span class="ayat-terjemah">' + escapeHtml(ayat.indonesia) + '</span>' +
        '</span>' +
      '</label>';
    });
    listEl.innerHTML = html;
    statusEl.textContent = data.jumlah_ayat + ' ayat · ' + (data.nama_latin || '');
    selectedAyat = [];
  }

  function loadSurat(nomor, keepSelection) {
    var key = String(nomor);
    if (cache[key]) {
      render(cache[key], keepSelection);
      return;
    }

    statusEl.textContent = 'Memuat ayat…';
    listEl.innerHTML = '<p class="muted">Mengambil teks dari cache/API…</p>';

    fetch(apiUrl + '?nomor=' + encodeURIComponent(nomor), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json.ok || !json.data) {
          throw new Error(json.message || 'Gagal');
        }
        cache[key] = json.data;
        render(json.data, keepSelection);
      })
      .catch(function () {
        listEl.innerHTML = '<p class="error">Gagal memuat ayat. Coba pilih surat lagi.</p>';
        statusEl.textContent = 'Gagal memuat';
      });
  }

  suratSelect.addEventListener('change', function () {
    selectedAyat = [];
    loadSurat(suratSelect.value, false);
  });

  document.querySelectorAll('.tampil-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var mode = btn.getAttribute('data-tampil');
      tampilInput.value = mode;
      document.querySelectorAll('.tampil-btn').forEach(function (b) {
        b.classList.toggle('is-active', b === btn);
      });
      listEl.classList.toggle('show-terjemah', mode === 'arab_terjemah');
    });
  });

  document.getElementById('btn-ayat-semua').addEventListener('click', function () {
    listEl.querySelectorAll('input[name="ayat[]"]').forEach(function (el) {
      el.checked = true;
    });
  });

  document.getElementById('btn-ayat-kosong').addEventListener('click', function () {
    listEl.querySelectorAll('input[name="ayat[]"]').forEach(function (el) {
      el.checked = false;
    });
  });

  loadSurat(suratSelect.value, false);
})();
</script>

<?php
/** @var array $daftarKelas */
/** @var array|null $kelas */
/** @var array $siswaList */
/** @var array $surahList */
/** @var array|null $suratData */
/** @var int $selectedSurat */
/** @var list<int> $selectedAyat */
/** @var string $tampilMode */
/** @var string $apiSuratUrl */
/** @var array $recent */
/** @var array<string, string> $errors */
/** @var string|null $selectedSiswa */
/** @var bool $fromRekap */
/** @var bool $prefillHint */
$success = flash('success');
$error = flash('error');
$errors = $errors ?? [];
$selectedSiswa = (string) ($selectedSiswa ?? ($_SESSION['_old']['siswa_id'] ?? ''));
$catatan = old('catatan');
$showTerjemah = ($tampilMode ?? 'arab_terjemah') === 'arab_terjemah';
$fromRekap = !empty($fromRekap);
$prefillHint = !empty($prefillHint);
?>
<section class="page-head">
  <div>
    <h1>Setoran hafalan</h1>
    <p class="muted">Centang ayat yang disetor, lalu pilih Lancar atau Ulang.</p>
  </div>
  <div class="actions">
    <?php if ($kelas): ?>
      <?php if ($fromRekap): ?>
        <a class="btn btn-ghost" href="<?= e(app_url('hafalan_rekap.php?kelas_id=' . $kelas['id'] . '&view=siswa')) ?>">← Rekap</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan_riwayat.php?kelas_id=' . $kelas['id'])) ?>">Riwayat</a>
    <?php endif; ?>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card">
    <p class="muted">Belum ada kelas. <a href="<?= e(app_url('kelas.php')) ?>">Buat kelas dulu</a>.</p>
  </section>
<?php else: ?>

  <form class="stack hafalan-form" method="get" action="<?= e(app_url('hafalan.php')) ?>">
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

  <?php if ($siswaList === []): ?>
    <section class="card">
      <p class="muted">Kelas ini belum punya siswa. <a href="<?= e(app_url('siswa.php?kelas_id=' . $kelas['id'])) ?>">Tambah siswa</a>.</p>
    </section>
  <?php else: ?>
    <section class="card stack hafalan-card">
      <?php if ($prefillHint): ?>
        <div class="alert success">Siap menyimak — siswa & surat sudah dipilih. Ayat yang sudah lancar (dari awal berurutan) sudah dicentang.</div>
      <?php endif; ?>
      <?php if (!empty($errors['_form'])): ?>
        <div class="alert error"><?= e($errors['_form']) ?></div>
      <?php endif; ?>

      <form class="stack" method="post" action="<?= e(app_url('hafalan.php?kelas_id=' . $kelas['id'])) ?>" id="form-hafalan">
        <?= Csrf::field() ?>
        <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">
        <input type="hidden" name="tampil" id="tampil-input" value="<?= e($tampilMode) ?>">
        <?php if ($fromRekap): ?>
          <input type="hidden" name="from_rekap" value="1">
        <?php endif; ?>

        <div class="hafalan-step">
          <span class="step-badge">1</span>
          <div class="field">
            <label for="siswa_id">Nama siswa</label>
            <select id="siswa_id" name="siswa_id" required autofocus>
              <option value="">— Pilih siswa —</option>
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
            <p class="hint">Centang ayat berurutan (tanpa loncat), misalnya 1–3 atau 4–7.</p>
          </div>
        </div>

        <div class="hafalan-step">
          <span class="step-badge">3</span>
          <div class="stack">
            <label class="field-label">Status</label>
            <div class="status-grid">
              <button class="btn-status lancar" type="submit" name="status" value="lancar">Lancar</button>
              <button class="btn-status ulang" type="submit" name="status" value="ulang">Ulang</button>
            </div>
            <?php if (!empty($errors['status'])): ?><div class="error"><?= e($errors['status']) ?></div><?php endif; ?>

            <div class="field">
              <label for="catatan">Catatan <span class="muted">(opsional, 1 kalimat)</span></label>
              <input id="catatan" type="text" name="catatan" maxlength="255" value="<?= $catatan ?>" placeholder="Mis. tajwid masih lemah">
              <?php if (!empty($errors['catatan'])): ?><div class="error"><?= e($errors['catatan']) ?></div><?php endif; ?>
            </div>
          </div>
        </div>
      </form>
    </section>

    <?php if ($recent !== []): ?>
      <section class="card">
        <h2 class="card-title">Setoran terakhir</h2>
        <ul class="recent-list">
          <?php foreach ($recent as $row): ?>
            <li>
              <strong><?= e($row['siswa_nama']) ?></strong>
              <span><?= e(Hafalan::formatLabel($row['surat_nama'], (int) $row['ayat_awal'], (int) $row['ayat_akhir'], $row['status'])) ?></span>
              <span class="muted"><?= e(date('d/m H:i', strtotime($row['created_at']))) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

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

          // keepSelection hanya setelah list sudah terisi; load awal pakai selectedAyat dari server
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

        // false = pakai selectedAyat prefill (bukan currentSelected yang masih kosong)
        loadSurat(suratSelect.value, false);
      })();
    </script>
  <?php endif; ?>
<?php endif; ?>

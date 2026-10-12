<?php
/** @var array $daftarKelas */
/** @var array|null $kelas */
/** @var array $siswaList */
/** @var array $surahList */
/** @var array|null $suratData */
/** @var int $selectedSurat */
/** @var list<int> $selectedAyat */
/** @var string $tampilMode */
/** @var bool $fokusMode */
/** @var string $jenis */
/** @var array<int, true> $setorHariIni */
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
$fokusMode = !empty($fokusMode);
$jenis = ($jenis ?? 'setoran') === 'murojaah' ? 'murojaah' : 'setoran';
$setorHariIni = $setorHariIni ?? [];
$isMurojaah = $jenis === 'murojaah';

$sudahCount = 0;
foreach ($siswaList as $s) {
    if (isset($setorHariIni[(int) $s['id']])) {
        $sudahCount++;
    }
}
$totalSiswa = count($siswaList);
?>
<?php if ($fokusMode): ?>
  <script>document.body.classList.add('hafalan-fokus-on');</script>
<?php endif; ?>

<section class="page-head fokus-hide">
  <div>
    <p class="muted">
      <?= $isMurojaah
        ? 'Mode muroja\'ah — ulang surat yang sudah tuntas.'
        : 'Antrian siswa · centang ayat · Lancar / Ulang (pintasan L / U).' ?>
    </p>
  </div>
  <div class="actions">
    <?php if ($kelas): ?>
      <?php if ($fromRekap): ?>
        <a class="btn btn-ghost" href="<?= e(app_url('hafalan/rekap.php?kelas_id=' . $kelas['id'] . '&view=siswa')) ?>">← Rekap</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan/murojaah.php?kelas_id=' . $kelas['id'])) ?>">Muroja'ah</a>
      <a class="btn btn-ghost" href="<?= e(app_url('hafalan/riwayat.php?kelas_id=' . $kelas['id'])) ?>">Riwayat</a>
    <?php endif; ?>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($daftarKelas === []): ?>
  <section class="card">
    <p class="muted">Belum ada kelas. <a href="<?= e(app_url('kelas/index.php')) ?>">Buat kelas dulu</a>.</p>
  </section>
<?php else: ?>

  <form class="stack hafalan-form fokus-hide" method="get" action="<?= e(app_url('hafalan/index.php')) ?>">
    <div class="hafalan-topbar">
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
      <?php if ($kelas && $totalSiswa > 0): ?>
        <p class="hafalan-progress-pill muted">
          Hari ini <strong><?= $sudahCount ?></strong>/<?= $totalSiswa ?> siswa
        </p>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($siswaList === []): ?>
    <section class="card">
      <p class="muted">Kelas ini belum punya siswa. <a href="<?= e(app_url('siswa/index.php?kelas_id=' . $kelas['id'])) ?>">Tambah siswa</a>.</p>
    </section>
  <?php else: ?>
    <section class="card stack hafalan-card<?= $fokusMode ? ' is-fokus' : '' ?>">
      <?php if ($prefillHint && !$isMurojaah): ?>
        <div class="alert success fokus-hide">Siap menyimak — ayat yang belum lancar (berurutan) sudah dicentang.</div>
      <?php endif; ?>
      <?php if ($isMurojaah): ?>
        <div class="alert success">Mode muroja'ah — seluruh ayat surat siap dicentang. Lancar = tetap hafal, Ulang = perlu diulang lagi.</div>
      <?php endif; ?>
      <?php if (!empty($errors['_form'])): ?>
        <div class="alert error"><?= e($errors['_form']) ?></div>
      <?php endif; ?>

      <div class="siswa-queue" role="listbox" aria-label="Antrian siswa">
        <?php foreach ($siswaList as $siswa): ?>
          <?php
            $sid = (int) $siswa['id'];
            $isActive = $selectedSiswa === (string) $sid;
            $done = isset($setorHariIni[$sid]);
          ?>
          <button
            type="button"
            class="siswa-chip<?= $isActive ? ' is-active' : '' ?><?= $done ? ' is-done' : '' ?>"
            data-siswa-id="<?= $sid ?>"
            role="option"
            aria-selected="<?= $isActive ? 'true' : 'false' ?>"
          >
            <span><?= e($siswa['nama']) ?></span>
            <?php if ($done): ?><span class="chip-dot" aria-label="Sudah setor hari ini"></span><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>

      <form class="stack" method="post" action="<?= e(app_url('hafalan/index.php?kelas_id=' . $kelas['id'])) ?>" id="form-hafalan">
        <?= Csrf::field() ?>
        <input type="hidden" name="kelas_id" value="<?= (int) $kelas['id'] ?>">
        <input type="hidden" name="tampil" id="tampil-input" value="<?= e($tampilMode) ?>">
        <input type="hidden" name="fokus" id="fokus-input" value="<?= $fokusMode ? '1' : '0' ?>">
        <input type="hidden" name="jenis" id="jenis-input" value="<?= e($jenis) ?>">
        <input type="hidden" name="advance_next" id="advance-next" value="0">
        <?php if ($fromRekap): ?>
          <input type="hidden" name="from_rekap" value="1">
        <?php endif; ?>

        <div class="hafalan-toolbar">
          <div class="jenis-toggle" role="group" aria-label="Jenis aktivitas">
            <button type="button" class="tampil-btn<?= !$isMurojaah ? ' is-active' : '' ?>" data-jenis="setoran">Setoran</button>
            <button type="button" class="tampil-btn<?= $isMurojaah ? ' is-active' : '' ?>" data-jenis="murojaah">Muroja'ah</button>
          </div>
          <div class="hafalan-toolbar-actions">
            <button type="button" class="btn btn-ghost btn-sm" id="btn-fokus" aria-pressed="<?= $fokusMode ? 'true' : 'false' ?>">
              <?= $fokusMode ? 'Keluar fokus' : 'Mode fokus' ?>
            </button>
            <button type="button" class="btn btn-ghost btn-sm" id="btn-next-siswa">Siswa berikutnya</button>
          </div>
        </div>

        <div class="hafalan-step fokus-compact">
          <span class="step-badge fokus-hide">1</span>
          <div class="field">
            <label for="siswa_id">Nama siswa</label>
            <select id="siswa_id" name="siswa_id" required<?= $selectedSiswa === '' ? ' autofocus' : '' ?>>
              <option value="">— Pilih siswa —</option>
              <?php foreach ($siswaList as $siswa): ?>
                <option value="<?= (int) $siswa['id'] ?>" <?= $selectedSiswa === (string) $siswa['id'] ? 'selected' : '' ?>>
                  <?= e($siswa['nama']) ?><?= isset($setorHariIni[(int) $siswa['id']]) ? ' · ✓' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['siswa_id'])): ?><div class="error"><?= e($errors['siswa_id']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="hafalan-step fokus-compact">
          <span class="step-badge fokus-hide">2</span>
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
            <p class="hint fokus-hide">Centang ayat berurutan (tanpa loncat). Pintasan: <kbd>L</kbd> Lancar · <kbd>U</kbd> Ulang · <kbd>N</kbd> siswa berikutnya.</p>
          </div>
        </div>

        <div class="hafalan-step hafalan-status-step">
          <span class="step-badge fokus-hide">3</span>
          <div class="stack">
            <label class="field-label fokus-hide">Status</label>
            <div class="status-grid">
              <button class="btn-status lancar" type="submit" name="status" value="lancar" id="btn-lancar">Lancar <span class="kbd-hint">L</span></button>
              <button class="btn-status ulang" type="submit" name="status" value="ulang" id="btn-ulang">Ulang <span class="kbd-hint">U</span></button>
            </div>
            <?php if (!empty($errors['status'])): ?><div class="error"><?= e($errors['status']) ?></div><?php endif; ?>

            <div class="field fokus-hide">
              <label for="catatan">Catatan <span class="muted">(opsional, 1 kalimat)</span></label>
              <input id="catatan" type="text" name="catatan" maxlength="255" value="<?= $catatan ?>" placeholder="Mis. tajwid masih lemah">
              <?php if (!empty($errors['catatan'])): ?><div class="error"><?= e($errors['catatan']) ?></div><?php endif; ?>
            </div>

            <label class="advance-check fokus-hide">
              <input type="checkbox" id="advance-check" checked>
              Setelah simpan, lanjut ke siswa berikutnya yang belum setor hari ini
            </label>
          </div>
        </div>
      </form>
    </section>

    <?php if ($recent !== [] && !$fokusMode): ?>
      <section class="card">
        <h2 class="card-title">Setoran terakhir</h2>
        <ul class="recent-list">
          <?php foreach ($recent as $row): ?>
            <li>
              <strong><?= e($row['siswa_nama']) ?></strong>
              <span>
                <?php if (($row['jenis'] ?? 'setoran') === 'murojaah'): ?>Muroja'ah · <?php endif; ?>
                <?= e(Hafalan::formatLabel($row['surat_nama'], (int) $row['ayat_awal'], (int) $row['ayat_akhir'], $row['status'])) ?>
              </span>
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
        var isMurojaah = <?= $isMurojaah ? 'true' : 'false' ?>;
        var fokusMode = <?= $fokusMode ? 'true' : 'false' ?>;
        var suratSelect = document.getElementById('surat_nomor');
        var siswaSelect = document.getElementById('siswa_id');
        var listEl = document.getElementById('ayat-list');
        var statusEl = document.getElementById('ayat-status');
        var tampilInput = document.getElementById('tampil-input');
        var fokusInput = document.getElementById('fokus-input');
        var jenisInput = document.getElementById('jenis-input');
        var advanceNext = document.getElementById('advance-next');
        var advanceCheck = document.getElementById('advance-check');
        var form = document.getElementById('form-hafalan');
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

        function syncChips() {
          var val = siswaSelect.value;
          document.querySelectorAll('.siswa-chip').forEach(function (chip) {
            var active = chip.getAttribute('data-siswa-id') === val;
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-selected', active ? 'true' : 'false');
          });
        }

        function render(data, keepSelection) {
          if (!data || !data.ayat) {
            listEl.innerHTML = '<p class="muted">Teks ayat belum tersedia.</p>';
            statusEl.textContent = 'Gagal memuat';
            return;
          }

          var keep = keepSelection ? currentSelected() : selectedAyat.slice();
          if (!keepSelection && isMurojaah && keep.length === 0) {
            keep = data.ayat.map(function (a) { return a.nomor; });
          }
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

        function selectSiswa(id) {
          siswaSelect.value = String(id);
          syncChips();
          siswaSelect.focus();
        }

        function nextSiswa() {
          var options = Array.prototype.filter.call(siswaSelect.options, function (o) {
            return o.value !== '';
          });
          if (!options.length) return;
          var idx = -1;
          for (var i = 0; i < options.length; i++) {
            if (options[i].value === siswaSelect.value) {
              idx = i;
              break;
            }
          }
          var next = options[(idx + 1) % options.length];
          selectSiswa(next.value);
        }

        function setJenis(mode) {
          isMurojaah = mode === 'murojaah';
          jenisInput.value = isMurojaah ? 'murojaah' : 'setoran';
          document.querySelectorAll('[data-jenis]').forEach(function (b) {
            b.classList.toggle('is-active', b.getAttribute('data-jenis') === mode);
          });
          if (isMurojaah) {
            selectedAyat = [];
            loadSurat(suratSelect.value, false);
          }
        }

        function toggleFokus() {
          fokusMode = !fokusMode;
          fokusInput.value = fokusMode ? '1' : '0';
          document.body.classList.toggle('hafalan-fokus-on', fokusMode);
          var card = document.querySelector('.hafalan-card');
          if (card) card.classList.toggle('is-fokus', fokusMode);
          var btn = document.getElementById('btn-fokus');
          if (btn) {
            btn.textContent = fokusMode ? 'Keluar fokus' : 'Mode fokus';
            btn.setAttribute('aria-pressed', fokusMode ? 'true' : 'false');
          }
        }

        suratSelect.addEventListener('change', function () {
          selectedAyat = [];
          loadSurat(suratSelect.value, false);
        });

        siswaSelect.addEventListener('change', syncChips);

        document.querySelectorAll('.siswa-chip').forEach(function (chip) {
          chip.addEventListener('click', function () {
            selectSiswa(chip.getAttribute('data-siswa-id'));
          });
        });

        document.querySelectorAll('.tampil-btn[data-tampil]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-tampil');
            tampilInput.value = mode;
            document.querySelectorAll('.tampil-btn[data-tampil]').forEach(function (b) {
              b.classList.toggle('is-active', b === btn);
            });
            listEl.classList.toggle('show-terjemah', mode === 'arab_terjemah');
          });
        });

        document.querySelectorAll('[data-jenis]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            setJenis(btn.getAttribute('data-jenis'));
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

        document.getElementById('btn-next-siswa').addEventListener('click', nextSiswa);
        document.getElementById('btn-fokus').addEventListener('click', toggleFokus);

        form.addEventListener('submit', function () {
          advanceNext.value = advanceCheck && advanceCheck.checked ? '1' : '0';
        });

        document.addEventListener('keydown', function (e) {
          var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
          if (tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable)) {
            return;
          }
          if (e.key === 'l' || e.key === 'L') {
            e.preventDefault();
            document.getElementById('btn-lancar').click();
          } else if (e.key === 'u' || e.key === 'U') {
            e.preventDefault();
            document.getElementById('btn-ulang').click();
          } else if (e.key === 'n' || e.key === 'N') {
            e.preventDefault();
            nextSiswa();
          } else if (e.key === 'f' || e.key === 'F') {
            e.preventDefault();
            toggleFokus();
          } else if (e.key === 'Escape' && fokusMode) {
            e.preventDefault();
            toggleFokus();
          }
        });

        syncChips();
        loadSurat(suratSelect.value, false);
      })();
    </script>
  <?php endif; ?>
<?php endif; ?>

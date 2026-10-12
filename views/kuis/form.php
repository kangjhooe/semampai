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
$soalFormOpsiGambar = ['', '', '', '', '', ''];
$soalFormOpsiUrl = ['', '', '', '', '', ''];
$soalFormOpsiHasGambar = [false, false, false, false, false, false];
if ($isEditingSoal && !$soalOldActive) {
    $teksList = [];
    foreach ($editSoal['opsi'] as $i => $item) {
        $n = (int) $i;
        if ($n < 0 || $n > 5) {
            continue;
        }
        $teksList[$n] = Kuis::opsiTeks($item);
        $src = Kuis::opsiGambarSrc((int) $editSoal['id'], $item, $n) ?? '';
        $soalFormOpsiGambar[$n] = $src;
        $soalFormOpsiUrl[$n] = is_array($item) ? trim((string) ($item['gambar_url'] ?? '')) : '';
        $soalFormOpsiHasGambar[$n] = $src !== '';
    }
    $soalFormOpsi = $teksList;
} else {
    $oldUrls = $_SESSION['_old']['opsi_gambar_url'] ?? [];
    $oldHapus = $_SESSION['_old']['opsi_hapus_gambar'] ?? [];
    if (is_array($oldUrls)) {
        foreach ($oldUrls as $i => $url) {
            $n = (int) $i;
            if ($n >= 0 && $n <= 5) {
                $soalFormOpsiUrl[$n] = trim((string) $url);
            }
        }
    }
    if ($isEditingSoal) {
        foreach ($editSoal['opsi'] as $i => $item) {
            $n = (int) $i;
            if ($n < 0 || $n > 5) {
                continue;
            }
            if (is_array($oldHapus) && !empty($oldHapus[$n])) {
                continue;
            }
            if ($soalFormOpsiUrl[$n] !== '') {
                $soalFormOpsiGambar[$n] = $soalFormOpsiUrl[$n];
                $soalFormOpsiHasGambar[$n] = true;
                continue;
            }
            $src = Kuis::opsiGambarSrc((int) $editSoal['id'], $item, $n) ?? '';
            if ($src !== '') {
                $soalFormOpsiGambar[$n] = $src;
                $soalFormOpsiHasGambar[$n] = true;
            }
        }
    } else {
        foreach ($soalFormOpsiUrl as $n => $url) {
            if ($url !== '') {
                $soalFormOpsiGambar[$n] = $url;
                $soalFormOpsiHasGambar[$n] = true;
            }
        }
    }
}
$currentGambar = $isEditingSoal ? Kuis::gambarPublicUrl($editSoal) : null;
?>

<section class="page-head kuis-form-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/index.php')) ?>">← Daftar kuis</a></p>
    <h2 class="card-title kuis-page-title"><?= $isEdit ? e($kuis['judul']) : 'Buat kuis' ?></h2>
    <?php if ($isEdit): ?>
      <p class="muted kuis-page-meta"><?= count($soalList) ?> soal<?= !empty($kuis['timer_menit']) ? ' · ' . (int) $kuis['timer_menit'] . ' menit' : '' ?></p>
    <?php else: ?>
      <p class="muted kuis-page-meta">Isi judul dan aturan main. Soal ditambahkan setelah kuis tersimpan.</p>
    <?php endif; ?>
  </div>
  <?php if ($isEdit): ?>
    <div class="actions">
      <a class="btn btn-primary" href="<?= e(app_url('kuis/live_host.php?kuis_id=' . $kuis['id'])) ?>">Live</a>
      <a class="btn btn-ghost" href="<?= e(app_url('kuis/hasil.php?id=' . $kuis['id'])) ?>">Hasil</a>
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

<div class="kuis-editor-flow">
<?php if ($isEdit): ?>
  <?php $joinUrl = app_url('kuis/ikut.php?kode=' . urlencode($kuis['kode'])); ?>
  <section class="card kuis-editor kuis-share-card">
    <div class="kuis-share">
      <div class="kuis-share-id">
        <span class="muted">ID</span>
        <strong class="kuis-kode-lg"><?= e($kuis['kode']) ?></strong>
      </div>
      <code class="kuis-link"><?= e($joinUrl) ?></code>
      <div class="kuis-share-actions">
        <button class="btn btn-sm btn-ghost" type="button" data-copy-text="<?= e($kuis['kode']) ?>">Salin ID</button>
        <button class="btn btn-sm btn-ghost" type="button" data-copy-text="<?= e($joinUrl) ?>">Salin tautan</button>
        <a class="btn btn-sm btn-ghost" href="<?= e($joinUrl) ?>" target="_blank" rel="noopener">Uji join</a>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($isEdit): ?>
<details class="card kuis-editor kuis-settings" <?= $errors !== [] ? 'open' : '' ?>>
  <summary>Pengaturan kuis</summary>
  <div class="kuis-settings-body">
<?php else: ?>
<section class="card stack kuis-editor">
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
      <input id="judul" type="text" name="judul" value="<?= old('judul', $isEdit ? $kuis['judul'] : '') ?>" required maxlength="200" placeholder="Contoh: Ulangan Bab 1">
      <?php if (!empty($errors['judul'])): ?><div class="error"><?= e($errors['judul']) ?></div><?php endif; ?>
    </div>

    <div class="kuis-meta-grid">
      <div class="field">
        <label for="kelas_id">Kelas</label>
        <select id="kelas_id" name="kelas_id">
          <option value="0">Tanpa kelas — siswa ketik nama sendiri</option>
          <?php foreach ($daftarKelas as $row): ?>
            <option value="<?= (int) $row['id'] ?>" <?= (string) (int) $row['id'] === (string) (int) $selectedKelas ? 'selected' : '' ?>>
              <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="hint">Kalau dipilih, siswa join dengan memilih nama dari daftar kelas.</p>
        <?php if (!empty($errors['kelas_id'])): ?><div class="error"><?= e($errors['kelas_id']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="timer_menit">Batas waktu</label>
        <input id="timer_menit" type="number" name="timer_menit" min="1" max="180" step="1" value="<?= e($timerValue) ?>" placeholder="Menit, kosong = bebas">
        <p class="hint">Saat habis, jawaban terkirim otomatis.</p>
        <?php if (!empty($errors['timer_menit'])): ?><div class="error"><?= e($errors['timer_menit']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="kuis-setting-grid">
      <label class="kuis-setting">
        <input type="checkbox" name="aktif" value="1" <?= $aktifChecked ? 'checked' : '' ?>>
        <span>
          <strong>Siswa bisa join</strong>
          <small>Matikan kalau kuis belum siap dibagikan.</small>
        </span>
      </label>
      <label class="kuis-setting">
        <input type="checkbox" name="acak_soal" value="1" <?= $acakChecked ? 'checked' : '' ?>>
        <span>
          <strong>Acak urutan soal</strong>
          <small>Tiap siswa mendapat urutan yang berbeda.</small>
        </span>
      </label>
      <label class="kuis-setting">
        <input type="checkbox" name="acak_opsi" value="1" <?= $acakOpsiChecked ? 'checked' : '' ?>>
        <span>
          <strong>Acak opsi jawaban</strong>
          <small>Posisi A–F tidak selalu sama.</small>
        </span>
      </label>
      <label class="kuis-setting">
        <input type="checkbox" name="izinkan_ulang" value="1" <?= $izinkanUlangChecked ? 'checked' : '' ?>>
        <span>
          <strong>Boleh dikerjakan ulang</strong>
          <small>Siswa bisa mengirim jawaban lagi.</small>
        </span>
      </label>
      <label class="kuis-setting">
        <input type="checkbox" name="tampil_pembahasan" value="1" <?= $tampilPembahasanChecked ? 'checked' : '' ?>>
        <span>
          <strong>Tampilkan pembahasan</strong>
          <small>Kunci muncul setelah kuis selesai.</small>
        </span>
      </label>
      <label class="kuis-setting">
        <input type="checkbox" name="mode_latihan" value="1" <?= $modeLatihanChecked ? 'checked' : '' ?>>
        <span>
          <strong>Mode latihan</strong>
          <small>Kunci langsung terlihat setelah tiap soal.</small>
        </span>
      </label>
    </div>

    <div class="actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Simpan pengaturan' : 'Buat kuis' ?></button>
      <?php if (!$isEdit): ?>
        <a class="btn btn-ghost" href="<?= e(app_url('kuis/index.php')) ?>">Batal</a>
      <?php endif; ?>
    </div>
  </form>
<?php if ($isEdit): ?>
  </div>
</details>
<?php else: ?>
</section>
<?php endif; ?>

<?php if ($isEdit): ?>
  <section class="card stack kuis-editor kuis-soal-list-card">
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
              <span class="kuis-soal-num"><?= $i + 1 ?></span>
              <div class="kuis-soal-copy">
                <p class="kuis-soal-ask"><?= e($soal['pertanyaan']) ?></p>
                <div class="kuis-soal-tags">
                  <span class="kuis-badge <?= ($soal['tipe'] ?? '') === 'isian' ? 'kuis-badge-off' : 'kuis-badge-on' ?>">
                    <?= ($soal['tipe'] ?? '') === 'isian' ? 'Isian' : 'PG' ?>
                  </span>
                  <?php if (!empty($soal['timer_detik'])): ?>
                    <span class="muted"><?= (int) $soal['timer_detik'] ?> dtk</span>
                  <?php endif; ?>
                </div>
              </div>
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
              <p class="muted kuis-soal-key">Kunci: <strong><?= e((string) ($soal['jawaban_teks'] ?? '')) ?></strong></p>
            <?php else: ?>
              <ul class="kuis-opsi-preview">
                <?php foreach ($soal['opsi'] as $oi => $item): ?>
                  <?php
                  $teks = Kuis::opsiTeks($item);
                  $gambar = Kuis::opsiGambarSrc((int) $soal['id'], $item, (int) $oi);
                  ?>
                  <li class="<?= $oi === (int) $soal['jawaban_index'] ? 'is-correct' : '' ?>">
                    <span class="kuis-opsi-letter"><?= e($labels[$oi] ?? (string) ($oi + 1)) ?></span>
                    <span class="kuis-opsi-body">
                      <?php if ($gambar): ?>
                        <img class="kuis-q-option-img" src="<?= e($gambar) ?>" alt="<?= $teks === '' ? 'Gambar opsi ' . e($labels[$oi] ?? (string) ($oi + 1)) : '' ?>">
                      <?php endif; ?>
                      <?php if ($teks !== '' || $oi === (int) $soal['jawaban_index']): ?>
                        <span class="kuis-opsi-caption">
                          <?php if ($teks !== ''): ?>
                            <span><?= e($teks) ?></span>
                          <?php endif; ?>
                          <?php if ($oi === (int) $soal['jawaban_index']): ?>
                            <em>benar</em>
                          <?php endif; ?>
                        </span>
                      <?php endif; ?>
                    </span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <section class="card stack kuis-editor kuis-soal-form" id="soal-form">
    <h2 class="card-title"><?= $isEditingSoal ? 'Edit soal' : 'Tambah soal' ?></h2>
    <p class="muted kuis-editor-lead"><?= $isEditingSoal ? 'Ubah pertanyaan atau jawaban benar, lalu simpan.' : 'Tulis pertanyaan, tandai jawaban benar, lalu tambahkan. Ulangi untuk soal berikutnya.' ?></p>

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

      <div class="kuis-type" role="radiogroup" aria-label="Tipe soal">
        <label>
          <input type="radio" name="tipe" value="pilihan_ganda" data-kuis-tipe <?= $soalFormTipe === 'pilihan_ganda' ? 'checked' : '' ?>>
          <span>Pilihan ganda</span>
        </label>
        <label>
          <input type="radio" name="tipe" value="isian" data-kuis-tipe <?= $soalFormTipe === 'isian' ? 'checked' : '' ?>>
          <span>Isian singkat</span>
        </label>
      </div>

      <div class="field">
        <label for="pertanyaan">Pertanyaan</label>
        <textarea id="pertanyaan" name="pertanyaan" rows="3" required placeholder="Tulis pertanyaan di sini"><?= e($soalFormPertanyaan) ?></textarea>
        <?php if (!empty($soalErrors['pertanyaan'])): ?><div class="error"><?= e($soalErrors['pertanyaan']) ?></div><?php endif; ?>
      </div>

      <div class="field" data-kuis-isian <?= $soalFormTipe === 'isian' ? '' : 'hidden' ?>>
        <label for="jawaban_teks">Kunci jawaban</label>
        <input id="jawaban_teks" type="text" name="jawaban_teks" value="<?= e($soalFormJawabanTeks) ?>" maxlength="255" placeholder="Contoh: Jakarta">
        <p class="hint">Huruf besar dan kecil dianggap sama.</p>
        <?php if (!empty($soalErrors['jawaban_teks'])): ?><div class="error"><?= e($soalErrors['jawaban_teks']) ?></div><?php endif; ?>
      </div>

      <div data-kuis-pg <?= $soalFormTipe === 'isian' ? 'hidden' : '' ?>>
        <?php
        $opsiValues = $soalFormOpsi;
        $opsiGambar = $soalFormOpsiGambar;
        $opsiGambarUrl = $soalFormOpsiUrl;
        $opsiHasGambar = $soalFormOpsiHasGambar;
        $jawabanIndex = $soalFormJawaban;
        $fieldErrors = $soalErrors;
        require BASE_PATH . '/views/kuis/_opsi_fields.php';
        ?>
      </div>

      <?php
      $soalExtraOpen = $soalFormTimerDetik !== ''
          || $soalFormGambarUrl !== ''
          || $currentGambar
          || !empty($soalErrors['timer_detik'])
          || !empty($soalErrors['gambar'])
          || !empty($soalErrors['gambar_url']);
      ?>
      <details class="kuis-extra" <?= $soalExtraOpen ? 'open' : '' ?>>
        <summary>Timer dan gambar <span class="muted">opsional</span></summary>
        <div class="kuis-extra-body">
          <div class="field">
            <label for="timer_detik">Timer soal ini</label>
            <input id="timer_detik" type="number" name="timer_detik" min="5" max="600" step="1" value="<?= e($soalFormTimerDetik) ?>" placeholder="Detik, kosong = tanpa batas">
            <?php if (!empty($soalErrors['timer_detik'])): ?><div class="error"><?= e($soalErrors['timer_detik']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="gambar">Gambar</label>
            <?php if ($currentGambar): ?>
              <div class="kuis-gambar-current">
                <img src="<?= e($currentGambar) ?>" alt="Gambar soal saat ini">
              </div>
            <?php endif; ?>
            <input id="gambar" type="file" name="gambar" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
            <p class="hint">JPG, PNG, GIF, atau WEBP. Maksimal 2 MB.</p>
            <?php if (!empty($soalErrors['gambar'])): ?><div class="error"><?= e($soalErrors['gambar']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="gambar_url">Atau URL gambar</label>
            <input id="gambar_url" type="url" name="gambar_url" value="<?= e($soalFormGambarUrl) ?>" placeholder="https://…">
            <?php if (!empty($soalErrors['gambar_url'])): ?><div class="error"><?= e($soalErrors['gambar_url']) ?></div><?php endif; ?>
          </div>
          <?php if ($currentGambar): ?>
            <label class="kuis-check">
              <input type="checkbox" name="hapus_gambar" value="1">
              <span>Hapus gambar dari soal ini</span>
            </label>
          <?php endif; ?>
        </div>
      </details>

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
    var pg = document.querySelector('[data-kuis-pg]');
    var isian = document.querySelector('[data-kuis-isian]');
    function syncTipe() {
      var checked = document.querySelector('[data-kuis-tipe]:checked');
      var isIsian = checked && checked.value === 'isian';
      if (pg) pg.hidden = isIsian;
      if (isian) isian.hidden = !isIsian;
      if (pg) {
        pg.querySelectorAll('input[required]').forEach(function (el) { el.required = !isIsian; });
      }
      var jt = document.getElementById('jawaban_teks');
      if (jt) jt.required = !!isIsian;
    }
    document.querySelectorAll('[data-kuis-tipe]').forEach(function (el) {
      el.addEventListener('change', syncTipe);
    });
    syncTipe();
    document.querySelectorAll('[data-copy-text]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy-text') || '';
        var label = btn.textContent;
        function done() {
          btn.textContent = 'Tersalin';
          setTimeout(function () { btn.textContent = label; }, 1200);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(done).catch(function () {});
        }
      });
    });

    function bindOpsi(root) {
      var rows = Array.prototype.slice.call(root.querySelectorAll('[data-opsi-row]'));
      var minVisible = 4;

      function rowFilled(row) {
        var input = row.querySelector('.kuis-opsi-input');
        if (input && input.value.trim() !== '') return true;
        var url = row.querySelector('[data-opsi-url]');
        if (url && url.value.trim() !== '') return true;
        var file = row.querySelector('[data-opsi-file]');
        if (file && file.files && file.files.length > 0) return true;
        var hapus = row.querySelector('[data-opsi-hapus]');
        var img = row.querySelector('[data-opsi-preview]');
        if (img && !img.hidden && img.getAttribute('src') && !(hapus && hapus.checked)) return true;
        return false;
      }

      function syncVisibility() {
        rows.forEach(function (row, i) {
          var earlierFilled = true;
          for (var j = 0; j < i; j++) {
            if (!rowFilled(rows[j])) {
              earlierFilled = false;
              break;
            }
          }
          var laterFilled = false;
          if (i >= minVisible) {
            for (var k = i; k < rows.length; k++) {
              if (rowFilled(rows[k])) {
                laterFilled = true;
                break;
              }
            }
          }
          var show = i < minVisible || earlierFilled || laterFilled;
          row.hidden = !show;
          if (show) return;
          var radio = row.querySelector('.kuis-opsi-radio input');
          if (!radio || !radio.checked) return;
          var fallback = rows[0];
          for (var f = 0; f < i; f++) {
            if (!rows[f].hidden && rowFilled(rows[f])) fallback = rows[f];
          }
          var target = fallback.querySelector('.kuis-opsi-radio input');
          if (target) target.checked = true;
        });
      }

      function setMedia(row, open) {
        var media = row.querySelector('[data-opsi-media]');
        var toggle = row.querySelector('[data-opsi-media-toggle]');
        if (media) media.hidden = !open;
        if (toggle) {
          toggle.classList.toggle('is-open', open);
          toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
      }

      rows.forEach(function (row) {
        var toggle = row.querySelector('[data-opsi-media-toggle]');
        if (toggle) {
          toggle.addEventListener('click', function () {
            var media = row.querySelector('[data-opsi-media]');
            var open = !media || media.hidden;
            setMedia(row, open);
            if (open) {
              var url = row.querySelector('[data-opsi-url]');
              if (url) url.focus();
            }
          });
        }
        var input = row.querySelector('.kuis-opsi-input');
        if (input) input.addEventListener('input', syncVisibility);
        var url = row.querySelector('[data-opsi-url]');
        if (url) url.addEventListener('input', syncVisibility);
        var hapus = row.querySelector('[data-opsi-hapus]');
        if (hapus) hapus.addEventListener('change', syncVisibility);
        var file = row.querySelector('[data-opsi-file]');
        if (!file) return;
        file.addEventListener('change', function () {
          var chosen = file.files && file.files[0];
          var nameEl = row.querySelector('[data-opsi-file-name]');
          if (nameEl) nameEl.textContent = chosen ? chosen.name : 'Unggah';
          if (chosen) setMedia(row, true);
          var img = row.querySelector('[data-opsi-preview]');
          if (img && chosen) {
            var reader = new FileReader();
            reader.onload = function () {
              img.src = String(reader.result || '');
              img.hidden = false;
              syncVisibility();
            };
            reader.readAsDataURL(chosen);
          }
          syncVisibility();
        });
      });

      syncVisibility();
    }

    document.querySelectorAll('[data-kuis-opsi]').forEach(bindOpsi);
    <?php if ($isEditingSoal || $soalErrors !== []): ?>
    var form = document.getElementById('soal-form');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    <?php endif; ?>
  })();
  </script>
<?php endif; ?>
</div>

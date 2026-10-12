<?php
/** @var string $appName */
/** @var array $user */
/** @var int $jumlahKelas */
/** @var int $jumlahSiswa */
/** @var int $setorHariIni */
/** @var array $recent */
/** @var array $daftarKelas */
/** @var array $attention */

$success = flash('success');
$error = flash('error');

$hariIni = [
    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu',
][(int) date('w')];
$tanggalHariIni = $hariIni . ', ' . date('d/m/Y');
$userInitial = mb_strtoupper(mb_substr((string) $user['nama'], 0, 1));
$hasKelas = $jumlahKelas > 0;
$attention = $attention ?? ['belum_setor' => [], 'sering_ulang' => [], 'tertinggal' => []];
$hasAttention = ($attention['belum_setor'] ?? []) !== []
    || ($attention['sering_ulang'] ?? []) !== []
    || ($attention['tertinggal'] ?? []) !== [];
?>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="dash-hero">
  <div class="dash-hero-main">
    <p class="dash-kicker"><?= e($tanggalHariIni) ?></p>
    <h1>Assalamu’alaikum, <?= e($user['nama']) ?></h1>
    <p class="muted">
      Sekolah aktif: <?= e($user['nama_sekolah']) ?> · NPSN <?= e($user['npsn']) ?>
      <?php if (count($user['sekolah_list'] ?? []) > 1): ?>
        · <a href="<?= e(app_url('profil.php?tab=sekolah')) ?>">Ganti sekolah</a>
      <?php endif; ?>
    </p>
  </div>
  <div class="dash-hero-aside" aria-hidden="true">
    <span class="dash-avatar"><?= e($userInitial) ?></span>
  </div>
</section>

<section class="stat-row dash-stats" aria-label="Ringkasan">
  <div class="stat-card">
    <span class="muted">Kelas</span>
    <strong><?= $jumlahKelas ?></strong>
  </div>
  <div class="stat-card">
    <span class="muted">Siswa</span>
    <strong><?= $jumlahSiswa ?></strong>
  </div>
  <div class="stat-card">
    <span class="muted">Setoran hari ini</span>
    <strong><?= $setorHariIni ?></strong>
  </div>
</section>

<section class="dash-section">
  <div class="dash-section-head">
    <h2>Mulai kerja</h2>
    <p class="muted">Pintu cepat ke aktivitas yang sering dipakai.</p>
  </div>

  <div class="dash-actions">
    <a class="dash-action dash-action-primary" href="<?= e(app_url('hafalan/index.php')) ?>">
      <span class="dash-action-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M6 4.5h9.5A2.5 2.5 0 0 1 18 7v13.5L12.5 17 7 20.5V7A2.5 2.5 0 0 1 9.5 4.5H6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9h6M9 12h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </span>
      <span class="dash-action-copy">
        <strong>Setoran hafalan</strong>
        <span>Antrian siswa · mode fokus · pintasan L/U</span>
      </span>
    </a>

    <a class="dash-action" href="<?= e(app_url('hafalan/murojaah.php')) ?>">
      <span class="dash-action-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 1 0 2.3-5.6M4 4v5h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="dash-action-copy">
        <strong>Muroja'ah</strong>
        <span>Surat tuntas yang perlu diulang</span>
      </span>
    </a>

    <a class="dash-action" href="<?= e(app_url('kelas/index.php')) ?>">
      <span class="dash-action-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 19V7.8A1.8 1.8 0 0 1 5.8 6H12v13H5.8A1.8 1.8 0 0 1 4 17.2V19Zm8-13h6.2A1.8 1.8 0 0 1 20 7.8v9.4A1.8 1.8 0 0 1 18.2 19H12V6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 10h2M8 13h2M14 10h2M14 13h2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </span>
      <span class="dash-action-copy">
        <strong>Kelas</strong>
        <span>Kelola rombel &amp; tahun ajaran</span>
      </span>
    </a>

    <a class="dash-action" href="<?= e(app_url('siswa/index.php')) ?>">
      <span class="dash-action-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 12a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 12Zm-7.5 8a7.5 7.5 0 0 1 15 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M18.5 8.5a2.5 2.5 0 1 0-0.3-4.98M20.8 15.2a5.2 5.2 0 0 0-3.1-2.55" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </span>
      <span class="dash-action-copy">
        <strong>Siswa</strong>
        <span>Tambah atau impor daftar siswa</span>
      </span>
    </a>

    <a class="dash-action" href="<?= e(app_url('bahan/index.php')) ?>">
      <span class="dash-action-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H14v16H6.5A2.5 2.5 0 0 1 4 17.5v-11Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 4h3.5A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20H14V4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 8h3M8 11h3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </span>
      <span class="dash-action-copy">
        <strong>Bahan ajar</strong>
        <span>Upload, Drive, OneDrive, Canva, YouTube, Vimeo</span>
      </span>
    </a>

    <a class="dash-action" href="<?= e(app_url('hafalan/riwayat.php')) ?>">
      <span class="dash-action-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M7 4h10a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2V6a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 9h6M9 12h6M9 15h3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </span>
      <span class="dash-action-copy">
        <strong>Riwayat</strong>
        <span>Lihat rekap &amp; progress hafalan</span>
      </span>
    </a>
  </div>
</section>

<?php if (!$hasKelas): ?>
  <section class="card dash-empty">
    <h2 class="card-title">Belum ada kelas</h2>
    <p class="muted">Buat kelas pertama, lalu isi daftar siswa. Setelah itu setoran hafalan siap dipakai.</p>
    <div class="actions">
      <a class="btn btn-primary" href="<?= e(app_url('kelas/index.php')) ?>">Buat kelas</a>
    </div>
  </section>
<?php else: ?>

  <?php if ($hasAttention): ?>
    <section class="dash-section">
      <div class="dash-section-head">
        <h2>Perlu perhatian</h2>
        <p class="muted">Siapa yang tertinggal — siap dilanjutkan dengan satu klik.</p>
      </div>
      <div class="attention-grid">
        <article class="card stack attention-card">
          <h3 class="card-title">Belum setor minggu ini</h3>
          <?php if (($attention['belum_setor'] ?? []) === []): ?>
            <p class="muted">Semua siswa sudah ada aktivitas minggu ini.</p>
          <?php else: ?>
            <ul class="attention-list">
              <?php foreach ($attention['belum_setor'] as $row): ?>
                <li>
                  <div>
                    <strong><?= e($row['nama']) ?></strong>
                    <span class="muted"><?= e($row['kelas_nama']) ?></span>
                  </div>
                  <a class="btn btn-primary btn-sm" href="<?= e(app_url('hafalan/index.php?kelas_id=' . (int) $row['kelas_id'] . '&siswa_id=' . (int) $row['siswa_id'])) ?>">Setor</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </article>

        <article class="card stack attention-card">
          <h3 class="card-title">Sering ulang (14 hari)</h3>
          <?php if (($attention['sering_ulang'] ?? []) === []): ?>
            <p class="muted">Tidak ada siswa dengan ≥2 ulang baru-baru ini.</p>
          <?php else: ?>
            <ul class="attention-list">
              <?php foreach ($attention['sering_ulang'] as $row): ?>
                <li>
                  <div>
                    <strong><?= e($row['nama']) ?></strong>
                    <span class="muted"><?= e($row['kelas_nama']) ?> · <?= (int) $row['total_ulang'] ?>× ulang</span>
                  </div>
                  <a class="btn btn-ghost btn-sm" href="<?= e(app_url('hafalan/index.php?kelas_id=' . (int) $row['kelas_id'] . '&siswa_id=' . (int) $row['siswa_id'])) ?>">Simak</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </article>

        <article class="card stack attention-card">
          <h3 class="card-title">Tertinggal target</h3>
          <?php if (($attention['tertinggal'] ?? []) === []): ?>
            <p class="muted">Belum ada target, atau semua sudah tuntas.</p>
          <?php else: ?>
            <ul class="attention-list">
              <?php foreach ($attention['tertinggal'] as $row): ?>
                <li>
                  <div>
                    <strong><?= e($row['nama']) ?></strong>
                    <span class="muted"><?= e($row['kelas_nama']) ?> · <?= (int) $row['tuntas'] ?>/<?= (int) $row['total'] ?> surat (<?= (int) $row['persen'] ?>%)</span>
                  </div>
                  <a class="btn btn-ghost btn-sm" href="<?= e(app_url('hafalan/rekap.php?kelas_id=' . (int) $row['kelas_id'] . '&view=siswa')) ?>">Rekap</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </article>
      </div>
    </section>
  <?php endif; ?>

  <div class="dash-grid">
    <section class="card stack">
      <div class="dash-section-head compact">
        <h2 class="card-title">Aktivitas terbaru</h2>
        <?php if ($recent !== []): ?>
          <a class="dash-link" href="<?= e(app_url('hafalan/riwayat.php')) ?>">Lihat semua</a>
        <?php endif; ?>
      </div>

      <?php if ($recent === []): ?>
        <p class="muted">Belum ada setoran. Mulai dari halaman hafalan.</p>
        <div class="actions">
          <a class="btn btn-primary" href="<?= e(app_url('hafalan/index.php')) ?>">Catat setoran</a>
        </div>
      <?php else: ?>
        <ul class="dash-feed">
          <?php foreach ($recent as $row): ?>
            <li>
              <div class="dash-feed-main">
                <strong><?= e($row['siswa_nama']) ?></strong>
                <span class="muted">
                  <?= e($row['kelas_nama']) ?> ·
                  <?php if (($row['jenis'] ?? '') === 'murojaah'): ?>Muroja'ah · <?php endif; ?>
                  <?= e($row['surat_nama']) ?>
                  <?= e(Hafalan::formatAyat((int) $row['ayat_awal'], (int) $row['ayat_akhir'])) ?>
                </span>
              </div>
              <div class="dash-feed-meta">
                <span class="badge <?= $row['status'] === 'lancar' ? 'badge-ok' : 'badge-warn' ?>">
                  <?= $row['status'] === 'lancar' ? 'Lancar' : 'Ulang' ?>
                </span>
                <time datetime="<?= e($row['created_at']) ?>">
                  <?= e(date('d/m H:i', strtotime((string) $row['created_at']))) ?>
                </time>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="card stack">
      <div class="dash-section-head compact">
        <h2 class="card-title">Kelas Anda</h2>
        <a class="dash-link" href="<?= e(app_url('kelas/index.php')) ?>">Kelola</a>
      </div>

      <ul class="dash-kelas-list">
        <?php foreach (array_slice($daftarKelas, 0, 5) as $kelas): ?>
          <li>
            <a href="<?= e(app_url('siswa/index.php?kelas_id=' . $kelas['id'])) ?>">
              <span>
                <strong><?= e($kelas['nama']) ?></strong>
                <span class="muted"><?= e($kelas['tahun_ajaran']) ?></span>
              </span>
              <span class="dash-kelas-count"><?= (int) $kelas['jumlah_siswa'] ?> siswa</span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if (count($daftarKelas) > 5): ?>
        <p class="muted">+<?= count($daftarKelas) - 5 ?> kelas lainnya</p>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>

<?php
/** @var array $user */
/** @var array $daftarKelas */
/** @var array<string, string> $errors */
$success = flash('success');
$error = flash('error');
$errors = $errors ?? [];
$defaultTahun = old('tahun_ajaran', date('Y') . '/' . (date('Y') + 1));
?>
<section class="page-head">
  <div>
    <h1>Pengelola kelas</h1>
    <p class="muted">Tambah, ubah, atau hapus kelas yang Anda ajar.</p>
  </div>
</section>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="layout-split">
  <section class="card stack">
    <h2 class="card-title">Tambah kelas</h2>
    <?php if (!empty($errors['_form'])): ?>
      <div class="alert error"><?= e($errors['_form']) ?></div>
    <?php endif; ?>
    <form class="stack" method="post" action="<?= e(app_url('kelas.php')) ?>">
      <?= Csrf::field() ?>
      <div class="field">
        <label for="nama">Nama kelas</label>
        <input id="nama" type="text" name="nama" value="<?= old('nama') ?>" placeholder="VII-A" required>
        <?php if (!empty($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label for="tahun_ajaran">Tahun ajaran</label>
        <input id="tahun_ajaran" type="text" name="tahun_ajaran" value="<?= $defaultTahun ?>" placeholder="2025/2026" required>
        <div class="hint">Format: 2025/2026</div>
        <?php if (!empty($errors['tahun_ajaran'])): ?><div class="error"><?= e($errors['tahun_ajaran']) ?></div><?php endif; ?>
      </div>
      <button class="btn btn-primary" type="submit">Simpan kelas</button>
    </form>
  </section>

  <section class="card">
    <h2 class="card-title">Daftar kelas</h2>
    <?php if ($daftarKelas === []): ?>
      <p class="muted">Belum ada kelas.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="col-num">No</th>
              <th>Nama</th>
              <th>Tahun</th>
              <th>Siswa</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($daftarKelas as $i => $row): ?>
              <tr>
                <td class="col-num"><?= $i + 1 ?></td>
                <td><strong><?= e($row['nama']) ?></strong></td>
                <td><?= e($row['tahun_ajaran']) ?></td>
                <td><?= (int) $row['jumlah_siswa'] ?></td>
                <td class="table-actions">
                  <a class="btn-icon" href="<?= e(app_url('kelas_edit.php?id=' . $row['id'])) ?>" title="Edit" aria-label="Edit">
                    <?= icon('edit') ?>
                  </a>
                  <form method="post" action="<?= e(app_url('kelas_hapus.php')) ?>" onsubmit="return confirm('Hapus kelas beserta seluruh siswa?');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button class="btn-icon danger" type="submit" title="Hapus" aria-label="Hapus">
                      <?= icon('trash') ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

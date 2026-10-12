<?php
/** @var array $daftarKelas */
/** @var array $daftar */
/** @var int $filterKelasId */
/** @var string $filterQ */
$success = flash('success');
$error = flash('error');
$filterKelasId = (int) ($filterKelasId ?? 0);
$filterQ = (string) ($filterQ ?? '');
$hasFilter = $filterKelasId > 0 || $filterQ !== '';
?>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="page-head">
  <div>
    <p class="muted">Buat kuis, bagikan ID ke siswa, lalu lihat hasilnya.</p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/live_join.php')) ?>" target="_blank" rel="noopener">Gabung live</a>
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/ikut.php')) ?>" target="_blank" rel="noopener">Halaman siswa</a>
    <a class="btn btn-primary" href="<?= e(app_url('kuis/form.php')) ?>">Buat kuis</a>
  </div>
</section>

<form class="card bahan-filter kuis-filter" method="get" action="<?= e(app_url('kuis/index.php')) ?>">
  <select id="kelas_id" name="kelas_id" aria-label="Kelas" onchange="this.form.submit()">
    <option value="0">Semua kelas</option>
    <?php foreach ($daftarKelas as $row): ?>
      <option value="<?= (int) $row['id'] ?>" <?= $filterKelasId === (int) $row['id'] ? 'selected' : '' ?>>
        <?= e($row['nama']) ?> · <?= e($row['tahun_ajaran']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <div class="kuis-filter-search">
    <input id="q" type="search" name="q" value="<?= e($filterQ) ?>" placeholder="Cari judul atau ID…" aria-label="Cari">
    <button class="btn btn-primary" type="submit" aria-label="Cari">
      <span class="kuis-filter-submit-label">Cari</span>
      <svg class="kuis-filter-submit-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </button>
  </div>
  <?php if ($hasFilter): ?>
    <a class="btn btn-ghost kuis-filter-reset" href="<?= e(app_url('kuis/index.php')) ?>">Reset</a>
  <?php endif; ?>
</form>

<?php if ($daftar === []): ?>
  <section class="card">
    <p class="muted">
      <?= $hasFilter ? 'Tidak ada kuis yang cocok.' : 'Belum ada kuis. Mulai dengan membuat kuis baru.' ?>
    </p>
  </section>
<?php else: ?>
  <section class="card">
    <div class="kuis-list-cards">
      <?php foreach ($daftar as $row): ?>
        <article class="kuis-list-card">
          <div class="kuis-list-main">
            <div class="kuis-list-card-top">
              <strong><?= e($row['judul']) ?></strong>
              <?php if (!(int) $row['aktif']): ?>
                <span class="kuis-badge kuis-badge-off">Nonaktif</span>
              <?php endif; ?>
            </div>
            <p class="kuis-list-meta">
              <code class="kuis-kode"><?= e($row['kode']) ?></code>
              · <?= !empty($row['kelas_nama']) ? e($row['kelas_nama']) : 'Tanpa kelas' ?>
              · <?= (int) $row['jumlah_soal'] ?> soal
              · <?= (int) $row['jumlah_hasil'] ?> hasil
            </p>
          </div>
          <div class="kuis-list-actions">
            <a class="btn-icon primary" href="<?= e(app_url('kuis/live_host.php?kuis_id=' . $row['id'])) ?>" title="Live" aria-label="Live">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.5" fill="currentColor"/><path d="M5.5 12a6.5 6.5 0 0 1 13 0M2.5 12a9.5 9.5 0 0 1 19 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </a>
            <a class="btn-icon" href="<?= e(app_url('kuis/form.php?id=' . $row['id'])) ?>" title="Edit" aria-label="Edit">
              <?= icon('edit') ?>
            </a>
            <div class="kuis-more" data-kuis-more>
              <button
                class="btn-icon"
                type="button"
                title="Aksi lain"
                aria-label="Aksi lain"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="kuis-more-<?= (int) $row['id'] ?>"
                data-kuis-more-trigger
              >
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.7" fill="currentColor"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/><circle cx="12" cy="19" r="1.7" fill="currentColor"/></svg>
              </button>
              <div class="kuis-more-menu" id="kuis-more-<?= (int) $row['id'] ?>" role="menu" hidden data-kuis-more-menu>
                <a role="menuitem" href="<?= e(app_url('kuis/hasil.php?id=' . $row['id'])) ?>">
                  <?= icon('users') ?>
                  <span>Hasil</span>
                </a>
                <form method="post" action="<?= e(app_url('kuis/duplikat.php')) ?>" onsubmit="return confirm('Duplikasi kuis ini beserta semua soalnya?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button role="menuitem" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M6.5 16H6a1.5 1.5 0 0 1-1.5-1.5V6A1.5 1.5 0 0 1 6 4.5h8.5A1.5 1.5 0 0 1 16 6v.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span>Duplikat</span>
                  </button>
                </form>
                <form method="post" action="<?= e(app_url('kuis/hapus.php')) ?>" onsubmit="return confirm('Hapus kuis ini beserta soal dan hasilnya?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="is-danger" role="menuitem" type="submit">
                    <?= icon('trash') ?>
                    <span>Hapus</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="table-wrap kuis-list-table">
      <table class="table">
        <thead>
          <tr>
            <th class="col-num">No</th>
            <th>Judul</th>
            <th>ID</th>
            <th>Kelas</th>
            <th>Soal</th>
            <th>Hasil</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $i => $row): ?>
            <tr>
              <td class="col-num"><?= $i + 1 ?></td>
              <td>
                <strong><?= e($row['judul']) ?></strong>
                <?php if (!(int) $row['aktif']): ?>
                  <span class="kuis-badge kuis-badge-off">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td><code class="kuis-kode"><?= e($row['kode']) ?></code></td>
              <td>
                <?php if (!empty($row['kelas_nama'])): ?>
                  <?= e($row['kelas_nama']) ?>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= (int) $row['jumlah_soal'] ?></td>
              <td><?= (int) $row['jumlah_hasil'] ?></td>
              <td class="table-actions">
                <a class="btn-icon primary" href="<?= e(app_url('kuis/live_host.php?kuis_id=' . $row['id'])) ?>" title="Live" aria-label="Live">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.5" fill="currentColor"/><path d="M5.5 12a6.5 6.5 0 0 1 13 0M2.5 12a9.5 9.5 0 0 1 19 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </a>
                <a class="btn-icon" href="<?= e(app_url('kuis/hasil.php?id=' . $row['id'])) ?>" title="Hasil" aria-label="Hasil">
                  <?= icon('users') ?>
                </a>
                <a class="btn-icon" href="<?= e(app_url('kuis/form.php?id=' . $row['id'])) ?>" title="Edit" aria-label="Edit">
                  <?= icon('edit') ?>
                </a>
                <form method="post" action="<?= e(app_url('kuis/duplikat.php')) ?>" onsubmit="return confirm('Duplikasi kuis ini beserta semua soalnya?');">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn-icon primary" type="submit" title="Duplikasi" aria-label="Duplikasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M6.5 16H6a1.5 1.5 0 0 1-1.5-1.5V6A1.5 1.5 0 0 1 6 4.5h8.5A1.5 1.5 0 0 1 16 6v.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                  </button>
                </form>
                <form method="post" action="<?= e(app_url('kuis/hapus.php')) ?>" onsubmit="return confirm('Hapus kuis ini beserta soal dan hasilnya?');">
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
  </section>
  <script>
    (function () {
      var openMenu = null;

      function closeMenu(menu) {
        if (!menu) return;
        menu.hidden = true;
        menu.classList.remove('is-up');
        var trigger = menu.parentElement && menu.parentElement.querySelector('[data-kuis-more-trigger]');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
        if (openMenu === menu) openMenu = null;
      }

      function openMenuFor(menu, trigger) {
        if (openMenu && openMenu !== menu) closeMenu(openMenu);
        menu.hidden = false;
        menu.classList.remove('is-up');
        var below = window.innerHeight - trigger.getBoundingClientRect().bottom;
        var nav = document.querySelector('.bottom-nav');
        var navHeight = 0;
        if (nav && getComputedStyle(nav).display !== 'none') {
          navHeight = nav.getBoundingClientRect().height;
        }
        if (below < menu.offsetHeight + navHeight + 8) {
          menu.classList.add('is-up');
        }
        trigger.setAttribute('aria-expanded', 'true');
        openMenu = menu;
      }

      document.querySelectorAll('[data-kuis-more]').forEach(function (wrap) {
        var trigger = wrap.querySelector('[data-kuis-more-trigger]');
        var menu = wrap.querySelector('[data-kuis-more-menu]');
        if (!trigger || !menu) return;

        trigger.addEventListener('click', function (event) {
          event.stopPropagation();
          if (menu.hidden) openMenuFor(menu, trigger);
          else closeMenu(menu);
        });
      });

      document.addEventListener('click', function (event) {
        if (!openMenu || openMenu.contains(event.target)) return;
        closeMenu(openMenu);
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || !openMenu) return;
        var trigger = openMenu.parentElement && openMenu.parentElement.querySelector('[data-kuis-more-trigger]');
        closeMenu(openMenu);
        if (trigger) trigger.focus();
      });
    })();
  </script>
<?php endif; ?>

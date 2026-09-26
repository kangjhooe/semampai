<?php
/** @var string $appName */
/** @var string $contentView */
/** @var string|null $title */
/** @var array|null $user */
$user = $user ?? Auth::user();
$pageTitle = trim(($title ?? 'Dashboard') . ' · ' . $appName);
$initial = mb_strtoupper(mb_substr($appName, 0, 1));
$script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$userInitial = mb_strtoupper(mb_substr((string) ($user['nama'] ?? 'U'), 0, 1));

$navItems = [
    [
        'label' => 'Beranda',
        'href' => app_url('dashboard.php'),
        'active' => in_array($script, ['dashboard.php'], true),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
    ],
    [
        'label' => 'Kelas',
        'href' => app_url('kelas.php'),
        'active' => str_starts_with($script, 'kelas'),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V7.8A1.8 1.8 0 0 1 5.8 6H12v13H5.8A1.8 1.8 0 0 1 4 17.2V19Zm8-13h6.2A1.8 1.8 0 0 1 20 7.8v9.4A1.8 1.8 0 0 1 18.2 19H12V6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 10h2M8 13h2M14 10h2M14 13h2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ],
    [
        'label' => 'Siswa',
        'href' => app_url('siswa.php'),
        'active' => str_starts_with($script, 'siswa'),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 12Zm-7.5 8a7.5 7.5 0 0 1 15 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M18.5 8.5a2.5 2.5 0 1 0-0.3-4.98M20.8 15.2a5.2 5.2 0 0 0-3.1-2.55" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ],
    [
        'label' => 'Hafalan',
        'href' => app_url('hafalan.php'),
        'active' => $script === 'hafalan.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.5h9.5A2.5 2.5 0 0 1 18 7v13.5L12.5 17 7 20.5V7A2.5 2.5 0 0 1 9.5 4.5H6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9h6M9 12h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ],
    [
        'label' => 'Riwayat',
        'href' => app_url('hafalan_riwayat.php'),
        'active' => in_array($script, ['hafalan_riwayat.php', 'hafalan_siswa.php', 'hafalan_ekspor.php'], true),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2V6a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 9h6M9 12h6M9 15h3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ],
    [
        'label' => 'Rekap',
        'href' => app_url('hafalan_rekap.php'),
        'active' => in_array($script, ['hafalan_rekap.php', 'hafalan_target.php', 'hafalan_rekap_ekspor.php'], true),
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16M7 16V9m5 7V5m5 11v-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="app-body">
  <div class="app-shell">
    <div class="sidebar-overlay" data-sidebar-close aria-hidden="true"></div>

    <aside class="sidebar" id="app-sidebar" aria-label="Navigasi utama">
      <div class="sidebar-brand">
        <a class="brand-inline" href="<?= e(app_url('dashboard.php')) ?>">
          <span class="brand-mark"><?= e($initial) ?></span>
          <span class="brand-copy">
            <strong><?= e($appName) ?></strong>
            <span>Panel sekolah</span>
          </span>
        </a>
        <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup menu">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
      </div>

      <nav class="sidebar-nav">
        <p class="sidebar-label">Menu</p>
        <?php foreach ($navItems as $item): ?>
          <a
            class="sidebar-link<?= $item['active'] ? ' is-active' : '' ?>"
            href="<?= e($item['href']) ?>"
            <?= $item['active'] ? 'aria-current="page"' : '' ?>
          >
            <span class="sidebar-icon"><?= $item['icon'] ?></span>
            <span><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <span class="sidebar-avatar"><?= e($userInitial) ?></span>
          <div class="sidebar-user-meta">
            <strong><?= e($user['nama'] ?? '') ?></strong>
            <span><?= e($user['nama_sekolah'] ?? '') ?></span>
          </div>
        </div>
        <a class="sidebar-link sidebar-logout" href="<?= e(app_url('logout.php')) ?>">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H6.8A1.8 1.8 0 0 0 5 6.8v10.4A1.8 1.8 0 0 0 6.8 19H10M15 16l4-4-4-4M19 12H10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <span>Keluar</span>
        </a>
      </div>
    </aside>

    <div class="app-frame">
      <header class="topbar">
        <button type="button" class="menu-toggle" data-sidebar-open aria-controls="app-sidebar" aria-expanded="false" aria-label="Buka menu">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
        <div class="topbar-title">
          <strong><?= e($title ?? 'Dashboard') ?></strong>
        </div>
      </header>

      <main class="app-main">
        <?php require $contentView; ?>
      </main>
    </div>
  </div>

  <script>
    (function () {
      var body = document.body;
      var openBtn = document.querySelector('[data-sidebar-open]');
      var closeTargets = document.querySelectorAll('[data-sidebar-close]');
      var media = window.matchMedia('(min-width: 960px)');

      function setOpen(open) {
        body.classList.toggle('sidebar-open', open);
        if (openBtn) openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }

      if (openBtn) {
        openBtn.addEventListener('click', function () {
          setOpen(!body.classList.contains('sidebar-open'));
        });
      }

      closeTargets.forEach(function (el) {
        el.addEventListener('click', function () {
          setOpen(false);
        });
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen(false);
      });

      function syncDesktop() {
        if (media.matches) setOpen(false);
      }

      if (media.addEventListener) {
        media.addEventListener('change', syncDesktop);
      } else if (media.addListener) {
        media.addListener(syncDesktop);
      }
    })();
  </script>
</body>
</html>

<?php
/** @var string $appName */
/** @var string $contentView */
/** @var string|null $title */
$pageTitle = trim(($title ?? 'Masuk') . ' · ' . $appName);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#f3f6f2">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body>
  <div class="guest-shell">
    <?php require $contentView; ?>
  </div>
  <script src="<?= e(asset_url('assets/js/password-toggle.js')) ?>"></script>
</body>
</html>

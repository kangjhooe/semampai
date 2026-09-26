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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body>
  <div class="guest-shell">
    <?php require $contentView; ?>
  </div>
</body>
</html>

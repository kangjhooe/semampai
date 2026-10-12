<?php
/** @var array $kuis */
$error = flash('error');
$success = flash('success');
?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="page-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/index.php')) ?>">← Kuis</a></p>
    <h1>Live: <?= e($kuis['judul']) ?></h1>
    <p class="muted">Mulai sesi multiplayer. Siswa bergabung dengan PIN 6 digit.</p>
  </div>
</section>

<section class="card stack" style="max-width:32rem;">
  <p>Soal akan sinkron untuk semua peserta. Anda yang mengatur maju soal, reveal, dan papan skor.</p>
  <form method="post" action="<?= e(app_url('kuis/live_host.php')) ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="kuis_id" value="<?= (int) $kuis['id'] ?>">
    <input type="hidden" name="action" value="create">
    <button class="btn btn-primary" type="submit">Buat sesi live</button>
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/form.php?id=' . (int) $kuis['id'])) ?>">Edit kuis</a>
  </form>
</section>

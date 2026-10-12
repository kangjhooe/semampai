<?php
/** @var array $kuis */
/** @var array $sesi */
/** @var array $snapshot */
/** @var string $csrfToken */
$error = flash('error');
$success = flash('success');
$sesiId = (int) $sesi['id'];
?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="page-head">
  <div>
    <p class="muted"><a href="<?= e(app_url('kuis/index.php')) ?>">← Kuis</a> · Live host</p>
    <h1 id="live-judul"><?= e($kuis['judul']) ?></h1>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(app_url('kuis/live_join.php?pin=' . urlencode((string) $sesi['pin']))) ?>" target="_blank" rel="noopener">Halaman siswa</a>
  </div>
</section>

<div class="live-host" id="live-host"
     data-sesi-id="<?= $sesiId ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-sse="<?= e(app_url('kuis/live_sse.php?role=host&sesi_id=' . $sesiId)) ?>"
     data-state="<?= e(app_url('kuis/live_state.php?role=host&sesi_id=' . $sesiId)) ?>"
     data-action="<?= e(app_url('kuis/live_action.php')) ?>">

  <div class="live-host-grid">
    <aside class="card live-pin-panel">
      <p class="muted" style="margin:0;">PIN gabung</p>
      <div class="live-pin" id="live-pin"><?= e((string) $snapshot['pin']) ?></div>
      <p class="muted" id="live-join-hint">Siswa buka halaman join lalu masukkan PIN.</p>
      <p><strong id="live-peserta-count"><?= (int) $snapshot['peserta_count'] ?></strong> peserta</p>
      <p class="muted" id="live-status-label">Status: <span id="live-status"><?= e((string) $snapshot['status']) ?></span></p>
      <div class="live-timer" id="live-timer" hidden>
        <span class="kuis-timer-label">Timer soal</span>
        <strong id="live-timer-value">--:--</strong>
      </div>
    </aside>

    <section class="card live-main-panel">
      <div id="live-stage"></div>
      <div class="live-host-actions" id="live-actions"></div>
      <div class="alert error" id="live-error" hidden></div>
    </section>
  </div>

  <section class="card" style="margin-top:1rem;">
    <h2 style="margin-top:0;">Peserta</h2>
    <ul class="live-peserta-list" id="live-peserta-list"></ul>
  </section>
</div>

<script>
(function () {
  var root = document.getElementById('live-host');
  if (!root) return;
  var sesiId = parseInt(root.getAttribute('data-sesi-id'), 10);
  var csrf = root.getAttribute('data-csrf');
  var sseUrl = root.getAttribute('data-sse');
  var stateUrl = root.getAttribute('data-state');
  var actionUrl = root.getAttribute('data-action');
  var snap = <?= json_encode($snapshot, JSON_UNESCAPED_UNICODE) ?>;
  var labels = ['A','B','C','D','E','F'];
  var timerIv = null;
  var usePoll = false;
  var pollIv = null;
  var es = null;
  var autoRevealFor = null;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  function setError(msg) {
    var el = document.getElementById('live-error');
    if (!msg) { el.hidden = true; el.textContent = ''; return; }
    el.hidden = false; el.textContent = msg;
  }

  function renderPeserta(list) {
    var ul = document.getElementById('live-peserta-list');
    if (!list || !list.length) {
      ul.innerHTML = '<li class="muted">Belum ada peserta.</li>';
      return;
    }
    ul.innerHTML = list.map(function (p) {
      return '<li><strong>' + esc(p.nama) + '</strong> <span class="muted">' + esc(p.skor) + ' poin</span></li>';
    }).join('');
  }

  function renderBoard(board) {
    if (!board || !board.length) return '<p class="muted">Belum ada skor.</p>';
    return '<ol class="live-board">' + board.map(function (r) {
      return '<li><span class="live-rank">' + r.rank + '</span> <strong>' + esc(r.nama) + '</strong> <span>' + r.skor + '</span></li>';
    }).join('') + '</ol>';
  }

  function renderSoal(soal, showKey) {
    if (!soal) return '<p class="muted">Menunggu soal…</p>';
    var html = '<div class="live-soal">';
    html += '<p class="muted">Soal ' + soal.nomor + '</p>';
    html += '<h2>' + esc(soal.pertanyaan) + '</h2>';
    if (soal.gambar) html += '<img class="kuis-q-image" src="' + esc(soal.gambar) + '" alt="">';
    if (soal.tipe === 'isian') {
      html += '<p class="muted">Tipe: isian singkat</p>';
      if (showKey) html += '<p>Kunci: <strong>' + esc(soal.benar_teks || soal.jawaban_teks) + '</strong></p>';
    } else {
      html += '<ul class="live-opsi-preview">';
      (soal.opsi || []).forEach(function (opt, idx) {
        var cls = showKey && opt.i === soal.jawaban_index ? ' is-correct' : '';
        html += '<li class="' + cls + '"><span>' + esc(labels[idx] || (idx+1)) + '</span> ' + esc(opt.teks) + '</li>';
      });
      html += '</ul>';
    }
    html += '</div>';
    return html;
  }

  function renderActions(s) {
    var box = document.getElementById('live-actions');
    var html = '';
    var st = s.status;
    if (st === 'lobby') {
      html += '<button type="button" class="btn btn-primary" data-act="start">Mulai kuis</button>';
    } else if (st === 'question') {
      html += '<button type="button" class="btn btn-primary" data-act="reveal">Reveal jawaban</button>';
      html += '<button type="button" class="btn btn-ghost" data-act="leaderboard">Papan skor</button>';
    } else if (st === 'reveal') {
      html += '<button type="button" class="btn btn-primary" data-act="leaderboard">Papan skor</button>';
      html += '<button type="button" class="btn btn-ghost" data-act="next">' +
        (s.current_index + 1 >= s.total_soal ? 'Ke podium' : 'Soal berikutnya') + '</button>';
    } else if (st === 'leaderboard') {
      html += '<button type="button" class="btn btn-primary" data-act="next">' +
        (s.current_index + 1 >= s.total_soal ? 'Ke podium' : 'Soal berikutnya') + '</button>';
    } else if (st === 'podium') {
      html += '<button type="button" class="btn btn-primary" data-act="finish">Akhiri sesi</button>';
    }
    if (st !== 'ended' && st !== 'podium') {
      html += '<button type="button" class="btn btn-ghost" data-act="finish">Akhiri</button>';
    }
    box.innerHTML = html;
  }

  function tickTimer(s) {
    clearInterval(timerIv);
    var el = document.getElementById('live-timer');
    var val = document.getElementById('live-timer-value');
    if (s.status !== 'question' || !s.question_ends_at) {
      el.hidden = true;
      return;
    }
    el.hidden = false;
    function update() {
      var left = Math.max(0, s.question_ends_at - Math.floor(Date.now() / 1000) + (s.server_now ? (s.server_now - Math.floor(Date.now()/1000)) : 0));
      // Align with server_now offset
      var offset = (s.server_now || Math.floor(Date.now()/1000)) - Math.floor(Date.now()/1000);
      left = Math.max(0, s.question_ends_at - (Math.floor(Date.now()/1000) + offset));
      val.textContent = pad(Math.floor(left / 60)) + ':' + pad(left % 60);
      if (left <= 0) {
        clearInterval(timerIv);
        var key = String(s.current_index) + ':' + String(s.question_ends_at);
        if (s.status === 'question' && autoRevealFor !== key) {
          autoRevealFor = key;
          doAction('reveal');
        }
      }
    }
    update();
    timerIv = setInterval(update, 250);
  }

  function apply(s) {
    if (!s) return;
    snap = s;
    document.getElementById('live-pin').textContent = s.pin || '';
    document.getElementById('live-peserta-count').textContent = String(s.peserta_count || 0);
    document.getElementById('live-status').textContent = s.status || '';
    document.getElementById('live-judul').textContent = (s.kuis && s.kuis.judul) || '';
    renderPeserta(s.peserta || []);

    var stage = document.getElementById('live-stage');
    var st = s.status;
    if (st === 'lobby') {
      stage.innerHTML = '<h2>Lobby</h2><p class="muted">Bagikan PIN. Mulai jika peserta sudah siap.</p>' +
        '<p>Menunggu: <strong>' + (s.peserta_count || 0) + '</strong> peserta</p>';
    } else if (st === 'question') {
      stage.innerHTML = renderSoal(s.soal, false) +
        '<p class="muted">Jawaban masuk: <strong>' + (s.answered_count || 0) + '</strong> / ' + (s.peserta_count || 0) + '</p>';
    } else if (st === 'reveal') {
      stage.innerHTML = '<h2>Jawaban</h2>' + renderSoal(s.soal, true) +
        '<p class="muted">Menjawab: ' + (s.answered_count || 0) + ' / ' + (s.peserta_count || 0) + '</p>';
    } else if (st === 'leaderboard') {
      stage.innerHTML = '<h2>Papan skor</h2>' + renderBoard(s.leaderboard);
    } else if (st === 'podium') {
      stage.innerHTML = '<h2>Podium</h2>' + renderBoard(s.leaderboard);
    } else if (st === 'ended') {
      stage.innerHTML = '<h2>Sesi berakhir</h2>' + renderBoard(s.leaderboard) +
        '<p><a class="btn btn-primary" href="<?= e(app_url('kuis/index.php')) ?>">Kembali ke daftar kuis</a></p>';
    }
    renderActions(s);
    tickTimer(s);
  }

  function doAction(action) {
    setError('');
    fetch(actionUrl, {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: JSON.stringify({ _token: csrf, sesi_id: sesiId, action: action }),
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res.ok) {
        setError((res.errors && res.errors._form) || 'Aksi gagal.');
        return;
      }
      if (res.snapshot) apply(res.snapshot);
    }).catch(function () { setError('Koneksi gagal.'); });
  }

  document.getElementById('live-actions').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-act]');
    if (!btn) return;
    var act = btn.getAttribute('data-act');
    if (act === 'finish' && !confirm('Akhiri sesi live?')) return;
    doAction(act);
  });

  function startPoll() {
    usePoll = true;
    if (pollIv) return;
    pollIv = setInterval(function () {
      fetch(stateUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (res) { if (res.ok && res.snapshot) apply(res.snapshot); })
        .catch(function () {});
    }, 1500);
  }

  function startSse() {
    if (typeof EventSource === 'undefined') { startPoll(); return; }
    try {
      es = new EventSource(sseUrl);
      es.addEventListener('state', function (ev) {
        try { apply(JSON.parse(ev.data)); } catch (e) {}
      });
      es.addEventListener('reconnect', function () {
        if (es) es.close();
        setTimeout(startSse, 200);
      });
      es.onerror = function () {
        if (es) es.close();
        if (!usePoll) startPoll();
        setTimeout(startSse, 2000);
      };
    } catch (e) {
      startPoll();
    }
  }

  apply(snap);
  startSse();
})();
</script>

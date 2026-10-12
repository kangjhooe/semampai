<?php
/** @var array $snapshot */
/** @var string $token */
/** @var string $csrfToken */
?>
<div class="kuis-public live-play" id="live-play"
     data-token="<?= e($token) ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-sse="<?= e(app_url('kuis/live_sse.php?role=player&token=' . urlencode($token))) ?>"
     data-state="<?= e(app_url('kuis/live_state.php?role=player&token=' . urlencode($token))) ?>"
     data-answer="<?= e(app_url('kuis/live_answer.php')) ?>">
  <div class="kuis-public-card live-play-card">
    <header class="live-play-head">
      <div>
        <p class="muted" style="margin:0;">Halo, <strong id="lp-nama"><?= e((string) ($snapshot['me']['nama'] ?? '')) ?></strong></p>
        <h1 id="lp-judul"><?= e((string) ($snapshot['kuis']['judul'] ?? 'Live')) ?></h1>
        <p class="muted" id="lp-meta"></p>
      </div>
      <div class="live-timer" id="lp-timer" hidden>
        <span class="kuis-timer-label">Waktu</span>
        <strong id="lp-timer-value">--:--</strong>
      </div>
    </header>
    <div class="alert error" id="lp-error" hidden></div>
    <div id="lp-stage"></div>
  </div>
</div>
<script>
(function () {
  var root = document.getElementById('live-play');
  if (!root) return;
  var token = root.getAttribute('data-token');
  var csrf = root.getAttribute('data-csrf');
  var sseUrl = root.getAttribute('data-sse');
  var stateUrl = root.getAttribute('data-state');
  var answerUrl = root.getAttribute('data-answer');
  var snap = <?= json_encode($snapshot, JSON_UNESCAPED_UNICODE) ?>;
  var labels = ['A','B','C','D','E','F'];
  var timerIv = null;
  var usePoll = false;
  var pollIv = null;
  var answering = false;
  var lastSoalId = null;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function pad(n) { return n < 10 ? '0' + n : String(n); }
  function setError(msg) {
    var el = document.getElementById('lp-error');
    if (!msg) { el.hidden = true; el.textContent = ''; return; }
    el.hidden = false; el.textContent = msg;
  }

  function renderBoard(board) {
    if (!board || !board.length) return '<p class="muted">Menunggu skor…</p>';
    return '<ol class="live-board">' + board.map(function (r) {
      var me = snap.me && r.id === snap.me.id ? ' is-me' : '';
      return '<li class="' + me + '"><span class="live-rank">' + r.rank + '</span> <strong>' + esc(r.nama) + '</strong> <span>' + r.skor + '</span></li>';
    }).join('') + '</ol>';
  }

  function tickTimer(s) {
    clearInterval(timerIv);
    var el = document.getElementById('lp-timer');
    var val = document.getElementById('lp-timer-value');
    if (s.status !== 'question' || !s.question_ends_at || (s.me && s.me.answered)) {
      el.hidden = true;
      return;
    }
    el.hidden = false;
    var initial = null;
    function update() {
      var offset = (s.server_now || Math.floor(Date.now()/1000)) - Math.floor(Date.now()/1000);
      var left = Math.max(0, s.question_ends_at - (Math.floor(Date.now()/1000) + offset));
      if (initial === null) initial = Math.max(left, 1);
      var warnAt = Math.max(3, Math.min(10, Math.round(initial * 0.25)));
      val.textContent = pad(Math.floor(left / 60)) + ':' + pad(left % 60);
      el.classList.toggle('is-urgent', left <= warnAt);
      if (left <= 0) clearInterval(timerIv);
    }
    update();
    timerIv = setInterval(update, 250);
  }

  function renderQuestion(s) {
    var soal = s.soal;
    if (!soal) return '<p class="muted">Menunggu soal…</p>';
    if (s.me && s.me.answered) {
      return '<div class="kuis-feedback is-ok"><strong>Jawaban terkirim</strong><p>Tunggu reveal dari guru.</p></div>';
    }
    var html = '<form id="lp-form" class="stack">';
    html += '<fieldset class="kuis-q"><legend>Soal ' + soal.nomor + '</legend>';
    html += '<p class="kuis-q-ask">' + esc(soal.pertanyaan) + '</p>';
    if (soal.gambar) html += '<img class="kuis-q-image" src="' + esc(soal.gambar) + '" alt="">';
    if (soal.tipe === 'isian') {
      html += '<div class="field"><label for="jawaban_teks">Jawaban</label>';
      html += '<input id="jawaban_teks" name="jawaban_teks" type="text" required autocomplete="off"></div>';
    } else {
      html += '<div class="kuis-q-options live-q-options">';
      (soal.opsi || []).forEach(function (opt, idx) {
        html += '<label class="kuis-q-option"><input type="radio" name="jawaban" value="' + opt.i + '" required>';
        html += '<span class="kuis-q-letter">' + esc(labels[idx] || (idx+1)) + '</span><span class="kuis-q-option-body">';
        if (opt.gambar) html += '<img class="kuis-q-option-img" src="' + esc(opt.gambar) + '" alt="' + (opt.teks ? '' : ('Gambar opsi ' + esc(labels[idx] || (idx+1)))) + '">';
        if (opt.teks) html += '<span>' + esc(opt.teks) + '</span>';
        html += '</span></label>';
      });
      html += '</div>';
    }
    html += '</fieldset><button class="btn btn-primary" type="submit">Kirim</button></form>';
    return html;
  }

  function apply(s) {
    if (!s) return;
    snap = s;
    document.getElementById('lp-nama').textContent = (s.me && s.me.nama) || '';
    document.getElementById('lp-judul').textContent = (s.kuis && s.kuis.judul) || 'Live';
    var meta = 'PIN ' + (s.pin || '') + ' · ' + (s.peserta_count || 0) + ' peserta';
    if (s.me) meta += ' · Skor ' + (s.me.skor || 0);
    if (s.status === 'question' || s.status === 'reveal') {
      meta += ' · Soal ' + ((s.current_index || 0) + 1) + '/' + (s.total_soal || 0);
    }
    document.getElementById('lp-meta').textContent = meta;

    var stage = document.getElementById('lp-stage');
    var st = s.status;
    if (st === 'lobby') {
      stage.innerHTML = '<div class="live-wait"><h2>Di lobby</h2><p class="muted">Menunggu guru memulai…</p><p><strong>' + (s.peserta_count || 0) + '</strong> pemain siap</p></div>';
    } else if (st === 'question') {
      if (lastSoalId !== (s.soal && s.soal.id)) {
        lastSoalId = s.soal ? s.soal.id : null;
        answering = false;
        setError('');
      }
      stage.innerHTML = renderQuestion(s);
      var form = document.getElementById('lp-form');
      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          if (answering) return;
          answering = true;
          setError('');
          var payload = { _token: csrf, token: token, tipe: (s.soal && s.soal.tipe) || 'pilihan_ganda' };
          if (payload.tipe === 'isian') {
            payload.jawaban_teks = (form.querySelector('[name=jawaban_teks]') || {}).value || '';
          } else {
            var checked = form.querySelector('input[name=jawaban]:checked');
            payload.jawaban = checked ? parseInt(checked.value, 10) : null;
          }
          fetch(answerUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload),
            credentials: 'same-origin'
          }).then(function (r) { return r.json(); }).then(function (res) {
            if (!res.ok) {
              answering = false;
              setError((res.errors && res.errors._form) || 'Gagal mengirim.');
              return;
            }
            if (res.snapshot) apply(res.snapshot);
          }).catch(function () {
            answering = false;
            setError('Koneksi gagal.');
          });
        });
      }
    } else if (st === 'reveal') {
      var fb = '';
      if (s.me && s.me.answered) {
        fb = '<div class="kuis-feedback ' + (s.me.last_benar ? 'is-ok' : 'is-bad') + '">' +
          '<strong>' + (s.me.last_benar ? 'Benar!' : 'Belum tepat') + '</strong>' +
          '<p>+' + (s.me.last_poin || 0) + ' poin</p></div>';
      } else {
        fb = '<div class="kuis-feedback is-bad"><strong>Tidak dijawab</strong></div>';
      }
      var kunci = (s.soal && (s.soal.benar_teks || s.soal.jawaban_teks)) ? '<p class="muted">Kunci: <strong>' + esc(s.soal.benar_teks || s.soal.jawaban_teks) + '</strong></p>' : '';
      stage.innerHTML = fb + kunci + '<p class="muted">Menunggu guru…</p>';
    } else if (st === 'leaderboard') {
      stage.innerHTML = '<h2>Papan skor</h2>' +
        (s.me && s.me.rank ? '<p>Peringkat Anda: <strong>#' + s.me.rank + '</strong> · ' + s.me.skor + ' poin</p>' : '') +
        renderBoard(s.leaderboard);
    } else if (st === 'podium') {
      stage.innerHTML = '<h2>Podium</h2>' + renderBoard(s.leaderboard) +
        '<p class="muted">Terima kasih sudah bermain!</p>' +
        '<a class="btn btn-ghost" href="<?= e(app_url('kuis/live_join.php?batal=1')) ?>">Keluar</a>';
    } else if (st === 'ended') {
      stage.innerHTML = '<h2>Sesi berakhir</h2>' + renderBoard(s.leaderboard) +
        '<a class="btn btn-primary" href="<?= e(app_url('kuis/live_join.php?batal=1')) ?>">Selesai</a>';
    }
    tickTimer(s);
  }

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
      var es = new EventSource(sseUrl);
      es.addEventListener('state', function (ev) {
        try { apply(JSON.parse(ev.data)); } catch (e) {}
      });
      es.addEventListener('reconnect', function () {
        es.close();
        setTimeout(startSse, 200);
      });
      es.onerror = function () {
        es.close();
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

// Mobile navigation
document.querySelectorAll('.nav-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.querySelector('.nav-links').classList.toggle('open');
  });
});

// Quantity steppers
document.querySelectorAll('.qty').forEach(function (box) {
  var input = box.querySelector('input');
  box.querySelectorAll('button').forEach(function (b) {
    b.addEventListener('click', function () {
      var max = parseInt(input.max || '20', 10);
      var v = (parseInt(input.value, 10) || 1) + (b.dataset.step === 'up' ? 1 : -1);
      input.value = Math.min(max, Math.max(1, v));
      if (box.dataset.autosubmit) { input.form.submit(); }
    });
  });
});

// Product gallery
document.querySelectorAll('.gallery-thumbs button').forEach(function (t) {
  t.addEventListener('click', function () {
    document.querySelector('.gallery-main img').src = t.dataset.src;
    document.querySelectorAll('.gallery-thumbs button').forEach(function (x) { x.classList.remove('on'); });
    t.classList.add('on');
  });
});

// Checkout: live totals as state / payment method change (server recalculates on submit)
(function () {
  var el = document.getElementById('checkout-data');
  if (!el) { return; }
  var d = JSON.parse(el.textContent);
  var state = document.getElementById('state');
  var fmt = function (n) { return '₹' + (Math.round(n * 100) / 100).toLocaleString('en-IN'); };

  function zoneFor(s) {
    var fallback = null, found = null;
    d.zones.forEach(function (z) {
      var list = z.states.toLowerCase().split(',').map(function (x) { return x.trim(); });
      if (!found && s && list.indexOf(s.toLowerCase()) !== -1) { found = z; }
      if (!fallback && list.indexOf('*') !== -1) { fallback = z; }
    });
    return found || fallback;
  }

  function update() {
    var m = document.querySelector('input[name=payment_method]:checked');
    var z = zoneFor(state.value);
    var after = d.subtotal - d.discount;
    var ship = 0, cod = 0;
    if (z) {
      ship = (parseFloat(z.free_above) > 0 && after >= parseFloat(z.free_above)) ? 0 : parseFloat(z.rate);
      cod = (m && m.value === 'cod') ? parseFloat(z.cod_fee) : 0;
    }
    document.getElementById('sum-shipping').textContent = !state.value ? 'Select state' : (ship === 0 ? 'FREE' : fmt(ship));
    document.getElementById('sum-cod-row').style.display = cod > 0 ? 'flex' : 'none';
    document.getElementById('sum-cod').textContent = fmt(cod);
    document.getElementById('sum-total').textContent = fmt(after + ship + cod);
    document.getElementById('sum-eta').textContent = (z && state.value && z.eta) ? 'Estimated delivery: ' + z.eta : '';
  }

  state.addEventListener('change', update);
  document.querySelectorAll('input[name=payment_method]').forEach(function (r) { r.addEventListener('change', update); });
  update();
})();

// Homepage hero slider
(function () {
  var box = document.querySelector('.hslider');
  if (!box) { return; }
  var slides = box.querySelectorAll('.hs-slide'), dots = box.querySelectorAll('.hs-dots button'), i = 0, timer;
  var track = box.classList.contains('full') ? box.querySelector('.hs-track') : null; // full-width banners scroll sideways
  function go(n) {
    i = (n + slides.length) % slides.length;
    slides.forEach(function (s, k) { s.classList.toggle('on', k === i); });
    dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
    if (track) { track.style.transform = 'translateX(' + (-i * 100) + '%)'; }
  }
  function auto() { clearInterval(timer); timer = setInterval(function () { go(i + 1); }, 4000); }
  box.querySelector('.prev').addEventListener('click', function () { go(i - 1); auto(); });
  box.querySelector('.next').addEventListener('click', function () { go(i + 1); auto(); });
  dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); auto(); }); });
  if (slides.length < 2) { return; }
  // Swipe on touch screens.
  var startX = null;
  box.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', function (e) {
    if (startX === null) { return; }
    var dx = e.changedTouches[0].clientX - startX;
    startX = null;
    if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); auto(); }
  });
  auto();
})();

// Email verification: "Send code" emails a 6-digit code, "Verify" checks it (verify-email.php).
document.querySelectorAll('[data-ev]').forEach(function (box) {
  var send = box.querySelector('[data-ev-send]');
  if (!send) { return; } // verification switched off in admin settings
  var form = box.closest('form');
  var input = box.querySelector('input[type=email]');
  var codeRow = box.querySelector('.ev-code');
  var code = box.querySelector('[data-ev-code]');
  var check = box.querySelector('[data-ev-check]');
  var msg = box.querySelector('[data-ev-msg]');
  var timer = null;

  function current() { return input.value.trim().toLowerCase(); }
  function isVerified() { return box.dataset.verified !== '' && box.dataset.verified === current(); }
  function say(text, ok) { msg.textContent = text; msg.className = 'ev-msg ' + (ok ? 'ok' : 'err'); }

  function render() {
    clearInterval(timer);
    send.disabled = false;
    if (isVerified()) {
      send.hidden = true;
      codeRow.hidden = true;
      box.classList.add('done');
      say('✓ Email verified', true);
    } else {
      send.hidden = false;
      send.textContent = 'Send code';
      box.classList.remove('done');
      codeRow.hidden = true;
      msg.textContent = '';
    }
  }

  function cooldown(sec) {
    send.disabled = true;
    timer = setInterval(function () {
      sec -= 1;
      send.textContent = sec > 0 ? 'Resend in ' + sec + 's' : 'Resend code';
      if (sec <= 0) { clearInterval(timer); send.disabled = false; }
    }, 1000);
  }

  function call(data, done) {
    var body = new FormData();
    body.append('csrf', form.querySelector('input[name=csrf]').value);
    body.append('email', current());
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    fetch(box.dataset.endpoint, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(done)
      .catch(function () { done({ ok: false, message: 'Something went wrong. Please refresh the page and try again.' }); });
  }

  send.addEventListener('click', function () {
    if (!input.checkValidity() || current() === '') { say('Please enter a valid email address first.', false); input.focus(); return; }
    send.disabled = true;
    send.textContent = 'Sending…';
    call({ action: 'send', context: box.dataset.context }, function (res) {
      if (res.ok && res.verified) { box.dataset.verified = current(); render(); return; }
      say(res.message, res.ok);
      if (res.ok) {
        codeRow.hidden = false;
        code.value = '';
        code.focus();
        cooldown(60);
      } else {
        send.disabled = false;
        send.textContent = 'Send code';
      }
    });
  });

  function verify() {
    if (!/^\d{6}$/.test(code.value.trim())) { say('Please enter the 6-digit code from the email.', false); code.focus(); return; }
    check.disabled = true;
    call({ action: 'verify', code: code.value.trim() }, function (res) {
      check.disabled = false;
      if (res.ok) { box.dataset.verified = current(); render(); } else { say(res.message, false); }
    });
  }
  check.addEventListener('click', verify);
  code.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); verify(); } });
  input.addEventListener('input', render);

  form.addEventListener('submit', function (ev) {
    if (!isVerified()) {
      ev.preventDefault();
      say('Please verify your email address before continuing.', false);
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  render();
});

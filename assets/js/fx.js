// FX layer: scroll progress, parallax, 3D card tilt, magnetic buttons, custom cursor, spotlight.
// Uses the independent `translate` / `rotate` CSS properties so it never fights the reveal transforms.
(function () {
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var root = document.documentElement;
  var $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };

  // ---- Scroll progress bar -------------------------------------------------
  var bar = document.createElement('div');
  bar.className = 'fx-progress';
  document.body.appendChild(bar);

  // ---- Parallax targets ----------------------------------------------------
  // [selector, speed] — positive speed moves slower than scroll (depth), negative faster.
  var parallax = [];
  [
    ['.why .pic img', 0.08], ['.about-pic img', 0.08], ['.split img', 0.06],
    ['.hero-art', -0.06], ['.hero h1', 0.04], ['.page-head .container', 0.1],
    ['.step', 0.03], ['.cta', 0.04]
  ].forEach(function (p) {
    $$(p[0]).forEach(function (el, i) { parallax.push({ el: el, speed: p[1] * (p[0] === '.step' ? (i % 3 - 1) : 1) }); });
  });

  var ticking = false;
  function onScroll() {
    if (ticking) { return; }
    ticking = true;
    requestAnimationFrame(function () {
      ticking = false;
      var y = window.scrollY, vh = window.innerHeight;
      var max = document.documentElement.scrollHeight - vh;
      bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(y / max, 1) : 0) + ')';
      root.style.setProperty('--scroll', y.toFixed(0));
      if (reduce) { return; }
      parallax.forEach(function (p) {
        var r = p.el.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) { return; }
        var offset = (r.top + r.height / 2 - vh / 2) * p.speed;
        p.el.style.translate = '0 ' + (-offset).toFixed(1) + 'px';
      });
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  onScroll();

  if (reduce || !fine) { return; }

  // ---- 3D tilt + glare on cards -------------------------------------------
  $$('.p-card, .benefit, .review, .step, .card, .stat, .value').forEach(function (el) {
    if (el.querySelector('input, select, textarea, table') || document.body.classList.contains('static-page')) { return; } // keep forms and tables still
    el.classList.add('fx-tilt');
    var glare = document.createElement('span');
    glare.className = 'fx-glare';
    el.appendChild(glare);
    var strength = el.classList.contains('p-card') ? 9 : 6;
    el.addEventListener('pointermove', function (e) {
      var r = el.getBoundingClientRect();
      var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
      el.style.setProperty('--ry', ((px - 0.5) * strength * 2).toFixed(2) + 'deg');
      el.style.setProperty('--rx', ((0.5 - py) * strength * 2).toFixed(2) + 'deg');
      el.style.setProperty('--gx', (px * 100).toFixed(1) + '%');
      el.style.setProperty('--gy', (py * 100).toFixed(1) + '%');
      el.classList.add('tilting');
    });
    el.addEventListener('pointerleave', function () { el.classList.remove('tilting'); });
  });

  // ---- About image: 3D tilt follows the mouse ------------------------------
  $$('.about-hero').forEach(function (sec) {
    var pic = sec.querySelector('.about-3d');
    if (!pic) { return; }
    sec.addEventListener('pointermove', function (e) {
      var r = sec.getBoundingClientRect();
      pic.style.setProperty('--ry', (((e.clientX - r.left) / r.width - 0.5) * 22).toFixed(1) + 'deg');
      pic.style.setProperty('--rx', ((0.5 - (e.clientY - r.top) / r.height) * 16).toFixed(1) + 'deg');
    });
    sec.addEventListener('pointerleave', function () { pic.style.setProperty('--ry', '0deg'); pic.style.setProperty('--rx', '0deg'); });
  });

  // ---- Magnetic buttons ----------------------------------------------------
  $$('.btn, .cart-link, .wa-float').forEach(function (el) {
    el.classList.add('fx-magnet');
    el.addEventListener('pointermove', function (e) {
      var r = el.getBoundingClientRect();
      var dx = (e.clientX - (r.left + r.width / 2)) * 0.28;
      var dy = (e.clientY - (r.top + r.height / 2)) * 0.4;
      el.style.translate = dx.toFixed(1) + 'px ' + dy.toFixed(1) + 'px';
    });
    el.addEventListener('pointerleave', function () { el.style.translate = ''; });
  });

  // ---- Hero spotlight that follows the pointer ----------------------------
  $$('.hero, .page-head, .dark-screen, .band').forEach(function (el) {
    var spot = document.createElement('div');
    spot.className = 'fx-spot';
    el.insertBefore(spot, el.firstChild);
    el.addEventListener('pointermove', function (e) {
      var r = el.getBoundingClientRect();
      spot.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      spot.style.setProperty('--my', (e.clientY - r.top) + 'px');
      spot.style.opacity = 1;
    });
    el.addEventListener('pointerleave', function () { spot.style.opacity = 0; });
  });

  // ---- Custom cursor: dot + trailing ring ---------------------------------
  var dot = document.createElement('div'), ring = document.createElement('div');
  dot.className = 'fx-dot'; ring.className = 'fx-ring';
  document.body.appendChild(ring); document.body.appendChild(dot);
  root.classList.add('fx-cursor');
  var mx = -100, my = -100, rx = -100, ry = -100;
  window.addEventListener('pointermove', function (e) {
    mx = e.clientX; my = e.clientY;
    dot.style.transform = 'translate(' + mx + 'px,' + my + 'px)';
    dot.style.opacity = ring.style.opacity = 1;
  });
  document.addEventListener('pointerleave', function () { dot.style.opacity = ring.style.opacity = 0; });
  (function loop() {
    rx += (mx - rx) * 0.16; ry += (my - ry) * 0.16;
    ring.style.transform = 'translate(' + rx.toFixed(1) + 'px,' + ry.toFixed(1) + 'px)';
    requestAnimationFrame(loop);
  })();
  document.addEventListener('pointerover', function (e) {
    ring.classList.toggle('hover', !!e.target.closest('a, button, summary, input, select, textarea, .p-card'));
  });
  document.addEventListener('pointerdown', function () { ring.classList.add('down'); });
  document.addEventListener('pointerup', function () { ring.classList.remove('down'); });
})();

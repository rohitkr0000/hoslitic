// Motion: header state, word-by-word headings, scroll reveal
(function () {
  var header = document.querySelector('.site-header');
  var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 12); };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Wrap each word of a heading so it can rise into place.
  function splitWords(el) {
    var i = 0;
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (ch) {
        if (ch.nodeType === 3) {
          var frag = document.createDocumentFragment();
          ch.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) { return; }
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
            var outer = document.createElement('span');
            outer.className = 'w';
            var inner = document.createElement('span');
            inner.style.setProperty('--i', i++);
            inner.textContent = part;
            outer.appendChild(inner);
            frag.appendChild(outer);
          });
          node.replaceChild(frag, ch);
        } else if (ch.nodeType === 1) {
          walk(ch);
        }
      });
    })(el);
    el.classList.add('words');
  }

  var headings = Array.prototype.slice.call(document.querySelectorAll('main h1, main h2'));
  headings.forEach(splitWords);

  var blocks = Array.prototype.slice.call(document.querySelectorAll(
    'main .kicker, main .lead, .hero-cta, .hero-trust, .benefit, .p-card, .step, .review, .faq details, main .card, .split > *, .cta, .pd > *, .page-head p, .crumbs, .value, .stat, .why .pic, .about-pic, .rotator'
  ));
  blocks.forEach(function (el) {
    el.classList.add('rv');
    var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
    el.style.setProperty('--d', Math.min(idx, 5));
  });

  // Typing phrase: <b data-rotate="one|two|three">
  document.querySelectorAll('[data-rotate]').forEach(function (el) {
    var words = el.dataset.rotate.split('|'), w = 0, c = 0, del = false;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = words[0]; return; }
    (function tick() {
      var word = words[w];
      c += del ? -1 : 1;
      el.textContent = word.slice(0, c);
      var wait = del ? 35 : 75;
      if (!del && c === word.length) { del = true; wait = 1700; }
      else if (del && c === 0) { del = false; w = (w + 1) % words.length; wait = 350; }
      setTimeout(tick, wait);
    })();
  });

  // Count-up numbers: <b data-count="60">
  var counters = Array.prototype.slice.call(document.querySelectorAll('[data-count]'));
  function countUp(el) {
    var target = parseInt(el.dataset.count, 10), start = null;
    (function step(ts) {
      start = start || ts;
      var p = Math.min((ts - start) / 1400, 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) { requestAnimationFrame(step); }
    })(performance.now());
  }

  var all = headings.concat(blocks);
  if (/noanim/.test(location.search)) { // preview helper: show everything immediately
    all.forEach(function (el) { el.classList.add('in'); });
    return;
  }
  if (!('IntersectionObserver' in window)) {
    all.forEach(function (el) { el.classList.add('in'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -5% 0px' });
  all.forEach(function (el) { io.observe(el); });
  var cio = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { countUp(en.target); cio.unobserve(en.target); }
    });
  }, { threshold: 0.6 });
  counters.forEach(function (el) { cio.observe(el); });
})();

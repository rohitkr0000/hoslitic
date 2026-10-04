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

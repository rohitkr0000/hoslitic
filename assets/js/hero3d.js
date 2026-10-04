// Home hero: 3D capsules orbiting the product (three.js) + mouse tilt on the product image.
(function () {
  var art = document.querySelector('.hero-art');
  if (!art) { return; }
  var hero = document.querySelector('.hero');
  var tilt = art.querySelector('.tilt');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var pointer = { x: 0, y: 0 };

  hero.addEventListener('pointermove', function (e) {
    var r = hero.getBoundingClientRect();
    pointer.x = ((e.clientX - r.left) / r.width - 0.5) * 2;
    pointer.y = ((e.clientY - r.top) / r.height - 0.5) * 2;
    if (tilt && !reduce) {
      tilt.style.transform = 'rotateY(' + (pointer.x * 9).toFixed(2) + 'deg) rotateX(' + (-pointer.y * 7).toFixed(2) + 'deg)';
    }
  });
  hero.addEventListener('pointerleave', function () {
    pointer.x = 0; pointer.y = 0;
    if (tilt) { tilt.style.transform = ''; }
  });

  var canvas = art.querySelector('canvas');
  if (!canvas || !window.THREE || reduce) { return; }

  var renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
  } catch (err) { return; }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.outputEncoding = THREE.sRGBEncoding;

  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(40, 1, 0.1, 50);
  camera.position.z = 10;

  scene.add(new THREE.AmbientLight(0xffffff, 0.55));
  var key = new THREE.DirectionalLight(0xffffff, 0.75);
  key.position.set(4, 6, 8);
  scene.add(key);
  var rim = new THREE.DirectionalLight(0xb9dc55, 0.35);
  rim.position.set(-6, -3, 2);
  scene.add(rim);

  // Two-tone capsule built from two half-cylinders and two hemispheres.
  function capsule(colorA, colorB, scale) {
    var g = new THREE.Group();
    var r = 0.2, h = 0.34;
    [[colorA, 1], [colorB, -1]].forEach(function (half) {
      var mat = new THREE.MeshStandardMaterial({ color: half[0], roughness: 0.28, metalness: 0.05 });
      var body = new THREE.Mesh(new THREE.CylinderGeometry(r, r, h, 28), mat);
      body.position.y = half[1] * h / 2;
      var cap = new THREE.Mesh(new THREE.SphereGeometry(r, 28, 16, 0, Math.PI * 2, 0, Math.PI / 2), mat);
      cap.position.y = half[1] * h;
      if (half[1] < 0) { cap.rotation.x = Math.PI; }
      g.add(body); g.add(cap);
    });
    g.scale.setScalar(scale);
    return g;
  }

  var orbit = new THREE.Group();
  scene.add(orbit);
  var items = [];
  var palette = [[0x3d8b37, 0xf4f0e2], [0x6fae3c, 0x14432a], [0xb9dc55, 0xf4f0e2], [0xf2952b, 0xf4f0e2]];
  var COUNT = 16;
  for (var i = 0; i < COUNT; i++) {
    var pair = palette[i % palette.length];
    var m = capsule(pair[0], pair[1], 0.6 + Math.random() * 0.55);
    var a = (i / COUNT) * Math.PI * 2 + Math.random() * 0.3;
    var radius = 2.95 + Math.random() * 0.7; // sits around the rim of the product image
    m.position.set(Math.cos(a) * radius, Math.sin(a) * radius, (Math.random() - 0.5) * 3);
    m.rotation.set(Math.random() * 3, Math.random() * 3, Math.random() * 3);
    orbit.add(m);
    items.push({ mesh: m, baseY: m.position.y, phase: Math.random() * 6.28, spin: 0.2 + Math.random() * 0.5 });
  }
  // Small berries for depth
  for (var j = 0; j < 10; j++) {
    var berry = new THREE.Mesh(
      new THREE.SphereGeometry(0.07 + Math.random() * 0.06, 18, 14),
      new THREE.MeshStandardMaterial({ color: j % 2 ? 0xc0392b : 0xb9dc55, roughness: 0.4 })
    );
    var ba = Math.random() * Math.PI * 2, br = 2.5 + Math.random() * 1.1;
    berry.position.set(Math.cos(ba) * br, Math.sin(ba) * br, (Math.random() - 0.5) * 3.5);
    orbit.add(berry);
    items.push({ mesh: berry, baseY: berry.position.y, phase: Math.random() * 6.28, spin: 0 });
  }
  // Tilted orbit rings
  var ringMat = new THREE.MeshBasicMaterial({ color: 0xb9dc55, transparent: true, opacity: 0.45 });
  var ring1 = new THREE.Mesh(new THREE.TorusGeometry(3.25, 0.012, 8, 160), ringMat);
  ring1.rotation.x = 1.15;
  var ring2 = new THREE.Mesh(new THREE.TorusGeometry(3.45, 0.008, 8, 160), ringMat);
  ring2.rotation.set(1.3, 0.5, 0);
  scene.add(ring1); scene.add(ring2);

  function resize() {
    var w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) { return; }
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
  }
  resize();
  window.addEventListener('resize', resize);

  var visible = true;
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }).observe(canvas);
  }

  var clock = new THREE.Clock();
  (function frame() {
    requestAnimationFrame(frame);
    if (!visible || document.hidden) { return; }
    var t = clock.getElapsedTime();
    var sp = Math.min(window.scrollY / 700, 1.5); // scroll-driven 3D: orbit spins and drifts as you scroll
    orbit.rotation.z = t * 0.07 + sp * 1.2;
    orbit.position.y = sp * 0.8;
    orbit.scale.setScalar(1 + sp * 0.12);
    orbit.rotation.y += (pointer.x * 0.35 - orbit.rotation.y) * 0.05;
    orbit.rotation.x += (pointer.y * 0.25 - orbit.rotation.x) * 0.05;
    ring1.rotation.z = t * 0.15;
    ring2.rotation.z = -t * 0.1;
    for (var k = 0; k < items.length; k++) {
      var it = items[k];
      it.mesh.position.y = it.baseY + Math.sin(t * 0.8 + it.phase) * 0.14;
      it.mesh.rotation.x += it.spin * 0.012;
      it.mesh.rotation.z += it.spin * 0.008;
    }
    renderer.render(scene, camera);
  })();
  art.classList.add('has-3d');
})();

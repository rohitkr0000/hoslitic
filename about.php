<?php
require __DIR__ . '/includes/init.php';

$first = products_with_image()[0] ?? null;
$imgs = $first ? product_images((int) $first['id']) : [];
$pic = $imgs[1] ?? ($imgs[0] ?? null);
$pageTitle = 'About us';
$metaDescription = 'Holistic Global Services is a Noida-based wellness company bringing Ayurvedic formulations like Jointex to homes across India.';
$activeNav = 'about';
require __DIR__ . '/includes/header.php';

$icon = [
    'leaf' => '<path d="M5 19c0-8 5-13 14-14 0 9-5 14-14 14z"/><path d="M5 19c3-5 6-8 10-10"/>',
    'shield' => '<path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
    'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    'truck' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="17.5" cy="17.5" r="1.5"/>',
];
?>
<section class="screen about-hero" id="story">
  <div class="container about-grid">
    <div>
      <span class="kicker">Our story</span>
      <h1>Ancient wisdom. <em>Everyday movement.</em></h1>
      <p class="lead">Holistic Global Services is a Noida-based wellness company. We bring Ayurvedic formulations to homes across India, starting with Jointex — a capsule made to support joint comfort and an active life.</p>
      <p class="lead">We sell directly to you, so every bottle is genuine, every price is clear, and every order is handled by people who answer your questions.</p>
      <div class="stats">
        <div class="stat"><b data-count="60">60</b><span>capsules in our large bottle</span></div>
        <div class="stat"><b data-count="2">2</b><span>capsules a day, after meals</span></div>
        <div class="stat"><b><span data-count="100">100</span>%</b><span>Ayurvedic herbal formula</span></div>
        <div class="stat"><b>1–2</b><span>business days to dispatch</span></div>
      </div>
    </div>
    <?php if ($pic): ?>
      <div class="about-pic">
        <div class="about-3d">
          <span class="orbit o1" aria-hidden="true"><i>GMP</i></span>
          <span class="orbit o2" aria-hidden="true"><i>ISO</i></span>
          <span class="orbit o3" aria-hidden="true"><i>100% Natural</i></span>
          <img src="<?= e(product_image_url($pic)) ?>" alt="Jointex Ayurvedic capsules">
        </div>
      </div>
    <?php endif; ?>
    <span class="about-dots" aria-hidden="true"><?php for ($i = 0; $i < 14; $i++) { echo '<i style="--x:' . (($i * 37) % 100) . '%;--s:' . (6 + $i % 5 * 3) . 'px;--t:' . (9 + $i % 6 * 2) . 's;--dl:-' . ($i * 1.3) . 's"></i>'; } ?></span>
  </div>
</section>

<section class="screen" id="values">
  <div class="container">
    <div class="screen-head">
      <span class="kicker">What we stand for</span>
      <h2>Simple promises, kept</h2>
      <p class="lead">Wellness should be honest. This is how we run our store.</p>
    </div>
    <div class="values">
      <div class="value"><div class="ico"><svg viewBox="0 0 24 24"><?= $icon['leaf'] ?></svg></div><h3>Ayurvedic by design</h3><p>Our formulation draws on traditional Ayurvedic herbs, in easy vegetarian capsules.</p></div>
      <div class="value"><div class="ico"><svg viewBox="0 0 24 24"><?= $icon['shield'] ?></svg></div><h3>Quality first</h3><p>Made in a GMP and ISO certified facility, sealed and tamper-evident when it reaches you.</p></div>
      <div class="value"><div class="ico"><svg viewBox="0 0 24 24"><?= $icon['eye'] ?></svg></div><h3>Honest information</h3><p>We tell you plainly what the product is for, how to take it, and when to ask your physician.</p></div>
      <div class="value"><div class="ico"><svg viewBox="0 0 24 24"><?= $icon['truck'] ?></svg></div><h3>Direct to you</h3><p>Secure online payment or Cash on Delivery, with delivery across India and live order tracking.</p></div>
    </div>
  </div>
</section>

<section class="screen" id="mission">
  <div class="container mission">
    <div>
      <span class="kicker">Our mission</span>
      <h2>Make everyday movement easier</h2>
      <p class="lead">Joint comfort matters at every age: for the long commute, the weekend game, the walk with grandchildren. We want a trusted Ayurvedic option to be simple to find, clear to understand and easy to order.</p>
      <ul class="checks">
        <li>Plain-language product information and honest guidance</li>
        <li>Transparent pricing, GST included, no hidden charges</li>
        <li>Real people to answer questions before and after you buy</li>
      </ul>
      <a class="btn btn-primary" href="<?= e(url('shop.php')) ?>">Explore Jointex</a>
    </div>
    <div class="mission-cards">
      <div class="mc"><b>Ayurvedic</b><span>Traditional herbal approach</span></div>
      <div class="mc"><b>Vegetarian</b><span>Easy-to-take capsules</span></div>
      <div class="mc"><b>GMP &amp; ISO</b><span>Certified facility</span></div>
      <div class="mc"><b>All India</b><span>Delivery with tracking</span></div>
    </div>
  </div>
</section>

<section class="screen" id="company">
  <div class="container">
    <div class="screen-head">
      <span class="kicker">The company</span>
      <h2>Who is behind Jointex</h2>
    </div>
    <div class="company">
      <div class="card">
        <dl>
          <dt>Trade name</dt><dd><?= e(setting('site_name')) ?></dd>
          <?php if (setting('legal_name') !== ''): ?><dt>Proprietor</dt><dd><?= e(setting('legal_name')) ?></dd><?php endif; ?>
          <dt>Business type</dt><dd>Proprietorship</dd>
          <dt>GSTIN</dt><dd><?= e(setting('gstin')) ?></dd>
          <?php if (setting('fssai') !== ''): ?><dt>FSSAI Lic. no.</dt><dd><?= e(setting('fssai')) ?></dd><?php endif; ?>
          <dt>Office</dt><dd><?= e(setting('address')) ?></dd>
        </dl>
        <div class="cert-row"><span class="cert">GMP</span><span class="cert">ISO Certified</span><span class="cert">100% Natural</span><span class="cert">Vegetarian</span></div>
      </div>
      <div class="cta" style="margin-top:0;flex-direction:column;align-items:flex-start;justify-content:center">
        <div><h2>Feel the difference</h2><p>Try Jointex and move through your day with ease.</p></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Shop now</a>
          <a class="btn btn-light" href="<?= e(url('contact.php')) ?>">Contact us</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
sec_delivery_steps();
sec_faq(4);
sec_help();
require __DIR__ . '/includes/footer.php';


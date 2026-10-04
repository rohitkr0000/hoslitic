<?php
require __DIR__ . '/includes/init.php';

$products = products_with_image();
$featured = null;
foreach ($products as $p) {
    if ($p['is_featured']) { $featured = $p; break; }
}
$featured = $featured ?? ($products[0] ?? null);
$gallery = $featured ? product_images((int) $featured['id']) : [];
$reviews = rows("SELECT r.*, p.name AS product_name FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.status = 'approved' ORDER BY r.rating DESC, r.id DESC LIMIT 3");
$faqs = rows('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id');
$activeNav = 'home';
$heroScene = true; // footer loads the 3D hero scripts
$heroParts = explode('. ', setting('hero_title'), 2); // "Move freely. Live fully." → second sentence highlighted
$ribbon = ['Ayurvedic formulation', 'GMP certified facility', 'ISO certified', 'Vegetarian capsules', 'Cash on Delivery', 'Secure online payment', 'Delivery across India'];
require __DIR__ . '/includes/header.php';
?>
<section class="hero screen" id="hero">
  <div class="container hero-grid">
    <div>
      <span class="kicker"><?= e(setting('hero_kicker')) ?></span>
      <h1><?= e($heroParts[0]) . (isset($heroParts[1]) ? '. <em>' . e($heroParts[1]) . '</em>' : '') ?></h1>
      <p class="rotator">Support for <b data-rotate="comfortable joints|easy, flexible movement|an active lifestyle|everyday wellness"></b></p>
      <p class="lead"><?= e(setting('hero_text')) ?></p>
      <div class="hero-cta">
        <?php if ($featured): ?>
          <a class="btn btn-primary" href="<?= e(url('product.php?slug=' . urlencode($featured['slug']))) ?>">Shop Jointex – <?= money($featured['price']) ?></a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="#how">How to use</a>
      </div>
      <ul class="hero-trust">
        <li>Ayurvedic formulation</li>
        <li>GMP &amp; ISO certified facility</li>
        <li>Vegetarian capsules</li>
      </ul>
    </div>
    <?php if ($featured): ?>
      <div class="hero-art">
        <div class="tilt">
          <div class="floaty"><img src="<?= e(product_image_url($featured['image'])) ?>" alt="<?= e($featured['name']) ?>"></div>
          <canvas aria-hidden="true"></canvas>
          <div class="hero-tag"><?= e($featured['pack_label']) ?><small>1 capsule, twice a day</small></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <div class="ribbon-wrap" aria-hidden="true">
    <div class="ribbon"><div class="ribbon-track">
      <?php for ($i = 0; $i < 4; $i++) { foreach ($ribbon as $word) { echo '<span>' . e($word) . '</span>'; } } ?>
    </div></div>
  </div>
</section>

<section class="screen" id="packs">
  <div class="container">
    <div class="screen-head">
      <span class="kicker">Choose your pack</span>
      <h2>Jointex, delivered to your door</h2>
      <p class="lead">Prices include GST. Pay online or choose Cash on Delivery.</p>
    </div>
    <div class="products four">
      <?php foreach ($products as $p) { product_card($p); } ?>
    </div>
    <?php if (!$products): ?><p class="muted center">Products will appear here once added from the admin panel.</p><?php endif; ?>
    <p class="center" style="margin:16px 0 0"><a class="btn btn-ghost btn-sm" href="<?= e(url('shop.php')) ?>">View all products</a></p>
  </div>
</section>

<section class="screen" id="why">
  <div class="container why">
    <?php $whyImg = $gallery[1] ?? ($gallery[0] ?? null); ?>
    <?php if ($whyImg): ?><div class="pic rv"><img src="<?= e(product_image_url($whyImg)) ?>" alt="<?= $featured ? e($featured['name']) : 'Jointex' ?>" loading="lazy"></div><?php endif; ?>
    <div>
      <span class="kicker">Why Jointex</span>
      <h2>Rooted in Ayurveda, made for modern days</h2>
      <p class="lead">Long hours at a desk, daily commutes, weekend sport — your joints work hard. Jointex brings a traditional herbal approach into an easy capsule.</p>
      <div class="benefits">
        <div class="benefit"><div class="ico"><svg viewBox="0 0 24 24"><circle cx="7.5" cy="7.5" r="3.5"/><circle cx="16.5" cy="16.5" r="3.5"/><path d="M10 10l4 4"/></svg></div><h3>Joint comfort</h3><p>Formulated to support comfortable joints through your day.</p></div>
        <div class="benefit"><div class="ico"><svg viewBox="0 0 24 24"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg></div><h3>Mobility</h3><p>Helps you stay flexible and keep moving the way you like.</p></div>
        <div class="benefit"><div class="ico"><svg viewBox="0 0 24 24"><path d="M5 19c0-8 5-13 14-14 0 9-5 14-14 14z"/><path d="M5 19c3-5 6-8 10-10"/></svg></div><h3>Herbal formula</h3><p>An Ayurvedic blend in easy-to-take vegetarian capsules.</p></div>
        <div class="benefit"><div class="ico"><svg viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></div><h3>Quality made</h3><p>Manufactured in a GMP and ISO certified facility.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="screen dark-screen" id="how">
  <div class="container">
    <div class="screen-head">
      <span class="kicker">How to use</span>
      <h2>A simple twice-a-day routine</h2>
      <p class="lead">Swallow with normal water, or as directed by your physician.</p>
    </div>
    <div class="steps">
      <div class="step"><h3>Morning</h3><p>Take 1 capsule after breakfast with a glass of normal water.</p></div>
      <div class="step"><h3>Night</h3><p>Take 1 capsule after dinner with a glass of normal water.</p></div>
      <div class="step"><h3>Stay regular</h3><p>Use daily, alongside a balanced diet and gentle movement.</p></div>
    </div>
  </div>
</section>

<?php if ($reviews): ?>
<section class="screen" id="reviews">
  <div class="container">
    <div class="screen-head"><span class="kicker">Customer reviews</span><h2>What our customers say</h2></div>
    <div class="reviews" style="text-align:left">
      <?php foreach ($reviews as $r): ?>
        <div class="review">
          <?= stars((float) $r['rating']) ?>
          <p><?= nl2br(e($r['comment'])) ?></p>
          <cite><?= e($r['name']) ?></cite><time><?= e(date('d M Y', strtotime($r['created_at']))) ?></time>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="screen" id="faq">
  <div class="container">
    <?php if ($faqs): ?>
      <div class="screen-head"><span class="kicker">Questions</span><h2>Good to know</h2></div>
      <div class="faq cols">
        <?php foreach ($faqs as $f): ?>
          <details><summary><?= e($f['question']) ?></summary><p><?= nl2br(e($f['answer'])) ?></p></details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="cta">
      <div><h2>Ready to get started?</h2><p>Order today — dispatched within 1–2 business days.</p></div>
      <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Shop now</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

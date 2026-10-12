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

$heroParts = explode('. ', setting('hero_title'), 2); // "Move freely. Live fully." → second sentence highlighted
$allImgs = [];
foreach ($products as $__p) { foreach (product_images((int) $__p['id']) as $__f) { $allImgs[] = $__f; } }
$ribbon = ['Ayurvedic formulation', 'GMP certified facility', 'ISO certified', 'Vegetarian capsules', 'Cash on Delivery', 'Secure online payment', 'Delivery across India'];
require __DIR__ . '/includes/header.php';
?>
<?php
$buy = $featured ? url('product.php?slug=' . urlencode($featured['slug'])) : url('shop.php');
// Built-in hero carousel, used until banners are added in the admin. Wide photos fill the banner;
// square ones are shown whole over a blurred copy of themselves instead of being cropped. Image only, no text.
$wide = $square = [];
foreach ($allImgs as $__f) {
    $__s = @getimagesize(ROOT_PATH . '/uploads/products/' . $__f);
    if ($__s && $__s[0] >= $__s[1] * 1.3) { $wide[] = $__f; } elseif ($__s && $__s[0] >= $__s[1] * 0.9) { $square[] = $__f; }
}
$heroImgs = array_slice(array_merge($wide, $square), 0, 3);
$copy = [
  ['k' => setting('hero_kicker'), 't' => setting('hero_title'), 'p' => setting('hero_text'), 'cta' => 'Shop now', 'href' => $buy],
  ['k' => 'Rooted in Ayurveda', 't' => 'Herbs your grandmother would trust.', 'p' => 'A traditional herbal approach in an easy vegetarian capsule.', 'cta' => 'See ingredients', 'href' => $buy],
  ['k' => 'Simple routine', 't' => 'One capsule. Twice a day.', 'p' => 'Take after breakfast and dinner with normal water.', 'cta' => 'View all products', 'href' => url('shop.php')],
];
$slides = [];
foreach ($heroImgs as $i => $__f) {
    $slides[] = $copy[$i] + ['src' => product_image_url($__f), 'mobile' => null, 'text' => false, 'fit' => in_array($__f, $wide, true) ? 'builtin' : 'builtin contain'];
}
$slides = $slides ?: [$copy[0] + ['src' => url('assets/img/placeholder.svg'), 'mobile' => null, 'text' => false, 'fit' => 'builtin contain']];
$concerns = [
  ['Joint Comfort', product_image_url($allImgs[0] ?? null), $buy],
  ['Mobility & Flexibility', product_image_url($allImgs[2] ?? null), $buy],
  ['Daily Wellness', product_image_url($allImgs[4] ?? null), url('shop.php')],
  ['Shop Combos', product_image_url($allImgs[5] ?? null), url('shop.php')],
];
// Admin-managed content replaces the built-in defaults above as soon as some is added.
$banners = rows('SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order, id');
if ($banners) {
    $slides = [];
    foreach ($banners as $b) {
        $slides[] = ['src' => media_url($b['image']), 'mobile' => $b['mobile_image'] !== '' ? media_url($b['mobile_image']) : null, 'text' => (bool) $b['show_text'],
            'k' => $b['kicker'], 't' => $b['title'], 'p' => $b['body'], 'cta' => $b['button_label'], 'href' => $b['link'] !== '' ? site_link($b['link']) : url('shop.php')];
    }
}
$homeCats = rows('SELECT name, slug, image FROM categories WHERE is_active = 1 AND show_on_home = 1 ORDER BY sort_order, name');
if ($homeCats) {
    $concerns = [];
    foreach ($homeCats as $c) {
        $concerns[] = [$c['name'], media_url($c['image']), url('shop.php?cat=' . urlencode($c['slug']))];
    }
}
$insta = rows('SELECT * FROM insta_posts WHERE is_active = 1 ORDER BY sort_order, id LIMIT 8');
$posts = rows('SELECT title, slug, image, excerpt, created_at FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC, id DESC LIMIT 6');
?>
<?php if (empty($slides[0]['text'])): ?><h1 class="sr-only"><?= e(setting('site_name') . ' – ' . setting('tagline')) ?></h1><?php endif; ?>
<section class="hslider full<?= count($slides) < 2 ? ' single' : '' ?>" id="hero" aria-roledescription="carousel">
  <div class="hs-track">
    <?php foreach ($slides as $i => $sl): ?>
      <div class="hs-slide<?= $i === 0 ? ' on' : '' ?><?= $sl['text'] ? ' has-text' : '' ?><?= !empty($sl['fit']) ? ' ' . $sl['fit'] : '' ?>">
        <a class="hs-banner" href="<?= e($sl['href']) ?>"<?= strpos($sl['fit'] ?? '', 'contain') !== false ? ' style="--bg:url(' . e($sl['src']) . ')"' : '' ?>>
          <picture>
            <?php if (!empty($sl['mobile'])): ?><source media="(max-width: 640px)" srcset="<?= e($sl['mobile']) ?>"><?php endif; ?>
            <img src="<?= e($sl['src']) ?>" alt="<?= e($sl['t']) ?>" <?= $i ? 'loading="lazy"' : '' ?>>
          </picture>
        </a>
        <?php if ($sl['text']): ?>
          <div class="hs-over">
            <div class="container">
              <div class="hs-copy">
                <?php if ($sl['k'] !== ''): ?><span class="pill"><?= e($sl['k']) ?></span><?php endif; ?>
                <?= $i === 0 ? '<h1>' : '<h2 class="hs-title">' ?><?= e($sl['t']) ?><?= $i === 0 ? '</h1>' : '</h2>' ?>
                <?php if ($sl['p'] !== ''): ?><p><?= e($sl['p']) ?></p><?php endif; ?>
                <?php if ($sl['cta'] !== ''): ?><div class="hs-cta"><a class="btn btn-primary" href="<?= e($sl['href']) ?>"><?= e($sl['cta']) ?></a></div><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="hs-nav prev" type="button" aria-label="Previous">‹</button>
  <button class="hs-nav next" type="button" aria-label="Next">›</button>
  <div class="hs-dots"><?php foreach ($slides as $i => $sl): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach; ?></div>
</section>

<section class="badges">
  <div class="container badges-row">
    <?php foreach (['Ayurvedic formulation', 'GMP certified', 'ISO certified', 'Vegetarian capsules', 'Cash on Delivery', 'Secure payments'] as $bt): ?><span><?= e($bt) ?></span><?php endforeach; ?>
  </div>
</section>

<section class="pillars">
  <div class="container pillars-row">
    <div class="pillar"><svg viewBox="0 0 24 24"><path d="M5 19c0-8 5-13 14-14 0 9-5 14-14 14z"/><path d="M5 19c3-5 6-8 10-10"/></svg><b>Ancient Wisdom, Modern Formulation</b></div>
    <div class="pillar"><svg viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg><b>Made in a GMP &amp; ISO certified facility</b></div>
    <div class="pillar"><svg viewBox="0 0 24 24"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg><b>Made to support your wellness journey</b></div>
  </div>
</section>

<section class="screen" id="concerns">
  <div class="container">
    <div class="screen-head"><span class="kicker">Shop by concern</span><h2>What are you looking for?</h2></div>
    <div class="concerns">
      <?php foreach ($concerns as $c): ?>
        <a class="concern" href="<?= e($c[2]) ?>">
          <img src="<?= e($c[1]) ?>" alt="" loading="lazy">
          <span class="c-label"><b><?= e($c[0]) ?></b><i>Shop now →</i></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="screen tint" id="packs">
  <div class="container">
    <div class="screen-head">
      <span class="kicker">Best sellers</span>
      <h2>Our most loved products</h2>
      <p class="lead">Prices include GST. Pay online or choose Cash on Delivery.</p>
    </div>
    <div class="products four">
      <?php foreach ($products as $p) { product_card($p); } ?>
    </div>
    <?php if (!$products): ?><p class="muted center">Products will appear here once added from the admin panel.</p><?php endif; ?>
    <p class="center" style="margin:22px 0 0"><a class="btn btn-ghost btn-sm" href="<?= e(url('shop.php')) ?>">View all products</a></p>
  </div>
</section>

<section class="statbar">
  <div class="container statbar-row">
    <div><b>60</b><span>capsules in the large bottle</span></div>
    <div><b>2</b><span>capsules a day, after meals</span></div>
    <div><b>100%</b><span>Ayurvedic herbal formula</span></div>
    <div><b>1–2</b><span>business days to dispatch</span></div>
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

<section class="screen sage-screen" id="how">
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

<?php if (count($allImgs) > 2): ?>
<section class="screen" id="gallery">
  <div class="container">
    <div class="screen-head"><span class="kicker">Inside the pack</span><h2>See Jointex up close</h2></div>
    <div class="photo-grid">
      <?php foreach (array_slice($allImgs, 0, 6) as $gi): ?><a href="<?= e($buy) ?>"><img src="<?= e(product_image_url($gi)) ?>" alt="Jointex" loading="lazy"></a><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($insta): ?>
<section class="screen" id="instagram">
  <div class="container">
    <div class="screen-head">
      <span class="kicker">Follow us</span>
      <h2><?= setting('instagram_handle') !== '' ? '@' . e(ltrim(setting('instagram_handle'), '@')) : 'On Instagram' ?></h2>
    </div>
    <div class="insta-grid">
      <?php foreach ($insta as $ig): ?>
        <a href="<?= e($ig['link'] !== '' ? site_link($ig['link']) : '#') ?>" target="_blank" rel="noopener"><img src="<?= e(media_url($ig['image'])) ?>" alt="Instagram post" loading="lazy"></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="screen tint" id="blogs">
  <div class="container">
    <div class="screen-head"><span class="kicker">Blogs</span><h2>Read and learn</h2></div>
    <div class="blog-grid">
      <?php foreach ($posts as $bp) { blog_card($bp); } ?>
    </div>
    <p class="center" style="margin:22px 0 0"><a class="btn btn-ghost btn-sm" href="<?= e(url('blog.php')) ?>">See all</a></p>
  </div>
</section>
<?php endif; ?>

<?php
sec_usps('Why customers shop with us');
sec_delivery_steps();
sec_faq(6);
sec_help();
sec_cta();
require __DIR__ . '/includes/footer.php';

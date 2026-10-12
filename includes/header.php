<?php
// Expects optional $pageTitle, $metaDescription, $activeNav
require_once __DIR__ . '/sections.php';
$siteName = setting('site_name', 'Holistic Global Services');
$nameParts = explode(' ', $siteName, 2);
$activeNav = $activeNav ?? '';
$user = current_user();

function product_card(array $p): void
{
    $off = discount_percent($p); ?>
    <article class="p-card">
      <a class="thumb" href="<?= e(url('product.php?slug=' . urlencode($p['slug']))) ?>">
        <?php if ($off): ?><span class="off-tag"><?= $off ?>% OFF</span><?php endif; ?>
        <img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <?php if ($p['review_count']): ?><span class="rate-pill">★ <?= e($p['avg_rating']) ?> <i>| <?= (int) $p['review_count'] ?></i></span><?php endif; ?>
      </a>
      <div class="body">
        <span class="pack"><?= e($p['pack_label']) ?></span>
        <h3><a href="<?= e(url('product.php?slug=' . urlencode($p['slug']))) ?>"><?= e($p['name']) ?></a></h3>
        <p class="muted desc"><?= e($p['short_description']) ?></p>
        <div class="price">
          <span class="now"><?= money($p['price']) ?></span>
          <?php if ($off): ?><span class="was"><?= money($p['mrp']) ?></span><span class="save">Save <?= money($p['mrp'] - $p['price']) ?></span><?php endif; ?>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <form method="post" action="<?= e(url('cart.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn-primary" type="submit" name="buy_now" value="1">Buy now</button>
            <button class="btn btn-cart" type="submit" aria-label="Add to cart" title="Add to cart"><svg viewBox="0 0 24 24"><path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg></button>
          </form>
        <?php else: ?>
          <p class="stock-out" style="margin:auto 0 0">Currently out of stock</p>
        <?php endif; ?>
      </div>
    </article>
<?php }

function blog_card(array $bp): void
{
    $href = url('blog.php?slug=' . urlencode($bp['slug'])); ?>
    <article class="blog-card">
      <a class="thumb" href="<?= e($href) ?>"><img src="<?= e(media_url($bp['image'])) ?>" alt="" loading="lazy"></a>
      <div class="body">
        <time><?= e(date('d M Y', strtotime($bp['created_at']))) ?></time>
        <h3><a href="<?= e($href) ?>"><?= e($bp['title']) ?></a></h3>
        <?php if ($bp['excerpt'] !== ''): ?><p class="muted"><?= e($bp['excerpt']) ?></p><?php endif; ?>
      </div>
    </article>
<?php }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName . ' – ' . setting('tagline')) ?></title>
<meta name="description" content="<?= e($metaDescription ?? setting('meta_description')) ?>">
<link rel="icon" href="<?= e(url('assets/img/logo.jpg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=9">
<link rel="stylesheet" href="<?= e(url('assets/css/motion.css')) ?>?v=8">
<link rel="stylesheet" href="<?= e(url('assets/css/fx.css')) ?>?v=7">
<link rel="stylesheet" href="<?= e(url('assets/css/screens.css')) ?>?v=8">
<link rel="stylesheet" href="<?= e(url('assets/css/theme.css')) ?>?v=17">
<script>
document.documentElement.classList.add('js');
// Safety net: never leave headings hidden if the animation script fails to load.
setTimeout(function () { if (!document.querySelector('.words')) { document.documentElement.classList.remove('js'); } }, 3000);
</script>
<noscript><style>html.js main :is(h1, h2) { visibility: visible !important; }</style></noscript>
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
<div class="announce"><div class="announce-track">
  <?php $ann = setting('announcement') !== '' ? setting('announcement') : 'Get extra discount on online payment*'; for ($i = 0; $i < 6; $i++) { echo '<span>' . e($ann) . '</span><span class="dot">✦</span><span>Cash on Delivery available</span><span class="dot">✦</span>'; } ?>
</div></div>
<header class="site-header">
  <div class="container nav">
    <button class="nav-toggle" type="button" aria-label="Menu"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?> home">
      <span class="logo-mark"><img src="<?= e(url('assets/img/logo.jpg')) ?>" alt=""></span>
      <span class="brand-text"><b><?= e(strtoupper($nameParts[0])) ?></b><span><?= e(strtoupper($nameParts[1] ?? '')) ?></span></span>
    </a>
    <ul class="nav-links">
      <li><a class="<?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= e(url('')) ?>">Home</a></li>
      <li><a class="<?= $activeNav === 'shop' ? 'active' : '' ?>" href="<?= e(url('shop.php')) ?>">All Products</a></li>
      <?php $navCats = rows('SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name'); ?>
      <?php if ($navCats): ?>
        <li class="has-sub">
          <a href="<?= e(url('shop.php')) ?>" aria-haspopup="true">Categories <span class="caret">▾</span></a>
          <ul class="sub-menu">
            <?php foreach ($navCats as $nc): ?>
              <li><a class="<?= ($_GET['cat'] ?? '') === $nc['slug'] ? 'active' : '' ?>" href="<?= e(url('shop.php?cat=' . urlencode($nc['slug']))) ?>"><?= e($nc['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endif; ?>
      <li><a class="<?= $activeNav === 'blog' ? 'active' : '' ?>" href="<?= e(url('blog.php')) ?>">Blog</a></li>
      <li><a class="<?= $activeNav === 'about' ? 'active' : '' ?>" href="<?= e(url('about.php')) ?>">Our Story</a></li>
      <li><a class="<?= $activeNav === 'track' ? 'active' : '' ?>" href="<?= e(url('track-order.php')) ?>">Track Order</a></li>
      <li><a class="<?= $activeNav === 'contact' ? 'active' : '' ?>" href="<?= e(url('contact.php')) ?>">Contact</a></li>
    </ul>
    <div class="nav-actions">
      <a class="icon-btn" href="<?= e(url('shop.php')) ?>" aria-label="Search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg></a>
      <?php if ($user): ?>
        <a class="icon-btn" href="<?= e(url('account.php')) ?>" aria-label="My account"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c1-4 4.5-6 8-6s7 2 8 6"/></svg></a>
      <?php else: ?>
        <a class="icon-btn" href="<?= e(url('login.php')) ?>" aria-label="Login"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c1-4 4.5-6 8-6s7 2 8 6"/></svg></a>
      <?php endif; ?>
      <a class="icon-btn cart-link" href="<?= e(url('cart.php')) ?>" aria-label="Cart"><svg viewBox="0 0 24 24"><path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg><span class="cart-count"><?= cart_count() ?></span></a>
    </div>
  </div>
</header>
<?php $__flashes = flashes(); if ($__flashes): ?>
  <div class="container flash-wrap">
    <?php foreach ($__flashes as $fl): ?><div class="flash flash-<?= e($fl['type']) ?>"><?= e($fl['msg']) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>
<main>

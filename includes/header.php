<?php
// Expects optional $pageTitle, $metaDescription, $activeNav
$siteName = setting('site_name', 'Holistic Global Services');
$nameParts = explode(' ', $siteName, 2);
$activeNav = $activeNav ?? '';
$user = current_user();

function product_card(array $p): void
{
    $off = discount_percent($p); ?>
    <article class="p-card">
      <?php if ($off): ?><span class="off-tag"><?= $off ?>% OFF</span><?php endif; ?>
      <a class="thumb" href="<?= e(url('product.php?slug=' . urlencode($p['slug']))) ?>">
        <img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
      </a>
      <div class="body">
        <span class="pack"><?= e($p['pack_label']) ?></span>
        <h3><a href="<?= e(url('product.php?slug=' . urlencode($p['slug']))) ?>"><?= e($p['name']) ?></a></h3>
        <?php if ($p['review_count']): ?>
          <div class="rating-line"><?= stars((float) $p['avg_rating']) ?> <?= e($p['avg_rating']) ?> (<?= (int) $p['review_count'] ?>)</div>
        <?php endif; ?>
        <p class="muted" style="margin:0"><?= e($p['short_description']) ?></p>
        <div class="price">
          <span class="now"><?= money($p['price']) ?></span>
          <?php if ($off): ?><span class="was"><?= money($p['mrp']) ?></span><span class="save">Save <?= money($p['mrp'] - $p['price']) ?></span><?php endif; ?>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <form method="post" action="<?= e(url('cart.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn-dark" type="submit">Add to cart</button>
            <button class="btn btn-primary" type="submit" name="buy_now" value="1">Buy now</button>
          </form>
        <?php else: ?>
          <p class="stock-out" style="margin:auto 0 0">Currently out of stock</p>
        <?php endif; ?>
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
<link rel="stylesheet" href="<?= e(url('assets/css/fx.css')) ?>?v=6">
<link rel="stylesheet" href="<?= e(url('assets/css/screens.css')) ?>?v=8">
<script>
document.documentElement.classList.add('js');
// Safety net: never leave headings hidden if the animation script fails to load.
setTimeout(function () { if (!document.querySelector('.words')) { document.documentElement.classList.remove('js'); } }, 3000);
</script>
<noscript><style>html.js main :is(h1, h2) { visibility: visible !important; }</style></noscript>
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>
<?php if (setting('announcement') !== ''): ?>
  <div class="announce"><?= e(setting('announcement')) ?></div>
<?php endif; ?>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e($siteName) ?> home">
      <span class="logo-mark"><img src="<?= e(url('assets/img/logo.jpg')) ?>" alt=""></span>
      <span class="brand-text"><b><?= e(strtoupper($nameParts[0])) ?></b><span><?= e(strtoupper($nameParts[1] ?? '')) ?></span></span>
    </a>
    <ul class="nav-links">
      <li><a class="<?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= e(url('')) ?>">Home</a></li>
      <li><a class="<?= $activeNav === 'shop' ? 'active' : '' ?>" href="<?= e(url('shop.php')) ?>">Shop</a></li>
      <li><a class="<?= $activeNav === 'about' ? 'active' : '' ?>" href="<?= e(url('about.php')) ?>">About</a></li>
      <li><a class="<?= $activeNav === 'track' ? 'active' : '' ?>" href="<?= e(url('track-order.php')) ?>">Track Order</a></li>
      <li><a class="<?= $activeNav === 'contact' ? 'active' : '' ?>" href="<?= e(url('contact.php')) ?>">Contact</a></li>
    </ul>
    <div class="nav-actions">
      <?php if ($user): ?>
        <a class="hide-sm" href="<?= e(url('account.php')) ?>">Hi, <?= e(explode(' ', $user['name'])[0]) ?></a>
      <?php else: ?>
        <a class="hide-sm" href="<?= e(url('login.php')) ?>">Login</a>
      <?php endif; ?>
      <a class="cart-link" href="<?= e(url('cart.php')) ?>">Cart <span class="cart-count"><?= cart_count() ?></span></a>
      <button class="nav-toggle" type="button" aria-label="Menu">☰</button>
    </div>
  </div>
</header>
<?php $__flashes = flashes(); if ($__flashes): ?>
  <div class="container flash-wrap">
    <?php foreach ($__flashes as $fl): ?><div class="flash flash-<?= e($fl['type']) ?>"><?= e($fl['msg']) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>
<main>

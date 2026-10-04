<?php
require __DIR__ . '/includes/init.php';

$list = products_with_image('p.is_active = 1 AND p.slug = ?', [(string) ($_GET['slug'] ?? '')]);
$p = $list[0] ?? null;
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty"><h1>Product not found</h1><a class="btn btn-dark" href="' . e(url('shop.php')) . '">Back to shop</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Review submission (shown after admin approval)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'review') {
    csrf_check();
    $name = mb_substr(post('name'), 0, 100);
    $rating = (int) post('rating');
    $comment = mb_substr(post('comment'), 0, 1500);
    if ($name === '' || $comment === '' || $rating < 1 || $rating > 5) {
        flash('error', 'Please add your name, a rating and a short review.');
    } elseif (throttle_hit('review', 3, 3600)) {
        flash('error', 'You have submitted several reviews already. Please try again later.');
    } else {
        q('INSERT INTO reviews (product_id, user_id, name, rating, comment) VALUES (?, ?, ?, ?, ?)', [$p['id'], current_user()['id'] ?? null, $name, $rating, $comment]);
        flash('success', 'Thank you! Your review will appear once it is approved.');
    }
    redirect('product.php?slug=' . urlencode($p['slug']) . '#reviews');
}

$images = product_images((int) $p['id']);
$reviews = rows("SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 30", [$p['id']]);
$others = products_with_image('p.is_active = 1 AND p.id <> ?', [$p['id']]);
$off = discount_percent($p);
$pageTitle = $p['name'];
$metaDescription = $p['short_description'];
$activeNav = 'shop';
require __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:36px">
  <div class="container">
    <?= breadcrumbs(['Shop' => 'shop.php', $p['name'] => null]) ?>
    <div class="pd" style="margin-top:18px">
      <div class="gallery">
        <div class="gallery-main"><img src="<?= e(product_image_url($images[0] ?? null)) ?>" alt="<?= e($p['name']) ?>"></div>
        <?php if (count($images) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach ($images as $i => $img): ?>
              <button type="button" class="<?= $i === 0 ? 'on' : '' ?>" data-src="<?= e(product_image_url($img)) ?>"><img src="<?= e(product_image_url($img)) ?>" alt="View <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="pd-info">
        <span class="pack"><?= e($p['pack_label']) ?></span>
        <h1><?= e($p['name']) ?></h1>
        <p class="muted" style="font-size:1.08rem"><?= e($p['tagline']) ?></p>
        <?php if ($p['review_count']): ?>
          <p class="rating-line"><?= stars((float) $p['avg_rating']) ?> <?= e($p['avg_rating']) ?> · <a href="#reviews"><?= (int) $p['review_count'] ?> review<?= $p['review_count'] == 1 ? '' : 's' ?></a></p>
        <?php endif; ?>
        <div class="price">
          <span class="now"><?= money($p['price']) ?></span>
          <?php if ($off): ?><span class="was">MRP <?= money($p['mrp']) ?></span><span class="save"><?= $off ?>% off</span><?php endif; ?>
        </div>
        <p class="tax-note">Inclusive of all taxes</p>
        <p><?= e($p['short_description']) ?></p>

        <?php if ($p['stock'] > 0): ?>
          <form method="post" action="<?= e(url('cart.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <div class="buy-row">
              <div class="qty">
                <button type="button" data-step="down" aria-label="Decrease">−</button>
                <input type="number" name="qty" value="1" min="1" max="<?= min(20, (int) $p['stock']) ?>" aria-label="Quantity">
                <button type="button" data-step="up" aria-label="Increase">+</button>
              </div>
              <button class="btn btn-dark" type="submit">Add to cart</button>
              <button class="btn btn-primary" type="submit" name="buy_now" value="1">Buy now</button>
            </div>
          </form>
          <?php if ($p['stock'] <= 10): ?><p class="stock-out">Only <?= (int) $p['stock'] ?> left in stock</p><?php endif; ?>
        <?php else: ?>
          <p class="stock-out">Currently out of stock</p>
        <?php endif; ?>

        <ul class="assure">
          <li><svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Secure payment</li>
          <li><svg viewBox="0 0 24 24"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="17.5" cy="17.5" r="1.5"/></svg>Delivery across India</li>
          <li><svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>Cash on Delivery</li>
        </ul>

        <div class="tabs">
          <details open><summary>Description</summary><div class="tab-body"><?= e($p['description']) ?></div></details>
          <?php if (trim((string) $p['how_to_use']) !== ''): ?>
            <details><summary>How to use</summary><div class="tab-body"><?= e($p['how_to_use']) ?></div></details>
          <?php endif; ?>
          <?php if (trim((string) $p['ingredients']) !== ''): ?>
            <details><summary>Ingredients</summary><div class="tab-body"><?= e($p['ingredients']) ?></div></details>
          <?php endif; ?>
          <details><summary>Product information</summary>
            <div class="tab-body"><?= e("Pack: {$p['pack_label']}\nSKU: {$p['sku']}\nHSN: {$p['hsn']}\nMarketed by: " . setting('site_name') . "\n" . setting('address')) ?></div>
          </details>
        </div>
        <?php if (setting('disclaimer') !== ''): ?><p class="disclaimer"><?= e(setting('disclaimer')) ?></p><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section" id="reviews" style="padding-top:0">
  <div class="container split" style="align-items:start">
    <div>
      <h2>Customer reviews</h2>
      <?php if (!$reviews): ?><p class="muted">No reviews yet. Be the first to share your experience.</p><?php endif; ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review" style="margin-bottom:14px">
          <?= stars((float) $r['rating']) ?>
          <p><?= nl2br(e($r['comment'])) ?></p>
          <cite><?= e($r['name']) ?></cite><time><?= e(date('d M Y', strtotime($r['created_at']))) ?></time>
        </div>
      <?php endforeach; ?>
    </div>
    <form class="card" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="review">
      <h3>Write a review</h3>
      <div class="form-grid">
        <div><label for="rv-name">Your name</label><input id="rv-name" type="text" name="name" maxlength="100" value="<?= e(current_user()['name'] ?? '') ?>" required></div>
        <div><label for="rv-rating">Rating</label>
          <select id="rv-rating" name="rating" required>
            <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
          </select>
        </div>
        <div class="full"><label for="rv-comment">Your review</label><textarea id="rv-comment" name="comment" maxlength="1500" required></textarea></div>
        <div class="full"><button class="btn btn-dark" type="submit">Submit review</button></div>
      </div>
    </form>
  </div>
</section>

<?php if ($others): ?>
<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Also available</h2>
    <div class="products four start">
      <?php foreach ($others as $o) { product_card($o); } ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

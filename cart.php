<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int) post('product_id');
    switch (post('action')) {
        case 'add':
            $before = cart_raw()[$pid] ?? 0;
            cart_add($pid, (int) post('qty', '1'));
            if ((cart_raw()[$pid] ?? 0) > $before) {
                if (post('buy_now') === '1') {
                    redirect('checkout.php');
                }
                flash('success', 'Added to your cart.');
            } else {
                flash('error', 'Sorry, no more stock is available for this item.');
            }
            break;
        case 'update':
            cart_set($pid, (int) post('qty'));
            break;
        case 'remove':
            cart_set($pid, 0);
            flash('info', 'Item removed from your cart.');
            break;
        case 'coupon':
            $r = coupon_check(post('code'), cart_subtotal());
            if ($r['coupon']) {
                $_SESSION['coupon'] = $r['coupon']['code'];
                flash('success', 'Coupon applied — you save ' . money($r['discount']) . '.');
            } else {
                flash('error', $r['error']);
            }
            break;
        case 'remove_coupon':
            unset($_SESSION['coupon']);
            break;
    }
    redirect('cart.php');
}

$items = cart_items();
$subtotal = cart_subtotal();
$coupon = coupon_current($subtotal);
$pageTitle = 'Your cart';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container"><?= breadcrumbs(['Cart' => null]) ?><h1>Your cart</h1></div></section>
<section class="section" style="padding-top:40px">
  <div class="container">
    <?php if (!$items): ?>
      <div class="card empty">
        <div class="big">🛒</div>
        <h2>Your cart is empty</h2>
        <p class="muted">Add Jointex to your cart to get started.</p>
        <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div class="card">
          <?php foreach ($items as $it): $p = $it['product']; ?>
            <div class="cart-row">
              <a href="<?= e(url('product.php?slug=' . urlencode($p['slug']))) ?>"><img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>"></a>
              <div>
                <h3><a href="<?= e(url('product.php?slug=' . urlencode($p['slug']))) ?>"><?= e($p['name']) ?></a></h3>
                <span class="muted"><?= money($p['price']) ?> each</span>
                <div class="controls">
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                    <div class="qty" data-autosubmit="1">
                      <button type="button" data-step="down" aria-label="Decrease">−</button>
                      <input type="number" name="qty" value="<?= (int) $it['qty'] ?>" min="1" max="<?= min(20, (int) $p['stock']) ?>" onchange="this.form.submit()" aria-label="Quantity">
                      <button type="button" data-step="up" aria-label="Increase">+</button>
                    </div>
                  </form>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                    <button class="link-btn" type="submit">Remove</button>
                  </form>
                </div>
              </div>
              <div class="line"><?= money($it['line']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <aside class="card summary">
          <h3>Order summary</h3>
          <div class="sum-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <?php if ($coupon['discount'] > 0): ?>
            <div class="sum-row"><span>Coupon <strong><?= e($coupon['code']) ?></strong>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="remove_coupon"><button class="link-btn" type="submit">remove</button></form>
            </span><span class="green">− <?= money($coupon['discount']) ?></span></div>
          <?php else: ?>
            <form method="post" class="coupon-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="coupon">
              <input type="text" name="code" placeholder="Coupon code" maxlength="40" required>
              <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
            </form>
          <?php endif; ?>
          <div class="sum-row"><span>Shipping</span><span class="muted">Calculated at checkout</span></div>
          <div class="sum-row total"><span>Total</span><span><?= money($subtotal - $coupon['discount']) ?></span></div>
          <p class="muted" style="font-size:.84rem">Prices include GST.</p>
          <a class="btn btn-primary btn-block" href="<?= e(url('checkout.php')) ?>">Proceed to checkout</a>
          <p class="center" style="margin:14px 0 0"><a href="<?= e(url('shop.php')) ?>">Continue shopping</a></p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

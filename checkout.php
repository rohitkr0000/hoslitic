<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/razorpay.php';
require __DIR__ . '/includes/fship.php';

$items = cart_items();
if (!$items) {
    flash('info', 'Your cart is empty.');
    redirect('cart.php');
}
$user = current_user();
$methods = payment_methods();
$states = indian_states();
$f = [
    'name' => $user['name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '',
    'address' => $user['address'] ?? '', 'city' => $user['city'] ?? '', 'state' => $user['state'] ?? '',
    'pincode' => $user['pincode'] ?? '', 'notes' => '', 'payment_method' => array_key_first($methods) ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $v) {
        $f[$k] = post($k);
    }
    $f['phone'] = preg_replace('/\D+/', '', $f['phone']);
    if (strlen($f['phone']) === 12 && strpos($f['phone'], '91') === 0) {
        $f['phone'] = substr($f['phone'], 2);
    }
    if (mb_strlen($f['name']) < 3) { $errors[] = 'Please enter your full name.'; }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (!preg_match('/^[6-9]\d{9}$/', $f['phone'])) { $errors[] = 'Please enter a valid 10-digit mobile number.'; }
    if (mb_strlen($f['address']) < 8) { $errors[] = 'Please enter your complete address.'; }
    if ($f['city'] === '') { $errors[] = 'Please enter your city.'; }
    if (!in_array($f['state'], $states, true)) { $errors[] = 'Please select your state.'; }
    if (!preg_match('/^[1-9]\d{5}$/', $f['pincode'])) { $errors[] = 'Please enter a valid 6-digit PIN code.'; }
    if (!isset($methods[$f['payment_method']])) { $errors[] = 'Please choose a payment method.'; }

    $t = compute_totals($f['state'], $f['payment_method']);
    if (!$errors && !$t['zone']) { $errors[] = 'Sorry, we do not deliver to this state yet.'; }

    // Courier serviceability for the PIN code (skipped if Fship cannot be reached).
    if (!$errors && fship_enabled()) {
        $svc = fship_pincode($f['pincode']);
        if ($svc && !$svc['serviceable']) {
            $errors[] = 'Sorry, our courier partner does not deliver to PIN code ' . $f['pincode'] . ' yet.';
        } elseif ($svc && $f['payment_method'] === 'cod' && !$svc['cod']) {
            $errors[] = 'Cash on Delivery is not available for PIN code ' . $f['pincode'] . '. Please pay online instead.';
        } elseif ($svc && $f['payment_method'] !== 'cod' && !$svc['prepaid']) {
            $errors[] = 'Only Cash on Delivery is available for PIN code ' . $f['pincode'] . '.';
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO orders (user_id, name, email, phone, address, city, state, pincode, notes, subtotal, discount, coupon_code, shipping, cod_fee, total, payment_method)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $user['id'] ?? null, mb_substr($f['name'], 0, 100), strtolower($f['email']), $f['phone'], mb_substr($f['address'], 0, 255),
            mb_substr($f['city'], 0, 80), $f['state'], $f['pincode'], mb_substr($f['notes'], 0, 500),
            $t['subtotal'], $t['discount'], $t['coupon_code'], $t['shipping'], $t['cod_fee'], $t['total'], $f['payment_method'],
        ]);
        $orderId = (int) $pdo->lastInsertId();
        $orderNo = setting('invoice_prefix', 'HGS') . date('ymd') . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
        q('UPDATE orders SET order_no = ? WHERE id = ?', [$orderNo, $orderId]);
        foreach ($items as $it) {
            $p = $it['product'];
            q('INSERT INTO order_items (order_id, product_id, name, sku, hsn, gst_rate, price, qty, weight_grams) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$orderId, $p['id'], $p['name'], $p['sku'], $p['hsn'], $p['gst_rate'], $p['price'], $it['qty'], $p['weight_grams']]);
        }
        $pdo->commit();
        $_SESSION['my_orders'][] = $orderId;
        order_event($orderId, 'Order placed', $f['payment_method'] === 'cod' ? 'Your Cash on Delivery order was placed.' : 'Order placed – waiting for online payment.');

        if ($user) {
            q('UPDATE users SET phone = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?',
                [$f['phone'], mb_substr($f['address'], 0, 255), mb_substr($f['city'], 0, 80), $f['state'], $f['pincode'], $user['id']]);
        }

        if ($f['payment_method'] === 'cod') {
            confirm_order($orderId);
            cart_clear();
            redirect('order-success.php?order=' . urlencode($orderNo));
        }

        $rzp = razorpay_create_order($t['total'], $orderNo);
        if (isset($rzp['id'])) {
            q('UPDATE orders SET razorpay_order_id = ? WHERE id = ?', [$rzp['id'], $orderId]);
            redirect('payment.php?order=' . urlencode($orderNo));
        }
        q("UPDATE orders SET payment_status = 'failed', status = 'cancelled' WHERE id = ?", [$orderId]);
        $errors[] = 'We could not start the online payment (' . $rzp['error'] . '). Please try again or choose another method.';
    }
}

$t = compute_totals($f['state'], $f['payment_method']);
$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container"><?= breadcrumbs(['Cart' => 'cart.php', 'Checkout' => null]) ?><h1>Checkout</h1></div></section>
<section class="section" style="padding-top:40px">
  <div class="container">
    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
    <?php if (!$user): ?>
      <p class="muted">Have an account? <a href="<?= e(url('login.php?next=checkout')) ?>">Log in</a> for faster checkout — or simply continue as a guest.</p>
    <?php endif; ?>
    <form method="post" class="checkout-layout" novalidate>
      <?= csrf_field() ?>
      <div>
        <div class="card">
          <h3>Delivery details</h3>
          <div class="form-grid">
            <div class="full"><label for="name">Full name</label><input id="name" type="text" name="name" value="<?= e($f['name']) ?>" autocomplete="name" required></div>
            <div><label for="phone">Mobile number</label><input id="phone" type="tel" name="phone" value="<?= e($f['phone']) ?>" autocomplete="tel" inputmode="numeric" maxlength="13" required></div>
            <div><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e($f['email']) ?>" autocomplete="email" required></div>
            <div class="full"><label for="address">Address (house no., street, area, landmark)</label><input id="address" type="text" name="address" value="<?= e($f['address']) ?>" autocomplete="street-address" required></div>
            <div><label for="city">City</label><input id="city" type="text" name="city" value="<?= e($f['city']) ?>" autocomplete="address-level2" required></div>
            <div><label for="pincode">PIN code</label><input id="pincode" type="text" name="pincode" value="<?= e($f['pincode']) ?>" inputmode="numeric" maxlength="6" autocomplete="postal-code" required></div>
            <div class="full"><label for="state">State</label>
              <select id="state" name="state" required>
                <option value="">Select state</option>
                <?php foreach ($states as $s): ?><option <?= $f['state'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="full"><label for="notes">Order notes (optional)</label><input id="notes" type="text" name="notes" value="<?= e($f['notes']) ?>" maxlength="500"></div>
          </div>
        </div>

        <div class="card" style="margin-top:20px">
          <h3>Payment method</h3>
          <?php if (!$methods): ?><p class="stock-out">No payment method is enabled yet. Please contact us to place your order.</p><?php endif; ?>
          <?php foreach ($methods as $key => $label): ?>
            <label class="pay-opt">
              <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $f['payment_method'] === $key ? 'checked' : '' ?>>
              <span><?= e($label) ?>
                <small><?= $key === 'cod' ? 'Pay in cash when your order arrives.' : 'Secured by Razorpay. You will be taken to the payment window next.' ?></small>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <aside class="card summary">
        <h3>Your order</h3>
        <?php foreach ($items as $it): ?>
          <div class="mini-item"><span><?= e($it['product']['name']) ?> × <?= (int) $it['qty'] ?></span><span><?= money($it['line']) ?></span></div>
        <?php endforeach; ?>
        <div class="sum-row" style="border-top:1px solid var(--line);margin-top:10px;padding-top:12px"><span>Subtotal</span><span><?= money($t['subtotal']) ?></span></div>
        <?php if ($t['discount'] > 0): ?>
          <div class="sum-row"><span>Coupon <?= e($t['coupon_code']) ?></span><span class="green">− <?= money($t['discount']) ?></span></div>
        <?php endif; ?>
        <div class="sum-row"><span>Shipping</span><span id="sum-shipping"><?= $f['state'] === '' ? 'Select state' : ($t['shipping'] > 0 ? money($t['shipping']) : 'FREE') ?></span></div>
        <div class="sum-row" id="sum-cod-row" style="<?= $t['cod_fee'] > 0 ? '' : 'display:none' ?>"><span>COD handling fee</span><span id="sum-cod"><?= money($t['cod_fee']) ?></span></div>
        <div class="sum-row total"><span>Total</span><span id="sum-total"><?= money($t['total']) ?></span></div>
        <p class="muted" id="sum-eta" style="font-size:.86rem;min-height:1.2em"></p>
        <button class="btn btn-primary btn-block" type="submit" <?= $methods ? '' : 'disabled' ?>>Place order</button>
        <p class="muted center" style="font-size:.82rem;margin:12px 0 0">By placing your order you agree to our <a href="<?= e(url('page.php?slug=terms')) ?>">Terms</a>, <a href="<?= e(url('page.php?slug=refund-policy')) ?>">Cancellation &amp; Refund Policy</a> and <a href="<?= e(url('page.php?slug=shipping-policy')) ?>">Shipping Policy</a>.</p>
      </aside>
    </form>
  </div>
</section>
<script type="application/json" id="checkout-data"><?= json_encode([
    'subtotal' => $t['subtotal'],
    'discount' => $t['discount'],
    'zones' => array_map(fn($z) => ['states' => $z['states'], 'rate' => $z['rate'], 'free_above' => $z['free_above'], 'cod_fee' => $z['cod_fee'], 'eta' => $z['eta']], shipping_zones()),
], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>

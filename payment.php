<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/razorpay.php';

$o = viewable_order((string) ($_GET['order'] ?? ''));
if (!$o || $o['payment_method'] !== 'razorpay' || $o['razorpay_order_id'] === '') {
    redirect('cart.php');
}
if ($o['payment_status'] === 'paid') {
    redirect('order-success.php?order=' . urlencode($o['order_no']));
}
$pageTitle = 'Complete payment';
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="auth">
    <div class="card center">
      <h2>Complete your payment</h2>
      <p class="muted">Order <strong><?= e($o['order_no']) ?></strong></p>
      <p style="font:600 2.2rem var(--serif);color:var(--forest)"><?= money($o['total']) ?></p>
      <button class="btn btn-primary btn-block" id="pay-btn" type="button">Pay securely</button>
      <p class="muted" style="font-size:.85rem">UPI, cards, net banking and wallets — secured by Razorpay.</p>
      <a href="<?= e(url('checkout.php')) ?>">Change payment method</a>
    </div>
  </div>
</section>
<form id="verify-form" method="post" action="<?= e(url('payment-verify.php')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="razorpay_payment_id">
  <input type="hidden" name="razorpay_order_id">
  <input type="hidden" name="razorpay_signature">
</form>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
  var options = <?= json_encode([
      'key' => setting('razorpay_key_id'),
      'amount' => (int) round($o['total'] * 100),
      'currency' => 'INR',
      'name' => setting('site_name'),
      'description' => 'Order ' . $o['order_no'],
      'image' => full_url('assets/img/logo.jpg'),
      'order_id' => $o['razorpay_order_id'],
      'prefill' => ['name' => $o['name'], 'email' => $o['email'], 'contact' => $o['phone']],
      'theme' => ['color' => '#14432a'],
  ], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  options.handler = function (res) {
    var f = document.getElementById('verify-form');
    f.razorpay_payment_id.value = res.razorpay_payment_id;
    f.razorpay_order_id.value = res.razorpay_order_id;
    f.razorpay_signature.value = res.razorpay_signature;
    f.submit();
  };
  var open = function () { new Razorpay(options).open(); };
  document.getElementById('pay-btn').addEventListener('click', open);
  open();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

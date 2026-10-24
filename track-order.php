<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/order-view.php';
require __DIR__ . '/includes/delhivery.php';

$orderNo = trim((string) ($_GET['order'] ?? ''));
$phone = substr(preg_replace('/\D+/', '', (string) ($_GET['phone'] ?? '')), -10);
$o = null;
$scans = [];
$searched = $orderNo !== '' || $phone !== '';
if ($searched && !throttle_hit('track', 20, 600)) {
    // Both the order number and the phone must match, so order numbers cannot be guessed.
    $o = row('SELECT * FROM orders WHERE order_no = ? AND phone = ?', [$orderNo, $phone]);
    if ($o && $o['awb'] !== '' && $o['courier'] === 'Delhivery' && delhivery_enabled()) {
        $scans = delhivery_track($o['awb'])['scans'] ?? [];
    }
}
$pageTitle = 'Track order';
$activeNav = 'track';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container"><?= breadcrumbs(['Track order' => null]) ?><h1>Track your order</h1><p>Enter your order number and the mobile number used at checkout.</p></div></section>
<section class="section" style="padding-top:40px">
  <div class="narrow">
    <form class="card" method="get">
      <div class="form-grid">
        <div><label for="order">Order number</label><input id="order" type="text" name="order" value="<?= e($orderNo) ?>" placeholder="e.g. HGS2610040001" required></div>
        <div><label for="phone">Mobile number</label><input id="phone" type="tel" name="phone" value="<?= e($phone) ?>" maxlength="13" required></div>
        <div class="full"><button class="btn btn-dark" type="submit">Track order</button></div>
      </div>
    </form>
    <?php if ($searched && !$o): ?>
      <div class="flash flash-error" style="margin-top:20px">We could not find an order with those details. Please check and try again.</div>
    <?php elseif ($o): ?>
      <div style="margin-top:24px"><?php render_order($o, false); ?></div>
      <?php if ($scans): ?>
        <div class="card" style="margin-top:20px">
          <h3>Shipment updates</h3>
          <table class="data">
            <?php foreach ($scans as $s): ?>
              <tr><td class="muted" style="white-space:nowrap"><?= e($s['date'] ?? '') ?></td><td><?= e($s['activity'] ?? '') ?><br><span class="muted"><?= e($s['location'] ?? '') ?></span></td></tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php
sec_delivery_steps();
sec_help();
require __DIR__ . '/includes/footer.php';


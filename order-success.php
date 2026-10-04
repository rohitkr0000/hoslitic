<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/order-view.php';

$o = viewable_order((string) ($_GET['order'] ?? ''));
if (!$o) {
    redirect('');
}
$pageTitle = 'Order placed';
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="narrow">
    <div class="center" style="margin-bottom:28px">
      <div style="font-size:3rem">🌿</div>
      <h1 style="font-size:clamp(2rem,4vw,3rem)">Thank you, <?= e(explode(' ', $o['name'])[0]) ?>!</h1>
      <p class="lead">Your order has been placed. Keep your order number <strong><?= e($o['order_no']) ?></strong> handy to track it.</p>
    </div>
    <?php render_order($o); ?>
    <p class="center no-print" style="margin-top:24px">
      <a class="btn btn-dark" href="<?= e(url('invoice.php?order=' . urlencode($o['order_no']))) ?>" target="_blank">View invoice</a>
      <a class="btn btn-ghost" href="<?= e(url('shop.php')) ?>">Continue shopping</a>
    </p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

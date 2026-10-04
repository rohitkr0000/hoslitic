<?php
// Shared order summary used by the success, account and tracking pages.
function render_order(array $o, bool $private = true): void
{
    $items = order_items((int) $o['id']);
    $flow = ['confirmed' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
    $pos = array_search($o['status'], array_keys($flow), true);
    ?>
    <div class="card">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
        <div>
          <h3 style="margin:0">Order <?= e($o['order_no']) ?></h3>
          <span class="muted">Placed on <?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></span>
        </div>
        <div><?= badge($o['status']) ?> <?= badge($o['payment_status']) ?></div>
      </div>

      <?php if ($pos !== false): ?>
        <div class="track-steps">
          <?php $i = 0; foreach ($flow as $label): ?>
            <div class="t-step <?= $i <= $pos ? 'done' : '' ?>"><i><?= $i <= $pos ? '✓' : $i + 1 ?></i><?= e($label) ?></div>
          <?php $i++; endforeach; ?>
        </div>
      <?php elseif ($o['status'] === 'pending'): ?>
        <p class="flash flash-info" style="margin-top:16px">This order is awaiting payment.</p>
      <?php else: ?>
        <p class="flash flash-error" style="margin-top:16px">This order was <?= e($o['status']) ?>.</p>
      <?php endif; ?>

      <?php if ($o['awb'] !== '' || $o['courier'] !== ''): ?>
        <p style="margin-top:14px"><strong>Courier:</strong> <?= e($o['courier'] ?: '—') ?> &nbsp; <strong>Tracking no.:</strong> <?= e($o['awb'] ?: '—') ?>
          <?php if ($o['tracking_url'] !== ''): ?> &nbsp; <a href="<?= e($o['tracking_url']) ?>" target="_blank" rel="noopener">Track on courier site</a><?php endif; ?>
        </p>
      <?php endif; ?>

      <div class="table-wrap" style="margin-top:14px">
        <table class="data">
          <thead><tr><th>Item</th><th>Qty</th><th style="text-align:right">Amount</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr><td><?= e($it['name']) ?></td><td><?= (int) $it['qty'] ?></td><td style="text-align:right"><?= money($it['price'] * $it['qty']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="max-width:320px;margin-left:auto;margin-top:10px">
        <div class="sum-row"><span>Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
        <?php if ($o['discount'] > 0): ?><div class="sum-row"><span>Discount <?= e($o['coupon_code']) ?></span><span class="green">− <?= money($o['discount']) ?></span></div><?php endif; ?>
        <div class="sum-row"><span>Shipping</span><span><?= $o['shipping'] > 0 ? money($o['shipping']) : 'FREE' ?></span></div>
        <?php if ($o['cod_fee'] > 0): ?><div class="sum-row"><span>COD handling fee</span><span><?= money($o['cod_fee']) ?></span></div><?php endif; ?>
        <div class="sum-row total"><span>Total</span><span><?= money($o['total']) ?></span></div>
      </div>

      <?php $events = order_events((int) $o['id']); if ($events): ?>
        <h4 style="margin:24px 0 8px">Order updates</h4>
        <ul class="timeline">
          <?php foreach ($events as $ev): ?>
            <li><strong><?= e($ev['title']) ?></strong><span class="muted"> · <?= e(date('d M Y, h:i A', strtotime($ev['created_at']))) ?></span>
              <?php if ($ev['message'] !== ''): ?><div class="muted"><?= e($ev['message']) ?></div><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($private): ?>
        <div class="info-grid" style="margin-top:22px">
          <div><h4>Deliver to</h4><?= e($o['name']) ?><br><?= e($o['address']) ?><br><?= e($o['city'] . ', ' . $o['state'] . ' – ' . $o['pincode']) ?><br><?= e($o['phone']) ?></div>
          <div><h4>Payment</h4><?= $o['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online (Razorpay)' ?><?php if ($o['razorpay_payment_id'] !== ''): ?><br><span class="muted">Ref: <?= e($o['razorpay_payment_id']) ?></span><?php endif; ?></div>
        </div>
      <?php endif; ?>
    </div>
<?php }

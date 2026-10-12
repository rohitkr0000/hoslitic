<?php
require __DIR__ . '/_layout.php';
require ROOT_PATH . '/includes/fship.php';
require ROOT_PATH . '/includes/razorpay.php';

$id = (int) ($_GET['id'] ?? 0);
$o = row('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$o) {
    redirect('admin/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'update') {
        $status = isset(order_statuses()[post('status')]) ? post('status') : $o['status'];
        $pay = isset(payment_statuses()[post('payment_status')]) ? post('payment_status') : $o['payment_status'];
        $url = post('tracking_url');
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $url = '';
        }
        if (in_array($status, ['cancelled', 'returned'], true)) {
            restore_stock($id);
        } elseif ($status !== 'pending') {
            confirm_order($id); // deducts stock + emails the customer the first time only
        }
        if ($status === 'delivered' && $o['status'] !== 'delivered') {
            q('UPDATE orders SET delivered_at = NOW() WHERE id = ?', [$id]);
        }
        q('UPDATE orders SET status = ?, payment_status = ?, courier = ?, awb = ?, tracking_url = ?, updated_at = NOW() WHERE id = ?',
            [$status, $pay, mb_substr(post('courier'), 0, 80), mb_substr(post('awb'), 0, 60), mb_substr($url, 0, 255), $id]);
        $newCourier = mb_substr(post('courier'), 0, 80);
        $newAwb = mb_substr(post('awb'), 0, 60);
        if ($status !== $o['status'] && $status !== 'pending') {
            $msgs = [
                'confirmed' => ['Order confirmed', 'Your order has been confirmed and is being prepared.'],
                'packed' => ['Order packed', 'Your order has been packed and is ready for dispatch.'],
                'shipped' => ['Order shipped', 'Good news – your order is on its way!'],
                'delivered' => ['Order delivered', 'Your order has been delivered. We hope you like it! If anything is wrong you can request a return or refund from your account within ' . RETURN_WINDOW_DAYS . ' days of delivery.'],
                'cancelled' => ['Order cancelled', 'Your order has been cancelled. If you already paid online, the refund goes back to your original payment method in 5–7 business days.'],
                'returned' => ['Order returned', 'Your order has been marked as returned.'],
            ];
            [$t, $m] = $msgs[$status];
            // "confirmed" was already emailed by confirm_order() when moving from pending.
            notify_order_update($id, $t, $m, !($status === 'confirmed' && $o['status'] === 'pending'));
        } elseif ($status === $o['status'] && $newAwb !== '' && ($newAwb !== $o['awb'] || $newCourier !== $o['courier'])) {
            notify_order_update($id, 'Tracking details updated', 'The tracking details for your order have been updated.');
        }
        if ($pay !== $o['payment_status']) {
            $pm = ['paid' => ['Payment received', 'We have received your payment. Thank you!'], 'failed' => ['Payment failed', 'Your payment could not be completed.'],
                'refunded' => ['Payment refunded', 'Your payment has been refunded to your original payment method. It normally reflects in 5–7 business days.'], 'pending' => null][$pay] ?? null;
            if ($pm) {
                notify_order_update($id, $pm[0], $pm[1], $pay !== 'paid' || $o['payment_method'] === 'cod');
            }
        }
        flash('success', 'Order updated.');
    } elseif (post('action') === 'return_decision') {
        $rr = row('SELECT * FROM return_requests WHERE id = ? AND order_id = ?', [(int) post('rid'), $id]);
        $note = mb_substr(post('admin_note'), 0, 500);
        $dec = post('decision');
        if ($rr && $rr['status'] === 'pending' && $dec === 'reject') {
            q("UPDATE return_requests SET status = 'rejected', admin_note = ?, updated_at = NOW() WHERE id = ?", [$note, $rr['id']]);
            send_mail($o['email'], 'Update on your return request for ' . $o['order_no'],
                '<p>Hi ' . e($o['name']) . ',</p><p>We have reviewed your return request for order <strong>' . e($o['order_no']) . '</strong> and are unable to approve it.</p>' . ($note !== '' ? '<p>' . e($note) . '</p>' : '') . '<p>Please contact us if you have questions.</p>');
            order_event($id, 'Return request rejected', $note);
            flash('success', 'Return request rejected and the customer was notified.');
        } elseif ($rr && in_array($rr['status'], ['pending', 'approved'], true) && in_array($dec, ['approve', 'refund'], true)) {
            $refundId = '';
            $newStatus = 'approved';
            if ($dec === 'refund') {
                if ($o['payment_method'] === 'razorpay' && $o['razorpay_payment_id'] !== '' && $o['payment_status'] === 'paid') {
                    $res = razorpay_refund($o['razorpay_payment_id'], (float) $o['total'], 'Return ' . $o['order_no']);
                    if (isset($res['error'])) {
                        flash('error', 'Razorpay refund failed: ' . $res['error']);
                        redirect('admin/order.php?id=' . $id);
                    }
                    $refundId = $res['id'];
                }
                $newStatus = 'refunded';
                q("UPDATE orders SET payment_status = 'refunded', status = 'returned', updated_at = NOW() WHERE id = ?", [$id]);
            }
            q('UPDATE return_requests SET status = ?, admin_note = ?, refund_id = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $note, $refundId, $rr['id']]);
            $body = $newStatus === 'refunded'
                ? '<p>Your refund of <strong>' . money($o['total']) . '</strong> for order <strong>' . e($o['order_no']) . '</strong> has been initiated' . ($refundId !== '' ? ' (ref ' . e($refundId) . ') to your original payment method. It normally reflects in 5–7 business days.' : '. We will contact you to complete it.') . '</p>'
                : '<p>Your return request for order <strong>' . e($o['order_no']) . '</strong> is approved. Our team will contact you to arrange the return.</p>';
            send_mail($o['email'], 'Update on your return request for ' . $o['order_no'], '<p>Hi ' . e($o['name']) . ',</p>' . $body . ($note !== '' ? '<p>' . e($note) . '</p>' : ''));
            order_event($id, $newStatus === 'refunded' ? 'Refund initiated' : 'Return approved', $note);
            flash('success', $newStatus === 'refunded' ? ($refundId !== '' ? 'Razorpay refund created: ' . $refundId : 'Marked as refunded (COD – send the money to the customer manually).') : 'Return request approved.');
        }
    } elseif (post('action') === 'fship_create' && fship_enabled() && $o['awb'] === '') {
        confirm_order($id);
        $res = fship_create_shipment($o, order_items($id));
        if ($res['ok']) {
            q("UPDATE orders SET courier = 'Fship', awb = ?, tracking_url = ?, status = IF(status IN ('pending', 'confirmed'), 'packed', status), updated_at = NOW() WHERE id = ?",
                [$res['awb'], full_url('track-order.php?order=' . urlencode($o['order_no']) . '&phone=' . urlencode($o['phone'])), $id]);
            notify_order_update($id, 'Order packed', 'Your order has been packed and handed to our courier partner for dispatch.');
            flash('success', 'Fship shipment created. Waybill ' . $res['awb'] . '. Print the label and hand the parcel over at pickup.');
        } else {
            flash('error', 'Fship: ' . $res['error']);
        }
    } elseif (post('action') === 'fship_label' && $o['awb'] !== '' && $o['courier'] === 'Fship') {
        $link = fship_label_url($o['awb']);
        if ($link) {
            header('Location: ' . $link);
            exit;
        }
        flash('error', 'Fship did not return a label for this waybill. You can also print it from the Fship panel.');
    } elseif (post('action') === 'fship_cancel' && $o['awb'] !== '' && $o['courier'] === 'Fship') {
        $res = fship_cancel($o['awb']);
        if ($res['ok']) {
            q("UPDATE orders SET awb = '', tracking_url = '', courier = '', status = IF(status IN ('packed', 'shipped'), 'confirmed', status), updated_at = NOW() WHERE id = ?", [$id]);
            flash('success', 'Fship shipment cancelled. You can create a new one if needed.');
        } else {
            flash('error', 'Fship: ' . $res['error']);
        }
    }
    redirect('admin/order.php?id=' . $id);
}

$items = order_items($id);
$returns = rows('SELECT * FROM return_requests WHERE order_id = ? ORDER BY id DESC', [$id]);
$isFship = $o['awb'] !== '' && $o['courier'] === 'Fship';
$track = ($isFship && fship_enabled()) ? fship_track($o['awb']) : null;
admin_header('Order ' . $o['order_no'], 'orders');
?>
<div class="top">
  <h1>Order <?= e($o['order_no']) ?> <?= badge($o['status']) ?> <?= badge($o['payment_status']) ?></h1>
  <div>
    <a class="btn alt" href="<?= e(url('invoice.php?order=' . urlencode($o['order_no']))) ?>" target="_blank">Invoice</a>
    <a class="btn alt" href="<?= e(url('admin/orders.php')) ?>">← All orders</a>
  </div>
</div>
<div class="grid">
  <div class="card">
    <h2>Customer</h2>
    <strong><?= e($o['name']) ?></strong><br>
    <?= e($o['address']) ?><br><?= e($o['city'] . ', ' . $o['state'] . ' – ' . $o['pincode']) ?><br>
    <a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a> · <a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a>
    <?php if ($o['notes'] !== ''): ?><p><strong>Note:</strong> <?= e($o['notes']) ?></p><?php endif; ?>
    <p class="muted">Placed <?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></p>
  </div>
  <div class="card">
    <h2>Payment</h2>
    Method: <strong><?= $o['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Razorpay (online)' ?></strong><br>
    <?php if ($o['razorpay_order_id'] !== ''): ?>Razorpay order: <?= e($o['razorpay_order_id']) ?><br><?php endif; ?>
    <?php if ($o['razorpay_payment_id'] !== ''): ?>Payment ID: <?= e($o['razorpay_payment_id']) ?><br><?php endif; ?>
    <?= $o['payment_method'] === 'cod' ? 'Amount to collect' : 'Amount' ?>: <strong><?= money($o['total']) ?></strong>
  </div>
</div>

<div class="card table-wrap">
  <h2>Items</h2>
  <table>
    <thead><tr><th>Item</th><th>SKU</th><th>Price</th><th>Qty</th><th style="text-align:right">Total</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['name']) ?></td><td><?= e($it['sku']) ?></td><td><?= money($it['price']) ?></td><td><?= (int) $it['qty'] ?></td><td style="text-align:right"><?= money($it['price'] * $it['qty']) ?></td></tr>
      <?php endforeach; ?>
      <tr><td colspan="4" style="text-align:right">Subtotal</td><td style="text-align:right"><?= money($o['subtotal']) ?></td></tr>
      <?php if ($o['discount'] > 0): ?><tr><td colspan="4" style="text-align:right">Discount (<?= e($o['coupon_code']) ?>)</td><td style="text-align:right">− <?= money($o['discount']) ?></td></tr><?php endif; ?>
      <tr><td colspan="4" style="text-align:right">Shipping</td><td style="text-align:right"><?= money($o['shipping']) ?></td></tr>
      <?php if ($o['cod_fee'] > 0): ?><tr><td colspan="4" style="text-align:right">COD fee</td><td style="text-align:right"><?= money($o['cod_fee']) ?></td></tr><?php endif; ?>
      <tr><td colspan="4" style="text-align:right"><strong>Total</strong></td><td style="text-align:right"><strong><?= money($o['total']) ?></strong></td></tr>
    </tbody>
  </table>
</div>

<?php foreach ($returns as $rq): ?>
<div class="card">
  <h2>Return / refund request <?= badge($rq['status']) ?></h2>
  <p><strong><?= e(return_reasons()[$rq['reason']] ?? $rq['reason']) ?></strong> · requested <?= e(date('d M Y, h:i A', strtotime($rq['created_at']))) ?><?= $o['delivered_at'] ? ' · delivered ' . e(date('d M Y', strtotime($o['delivered_at']))) : '' ?></p>
  <?php if ($rq['details'] !== ''): ?><p><?= nl2br(e($rq['details'])) ?></p><?php endif; ?>
  <?php if ($rq['refund_id'] !== ''): ?><p class="muted">Razorpay refund ID: <?= e($rq['refund_id']) ?></p><?php endif; ?>
  <?php if (in_array($rq['status'], ['pending', 'approved'], true)): ?>
    <form method="post"><?= csrf_field() ?>
      <input type="hidden" name="action" value="return_decision"><input type="hidden" name="rid" value="<?= (int) $rq['id'] ?>">
      <label>Message to customer (optional)</label><input type="text" name="admin_note" value="<?= e($rq['admin_note']) ?>">
      <p style="margin-top:12px">
        <?php if ($rq['status'] === 'pending'): ?><button class="btn alt" name="decision" value="approve" type="submit">Approve return</button> <?php endif; ?>
        <button class="btn" name="decision" value="refund" type="submit" onclick="return confirm('Refund <?= e(money($o['total'])) ?> for this order?<?= $o['payment_method'] === 'razorpay' ? ' This sends the refund through Razorpay.' : '' ?>')">Refund <?= e(money($o['total'])) ?><?= $o['payment_method'] === 'razorpay' ? ' via Razorpay' : ' (COD – manual)' ?></button>
        <?php if ($rq['status'] === 'pending'): ?><button class="btn warn" name="decision" value="reject" type="submit">Reject</button><?php endif; ?>
      </p>
    </form>
  <?php elseif ($rq['admin_note'] !== ''): ?><p class="muted">Note: <?= e($rq['admin_note']) ?></p><?php endif; ?>
</div>
<?php endforeach; ?>

<form class="card" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="update">
  <h2>Status &amp; shipment</h2>
  <div class="grid three">
    <div><label>Order status</label><select name="status">
      <?php foreach (order_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Payment status</label><select name="payment_status">
      <?php foreach (payment_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $o['payment_status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Courier</label><input type="text" name="courier" value="<?= e($o['courier']) ?>" placeholder="e.g. Fship"></div>
    <div><label>Tracking / AWB number</label><input type="text" name="awb" value="<?= e($o['awb']) ?>"></div>
    <div class="full"><label>Tracking link</label><input type="text" name="tracking_url" value="<?= e($o['tracking_url']) ?>" placeholder="https://…"></div>
  </div>
  <p class="hint">Cancelling or returning an order puts its stock back. Marking it Shipped emails the tracking details to the customer.</p>
  <button class="btn" type="submit">Save changes</button>
</form>

<div class="card">
  <h2>Fship <?= fship_enabled() ? '<span class="badge">' . (setting('fship_live', '1') === '1' ? 'Live' : 'Test mode') . '</span>' : '' ?></h2>
  <?php if (!fship_enabled()): ?>
    <p class="muted">Add your Fship API key and pickup warehouse in <a href="<?= e(url('admin/settings.php')) ?>">Settings</a> to create shipments and labels from here.</p>
  <?php elseif ($o['awb'] !== '' && !$isFship): ?>
    <p class="muted">This order was shipped with <?= e($o['courier'] ?: 'another courier') ?> (tracking no. <?= e($o['awb']) ?>), so it is managed outside Fship.</p>
  <?php elseif ($o['awb'] !== ''): ?>
    <p>Waybill <strong><?= e($o['awb']) ?></strong><?= $track && $track['status'] !== '' ? ' — current status: <strong>' . e($track['status']) . '</strong>' : '' ?></p>
    <form class="inline" method="post" target="_blank"><?= csrf_field() ?><input type="hidden" name="action" value="fship_label">
      <button class="btn" type="submit">Print shipping label</button>
    </form>
    <form class="inline" method="post" onsubmit="return confirm('Cancel this Fship shipment?')"><?= csrf_field() ?><input type="hidden" name="action" value="fship_cancel">
      <button class="btn warn" type="submit">Cancel shipment</button>
    </form>
    <?php if ($track && $track['scans']): ?>
      <table style="margin-top:14px">
        <?php foreach (array_slice($track['scans'], 0, 8) as $s): ?>
          <tr><td class="muted" style="white-space:nowrap"><?= e($s['date']) ?></td><td><?= e($s['activity']) ?></td><td class="muted"><?= e($s['location']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  <?php elseif (in_array($o['status'], ['cancelled', 'returned'], true)): ?>
    <p class="muted">This order is <?= e($o['status']) ?>.</p>
  <?php else: ?>
    <?php if ($o['payment_method'] === 'razorpay' && $o['payment_status'] !== 'paid'): ?><p class="flash flash-error">This online order is not paid yet — check the payment before shipping.</p><?php endif; ?>
    <?php if (setting('fship_warehouse_id') === ''): ?><p class="flash flash-error">Add your Fship pickup warehouse ID in Settings before creating a shipment.</p><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="fship_create">
      <button class="btn" type="submit">Create Fship shipment</button>
      <span class="hint"><?= $o['payment_method'] === 'cod' ? 'COD – the courier will collect ' . money($o['total']) : 'Prepaid' ?> · pickup warehouse ID <?= e(setting('fship_warehouse_id') ?: '—') ?></span>
    </form>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>

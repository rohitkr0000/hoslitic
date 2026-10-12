<?php
require __DIR__ . '/_layout.php';

$live = "status NOT IN ('pending', 'cancelled', 'returned')";
$stats = [
    'Sales today' => money(val("SELECT COALESCE(SUM(total), 0) FROM orders WHERE $live AND DATE(created_at) = CURDATE()")),
    'Sales this month' => money(val("SELECT COALESCE(SUM(total), 0) FROM orders WHERE $live AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")),
    'Total orders' => (int) val("SELECT COUNT(*) FROM orders WHERE $live"),
    'To be shipped' => (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed', 'packed')"),
    'Customers' => (int) val('SELECT COUNT(*) FROM users'),
];
$recent = rows('SELECT * FROM orders ORDER BY id DESC LIMIT 8');
$low = rows('SELECT name, stock FROM products WHERE is_active = 1 AND stock <= 10 ORDER BY stock');
$todo = [];
if (!payment_methods()) { $todo[] = 'No payment method is enabled — customers cannot check out.'; }
if (setting('razorpay_key_id') === '') { $todo[] = 'Add your Razorpay keys in Settings to accept online payments.'; }
if (setting('fship_key') === '') { $todo[] = 'Add your Fship API key in Settings to create shipments and labels from the order screen.'; }
if (setting('phone') === '' || setting('email') === '') { $todo[] = 'Add your support phone and email in Settings (the email is also used to send order confirmations).'; }
if (is_file(ROOT_PATH . '/install.php')) { $todo[] = 'Delete install.php from the server.'; }

admin_header('Dashboard', 'dashboard');
?>
<div class="top"><h1>Dashboard</h1><span class="muted">Hello, <?= e($admin['name']) ?></span></div>
<?php foreach ($todo as $t): ?><div class="flash flash-info"><?= e($t) ?></div><?php endforeach; ?>
<div class="stats">
  <?php foreach ($stats as $label => $v): ?><div class="stat"><b><?= e($v) ?></b><span><?= e($label) ?></span></div><?php endforeach; ?>
</div>
<div class="card">
  <h2>Recent orders</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php foreach ($recent as $o): ?>
        <tr>
          <td><a href="<?= e(url('admin/order.php?id=' . $o['id'])) ?>"><strong><?= e($o['order_no']) ?></strong></a></td>
          <td><?= e($o['name']) ?></td><td><?= money($o['total']) ?></td>
          <td><?= e(strtoupper($o['payment_method'])) ?> <?= badge($o['payment_status']) ?></td>
          <td><?= badge($o['status']) ?></td><td><?= e(date('d M, h:i A', strtotime($o['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="6" class="muted">No orders yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php if ($low): ?>
<div class="card">
  <h2>Low stock</h2>
  <?php foreach ($low as $l): ?><p style="margin:4px 0"><?= e($l['name']) ?> — <strong><?= (int) $l['stock'] ?></strong> left</p><?php endforeach; ?>
</div>
<?php endif; ?>
<?php admin_footer(); ?>

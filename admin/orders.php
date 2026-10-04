<?php
require __DIR__ . '/_layout.php';

$status = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['q'] ?? ''));
$where = ['1 = 1'];
$params = [];
if (isset(order_statuses()[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(order_no LIKE ? OR name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 25;
$total = (int) val('SELECT COUNT(*) FROM orders WHERE ' . implode(' AND ', $where), $params);
$orders = rows('SELECT * FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);
$qs = fn(array $extra) => e(url('admin/orders.php?' . http_build_query(array_filter($extra + ['status' => $status, 'q' => $search]))));

admin_header('Orders', 'orders');
?>
<div class="top">
  <h1>Orders <span class="muted" style="font-size:1rem">(<?= $total ?>)</span></h1>
  <form method="get" style="display:flex;gap:8px">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Order no., name, phone">
    <button class="btn" type="submit">Search</button>
  </form>
</div>
<div class="filters">
  <a class="<?= $status === '' ? 'on' : '' ?>" href="<?= $qs(['status' => '']) ?>">All</a>
  <?php foreach (order_statuses() as $k => $label): ?>
    <a class="<?= $status === $k ? 'on' : '' ?>" href="<?= $qs(['status' => $k]) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<div class="card table-wrap">
  <table>
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Location</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><a href="<?= e(url('admin/order.php?id=' . $o['id'])) ?>"><strong><?= e($o['order_no']) ?></strong></a></td>
          <td><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></td>
          <td><?= e($o['name']) ?><br><span class="muted"><?= e($o['phone']) ?></span></td>
          <td><?= e($o['city'] . ', ' . $o['state']) ?></td>
          <td><?= money($o['total']) ?></td>
          <td><?= e(strtoupper($o['payment_method'])) ?> <?= badge($o['payment_status']) ?></td>
          <td><?= badge($o['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="7" class="muted">No orders found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($total > $per): ?>
  <div class="filters">
    <?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a><?php endfor; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>

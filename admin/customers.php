<?php
require __DIR__ . '/_layout.php';

$customers = rows("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
    (SELECT COALESCE(SUM(total), 0) FROM orders o WHERE o.user_id = u.id AND o.status NOT IN ('pending', 'cancelled', 'returned')) AS spent
    FROM users u ORDER BY u.id DESC LIMIT 500");
admin_header('Customers', 'customers');
?>
<div class="top"><h1>Customers</h1></div>
<div class="card table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Orders</th><th>Spent</th><th>Joined</th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><strong><?= e($c['name']) ?></strong></td>
          <td><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></td>
          <td><?= e($c['phone']) ?></td>
          <td><?= e(trim($c['city'] . ', ' . $c['state'], ', ')) ?></td>
          <td><a href="<?= e(url('admin/orders.php?q=' . urlencode($c['email']))) ?>"><?= (int) $c['order_count'] ?></a></td>
          <td><?= money($c['spent']) ?></td>
          <td><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?><tr><td colspan="7" class="muted">No registered customers yet. Guest orders appear under Orders.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>

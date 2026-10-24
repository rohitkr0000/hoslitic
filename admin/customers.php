<?php
require __DIR__ . '/_layout.php';

// Filters (also applied to the Excel download).
$f = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'state' => trim((string) ($_GET['state'] ?? '')),
    'orders' => (string) ($_GET['orders'] ?? ''),
    'verified' => (string) ($_GET['verified'] ?? ''),
    'from' => (string) ($_GET['from'] ?? ''),
    'to' => (string) ($_GET['to'] ?? ''),
];
$where = ['1 = 1'];
$params = [];
if ($f['q'] !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.city LIKE ?)';
    array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
}
if ($f['state'] !== '') {
    $where[] = 'u.state = ?';
    $params[] = $f['state'];
}
if ($f['orders'] === 'with') {
    $where[] = 'EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)';
} elseif ($f['orders'] === 'none') {
    $where[] = 'NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)';
}
if ($f['verified'] === 'yes') {
    $where[] = 'u.email_verified_at IS NOT NULL';
} elseif ($f['verified'] === 'no') {
    $where[] = 'u.email_verified_at IS NULL';
}
foreach (['from' => '>=', 'to' => '<='] as $k => $op) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$k])) {
        $where[] = "DATE(u.created_at) $op ?";
        $params[] = $f[$k];
    } else {
        $f[$k] = '';
    }
}
$whereSql = implode(' AND ', $where);
$select = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
    (SELECT COALESCE(SUM(total), 0) FROM orders o WHERE o.user_id = u.id AND o.status NOT IN ('pending', 'cancelled', 'returned')) AS spent
    FROM users u WHERE $whereSql ORDER BY u.id DESC";

if (isset($_GET['export'])) {
    require ROOT_PATH . '/includes/xlsx.php';
    $out = [];
    foreach (rows($select, $params) as $c) {
        $out[] = [
            $c['name'], $c['email'], $c['phone'], $c['address'], $c['city'], $c['state'], $c['pincode'],
            $c['email_verified_at'] ? 'Yes' : 'No', (int) $c['order_count'], (float) $c['spent'],
            date('d M Y, h:i A', strtotime($c['created_at'])),
        ];
    }
    xlsx_download('customers-' . date('Y-m-d') . '.xlsx',
        ['Name', 'Email', 'Mobile', 'Address', 'City', 'State', 'PIN code', 'Email verified', 'Orders', 'Total spent (₹)', 'Joined'],
        $out, [22, 30, 14, 36, 16, 18, 10, 14, 9, 15, 20]);
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 50;
$total = (int) val("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
$customers = rows($select . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);
$states = array_column(rows("SELECT DISTINCT state FROM users WHERE state <> '' ORDER BY state"), 'state');
$qs = fn(array $extra) => e(url('admin/customers.php?' . http_build_query(array_filter($extra + $f, 'strlen'))));
$filtered = array_filter($f, 'strlen') !== [];

admin_header('Customers', 'customers');
?>
<div class="top">
  <h1>Customers <span class="muted" style="font-size:1rem">(<?= $total ?>)</span></h1>
  <a class="btn" href="<?= $qs(['export' => '1']) ?>">⬇ Download Excel<?= $filtered ? ' (filtered)' : '' ?></a>
</div>
<form class="card cust-filters" method="get">
  <div><label>Search</label><input type="text" name="q" value="<?= e($f['q']) ?>" placeholder="Name, email, mobile, city"></div>
  <div><label>State</label>
    <select name="state"><option value="">All states</option>
      <?php foreach ($states as $s): ?><option <?= $f['state'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div><label>Orders</label>
    <select name="orders">
      <option value="">All customers</option>
      <option value="with" <?= $f['orders'] === 'with' ? 'selected' : '' ?>>Have ordered</option>
      <option value="none" <?= $f['orders'] === 'none' ? 'selected' : '' ?>>No orders yet</option>
    </select>
  </div>
  <div><label>Email verified</label>
    <select name="verified">
      <option value="">Any</option>
      <option value="yes" <?= $f['verified'] === 'yes' ? 'selected' : '' ?>>Verified</option>
      <option value="no" <?= $f['verified'] === 'no' ? 'selected' : '' ?>>Not verified</option>
    </select>
  </div>
  <div><label>Joined from</label><input type="date" name="from" value="<?= e($f['from']) ?>"></div>
  <div><label>Joined to</label><input type="date" name="to" value="<?= e($f['to']) ?>"></div>
  <div class="actions">
    <button class="btn" type="submit">Apply filters</button>
    <?php if ($filtered): ?><a class="btn alt" href="<?= e(url('admin/customers.php')) ?>">Clear</a><?php endif; ?>
  </div>
</form>
<div class="card table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Orders</th><th>Spent</th><th>Joined</th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><strong><?= e($c['name']) ?></strong></td>
          <td><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?= $c['email_verified_at'] ? ' <span class="badge badge-paid" title="Email verified">✓</span>' : '' ?></td>
          <td><?= e($c['phone']) ?></td>
          <td><?= e(trim($c['city'] . ', ' . $c['state'], ', ')) ?></td>
          <td><a href="<?= e(url('admin/orders.php?q=' . urlencode($c['email']))) ?>"><?= (int) $c['order_count'] ?></a></td>
          <td><?= money($c['spent']) ?></td>
          <td><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?><tr><td colspan="7" class="muted"><?= $filtered ? 'No customers match these filters.' : 'No registered customers yet.' ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($total > $per): ?>
  <div class="filters">
    <?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a><?php endfor; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>

<?php
// Admin bootstrap + layout. Every admin page requires this first.
require dirname(__DIR__) . '/includes/init.php';
$admin = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}

function admin_header(string $title, string $active): void
{
    $newOrders = (int) val("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'");
    $pendingReviews = (int) val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
    $unread = (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0');
    $nav = [
        'dashboard' => ['Dashboard', 'index.php', 0],
        'orders' => ['Orders', 'orders.php', $newOrders],
        'products' => ['Products', 'products.php', 0],
        'customers' => ['Customers', 'customers.php', 0],
        'reviews' => ['Reviews', 'reviews.php', $pendingReviews],
        'messages' => ['Messages', 'messages.php', $unread],
        '-1' => null,
        'coupons' => ['Coupons', 'crud.php?t=coupons', 0],
        'zones' => ['Shipping', 'crud.php?t=zones', 0],
        'faqs' => ['FAQs', 'crud.php?t=faqs', 0],
        'pages' => ['Pages', 'crud.php?t=pages', 0],
        'settings' => ['Settings', 'settings.php', 0],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Admin</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=1">
</head>
<body>
<div class="shell">
  <aside class="side">
    <a class="logo" href="<?= e(url('admin/index.php')) ?>"><?= e(setting('site_name')) ?><small>Store admin</small></a>
    <nav>
      <?php foreach ($nav as $key => $n): ?>
        <?php if ($n === null): ?><hr><?php else: ?>
          <a class="<?= $active === $key ? 'on' : '' ?>" href="<?= e(url('admin/' . $n[1])) ?>"><?= e($n[0]) ?><?php if ($n[2]): ?><span class="pill"><?= $n[2] ?></span><?php endif; ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <hr>
      <a href="<?= e(url('')) ?>" target="_blank">View store ↗</a>
      <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
    </nav>
  </aside>
  <div class="main">
    <?php foreach (flashes() as $fl): ?><div class="flash flash-<?= e($fl['type']) ?>"><?= e($fl['msg']) ?></div><?php endforeach; ?>
<?php }

function admin_footer(): void
{ ?>
  </div>
</div>
</body>
</html>
<?php }

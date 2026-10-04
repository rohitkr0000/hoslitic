<?php
require __DIR__ . '/includes/init.php';

$search = trim((string) ($_GET['q'] ?? ''));
$products = $search !== ''
    ? products_with_image('p.is_active = 1 AND (p.name LIKE ? OR p.short_description LIKE ?)', ["%$search%", "%$search%"])
    : products_with_image();
$pageTitle = 'Shop';
$activeNav = 'shop';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <div class="container">
    <?= breadcrumbs(['Shop' => null]) ?>
    <h1>Shop</h1>
    <p>All prices include GST. Shipping is calculated at checkout.</p>
  </div>
</section>
<section class="screen">
  <div class="container">
    <form method="get" style="display:flex;gap:10px;max-width:420px">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products">
      <button class="btn btn-dark btn-sm" type="submit">Search</button>
    </form>
    <div class="products four start">
      <?php foreach ($products as $p) { product_card($p); } ?>
    </div>
    <?php if (!$products): ?><p class="muted">No products found.</p><?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

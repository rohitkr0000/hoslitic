<?php
require __DIR__ . '/includes/init.php';

$search = trim((string) ($_GET['q'] ?? ''));
$products = $search !== ''
    ? products_with_image('p.is_active = 1 AND (p.name LIKE ? OR p.short_description LIKE ?)', ["%$search%", "%$search%"])
    : products_with_image();
$sort = (string) ($_GET['sort'] ?? '');
if ($sort === 'low') { usort($products, fn($a, $b) => $a['price'] <=> $b['price']); }
if ($sort === 'high') { usort($products, fn($a, $b) => $b['price'] <=> $a['price']); }
if ($sort === 'save') { usort($products, fn($a, $b) => discount_percent($b) <=> discount_percent($a)); }
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
<section class="screen shop-body">
  <div class="container">
    <form method="get" class="shop-bar">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products">
      <select name="sort" aria-label="Sort by" onchange="this.form.submit()">
        <option value="">Featured</option>
        <option value="low"<?= $sort === 'low' ? ' selected' : '' ?>>Price: low to high</option>
        <option value="high"<?= $sort === 'high' ? ' selected' : '' ?>>Price: high to low</option>
        <option value="save"<?= $sort === 'save' ? ' selected' : '' ?>>Biggest discount</option>
      </select>
      <button class="btn btn-dark btn-sm" type="submit">Search</button>
      <span class="shop-count"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></span>
    </form>
    <div class="products four start">
      <?php foreach ($products as $p) { product_card($p); } ?>
    </div>
    <?php if (!$products): ?><p class="muted">No products found. <a href="<?= e(url('shop.php')) ?>">Clear search</a></p><?php endif; ?>
  </div>
</section>
<?php
sec_usps();
sec_delivery_steps();
sec_faq(4);
sec_cta();
require __DIR__ . '/includes/footer.php';

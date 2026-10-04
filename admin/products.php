<?php
require __DIR__ . '/_layout.php';

$uploadDir = ROOT_PATH . '/uploads/products/';
$editing = isset($_GET['id']) || isset($_GET['new']);
$id = (int) ($_GET['id'] ?? 0);
$p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$p) {
    redirect('admin/products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'delete') {
        $pid = (int) post('id');
        foreach (product_images($pid) as $file) {
            @unlink($uploadDir . basename($file));
        }
        q('DELETE FROM products WHERE id = ?', [$pid]);
        flash('success', 'Product deleted.');
        redirect('admin/products.php');
    }

    if ($action === 'delete_image') {
        $img = row('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int) post('image_id'), $id]);
        if ($img) {
            @unlink($uploadDir . basename($img['filename']));
            q('DELETE FROM product_images WHERE id = ?', [$img['id']]);
            flash('success', 'Image removed.');
        }
        redirect('admin/products.php?id=' . $id);
    }

    if ($action === 'save') {
        $d = [
            'name' => mb_substr(post('name'), 0, 150),
            'tagline' => mb_substr(post('tagline'), 0, 200),
            'pack_label' => mb_substr(post('pack_label'), 0, 60),
            'sku' => mb_substr(post('sku'), 0, 40),
            'hsn' => mb_substr(post('hsn'), 0, 12),
            'gst_rate' => max(0, (float) post('gst_rate')),
            'mrp' => max(0, (float) post('mrp')),
            'price' => max(0, (float) post('price')),
            'stock' => max(0, (int) post('stock')),
            'weight_grams' => max(1, (int) post('weight_grams')),
            'short_description' => post('short_description'),
            'description' => post('description'),
            'ingredients' => post('ingredients'),
            'how_to_use' => post('how_to_use'),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int) post('sort_order'),
        ];
        if ($d['name'] === '' || $d['price'] <= 0) {
            flash('error', 'Product name and selling price are required.');
            redirect('admin/products.php?' . ($id ? 'id=' . $id : 'new=1'));
        }
        // Unique slug
        $slug = slugify(post('slug') !== '' ? post('slug') : $d['name']);
        $base = $slug;
        for ($i = 2; val('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $id]); $i++) {
            $slug = $base . '-' . $i;
        }
        $d['slug'] = $slug;

        if ($id) {
            q('UPDATE products SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($d))) . ' WHERE id = ?', array_merge(array_values($d), [$id]));
        } else {
            q('INSERT INTO products (' . implode(', ', array_keys($d)) . ') VALUES (' . implode(', ', array_fill(0, count($d), '?')) . ')', array_values($d));
            $id = (int) db()->lastInsertId();
        }

        // Image uploads: real images only, renamed to a random file name.
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        $files = $_FILES['images'] ?? null;
        $skipped = 0;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $unused) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $info = $files['error'][$i] === UPLOAD_ERR_OK ? @getimagesize($files['tmp_name'][$i]) : false;
                if (!$info || !isset($types[$info[2]]) || $files['size'][$i] > 5 * 1024 * 1024) {
                    $skipped++;
                    continue;
                }
                $name = $slug . '-' . bin2hex(random_bytes(5)) . '.' . $types[$info[2]];
                if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $name)) {
                    q('INSERT INTO product_images (product_id, filename, sort_order) VALUES (?, ?, ?)', [$id, $name, 100 + $i]);
                }
            }
        }
        flash('success', 'Product saved.');
        if ($skipped) {
            flash('error', "$skipped image(s) were skipped — use JPG, PNG or WEBP under 5 MB.");
        }
        redirect('admin/products.php?id=' . $id);
    }
}

admin_header('Products', 'products');

if ($editing):
    $p = $p ?? ['name' => '', 'slug' => '', 'tagline' => '', 'pack_label' => '', 'sku' => '', 'hsn' => '30049011', 'gst_rate' => 5, 'mrp' => '', 'price' => '',
        'stock' => 0, 'weight_grams' => 100, 'short_description' => '', 'description' => '', 'ingredients' => '', 'how_to_use' => '', 'is_featured' => 0, 'is_active' => 1, 'sort_order' => 0];
    $images = $id ? rows('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$id]) : [];
    ?>
    <div class="top"><h1><?= $id ? 'Edit product' : 'Add product' ?></h1><a class="btn alt" href="<?= e(url('admin/products.php')) ?>">← All products</a></div>
    <form class="card" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <div class="grid">
        <div><label>Product name</label><input type="text" name="name" value="<?= e($p['name']) ?>" required></div>
        <div><label>URL slug</label><input type="text" name="slug" value="<?= e($p['slug']) ?>" placeholder="auto from name"></div>
        <div><label>Tagline</label><input type="text" name="tagline" value="<?= e($p['tagline']) ?>"></div>
        <div><label>Pack label</label><input type="text" name="pack_label" value="<?= e($p['pack_label']) ?>" placeholder="e.g. 60 Capsules"></div>
      </div>
      <div class="grid three" style="margin-top:14px">
        <div><label>MRP (₹)</label><input type="number" step="0.01" min="0" name="mrp" value="<?= e($p['mrp']) ?>"></div>
        <div><label>Selling price (₹, incl. GST)</label><input type="number" step="0.01" min="0" name="price" value="<?= e($p['price']) ?>" required></div>
        <div><label>Stock</label><input type="number" min="0" name="stock" value="<?= e($p['stock']) ?>"></div>
        <div><label>SKU</label><input type="text" name="sku" value="<?= e($p['sku']) ?>"></div>
        <div><label>HSN code</label><input type="text" name="hsn" value="<?= e($p['hsn']) ?>"></div>
        <div><label>GST rate (%)</label><input type="number" step="0.01" min="0" name="gst_rate" value="<?= e($p['gst_rate']) ?>"></div>
        <div><label>Shipping weight (grams)</label><input type="number" min="1" name="weight_grams" value="<?= e($p['weight_grams']) ?>"></div>
        <div><label>Sort order</label><input type="number" name="sort_order" value="<?= e($p['sort_order']) ?>"></div>
        <div style="display:flex;gap:18px;align-items:end">
          <label><input type="checkbox" name="is_active" <?= $p['is_active'] ? 'checked' : '' ?>> Active</label>
          <label><input type="checkbox" name="is_featured" <?= $p['is_featured'] ? 'checked' : '' ?>> Featured</label>
        </div>
      </div>
      <div class="grid" style="margin-top:14px">
        <div class="full"><label>Short description</label><textarea name="short_description" style="min-height:70px"><?= e($p['short_description']) ?></textarea></div>
        <div class="full"><label>Full description</label><textarea name="description" style="min-height:170px"><?= e($p['description']) ?></textarea></div>
        <div><label>How to use</label><textarea name="how_to_use"><?= e($p['how_to_use']) ?></textarea></div>
        <div><label>Ingredients</label><textarea name="ingredients"><?= e($p['ingredients']) ?></textarea></div>
        <div class="full"><label>Add images</label><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><div class="hint">JPG, PNG or WEBP, up to 5 MB each. Square images look best.</div></div>
      </div>
      <p><button class="btn" type="submit">Save product</button></p>
    </form>
    <?php if ($images): ?>
      <div class="card">
        <h2>Images (first one is the main image)</h2>
        <div class="imgs">
          <?php foreach ($images as $img): ?>
            <figure>
              <img src="<?= e(product_image_url($img['filename'])) ?>" alt="">
              <form method="post" onsubmit="return confirm('Remove this image?')"><?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_image"><input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                <button class="btn warn sm" type="submit">Remove</button>
              </form>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
<?php else:
    $products = products_with_image('1 = 1'); ?>
    <div class="top"><h1>Products</h1><a class="btn" href="<?= e(url('admin/products.php?new=1')) ?>">+ Add product</a></div>
    <div class="card table-wrap">
      <table>
        <thead><tr><th></th><th>Product</th><th>SKU</th><th>Price</th><th>MRP</th><th>Stock</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($products as $pr): ?>
            <tr>
              <td><img class="thumb" src="<?= e(product_image_url($pr['image'])) ?>" alt=""></td>
              <td><strong><?= e($pr['name']) ?></strong><br><span class="muted"><?= e($pr['pack_label']) ?></span></td>
              <td><?= e($pr['sku']) ?></td><td><?= money($pr['price']) ?></td><td><?= money($pr['mrp']) ?></td>
              <td><?= (int) $pr['stock'] ?></td>
              <td><?= $pr['is_active'] ? '<span class="badge badge-approved">Active</span>' : '<span class="badge">Hidden</span>' ?></td>
              <td style="white-space:nowrap">
                <a class="btn alt sm" href="<?= e(url('admin/products.php?id=' . $pr['id'])) ?>">Edit</a>
                <form class="inline" method="post" onsubmit="return confirm('Delete this product permanently?')"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $pr['id'] ?>">
                  <button class="btn warn sm" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php endif;
admin_footer();

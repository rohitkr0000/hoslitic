<?php
// One generic list + form screen for the simple tables.
require __DIR__ . '/_layout.php';

$defs = [
    'categories' => [
        'table' => 'categories', 'title' => 'Product categories', 'single' => 'category', 'order' => 'sort_order, name',
        'list' => ['name' => 'Category', 'slug' => 'URL slug', 'sort_order' => 'Order', 'is_active' => 'Active'],
        'fields' => [
            'name' => ['Category name', 'text'],
            'slug' => ['URL slug', 'text', 'Leave empty to create it from the name. Opens at /shop?cat=…'],
            'image' => ['Tile image', 'image', 'Shown in "Shop by concern" on the home page. Square images look best.'],
            'sort_order' => ['Sort order', 'int'],
            'show_on_home' => ['Show on home page', 'bool'],
            'is_active' => ['Active', 'bool', 'Inactive categories are hidden from the shop filter.'],
        ],
    ],
    'banners' => [
        'table' => 'banners', 'title' => 'Home banners', 'single' => 'banner', 'order' => 'sort_order, id',
        'list' => ['title' => 'Heading', 'link' => 'Link', 'sort_order' => 'Order', 'is_active' => 'Active'],
        'fields' => [
            'title' => ['Banner name', 'text', 'Describes the banner for screen readers and search engines. Also the heading when text is shown over the image.'],
            'link' => ['Link', 'text', 'Where the banner goes when clicked: a page on this site (e.g. shop or shop?cat=combos) or a full https:// address.'],
            'image' => ['Banner image (desktop)', 'image', 'Full-width artwork, about 1920 × 700. Put your offer text in the image itself.'],
            'mobile_image' => ['Banner image (mobile, optional)', 'image', 'Shown on phones instead of the desktop image, about 800 × 800.'],
            'show_text' => ['Show heading, text and button over the image', 'bool', 'Leave unticked for image-only banners.'],
            'kicker' => ['Small heading', 'text', 'Short label above the heading.'],
            'body' => ['Text', 'textarea'],
            'button_label' => ['Button text', 'text', 'e.g. Shop now'],
            'sort_order' => ['Sort order', 'int'],
            'is_active' => ['Active', 'bool'],
        ],
    ],
    'blog' => [
        'table' => 'blog_posts', 'title' => 'Blog', 'single' => 'blog post', 'order' => 'created_at DESC, id DESC',
        'list' => ['title' => 'Title', 'slug' => 'URL slug', 'created_at' => 'Date', 'is_published' => 'Published'],
        'fields' => [
            'title' => ['Title', 'text'],
            'slug' => ['URL slug', 'text', 'Leave empty to create it from the title. Opens at /blog/…'],
            'image' => ['Cover image', 'image'],
            'excerpt' => ['Short summary', 'textarea', 'One or two sentences shown on the blog cards.'],
            'content' => ['Article', 'html'],
            'is_published' => ['Published', 'bool'],
        ],
    ],
    'instagram' => [
        'table' => 'insta_posts', 'title' => 'Instagram tiles', 'single' => 'Instagram tile', 'order' => 'sort_order, id',
        'list' => ['link' => 'Link', 'sort_order' => 'Order', 'is_active' => 'Active'],
        'fields' => [
            'link' => ['Post or reel link', 'text', 'The full Instagram address this tile opens.'],
            'image' => ['Image', 'image', 'Square image or reel cover.'],
            'sort_order' => ['Sort order', 'int'],
            'is_active' => ['Active', 'bool'],
        ],
    ],
    'coupons' => [
        'table' => 'coupons', 'title' => 'Coupons', 'single' => 'coupon', 'order' => 'id DESC',
        'list' => ['code' => 'Code', 'type' => 'Type', 'value' => 'Value', 'min_order' => 'Min order', 'used_count' => 'Used', 'expires_on' => 'Expires', 'is_active' => 'Active'],
        'fields' => [
            'code' => ['Coupon code', 'text', 'Customers type this at the cart. Saved in capitals.'],
            'type' => ['Discount type', 'select', '', ['percent' => 'Percent (%)', 'flat' => 'Flat amount (₹)']],
            'value' => ['Discount value', 'number'],
            'min_order' => ['Minimum order (₹)', 'number'],
            'max_discount' => ['Maximum discount (₹)', 'number', '0 = no cap'],
            'usage_limit' => ['Usage limit', 'int', '0 = unlimited'],
            'expires_on' => ['Expires on', 'date', 'Leave empty for no expiry'],
            'is_active' => ['Active', 'bool'],
        ],
    ],
    'zones' => [
        'table' => 'shipping_zones', 'title' => 'Shipping zones', 'single' => 'shipping zone', 'order' => 'sort_order, id',
        'list' => ['name' => 'Zone', 'states' => 'States', 'rate' => 'Rate', 'free_above' => 'Free above', 'cod_fee' => 'COD fee', 'eta' => 'Delivery time', 'is_active' => 'Active'],
        'fields' => [
            'name' => ['Zone name', 'text'],
            'states' => ['States', 'textarea', 'Comma-separated state names exactly as in the checkout list (e.g. Uttar Pradesh, Delhi). Use * for "all other states".'],
            'rate' => ['Shipping charge (₹)', 'number'],
            'free_above' => ['Free shipping above (₹)', 'number', '0 = never free'],
            'cod_fee' => ['COD handling fee (₹)', 'number'],
            'eta' => ['Delivery time', 'text', 'Shown at checkout, e.g. 3–5 business days'],
            'sort_order' => ['Sort order', 'int', 'Lower numbers are matched first'],
            'is_active' => ['Active', 'bool'],
        ],
    ],
    'faqs' => [
        'table' => 'faqs', 'title' => 'FAQs', 'single' => 'FAQ', 'order' => 'sort_order, id',
        'list' => ['question' => 'Question', 'sort_order' => 'Order', 'is_active' => 'Active'],
        'fields' => [
            'question' => ['Question', 'text'],
            'answer' => ['Answer', 'textarea'],
            'sort_order' => ['Sort order', 'int'],
            'is_active' => ['Active', 'bool'],
        ],
    ],
    'pages' => [
        'table' => 'pages', 'title' => 'Pages', 'single' => 'page', 'order' => 'sort_order, id',
        'list' => ['title' => 'Title', 'slug' => 'URL slug', 'show_in_footer' => 'In footer'],
        'fields' => [
            'title' => ['Page title', 'text'],
            'slug' => ['URL slug', 'text', 'Opens at /page/…  Keep "about" and "terms" as they are linked from the site.'],
            'content' => ['Content (HTML allowed)', 'html'],
            'sort_order' => ['Sort order', 'int'],
            'show_in_footer' => ['Show in footer', 'bool'],
        ],
    ],
];

$t = (string) ($_GET['t'] ?? '');
if (!isset($defs[$t])) {
    redirect('admin/index.php');
}
$def = $defs[$t];
$table = $def['table'];
$self = 'admin/crud.php?t=' . $t;
$id = (int) ($_GET['id'] ?? 0);
$record = $id ? row("SELECT * FROM $table WHERE id = ?", [$id]) : null;
$mediaDir = ROOT_PATH . '/uploads/media/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'delete') {
        $gone = row("SELECT * FROM $table WHERE id = ?", [(int) post('id')]) ?? [];
        foreach ($def['fields'] as $name => $fd) {
            if ($fd[1] === 'image' && ($gone[$name] ?? '') !== '') { @unlink($mediaDir . basename($gone[$name])); }
        }
        q("DELETE FROM $table WHERE id = ?", [(int) post('id')]);
        if ($t === 'categories') { q('UPDATE products SET category_id = NULL WHERE category_id = ?', [(int) post('id')]); }
        flash('success', ucfirst($def['single']) . ' deleted.');
        redirect($self);
    }
    $data = [];
    foreach ($def['fields'] as $name => $fd) {
        $v = post($name);
        switch ($fd[1]) {
            case 'bool': $v = isset($_POST[$name]) ? 1 : 0; break;
            case 'int': $v = (int) $v; break;
            case 'number': $v = max(0, (float) $v); break;
            case 'date': $v = preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null; break;
            case 'select': $v = isset($fd[3][$v]) ? $v : array_key_first($fd[3]); break;
            case 'image':
                // Real images only, renamed to a random file name. No new upload keeps the current image.
                $v = (string) ($record[$name] ?? '');
                $up = $_FILES[$name] ?? null;
                if ($up && $up['error'] !== UPLOAD_ERR_NO_FILE) {
                    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
                    $info = $up['error'] === UPLOAD_ERR_OK ? @getimagesize($up['tmp_name']) : false;
                    if (!$info || !isset($types[$info[2]]) || $up['size'] > 5 * 1024 * 1024) {
                        flash('error', 'Image skipped — use JPG, PNG or WEBP under 5 MB.');
                    } else {
                        if (!is_dir($mediaDir)) { mkdir($mediaDir, 0755, true); }
                        $file = $t . '-' . bin2hex(random_bytes(6)) . '.' . $types[$info[2]];
                        if (move_uploaded_file($up['tmp_name'], $mediaDir . $file)) {
                            if ($v !== '') { @unlink($mediaDir . basename($v)); }
                            $v = $file;
                        }
                    }
                }
                break;
        }
        $data[$name] = $v;
    }
    if ($t === 'coupons') { $data['code'] = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $data['code'])); }
    $first = array_key_first($def['fields']);
    if (isset($data['slug'])) { $data['slug'] = slugify($data['slug'] !== '' ? $data['slug'] : $data[$first]); }
    if ($data[$first] === '') {
        flash('error', $def['fields'][$first][0] . ' is required.');
        redirect($self . ($id ? '&id=' . $id : '&new=1'));
    }
    try {
        if ($record) {
            q("UPDATE $table SET " . implode(', ', array_map(fn($k) => "$k = ?", array_keys($data))) . ' WHERE id = ?', array_merge(array_values($data), [$id]));
        } else {
            q("INSERT INTO $table (" . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
        }
        flash('success', ucfirst($def['single']) . ' saved.');
    } catch (PDOException $ex) {
        flash('error', $ex->getCode() === '23000' ? 'That ' . $first . ' is already in use. Please choose another.' : 'Could not save: ' . $ex->getMessage());
        redirect($self . ($id ? '&id=' . $id : '&new=1'));
    }
    redirect($self);
}

admin_header($def['title'], $t);

if ($record || isset($_GET['new'])): ?>
  <div class="top"><h1><?= $record ? 'Edit' : 'Add' ?> <?= e($def['single']) ?></h1><a class="btn alt" href="<?= e(url($self)) ?>">← Back</a></div>
  <form class="card" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="grid">
      <?php foreach ($def['fields'] as $name => $fd):
          $value = $record[$name] ?? ($fd[1] === 'bool' ? 1 : '');
          $wide = in_array($fd[1], ['textarea', 'html'], true); ?>
        <div class="<?= $wide ? 'full' : '' ?>">
          <?php if ($fd[1] === 'bool'): ?>
            <label><input type="checkbox" name="<?= e($name) ?>" <?= $value ? 'checked' : '' ?>> <?= e($fd[0]) ?></label>
          <?php else: ?>
            <label><?= e($fd[0]) ?></label>
            <?php if ($wide): ?>
              <textarea name="<?= e($name) ?>" <?= $fd[1] === 'html' ? 'class="wysiwyg"' : '' ?> style="<?= $fd[1] === 'html' ? 'min-height:320px;font-family:Consolas,monospace' : '' ?>"><?= e($value) ?></textarea>
            <?php elseif ($fd[1] === 'image'): ?>
              <?php if ($value !== ''): ?><div class="imgs"><figure><img src="<?= e(media_url($value)) ?>" alt=""></figure></div><?php endif; ?>
              <input type="file" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp">
            <?php elseif ($fd[1] === 'select'): ?>
              <select name="<?= e($name) ?>"><?php foreach ($fd[3] as $k => $label): ?><option value="<?= e($k) ?>" <?= $value === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
            <?php else: ?>
              <input type="<?= $fd[1] === 'text' ? 'text' : ($fd[1] === 'date' ? 'date' : 'number') ?>" <?= $fd[1] === 'number' ? 'step="0.01" min="0"' : '' ?> name="<?= e($name) ?>" value="<?= e($value) ?>">
            <?php endif; ?>
          <?php endif; ?>
          <?php if (!empty($fd[2])): ?><div class="hint"><?= e($fd[2]) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p><button class="btn" type="submit">Save</button></p>
  </form>
  <?php if (in_array('html', array_column($def['fields'], 1), true)): ?>
    <!-- Visual editor for HTML fields; without it the plain textarea still works. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    if (window.tinymce) {
      tinymce.init({
        selector: 'textarea.wysiwyg',
        height: 460,
        menubar: false,
        branding: false,
        promotion: false,
        convert_urls: false,
        plugins: 'lists link image table code fullscreen',
        toolbar: 'blocks | bold italic underline strikethrough | bullist numlist blockquote | alignleft aligncenter alignright | link image table | removeformat undo redo | code fullscreen',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
        content_style: 'body{font-family:system-ui,Segoe UI,Arial,sans-serif;font-size:16px;line-height:1.6}'
      });
    }
    </script>
  <?php endif; ?>
<?php else:
    $list = rows("SELECT * FROM $table ORDER BY {$def['order']}"); ?>
  <div class="top"><h1><?= e($def['title']) ?></h1><a class="btn" href="<?= e(url($self . '&new=1')) ?>">+ Add <?= e($def['single']) ?></a></div>
  <div class="card table-wrap">
    <table>
      <thead><tr><?php foreach ($def['list'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th></th></tr></thead>
      <tbody>
        <?php foreach ($list as $r): ?>
          <tr>
            <?php foreach ($def['list'] as $col => $label):
                $isBool = ($def['fields'][$col][1] ?? '') === 'bool'; ?>
              <td><?= $isBool ? ($r[$col] ? 'Yes' : 'No') : e(mb_strimwidth((string) $r[$col], 0, 70, '…')) ?></td>
            <?php endforeach; ?>
            <td style="white-space:nowrap">
              <a class="btn alt sm" href="<?= e(url($self . '&id=' . $r['id'])) ?>">Edit</a>
              <form class="inline" method="post" onsubmit="return confirm('Delete this <?= e($def['single']) ?>?')"><?= csrf_field() ?>
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn warn sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$list): ?><tr><td colspan="<?= count($def['list']) + 1 ?>" class="muted">Nothing here yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif;
admin_footer();

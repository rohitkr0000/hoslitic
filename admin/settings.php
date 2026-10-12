<?php
require __DIR__ . '/_layout.php';

// key => [label, type, hint]
$groups = [
    'Store' => [
        'site_name' => ['Store name', 'text'],
        'tagline' => ['Tagline', 'text'],
        'phone' => ['Support phone', 'text'],
        'whatsapp' => ['WhatsApp number', 'text', 'With country code, e.g. 919876543210. Shows a chat button on the site.'],
        'email' => ['Support email', 'text', 'Also the "from" address for order emails.'],
        'address' => ['Business address', 'textarea'],
        'map_query' => ['Map location (optional)', 'text', 'Text Google Maps searches for on the Contact page, e.g. a place name or full address. Leave empty to use the business address.'],
        'announcement' => ['Announcement bar', 'text', 'Leave empty to hide.'],
        'meta_description' => ['Search engine description', 'textarea'],
        'disclaimer' => ['Product disclaimer', 'textarea'],
    ],
    'Home page' => [
        'hero_kicker' => ['Small heading', 'text'],
        'hero_title' => ['Main heading', 'text'],
        'hero_text' => ['Intro text', 'textarea', 'The home page banner is image-only, so of these three only the main heading is used, as the description of the first built-in banner image.'],
        'instagram_handle' => ['Instagram handle', 'text', 'Without the @. Heading of the Instagram section on the home page.'],
    ],
    'Social links' => [
        'facebook_url' => ['Facebook page', 'text', 'Full https:// address. Leave empty to hide.'],
        'instagram_url' => ['Instagram profile', 'text'],
        'youtube_url' => ['YouTube channel', 'text'],
        'pinterest_url' => ['Pinterest profile', 'text'],
    ],
    'Tax & invoice' => [
        'legal_name' => ['Legal / proprietor name', 'text'],
        'gstin' => ['GSTIN', 'text'],
        'state' => ['Home state', 'text', 'Orders to this state show CGST + SGST; others show IGST.'],
        'fssai' => ['FSSAI licence no.', 'text', 'Leave empty to hide.'],
        'invoice_prefix' => ['Order number prefix', 'text'],
    ],
    'Payments' => [
        'cod_enabled' => ['Enable Cash on Delivery', 'bool'],
        'razorpay_key_id' => ['Razorpay Key ID', 'text', 'From Razorpay Dashboard › Account & Settings › API Keys. Use rzp_test_… keys to test.'],
        'razorpay_key_secret' => ['Razorpay Key Secret', 'secret'],
        'razorpay_webhook_secret' => ['Razorpay Webhook Secret', 'secret', 'Webhook URL: ' . full_url('razorpay-webhook.php') . ' (events: payment.captured, order.paid)'],
    ],
    'Fship shipping' => [
        'fship_key' => ['API key', 'secret', 'The API key (signature) from your Fship panel. Test and live accounts have different keys.'],
        'fship_warehouse_id' => ['Pickup warehouse ID', 'text', 'The numeric ID of your pickup address in the Fship panel (Settings › Warehouse).'],
        'fship_pickup_pincode' => ['Pickup PIN code', 'text', 'PIN code of that warehouse. Used at checkout to check that the delivery PIN code is serviceable.'],
        'fship_mode' => ['Shipping mode', 'text', 'Surface or Air.'],
        'fship_live' => ['Live mode (untick to use the Fship test server)', 'bool'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $admin['password_hash'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            flash('success', 'Admin password changed.');
        }
    } else {
        foreach ($groups as $fields) {
            foreach ($fields as $key => $fd) {
                if ($fd[1] === 'bool') {
                    set_setting($key, isset($_POST[$key]) ? '1' : '0');
                } elseif ($fd[1] === 'secret') {
                    if (post($key) !== '') { // empty = keep the saved value
                        set_setting($key, post($key));
                    }
                    if (isset($_POST['clear_' . $key])) {
                        set_setting($key, '');
                    }
                } else {
                    set_setting($key, post($key));
                }
            }
        }
        set_setting('fship_mode', strcasecmp(setting('fship_mode'), 'Air') === 0 ? 'Air' : 'Surface');
        set_setting('fship_warehouse_id', preg_replace('/\D+/', '', setting('fship_warehouse_id')));
        set_setting('fship_pickup_pincode', preg_replace('/\D+/', '', setting('fship_pickup_pincode')));
        flash('success', 'Settings saved.');
    }
    redirect('admin/settings.php');
}

$all = settings_all();
admin_header('Settings', 'settings');
?>
<div class="top"><h1>Settings</h1></div>
<form method="post">
  <?= csrf_field() ?>
  <?php foreach ($groups as $title => $fields): ?>
    <div class="card">
      <h2><?= e($title) ?></h2>
      <div class="grid">
        <?php foreach ($fields as $key => $fd): $v = $all[$key] ?? ''; ?>
          <div class="<?= $fd[1] === 'textarea' ? 'full' : '' ?>">
            <?php if ($fd[1] === 'bool'): ?>
              <label><input type="checkbox" name="<?= e($key) ?>" <?= $v === '1' ? 'checked' : '' ?>> <?= e($fd[0]) ?></label>
            <?php elseif ($fd[1] === 'textarea'): ?>
              <label><?= e($fd[0]) ?></label><textarea name="<?= e($key) ?>" style="min-height:80px"><?= e($v) ?></textarea>
            <?php elseif ($fd[1] === 'secret'): ?>
              <label><?= e($fd[0]) ?></label>
              <input type="password" name="<?= e($key) ?>" value="" autocomplete="new-password" placeholder="<?= $v !== '' ? 'Saved — leave blank to keep' : 'Not set' ?>">
              <?php if ($v !== ''): ?><label class="hint"><input type="checkbox" name="clear_<?= e($key) ?>"> Remove saved value</label><?php endif; ?>
            <?php else: ?>
              <label><?= e($fd[0]) ?></label><input type="text" name="<?= e($key) ?>" value="<?= e($v) ?>">
            <?php endif; ?>
            <?php if (!empty($fd[2])): ?><div class="hint"><?= e($fd[2]) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <p><button class="btn" type="submit">Save settings</button></p>
</form>

<form class="card" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <h2>Change admin password</h2>
  <div class="grid">
    <div><label>Current password</label><input type="password" name="current_password" required></div>
    <div><label>New password</label><input type="password" name="new_password" minlength="8" required></div>
  </div>
  <p><button class="btn" type="submit">Update password</button></p>
</form>
<?php admin_footer(); ?>

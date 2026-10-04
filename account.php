<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/order-view.php';

$user = require_login();
$tab = in_array($_GET['tab'] ?? '', ['orders', 'profile'], true) ? $_GET['tab'] : 'orders';
$states = indian_states();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'return_request') {
        $ro = row('SELECT * FROM orders WHERE order_no = ? AND (user_id = ? OR (user_id IS NULL AND email = ?))', [post('order_no'), $user['id'], strtolower($user['email'])]);
        if (!$ro) {
            flash('error', 'Order not found.');
            redirect('account.php');
        }
        $rw = return_window($ro);
        $reason = isset(return_reasons()[post('reason')]) ? post('reason') : '';
        if ($rw['state'] === 'expired') {
            flash('error', 'The ' . RETURN_WINDOW_DAYS . '-day return window for this order has closed, so a refund can no longer be requested.');
        } elseif ($rw['state'] === 'requested') {
            flash('info', 'A return request for this order has already been submitted.');
        } elseif ($rw['state'] !== 'open') {
            flash('error', 'Returns can be requested only after an order is delivered.');
        } elseif ($reason === '') {
            flash('error', 'Please choose a reason for the return.');
        } else {
            q('INSERT INTO return_requests (order_id, user_id, reason, details) VALUES (?, ?, ?, ?)',
                [$ro['id'], $user['id'], $reason, mb_substr(post('details'), 0, 1000)]);
            $msg = '<p>Order <strong>' . e($ro['order_no']) . '</strong> – return/refund requested by ' . e($ro['name']) . '.</p><p>Reason: ' . e(return_reasons()[$reason]) . '<br>' . nl2br(e(post('details'))) . '</p>';
            order_event((int) $ro['id'], 'Return requested', return_reasons()[$reason]);
            send_mail(setting('email'), 'Return request for ' . $ro['order_no'], $msg);
            send_mail($ro['email'], 'We received your return request for ' . $ro['order_no'],
                '<p>Hi ' . e($ro['name']) . ',</p><p>We have received your return/refund request for order <strong>' . e($ro['order_no']) . '</strong>. Our team will review it and contact you within 1–2 business days.</p>');
            flash('success', 'Your return request has been submitted. We will contact you within 1–2 business days.');
        }
        redirect('account.php?order=' . urlencode($ro['order_no']));
    }
    if (post('action') === 'profile') {
        $phone = substr(preg_replace('/\D+/', '', post('phone')), -10);
        $state = in_array(post('state'), $states, true) ? post('state') : '';
        $pin = preg_match('/^[1-9]\d{5}$/', post('pincode')) ? post('pincode') : '';
        if (mb_strlen(post('name')) < 3 || !preg_match('/^[6-9]\d{9}$/', $phone)) {
            flash('error', 'Please enter your name and a valid 10-digit mobile number.');
        } else {
            q('UPDATE users SET name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?',
                [mb_substr(post('name'), 0, 100), $phone, mb_substr(post('address'), 0, 255), mb_substr(post('city'), 0, 80), $state, $pin, $user['id']]);
            flash('success', 'Profile updated.');
        }
    } elseif (post('action') === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Password changed.');
        }
    }
    redirect('account.php?tab=profile');
}

// Button that opens the return/refund popup (only for delivered orders without a request yet).
function return_button(array $o, bool $small): string
{
    $rw = return_window($o);
    if (!in_array($rw['state'], ['open', 'expired'], true)) {
        return '';
    }
    return ($small ? ' · ' : '') . '<button type="button" class="' . ($small ? 'link-btn plain' : 'btn btn-dark btn-sm') . '" data-return="' . e($o['order_no']) . '" data-state="' . $rw['state'] . '" data-deadline="' . e(date('d M Y', $rw['deadline'])) . '">Request return / refund</button>';
}

$viewNo = (string) ($_GET['order'] ?? '');
$view = $viewNo !== '' ? row('SELECT * FROM orders WHERE order_no = ? AND (user_id = ? OR (user_id IS NULL AND email = ?))', [$viewNo, $user['id'], strtolower($user['email'])]) : null;
$orders = rows('SELECT * FROM orders WHERE user_id = ? OR (user_id IS NULL AND email = ?) ORDER BY id DESC', [$user['id'], strtolower($user['email'])]);
$pageTitle = 'My account';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container"><?= breadcrumbs(['My account' => null]) ?><h1>My account</h1><p><?= e($user['email']) ?></p></div></section>
<section class="section" style="padding-top:40px">
  <div class="container account-layout">
    <nav class="card side-nav">
      <a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="<?= e(url('account.php')) ?>">My orders</a>
      <a class="<?= $tab === 'profile' ? 'active' : '' ?>" href="<?= e(url('account.php?tab=profile')) ?>">Profile</a>
      <form method="post" action="<?= e(url('logout.php')) ?>" style="padding:11px 16px"><?= csrf_field() ?><button class="link-btn" type="submit">Log out</button></form>
    </nav>
    <div>
      <?php if ($view): ?>
        <p><a href="<?= e(url('account.php')) ?>">← All orders</a></p>
        <?php render_order($view); ?>
        <?php $rw = return_window($view); ?>
        <div class="card" style="margin-top:18px">
          <h3>Returns &amp; refunds</h3>
          <?php if ($rw['request']): $rq = $rw['request']; ?>
            <p>Your return request: <?= badge($rq['status']) ?> <strong><?= e(return_statuses()[$rq['status']] ?? $rq['status']) ?></strong>
              <span class="muted">· submitted <?= e(date('d M Y', strtotime($rq['created_at']))) ?> · <?= e(return_reasons()[$rq['reason']] ?? '') ?></span></p>
            <?php if ($rq['admin_note'] !== ''): ?><p class="muted">Note from our team: <?= e($rq['admin_note']) ?></p><?php endif; ?>
          <?php elseif ($rw['state'] === 'none'): ?>
            <p class="muted">Once your order is delivered you can request a return or refund here within <?= RETURN_WINDOW_DAYS ?> days of delivery.</p>
          <?php else: ?>
            <p class="muted"><?= $rw['state'] === 'open' ? 'You can request a return or refund until ' . e(date('d M Y, h:i A', $rw['deadline'])) . '.' : 'The ' . RETURN_WINDOW_DAYS . '-day return window for this order closed on ' . e(date('d M Y', $rw['deadline'])) . '.' ?></p>
            <?= return_button($view, false) ?>
          <?php endif; ?>
          <p class="muted" style="margin:10px 0 0;font-size:.85rem">See our <a href="<?= e(url('page.php?slug=refund-policy')) ?>">Cancellation &amp; Refund Policy</a>.</p>
        </div>
        <p style="margin-top:18px"><a class="btn btn-dark btn-sm" href="<?= e(url('invoice.php?order=' . urlencode($view['order_no']))) ?>" target="_blank">View invoice</a></p>
      <?php elseif ($tab === 'orders'): ?>
        <div class="card">
          <h3>My orders</h3>
          <?php if (!$orders): ?>
            <p class="muted">You have not placed any orders yet.</p>
            <a class="btn btn-dark btn-sm" href="<?= e(url('shop.php')) ?>">Shop now</a>
          <?php else: ?>
            <div class="table-wrap"><table class="data">
              <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td><strong><?= e($o['order_no']) ?></strong></td>
                    <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
                    <td><?= money($o['total']) ?></td>
                    <td><?= badge($o['status']) ?></td>
                    <td><a href="<?= e(url('account.php?order=' . urlencode($o['order_no']))) ?>">View</a><?= return_button($o, true) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <form class="card" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
          <h3>Profile &amp; default address</h3>
          <div class="form-grid">
            <div><label>Full name</label><input type="text" name="name" value="<?= e($user['name']) ?>" required></div>
            <div><label>Mobile number</label><input type="tel" name="phone" value="<?= e($user['phone']) ?>" maxlength="13" required></div>
            <div class="full"><label>Address</label><input type="text" name="address" value="<?= e($user['address']) ?>"></div>
            <div><label>City</label><input type="text" name="city" value="<?= e($user['city']) ?>"></div>
            <div><label>PIN code</label><input type="text" name="pincode" value="<?= e($user['pincode']) ?>" maxlength="6"></div>
            <div class="full"><label>State</label>
              <select name="state"><option value="">Select state</option>
                <?php foreach ($states as $s): ?><option <?= $user['state'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="full"><button class="btn btn-dark" type="submit">Save profile</button></div>
          </div>
        </form>
        <form class="card" method="post" style="margin-top:20px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <h3>Change password</h3>
          <div class="form-grid">
            <div><label>Current password</label><input type="password" name="current_password" autocomplete="current-password" required></div>
            <div><label>New password</label><input type="password" name="new_password" minlength="8" autocomplete="new-password" required></div>
            <div class="full"><button class="btn btn-dark" type="submit">Update password</button></div>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="modal" id="returnModal" hidden role="dialog" aria-modal="true" aria-labelledby="rmTitle">
  <div class="modal-box">
    <button type="button" class="modal-x" data-close aria-label="Close">×</button>
    <div id="rmOpen">
      <h3 id="rmTitle">Request return / refund</h3>
      <p class="muted">Order <strong class="rm-order"></strong> · eligible until <strong class="rm-deadline"></strong></p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="return_request">
        <input type="hidden" name="order_no" class="rm-order-input">
        <label>Reason</label>
        <select name="reason" required>
          <option value="">Select a reason</option>
          <?php foreach (return_reasons() as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <label style="margin-top:12px">Tell us more (optional)</label>
        <textarea name="details" maxlength="1000" style="min-height:90px" placeholder="Describe the issue so we can help faster"></textarea>
        <p class="muted" style="font-size:.84rem">Refunds for online payments go back to your original payment method in 5–7 business days after approval.</p>
        <div class="modal-actions"><button class="btn btn-ghost btn-sm" type="button" data-close>Cancel</button><button class="btn btn-dark" type="submit">Submit request</button></div>
      </form>
    </div>
    <div id="rmClosed" hidden>
      <div class="modal-ico">⏳</div>
      <h3>Return window closed</h3>
      <p>Order <strong class="rm-order"></strong> was delivered more than <?= RETURN_WINDOW_DAYS ?> days ago. Returns and refunds can only be requested within <?= RETURN_WINDOW_DAYS ?> days of delivery (until <strong class="rm-deadline"></strong>), so we can no longer accept a refund request for this order.</p>
      <p class="muted">If there is a serious problem with your order, please <a href="<?= e(url('contact.php')) ?>">contact us</a>. See our <a href="<?= e(url('page.php?slug=refund-policy')) ?>">Cancellation &amp; Refund Policy</a>.</p>
      <div class="modal-actions"><button class="btn btn-dark btn-sm" type="button" data-close>Okay, got it</button></div>
    </div>
  </div>
</div>
<script>
(function () {
  var m = document.getElementById('returnModal');
  function close() { m.hidden = true; document.body.style.overflow = ''; }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-return]');
    if (b) {
      var open = b.dataset.state === 'open';
      m.querySelectorAll('.rm-order').forEach(function (n) { n.textContent = b.dataset.return; });
      m.querySelectorAll('.rm-deadline').forEach(function (n) { n.textContent = b.dataset.deadline; });
      m.querySelector('.rm-order-input').value = b.dataset.return;
      document.getElementById('rmOpen').hidden = !open;
      document.getElementById('rmClosed').hidden = open;
      m.hidden = false; document.body.style.overflow = 'hidden';
      return;
    }
    if (e.target === m || e.target.closest('[data-close]')) { close(); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

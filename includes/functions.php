<?php
// ---------- Output / URL helpers ----------
// Breadcrumb trail: pass [label => path|null]; the last item is the current page. Home is added automatically.
function breadcrumbs(array $trail): string
{
    $items = ['Home' => ''] + $trail;
    $last = array_key_last($items);
    $out = '<nav class="crumbs" aria-label="Breadcrumb"><ol>';
    foreach ($items as $label => $path) {
        $out .= $label === $last || $path === null
            ? '<li aria-current="page">' . e($label) . '</li>'
            : '<li><a href="' . e(url($path)) . '">' . e($label) . '</a></li>';
    }
    return $out . '</ol></nav>';
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function full_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function money($amount): string
{
    $n = (float) $amount;
    return '₹' . number_format($n, floor($n) == $n ? 0 : 2);
}

function slugify(string $text): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $s !== '' ? $s : 'item';
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// ---------- Settings ----------
function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (rows('SELECT skey, svalue FROM settings') as $r) {
            $cache[$r['skey']] = (string) $r['svalue'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, $value]);
    settings_all(true);
}

// ---------- CSRF / flash ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- Auth ----------
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = empty($_SESSION['user_id']) ? null : row('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('info', 'Please log in to continue.');
        redirect('login.php');
    }
    return $u;
}

function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $admin = empty($_SESSION['admin_id']) ? null : row('SELECT * FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
    }
    return $admin;
}

function require_admin(): array
{
    $a = current_admin();
    if (!$a) {
        redirect('admin/login.php');
    }
    return $a;
}

// Simple per-session throttle for login forms.
function throttle_hit(string $key, int $max = 8, int $window = 600): bool
{
    $now = time();
    $hits = array_filter($_SESSION['throttle'][$key] ?? [], fn($t) => $t > $now - $window);
    $hits[] = $now;
    $_SESSION['throttle'][$key] = $hits;
    return count($hits) > $max;
}

// ---------- Catalogue ----------
function product_images(int $productId): array
{
    return array_column(rows('SELECT filename FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$productId]), 'filename');
}

function product_image_url(?string $filename): string
{
    if ($filename && is_file(ROOT_PATH . '/uploads/products/' . $filename)) {
        return url('uploads/products/' . rawurlencode($filename));
    }
    return url('assets/img/placeholder.svg');
}

function products_with_image(string $where = 'p.is_active = 1', array $params = []): array
{
    return rows(
        "SELECT p.*, (SELECT filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image,
                (SELECT ROUND(AVG(rating), 1) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS avg_rating,
                (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS review_count
         FROM products p WHERE $where ORDER BY p.sort_order, p.id",
        $params
    );
}

function discount_percent(array $p): int
{
    return ($p['mrp'] > $p['price'] && $p['mrp'] > 0) ? (int) round(($p['mrp'] - $p['price']) / $p['mrp'] * 100) : 0;
}

function stars(float $rating): string
{
    $full = (int) round($rating);
    return '<span class="stars" aria-label="' . e($rating) . ' out of 5">' . str_repeat('★', $full) . '<span class="stars-off">' . str_repeat('★', 5 - $full) . '</span></span>';
}

function indian_states(): array
{
    return ['Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh', 'Chhattisgarh',
        'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir',
        'Jharkhand', 'Karnataka', 'Kerala', 'Ladakh', 'Lakshadweep', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya',
        'Mizoram', 'Nagaland', 'Odisha', 'Puducherry', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal'];
}

// ---------- Shipping ----------
function shipping_zones(): array
{
    return rows('SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order, id');
}

// A zone lists states separated by commas; "*" means "everywhere else".
function find_zone(string $state): ?array
{
    $fallback = null;
    foreach (shipping_zones() as $z) {
        $list = array_map('trim', explode(',', strtolower($z['states'])));
        if ($state !== '' && in_array(strtolower($state), $list, true)) {
            return $z;
        }
        if ($fallback === null && in_array('*', $list, true)) {
            $fallback = $z;
        }
    }
    return $fallback;
}

function payment_methods(): array
{
    $m = [];
    if (setting('razorpay_key_id') !== '' && setting('razorpay_key_secret') !== '') {
        $m['razorpay'] = 'Pay Online (UPI, Cards, Net Banking, Wallets)';
    }
    if (setting('cod_enabled', '1') === '1') {
        $m['cod'] = 'Cash on Delivery';
    }
    return $m;
}

// Single source of truth for order totals (used by cart, checkout and order creation).
function compute_totals(string $state, string $method): array
{
    $subtotal = cart_subtotal();
    $coupon = coupon_current($subtotal);
    $discount = $coupon['discount'];
    $zone = find_zone($state);
    $after = $subtotal - $discount;
    $shipping = 0.0;
    $cod = 0.0;
    if ($zone) {
        $shipping = ($zone['free_above'] > 0 && $after >= $zone['free_above']) ? 0.0 : (float) $zone['rate'];
        $cod = $method === 'cod' ? (float) $zone['cod_fee'] : 0.0;
    }
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'coupon_code' => $coupon['code'],
        'zone' => $zone,
        'shipping' => $shipping,
        'cod_fee' => $cod,
        'total' => max(0, round($after + $shipping + $cod, 2)),
    ];
}

// ---------- Orders ----------
function order_statuses(): array
{
    return ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Shipped',
        'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned'];
}

function payment_statuses(): array
{
    return ['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'];
}

function badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

function order_items(int $orderId): array
{
    return rows('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
}

// Orders are visible to the account that placed them, or to the browser session that placed them (guests).
function viewable_order(string $orderNo): ?array
{
    $o = row('SELECT * FROM orders WHERE order_no = ?', [$orderNo]);
    if (!$o) {
        return null;
    }
    $u = current_user();
    $mine = in_array((int) $o['id'], array_map('intval', $_SESSION['my_orders'] ?? []), true);
    return ($mine || ($u && (int) $o['user_id'] === (int) $u['id'])) ? $o : null;
}

// Confirms an order exactly once: deducts stock, counts the coupon, emails the customer.
function confirm_order(int $orderId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    $o = row('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
    if (!$o || $o['stock_deducted']) {
        $pdo->rollBack();
        return;
    }
    foreach (order_items($orderId) as $it) {
        if ($it['product_id']) {
            q('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?', [$it['qty'], $it['product_id']]);
        }
    }
    if ($o['coupon_code'] !== '') {
        q('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?', [$o['coupon_code']]);
    }
    q("UPDATE orders SET stock_deducted = 1, status = 'confirmed', updated_at = NOW() WHERE id = ?", [$orderId]);
    $pdo->commit();
    order_event($orderId, 'Order confirmed', 'Your order has been confirmed and is being prepared.');
    send_order_email($orderId);
}

function restore_stock(int $orderId): void
{
    $o = row('SELECT stock_deducted FROM orders WHERE id = ?', [$orderId]);
    if (!$o || !$o['stock_deducted']) {
        return;
    }
    foreach (order_items($orderId) as $it) {
        if ($it['product_id']) {
            q('UPDATE products SET stock = stock + ? WHERE id = ?', [$it['qty'], $it['product_id']]);
        }
    }
    q('UPDATE orders SET stock_deducted = 0 WHERE id = ?', [$orderId]);
}

// GST is included in prices; this splits each line back into taxable value + tax.
function order_tax_lines(array $order, array $items): array
{
    $factor = $order['subtotal'] > 0 ? ($order['subtotal'] - $order['discount']) / $order['subtotal'] : 1;
    $lines = [];
    foreach ($items as $it) {
        $gross = round($it['price'] * $it['qty'] * $factor, 2);
        $taxable = round($gross / (1 + $it['gst_rate'] / 100), 2);
        $lines[] = $it + ['gross' => $gross, 'taxable' => $taxable, 'tax' => round($gross - $taxable, 2)];
    }
    return $lines;
}

// ---------- Mail ----------
function send_mail(string $to, string $subject, string $html): bool
{
    if ($to === '' || !function_exists('mail')) {
        return false;
    }
    $from = setting('email');
    if ($from === '') {
        $host = preg_replace('/^www\./', '', preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
        $from = 'noreply@' . $host;
    }
    $name = str_replace(['"', "\r", "\n"], '', setting('site_name', 'Store'));
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: \"$name\" <$from>\r\nReply-To: $from\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers);
}

// Branded wrapper for every customer email.
function mail_layout(string $headline, string $bodyHtml): string
{
    return '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#17211b;border:1px solid #e3e8df;border-radius:12px;overflow:hidden">'
        . '<div style="background:#14432a;color:#fff;padding:18px 24px;font-size:18px;font-weight:bold">' . e(setting('site_name')) . '</div>'
        . '<div style="padding:24px"><h2 style="margin:0 0 12px;color:#14432a">' . e($headline) . '</h2>' . $bodyHtml . '</div>'
        . '<div style="background:#f4f1e6;color:#667;padding:14px 24px;font-size:12px">' . e(setting('site_name')) . ' · ' . e(setting('address'))
        . (setting('phone') !== '' ? '<br>Phone: ' . e(setting('phone')) : '') . (setting('email') !== '' ? ' · Email: ' . e(setting('email')) : '') . '</div></div>';
}

// Items, totals, payment and delivery address of an order, as email HTML.
function order_summary_html(array $o): string
{
    $rows = '';
    foreach (order_items((int) $o['id']) as $it) {
        $rows .= '<tr><td style="padding:7px 0;border-bottom:1px solid #eee">' . e($it['name']) . ' × ' . (int) $it['qty'] . '</td><td align="right" style="border-bottom:1px solid #eee">' . money($it['price'] * $it['qty']) . '</td></tr>';
    }
    $line = fn($l, $v, $b = false) => '<tr><td style="padding:4px 0;' . ($b ? 'font-weight:bold;border-top:1px solid #ccc;padding-top:8px' : 'color:#556') . '">' . $l . '</td><td align="right" style="' . ($b ? 'font-weight:bold;border-top:1px solid #ccc;padding-top:8px' : '') . '">' . $v . '</td></tr>';
    $rows .= $line('Subtotal', money($o['subtotal']));
    if ($o['discount'] > 0) {
        $rows .= $line('Discount ' . e($o['coupon_code']), '− ' . money($o['discount']));
    }
    $rows .= $line('Shipping', $o['shipping'] > 0 ? money($o['shipping']) : 'FREE');
    if ($o['cod_fee'] > 0) {
        $rows .= $line('COD handling fee', money($o['cod_fee']));
    }
    $rows .= $line('Total', money($o['total']), true);
    $pay = ($o['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online (Razorpay)') . ' – ' . ucfirst($o['payment_status']);
    return '<p style="margin:0 0 6px"><strong>Order ' . e($o['order_no']) . '</strong> · placed ' . e(date('d M Y, h:i A', strtotime($o['created_at']))) . '</p>'
        . '<table width="100%" style="border-collapse:collapse;font-size:14px">' . $rows . '</table>'
        . '<p style="font-size:14px"><strong>Payment:</strong> ' . e($pay) . '<br>'
        . '<strong>Deliver to:</strong> ' . e($o['name'] . ', ' . $o['address'] . ', ' . $o['city'] . ', ' . $o['state'] . ' – ' . $o['pincode']) . '<br>'
        . '<strong>Phone:</strong> ' . e($o['phone']) . '</p>';
}

function order_links_html(array $o): string
{
    $acc = full_url('account.php?order=' . urlencode($o['order_no']));
    $trk = full_url('track-order.php?order=' . urlencode($o['order_no']) . '&phone=' . urlencode($o['phone']));
    return '<p style="margin:18px 0"><a href="' . e($acc) . '" style="background:#14432a;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:bold">View order in your account</a>'
        . ' &nbsp; <a href="' . e($trk) . '" style="color:#14432a">Track your order</a></p>';
}

// ---------- Order timeline (shown in the customer dashboard) ----------
function order_event(int $orderId, string $title, string $message = ''): void
{
    try {
        q('INSERT INTO order_events (order_id, title, message) VALUES (?, ?, ?)', [$orderId, mb_substr($title, 0, 150), mb_substr($message, 0, 500)]);
    } catch (Throwable $e) {
        error_log('order_event failed: ' . $e->getMessage());
    }
}

function order_events(int $orderId): array
{
    try {
        return rows('SELECT * FROM order_events WHERE order_id = ? ORDER BY id DESC', [$orderId]);
    } catch (Throwable $e) {
        return [];
    }
}

// Records an update on the order timeline and emails the customer the order details + the update.
function notify_order_update(int $orderId, string $title, string $message, bool $email = true): void
{
    order_event($orderId, $title, $message);
    $o = row('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if (!$o || !$email) {
        return;
    }
    $body = '<p>Hi ' . e($o['name']) . ',</p><p style="font-size:15px">' . $message . '</p>';
    if ($o['awb'] !== '' || $o['courier'] !== '') {
        $body .= '<p style="background:#f4f1e6;padding:10px 14px;border-radius:8px;font-size:14px"><strong>Courier:</strong> ' . e($o['courier'] ?: '—') . ' &nbsp; <strong>Tracking no.:</strong> ' . e($o['awb'] ?: '—')
            . ($o['tracking_url'] !== '' ? ' &nbsp; <a href="' . e($o['tracking_url']) . '">Track on courier site</a>' : '') . '</p>';
    }
    $body .= order_summary_html($o) . order_links_html($o);
    send_mail($o['email'], $title . ' – Order ' . $o['order_no'], mail_layout($title, $body));
}

function send_order_email(int $orderId): void
{
    $o = row('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if (!$o) {
        return;
    }
    $body = '<p>Hi ' . e($o['name']) . ',</p><p>Thank you for your order! We have received it and will dispatch it within 1–2 business days. We will email you at every step.</p>'
        . order_summary_html($o) . order_links_html($o)
        . '<p style="font-size:13px;color:#667">Need to cancel or return? Cancellation is free before dispatch, and returns can be requested within 3 days of delivery from your account.</p>';
    send_mail($o['email'], 'Order ' . $o['order_no'] . ' confirmed', mail_layout('Order confirmed', $body));
    send_mail(setting('email'), 'New order ' . $o['order_no'] . ' - ' . money($o['total']), mail_layout('New order received', order_summary_html($o)));
}

// ---------- HTTP (used by the Razorpay client) ----------
function http_json(string $method, string $url, ?array $body = null, array $headers = [], ?string $basicAuth = null): array
{
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'data' => null, 'error' => 'PHP cURL extension is not enabled on this server.'];
    }
    $ch = curl_init($url);
    $headers[] = 'Content-Type: application/json';
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 25,
    ]);
    if ($basicAuth !== null) {
        curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['status' => $status, 'data' => $raw === false ? null : json_decode($raw, true), 'error' => $err];
}

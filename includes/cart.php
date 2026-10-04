<?php
// Session cart: [product_id => qty]

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_set(int $productId, int $qty): void
{
    $p = row('SELECT stock FROM products WHERE id = ? AND is_active = 1', [$productId]);
    if (!$p || $qty <= 0) {
        unset($_SESSION['cart'][$productId]);
        return;
    }
    $_SESSION['cart'][$productId] = min($qty, max(0, (int) $p['stock']), 20);
    if ($_SESSION['cart'][$productId] <= 0) {
        unset($_SESSION['cart'][$productId]);
    }
}

function cart_add(int $productId, int $qty): void
{
    cart_set($productId, (cart_raw()[$productId] ?? 0) + max(1, $qty));
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['coupon']);
}

// Cart lines joined with live product data (drops anything no longer purchasable).
function cart_items(): array
{
    static $cache = null;
    $raw = cart_raw();
    $key = md5(json_encode($raw));
    if ($cache !== null && $cache['key'] === $key) {
        return $cache['items'];
    }
    $items = [];
    if ($raw) {
        $ids = array_map('intval', array_keys($raw));
        $in = implode(',', array_fill(0, count($ids), '?'));
        foreach (products_with_image("p.is_active = 1 AND p.id IN ($in)", $ids) as $p) {
            $qty = min((int) $raw[$p['id']], (int) $p['stock']);
            if ($qty > 0) {
                $items[] = ['product' => $p, 'qty' => $qty, 'line' => round($p['price'] * $qty, 2)];
            }
        }
    }
    $cache = ['key' => $key, 'items' => $items];
    return $items;
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'qty'));
}

function cart_subtotal(): float
{
    return round(array_sum(array_column(cart_items(), 'line')), 2);
}

// Returns ['coupon' => row|null, 'discount' => float, 'error' => string]
function coupon_check(string $code, float $subtotal): array
{
    $c = row('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [strtoupper(trim($code))]);
    $fail = fn(string $msg) => ['coupon' => null, 'discount' => 0.0, 'error' => $msg];
    if (!$c) {
        return $fail('This coupon code is not valid.');
    }
    if ($c['expires_on'] && $c['expires_on'] < date('Y-m-d')) {
        return $fail('This coupon has expired.');
    }
    if ($c['usage_limit'] > 0 && $c['used_count'] >= $c['usage_limit']) {
        return $fail('This coupon has reached its usage limit.');
    }
    if ($subtotal < $c['min_order']) {
        return $fail('Add items worth ' . money($c['min_order']) . ' or more to use this coupon.');
    }
    $d = $c['type'] === 'flat' ? (float) $c['value'] : $subtotal * $c['value'] / 100;
    if ($c['max_discount'] > 0) {
        $d = min($d, (float) $c['max_discount']);
    }
    return ['coupon' => $c, 'discount' => round(min($d, $subtotal), 2), 'error' => ''];
}

// The coupon stored in the session, re-validated against the current cart.
function coupon_current(float $subtotal): array
{
    $code = $_SESSION['coupon'] ?? '';
    if ($code === '') {
        return ['code' => '', 'discount' => 0.0];
    }
    $r = coupon_check($code, $subtotal);
    if (!$r['coupon']) {
        unset($_SESSION['coupon']);
        return ['code' => '', 'discount' => 0.0];
    }
    return ['code' => $r['coupon']['code'], 'discount' => $r['discount']];
}

<?php
// Minimal Razorpay client (Orders API + signature checks). No SDK required.

function razorpay_enabled(): bool
{
    return setting('razorpay_key_id') !== '' && setting('razorpay_key_secret') !== '';
}

// Creates a Razorpay order for the given amount in rupees. Returns ['id' => ..] or ['error' => ..].
function razorpay_create_order(float $amount, string $receipt): array
{
    $res = http_json('POST', 'https://api.razorpay.com/v1/orders', [
        'amount' => (int) round($amount * 100),
        'currency' => 'INR',
        'receipt' => $receipt,
        'payment_capture' => 1,
    ], [], setting('razorpay_key_id') . ':' . setting('razorpay_key_secret'));

    if ($res['status'] === 200 && !empty($res['data']['id'])) {
        return ['id' => $res['data']['id']];
    }
    return ['error' => $res['data']['error']['description'] ?? ($res['error'] ?: 'Could not reach the payment gateway.')];
}

// Verifies the signature returned to the browser after a successful payment.
function razorpay_verify_payment(string $rzpOrderId, string $paymentId, string $signature): bool
{
    $expected = hash_hmac('sha256', $rzpOrderId . '|' . $paymentId, setting('razorpay_key_secret'));
    return $signature !== '' && hash_equals($expected, $signature);
}

// Verifies a webhook body against the X-Razorpay-Signature header.
function razorpay_verify_webhook(string $rawBody, string $signature): bool
{
    $secret = setting('razorpay_webhook_secret');
    return $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
}

// Marks an order paid (idempotent) and confirms it.
function mark_order_paid(int $orderId, string $paymentId): void
{
    $st = q("UPDATE orders SET payment_status = 'paid', razorpay_payment_id = ?, updated_at = NOW() WHERE id = ? AND payment_status <> 'paid'", [$paymentId, $orderId]);
    if ($st->rowCount() > 0) {
        order_event($orderId, 'Payment received', 'We received your online payment. Payment ID: ' . $paymentId);
    }
    confirm_order($orderId);
}

// Refunds a captured payment (amount in rupees). Returns ['id' => refund id] or ['error' => ..].
function razorpay_refund(string $paymentId, float $amount, string $note = ''): array
{
    $res = http_json('POST', 'https://api.razorpay.com/v1/payments/' . rawurlencode($paymentId) . '/refund', [
        'amount' => (int) round($amount * 100),
        'speed' => 'normal',
        'notes' => ['reason' => mb_substr($note, 0, 200)],
    ], [], setting('razorpay_key_id') . ':' . setting('razorpay_key_secret'));

    if (in_array($res['status'], [200, 201], true) && !empty($res['data']['id'])) {
        return ['id' => $res['data']['id']];
    }
    return ['error' => $res['data']['error']['description'] ?? ($res['error'] ?: 'Could not reach the payment gateway.')];
}

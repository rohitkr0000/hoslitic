<?php
// Razorpay webhook endpoint. Add this URL in Razorpay Dashboard › Webhooks with the events
// "payment.captured" and "order.paid", using the webhook secret saved in Admin › Settings.
// It confirms orders even if the customer closes the browser before returning to the site.
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/razorpay.php';

$raw = file_get_contents('php://input');
if (!razorpay_verify_webhook($raw, $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '')) {
    http_response_code(400);
    exit('invalid signature');
}

$event = json_decode($raw, true);
$payment = $event['payload']['payment']['entity'] ?? null;
if ($payment && in_array($event['event'] ?? '', ['payment.captured', 'order.paid'], true) && !empty($payment['order_id'])) {
    $o = row('SELECT id, total FROM orders WHERE razorpay_order_id = ?', [$payment['order_id']]);
    if ($o && (int) $payment['amount'] === (int) round($o['total'] * 100)) {
        mark_order_paid((int) $o['id'], (string) $payment['id']);
    }
}
echo 'ok';

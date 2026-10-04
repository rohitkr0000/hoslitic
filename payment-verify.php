<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/razorpay.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}
csrf_check();

$rzpOrderId = post('razorpay_order_id');
$o = $rzpOrderId !== '' ? row('SELECT * FROM orders WHERE razorpay_order_id = ?', [$rzpOrderId]) : null;
if (!$o) {
    flash('error', 'We could not find that order.');
    redirect('cart.php');
}

if (razorpay_verify_payment($o['razorpay_order_id'], post('razorpay_payment_id'), post('razorpay_signature'))) {
    mark_order_paid((int) $o['id'], post('razorpay_payment_id'));
    cart_clear();
    redirect('order-success.php?order=' . urlencode($o['order_no']));
}

flash('error', 'Payment could not be verified. If money was deducted, it will be refunded automatically — or contact us with your order number.');
redirect('payment.php?order=' . urlencode($o['order_no']));

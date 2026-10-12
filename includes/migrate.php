<?php
// Policy page text (Razorpay-ready) + one-time database upgrade.
// Placeholders {{site_name}} {{legal_name}} {{address}} {{phone}} {{email}} {{gstin}} are filled in when a page is shown (see page_fill()).

const RETURN_WINDOW_DAYS = 3;

function policy_pages(): array
{
    $contact = '<h3>Contact us</h3><p>{{site_name}}<br>{{address}}<br>Phone: {{phone}}<br>Email: {{email}}</p>';
    return [
        ['shipping-policy', 'Shipping & Delivery Policy',
            '<p>This policy explains how {{site_name}} ships orders placed on this website. We deliver across India through trusted courier partners.</p>'
            . '<h3>Order processing</h3><p>Orders are confirmed once payment is received (or, for Cash on Delivery, once the order is placed). We dispatch confirmed orders within <strong>1–2 business days</strong>. Orders placed on Sundays or public holidays are processed on the next working day.</p>'
            . '<h3>Delivery time</h3><p>Delivery usually takes <strong>2–10 business days</strong> after dispatch, depending on your location. The estimated delivery time for your state is shown at checkout before you pay. Remote areas and some PIN codes may take longer.</p>'
            . '<h3>Shipping charges</h3><p>Shipping charges depend on your delivery state and order value and are shown clearly at checkout before payment. Free shipping applies on orders above the amount shown on our website. Cash on Delivery orders may carry a small COD handling fee, which is also shown at checkout.</p>'
            . '<h3>Order tracking</h3><p>Once your order is shipped you will receive the courier name and tracking number by email, and you can follow the parcel from our <a href="track-order.php">Track Order</a> page or from your account dashboard.</p>'
            . '<h3>Delivery attempts and undelivered parcels</h3><p>If a delivery attempt fails, the courier will normally try again. If the parcel cannot be delivered because of an incorrect address, an unreachable phone number or refusal to accept it, it is returned to us. We will contact you, and the order may be re-shipped on payment of the shipping charge again, or cancelled and refunded (less the original shipping and COD charges).</p>'
            . '<h3>Damaged or wrong parcels</h3><p>Please check the parcel at the time of delivery. If it is damaged, tampered with or incorrect, see our <a href="page.php?slug=refund-policy">Cancellation &amp; Refund Policy</a> and contact us within 3 days of delivery.</p>'
            . $contact],
        ['refund-policy', 'Cancellation & Refund Policy',
            '<p>We want you to be happy with your purchase. This policy covers cancellations, returns and refunds for orders placed with {{site_name}}.</p>'
            . '<h3>Cancellation</h3><p>You can cancel an order free of charge at any time <strong>before it is dispatched</strong> by contacting us with your order number. Once an order has been shipped it cannot be cancelled, but it can be returned as described below. For prepaid orders cancelled before dispatch, the full amount is refunded.</p>'
            . '<h3>Returns and refunds – within 3 days of delivery</h3><p>You may request a return and refund within <strong>3 days (72 hours) of the delivery date</strong>. To keep it simple, you can do this yourself:</p>'
            . '<ol><li>Log in to your account and open <strong>My orders</strong>.</li><li>Open the delivered order and click <strong>Request return / refund</strong>.</li><li>Choose a reason, add a short note and submit the request.</li></ol>'
            . '<p>Our team reviews every request, usually within 1–2 business days, and will contact you by email or phone to arrange the pick-up or return of the product. Please keep the product and its original packaging safe until then.</p>'
            . '<h3>After 3 days from delivery</h3><p>Return and refund requests are <strong>not accepted once 3 days have passed since delivery</strong>. If you try to request a refund after this time, your account will show a message that the return window has closed. If you believe there is an exceptional problem with your order, you are welcome to contact us and we will do our best to help.</p>'
            . '<h3>Damaged, defective or incorrect products</h3><p>If your order arrives damaged, tampered with or different from what you ordered, contact us within 3 days of delivery with your order number and clear photos. We will arrange a replacement or a full refund at no cost to you.</p>'
            . '<h3>How refunds are paid</h3><ul><li><strong>Online (Razorpay) payments:</strong> the refund is sent to the original payment method (card, UPI, net banking or wallet). It is initiated within 2 business days of the return being approved, and the amount normally reaches you within <strong>5–7 business days</strong>, depending on your bank.</li><li><strong>Cash on Delivery orders:</strong> the refund is sent by bank transfer or UPI to the account details you share with us, within 5–7 business days of approval.</li></ul>'
            . '<p>The refund covers the amount you paid for the product. Original shipping and COD handling charges are refunded only when the return is due to our error (damaged, defective or wrong product).</p>'
            . '<h3>Non-returnable items</h3><p>Products that have been used beyond reasonable inspection, or that are returned without their original packaging, may not be eligible for a refund.</p>'
            . $contact],
        ['privacy-policy', 'Privacy Policy',
            '<p>{{site_name}} ("we", "us") respects your privacy. This policy explains what information we collect when you use this website, how we use it and the choices you have.</p>'
            . '<h3>Information we collect</h3><ul><li><strong>Account and order details:</strong> your name, mobile number, email address and delivery address.</li><li><strong>Order information:</strong> the products you buy, order value, and payment status.</li><li><strong>Messages:</strong> anything you send us through the Contact form.</li><li><strong>Technical data:</strong> basic information such as browser type and cookies needed to keep you logged in and your cart working.</li></ul>'
            . '<h3>Payments</h3><p>Online payments are processed securely by <strong>Razorpay</strong>. Your card, UPI, net-banking and wallet details are entered on Razorpay\'s secure payment page and are <strong>never seen or stored</strong> by us. We receive only the payment confirmation and payment ID.</p>'
            . '<h3>How we use your information</h3><ul><li>To process, deliver and invoice your orders;</li><li>To send order confirmations, shipping updates and refund updates;</li><li>To respond to your questions and requests;</li><li>To prevent fraud and meet legal and tax obligations.</li></ul>'
            . '<h3>Sharing</h3><p>We share your name, address and phone number with our courier partners solely to deliver your order, and payment details with Razorpay solely to process your payment or refund. We do <strong>not</strong> sell or rent your personal information to anyone.</p>'
            . '<h3>Cookies</h3><p>We use essential cookies to keep you signed in, remember your cart and protect forms against misuse. We do not use them to show third-party advertising.</p>'
            . '<h3>Security and retention</h3><p>We take reasonable technical and organisational steps to protect your data. We keep order records for as long as required for accounting and tax law, and other data for as long as your account is active.</p>'
            . '<h3>Your rights</h3><p>You can view and update your details from your account. You may ask us to correct or delete your personal information (subject to legal record-keeping requirements) by contacting us below.</p>'
            . $contact],
        ['terms', 'Terms & Conditions',
            '<p>Welcome to the website of {{site_name}} (proprietor: {{legal_name}}; GSTIN {{gstin}}). By browsing this website or placing an order you agree to these terms.</p>'
            . '<h3>Products and information</h3><p>We sell Ayurvedic wellness products. Product images and descriptions are provided in good faith, but packaging may change slightly. The information on this website is general and is <strong>not medical advice</strong>. Our products are not intended to diagnose, treat, cure or prevent any disease. Please consult a qualified physician before use if you are pregnant, nursing, on medication or have a medical condition.</p>'
            . '<h3>Pricing and payment</h3><p>All prices are in Indian Rupees (INR) and include GST unless stated otherwise. Prices, offers and availability may change without notice, but the price confirmed in your order is honoured. You can pay online through Razorpay (UPI, cards, net banking, wallets) or choose Cash on Delivery where available. An order is confirmed only after successful payment, or placement for Cash on Delivery. We may cancel an order in case of a pricing error, stock shortage or suspected fraud, and any amount paid will be refunded in full.</p>'
            . '<h3>Shipping, cancellation and refunds</h3><p>Please read our <a href="page.php?slug=shipping-policy">Shipping &amp; Delivery Policy</a> and <a href="page.php?slug=refund-policy">Cancellation &amp; Refund Policy</a>, which form part of these terms. In short: orders ship in 1–2 business days, can be cancelled before dispatch, and eligible returns can be requested within <strong>3 days of delivery</strong>.</p>'
            . '<h3>Your account</h3><p>You are responsible for keeping your login details confidential and for the accuracy of the information you provide.</p>'
            . '<h3>Intellectual property</h3><p>The content, logo, product names and images on this website belong to {{site_name}} and may not be copied or used without our written permission.</p>'
            . '<h3>Limitation of liability</h3><p>To the extent permitted by law, our liability for any claim relating to an order is limited to the amount you paid for that order. Individual results from wellness products vary from person to person.</p>'
            . '<h3>Governing law</h3><p>These terms are governed by the laws of India. Any dispute is subject to the exclusive jurisdiction of the courts at Gautambuddha Nagar, Uttar Pradesh.</p>'
            . $contact],
    ];
}

// Fills the {{placeholders}} in page content with the live store settings.
function page_fill(string $html): string
{
    $map = [
        '{{site_name}}' => setting('site_name'),
        '{{legal_name}}' => setting('legal_name', setting('site_name')),
        '{{address}}' => setting('address'),
        '{{phone}}' => setting('phone', 'see the Contact page'),
        '{{email}}' => setting('email', 'see the Contact page'),
        '{{gstin}}' => setting('gstin'),
    ];
    return strtr($html, array_map('e', $map));
}

// ---------- Returns & refunds ----------
// state: none (not delivered) | open | expired | requested
function return_window(array $o): array
{
    $req = row('SELECT * FROM return_requests WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$o['id']]);
    if ($req) {
        return ['state' => 'requested', 'request' => $req, 'deadline' => null];
    }
    if ($o['status'] !== 'delivered') {
        return ['state' => 'none', 'request' => null, 'deadline' => null];
    }
    $deliveredAt = strtotime($o['delivered_at'] ?: ($o['updated_at'] ?: $o['created_at']));
    $deadline = $deliveredAt + RETURN_WINDOW_DAYS * 86400;
    return ['state' => time() <= $deadline ? 'open' : 'expired', 'request' => null, 'deadline' => $deadline, 'delivered_at' => $deliveredAt];
}

function return_reasons(): array
{
    return ['damaged' => 'Product arrived damaged', 'wrong' => 'Wrong product received', 'defective' => 'Quality issue / defective',
        'not_needed' => 'No longer needed', 'other' => 'Other reason'];
}

function return_statuses(): array
{
    return ['pending' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'refunded' => 'Refunded'];
}

function migrate_database(): void
{
    if ((int) setting('schema_v', '1') >= 6) {
        return;
    }
    try {
        run_migration();
    } catch (Throwable $e) {
        error_log('Migration failed: ' . $e->getMessage());
    }
}

function run_migration(): void
{
    $pdo = db();
    $has = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivered_at'")->fetch();
    if (!$has) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN delivered_at DATETIME NULL');
        $pdo->exec("UPDATE orders SET delivered_at = COALESCE(updated_at, created_at) WHERE status = 'delivered' AND delivered_at IS NULL");
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS return_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NULL,
        reason VARCHAR(30) NOT NULL DEFAULT \'other\',
        details VARCHAR(1000) NOT NULL DEFAULT \'\',
        status VARCHAR(20) NOT NULL DEFAULT \'pending\',
        admin_note VARCHAR(500) NOT NULL DEFAULT \'\',
        refund_id VARCHAR(60) NOT NULL DEFAULT \'\',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        KEY idx_rr_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    if ((int) setting('schema_v', '1') < 2) {
    foreach (policy_pages() as $i => $p) {
        q('INSERT INTO pages (slug, title, content, show_in_footer, sort_order) VALUES (?, ?, ?, 1, ?)
           ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content)', [$p[0], $p[1], $p[2], $i + 1]);
    }
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS order_events (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        title VARCHAR(150) NOT NULL,
        message VARCHAR(500) NOT NULL DEFAULT \'\',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_oe_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    if ((int) setting('schema_v', '1') < 3 && !(int) $pdo->query('SELECT COUNT(*) FROM order_events')->fetchColumn()) {
        $pdo->exec("INSERT INTO order_events (order_id, title, created_at) SELECT id, 'Order placed', created_at FROM orders");
        $pdo->exec("INSERT INTO order_events (order_id, title, created_at) SELECT id, CONCAT('Status: ', UPPER(SUBSTRING(status, 1, 1)), SUBSTRING(status, 2)), COALESCE(updated_at, created_at) FROM orders WHERE status <> 'pending'");
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(110) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    if (!$pdo->query("SHOW COLUMNS FROM products LIKE 'category_id'")->fetch()) {
        $pdo->exec('ALTER TABLE products ADD COLUMN category_id INT UNSIGNED NULL AFTER id, ADD KEY idx_p_category (category_id)');
    }
    if (!$pdo->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetch()) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(200) NOT NULL DEFAULT '' AFTER slug, ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 1 AFTER image");
    }
    // Home page content managed from the admin: hero banners, blog, Instagram tiles.
    $pdo->exec('CREATE TABLE IF NOT EXISTS banners (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(200) NOT NULL DEFAULT \'\',
        kicker VARCHAR(80) NOT NULL DEFAULT \'\',
        title VARCHAR(150) NOT NULL,
        body VARCHAR(300) NOT NULL DEFAULT \'\',
        button_label VARCHAR(40) NOT NULL DEFAULT \'\',
        link VARCHAR(255) NOT NULL DEFAULT \'\',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS blog_posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(210) NOT NULL UNIQUE,
        image VARCHAR(200) NOT NULL DEFAULT \'\',
        excerpt VARCHAR(300) NOT NULL DEFAULT \'\',
        content MEDIUMTEXT NULL,
        is_published TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS insta_posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(200) NOT NULL DEFAULT \'\',
        link VARCHAR(255) NOT NULL DEFAULT \'\',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    if (!$pdo->query("SHOW COLUMNS FROM banners LIKE 'mobile_image'")->fetch()) {
        $pdo->exec("ALTER TABLE banners ADD COLUMN mobile_image VARCHAR(200) NOT NULL DEFAULT '' AFTER image, ADD COLUMN show_text TINYINT(1) NOT NULL DEFAULT 0 AFTER mobile_image");
    }
    set_setting('schema_v', '6');
}

<?php
// Starter data inserted once by install.php. Everything here is editable later from the admin panel.

function seed_database(PDO $pdo): void
{
    // $ignore skips rows whose unique key (coupon code, page slug) already exists.
    $ins = function (string $table, array $data, bool $ignore = false) use ($pdo): int {
        $cols = array_keys($data);
        $st = $pdo->prepare('INSERT ' . ($ignore ? 'IGNORE ' : '') . "INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
        $st->execute(array_values($data));
        return (int) $pdo->lastInsertId();
    };

    // ---- Settings ----
    $settings = [
        'site_name' => 'Holistic Global Services',
        'tagline' => 'Ayurvedic wellness, made with care',
        'legal_name' => 'MD MAHTAB ALAM',
        'gstin' => '09AMEPA3490J1Z1',
        'fssai' => '12118441000654',
        'address' => 'Office No. G-01, Ground Floor, A-87, Sector-4, Noida, Gautambuddha Nagar, Uttar Pradesh - 201301',
        'state' => 'Uttar Pradesh',
        'phone' => '',
        'whatsapp' => '',
        'email' => '',
        'announcement' => 'Free shipping on prepaid orders above ₹999 · Cash on Delivery available',
        'hero_kicker' => 'Ayurvedic joint care',
        'hero_title' => 'Move freely. Live fully.',
        'hero_text' => 'Jointex is an Ayurvedic capsule formulated to support joint comfort, mobility and an active everyday life.',
        'meta_description' => 'Jointex Ayurvedic capsules for joint support by Holistic Global Services. Order online with secure payment and delivery across India.',
        'invoice_prefix' => 'HGS',
        'cod_enabled' => '1',
        'razorpay_key_id' => '',
        'razorpay_key_secret' => '',
        'razorpay_webhook_secret' => '',
        'fship_key' => '',
        'fship_warehouse_id' => '',
        'fship_pickup_pincode' => '',
        'fship_mode' => 'Surface',
        'fship_live' => '1',
        'disclaimer' => 'This product is not intended to diagnose, treat, cure or prevent any disease. Results may vary from person to person. Please consult your physician before use if you are pregnant, nursing, on medication or have a medical condition.',
    ];
    $st = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE skey = skey');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) {
        return; // already seeded
    }

    // ---- Products (prices and stock are placeholders – update in Admin › Products) ----
    $usage = "Take 1 capsule twice a day after meals — one in the morning and one at night.\nSwallow with normal water.\nUse regularly, or as directed by your physician.";
    $ingredients = 'Please refer to the product label for the complete list of ingredients.';

    $p60 = $ins('products', [
        'name' => 'Jointex – 60 Capsules', 'slug' => 'jointex-60-capsules',
        'tagline' => 'Support for comfortable movement', 'pack_label' => '60 Capsules · 30-day pack',
        'sku' => 'JNTX-60', 'hsn' => '30049011', 'gst_rate' => 5, 'mrp' => 1499, 'price' => 999,
        'stock' => 100, 'weight_grams' => 120,
        'short_description' => 'Ayurvedic capsules for joint support. A full month of twice-daily use in one bottle.',
        'description' => "Jointex is an Ayurvedic formulation made to support joint comfort and everyday mobility, so you can keep up with the things you enjoy.\n\nEach bottle contains 60 vegetarian capsules — a 30-day supply at the suggested use of two capsules a day.\n\n• Supports joint comfort\n• Helps in mobility\n• Supports daily wellness and an active lifestyle\n• Made in a GMP and ISO certified facility",
        'ingredients' => $ingredients, 'how_to_use' => $usage, 'is_featured' => 1, 'sort_order' => 1,
    ]);
    foreach (['jointex-60-hero.jpg', 'jointex-60-herbs.jpg', 'jointex-60-features.jpg'] as $i => $f) {
        $ins('product_images', ['product_id' => $p60, 'filename' => $f, 'sort_order' => $i]);
    }

    $p30 = $ins('products', [
        'name' => 'Jointex – 30 Capsules', 'slug' => 'jointex-30-capsules',
        'tagline' => 'Ayurvedic support for healthy joints', 'pack_label' => '30 Capsules · 15-day pack',
        'sku' => 'JNTX-30', 'hsn' => '30049011', 'gst_rate' => 5, 'mrp' => 799, 'price' => 599,
        'stock' => 100, 'weight_grams' => 80,
        'short_description' => 'The starter pack of Jointex Ayurvedic capsules — 15 days of twice-daily use.',
        'description' => "A convenient starter pack of Jointex, the Ayurvedic capsule formulated to support healthy joints, back and muscle comfort, and better flexibility.\n\nEach bottle contains 30 vegetarian capsules — a 15-day supply at the suggested use of two capsules a day.\n\n• Supports joint, back and muscle comfort\n• Supports mobility and flexibility\n• Ayurvedic herbal formulation\n• Made in a GMP and ISO certified facility",
        'ingredients' => $ingredients, 'how_to_use' => $usage, 'is_featured' => 1, 'sort_order' => 2,
    ]);
    foreach (['jointex-30-hero.jpg', 'jointex-30-benefits.jpg', 'jointex-30-usage.jpg', 'jointex-30-lifestyle.jpg'] as $i => $f) {
        $ins('product_images', ['product_id' => $p30, 'filename' => $f, 'sort_order' => $i]);
    }

    // ---- Shipping zones (rates are placeholders – update in Admin › Shipping) ----
    $ins('shipping_zones', ['name' => 'Uttar Pradesh & Delhi NCR', 'states' => 'Uttar Pradesh, Delhi, Haryana', 'rate' => 40, 'free_above' => 499, 'cod_fee' => 30, 'eta' => '2–4 business days', 'sort_order' => 1]);
    $ins('shipping_zones', ['name' => 'North-East & Islands', 'states' => 'Arunachal Pradesh, Assam, Manipur, Meghalaya, Mizoram, Nagaland, Sikkim, Tripura, Andaman and Nicobar Islands, Lakshadweep, Ladakh, Jammu and Kashmir', 'rate' => 120, 'free_above' => 1999, 'cod_fee' => 50, 'eta' => '6–10 business days', 'sort_order' => 2]);
    $ins('shipping_zones', ['name' => 'Rest of India', 'states' => '*', 'rate' => 70, 'free_above' => 999, 'cod_fee' => 40, 'eta' => '4–7 business days', 'sort_order' => 3]);

    // ---- Coupon ----
    $ins('coupons', ['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'min_order' => 499, 'max_discount' => 150], true);

    // ---- FAQs ----
    $faqs = [
        ['How should I take Jointex?', 'Take 1 capsule twice a day after meals — one in the morning and one at night — with normal water, or as directed by your physician.'],
        ['How long does one bottle last?', 'At two capsules a day, the 30-capsule bottle lasts 15 days and the 60-capsule bottle lasts 30 days.'],
        ['Is Jointex vegetarian?', 'Yes. Jointex carries the green vegetarian mark and is suitable for vegetarians.'],
        ['Can I take it with my other medicines?', 'If you are on any medication, are pregnant or nursing, or have a medical condition, please consult your physician before using this product.'],
        ['Which payment options do you accept?', 'You can pay online by UPI, debit/credit card, net banking or wallet through our secure payment gateway, or choose Cash on Delivery where available.'],
        ['How do I track my order?', 'Use the Track Order page with your order number and phone number. Once your parcel is shipped, the courier name and tracking number appear there.'],
    ];
    foreach ($faqs as $i => $f) {
        $ins('faqs', ['question' => $f[0], 'answer' => $f[1], 'sort_order' => $i]);
    }

    // ---- Policy pages (starter text – please review before going live) ----
    $pages = [
        ['about', 'About Us', '<p>Holistic Global Services is a Noida-based wellness company bringing Ayurvedic formulations to homes across India. Our flagship product, Jointex, is made for people who want to stay active and comfortable in their everyday movement.</p><p>We work with GMP and ISO certified manufacturing, and we sell directly to you so that every bottle is genuine.</p><h3>Registered details</h3><p>Trade name: Holistic Global Services<br>GSTIN: 09AMEPA3490J1Z1<br>Office No. G-01, Ground Floor, A-87, Sector-4, Noida, Gautambuddha Nagar, Uttar Pradesh – 201301</p>'],
        ['shipping-policy', 'Shipping Policy', '<p>We ship across India through our courier partners. Orders are usually dispatched within 1–2 business days of confirmation.</p><p>Delivery time and shipping charges depend on your state and are shown at checkout before you pay. Once shipped, you can follow your parcel from the Track Order page.</p><p>If a delivery attempt fails, the courier will normally try again. Orders returned to us as undeliverable may be re-shipped at an additional charge.</p>'],
        ['refund-policy', 'Returns & Refunds', '<p>If your order arrives damaged, tampered with or incorrect, contact us within 48 hours of delivery with your order number and photos, and we will arrange a replacement or refund.</p><p>For hygiene and safety reasons, opened bottles cannot be returned.</p><p>Approved refunds for prepaid orders are issued to the original payment method, normally within 5–7 business days. Orders can be cancelled free of charge any time before dispatch.</p>'],
        ['privacy-policy', 'Privacy Policy', '<p>We collect only the details needed to process your order — your name, phone number, email and delivery address. Online payments are handled by our payment gateway; we never see or store your card or UPI details.</p><p>We share your delivery details with our courier partners solely to deliver your order. We do not sell your personal information.</p><p>To have your account or data removed, write to us from the Contact page.</p>'],
        ['terms', 'Terms & Conditions', '<p>By placing an order on this website you agree to these terms. Prices are in Indian Rupees and include GST. We may update prices and offers without notice, but the price confirmed in your order will be honoured.</p><p>The information on this website is for general purposes and is not medical advice. This product is not intended to diagnose, treat, cure or prevent any disease. Please consult a qualified physician about any health condition.</p><p>Any dispute is subject to the jurisdiction of the courts of Gautambuddha Nagar, Uttar Pradesh.</p>'],
    ];
    foreach ($pages as $i => $p) {
        $ins('pages', ['slug' => $p[0], 'title' => $p[1], 'content' => $p[2], 'sort_order' => $i], true);
    }
}

<?php
// Reusable page sections shared by the storefront pages.

function sec_icon(string $name): string
{
    static $i = [
        'leaf' => '<path d="M5 19c0-8 5-13 14-14 0 9-5 14-14 14z"/><path d="M5 19c3-5 6-8 10-10"/>',
        'shield' => '<path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
        'truck' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="17.5" cy="17.5" r="1.5"/>',
        'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'cash' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'cart' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 016 0v2"/>',
        'box' =>'<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'pin' => '<path d="M12 21s7-6 7-11a7 7 0 0 0-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'pulse' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'heart' => '<path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.5A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($i[$name] ?? '') . '</svg>';
}

/** Four "why buy direct" reassurance tiles. */
function sec_usps(string $title = 'Why shop with us'): void
{
    $items = [
        ['shield', 'Genuine & sealed', 'Made in a GMP and ISO certified facility and shipped tamper-evident.'],
        ['lock', 'Secure payments', 'Pay online with UPI, cards or net banking through Razorpay.'],
        ['cash', 'Cash on Delivery', 'Prefer to pay at the door? COD is available on eligible pin codes.'],
        ['truck', 'Delivery across India', 'Dispatched within 1–2 business days, with live order tracking.'],
    ]; ?>
<section class="screen" id="usps">
  <div class="container">
    <div class="screen-head"><span class="kicker">Our promise</span><h2><?= e($title) ?></h2></div>
    <div class="usps">
      <?php foreach ($items as $it): ?>
        <div class="usp"><span class="usp-ico"><?= sec_icon($it[0]) ?></span><h3><?= e($it[1]) ?></h3><p><?= e($it[2]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php }

/** Order → dispatch → track → delivered. */
function sec_delivery_steps(): void
{
    $steps = [
        ['cart', 'Place your order', 'Choose your pack and check out in a minute, as a guest or with an account.'],
        ['box', 'We pack & dispatch', 'Orders ship within 1–2 business days from our Noida office.'],
        ['pin', 'Track every step', 'Follow your parcel any time from the Track Order page.'],
        ['check', 'Delivered to you', 'Pay online beforehand, or in cash at the door.'],
    ]; ?>
<section class="screen tint" id="delivery">
  <div class="container">
    <div class="screen-head"><span class="kicker">Easy ordering</span><h2>From our shelf to your door</h2><p class="lead">Four simple steps, and you can see where your order is at every one.</p></div>
    <ol class="flow">
      <?php foreach ($steps as $n => $s): ?>
        <li><span class="flow-n"><?= $n + 1 ?></span><h3><?= e($s[1]) ?></h3><p><?= e($s[2]) ?></p></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php }

/** Questions from the admin-managed FAQ list. */
function sec_faq(int $limit = 6): void
{
    $faqs = rows('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id LIMIT ' . $limit);
    if (!$faqs) { return; } ?>
<section class="screen" id="faq">
  <div class="container narrow-faq">
    <div class="screen-head"><span class="kicker">Questions</span><h2>Good to know</h2></div>
    <div class="faq">
      <?php foreach ($faqs as $f): ?>
        <details><summary><?= e($f['question']) ?></summary><p><?= nl2br(e($f['answer'])) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php }

/** "Talk to us" strip with call / WhatsApp / email. */
function sec_help(): void
{
    $wa = preg_replace('/\D+/', '', setting('whatsapp'));
    $phone = setting('phone');
    $email = setting('email'); ?>
<section class="screen help" id="help">
  <div class="container help-box">
    <div><span class="kicker">We are here</span><h2>Need help choosing?</h2><p class="muted">Ask us about the product, your order or delivery. A real person will reply.</p></div>
    <div class="help-actions">
      <?php if ($wa !== ''): ?><a class="help-btn" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= sec_icon('chat') ?><span><b>WhatsApp</b><small>Chat with us</small></span></a><?php endif; ?>
      <?php if ($phone !== ''): ?><a class="help-btn" href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= sec_icon('phone') ?><span><b>Call us</b><small><?= e($phone) ?></small></span></a><?php endif; ?>
      <?php if ($email !== ''): ?><a class="help-btn" href="mailto:<?= e($email) ?>"><?= sec_icon('mail') ?><span><b>Email</b><small><?= e($email) ?></small></span></a><?php endif; ?>
      <a class="help-btn" href="<?= e(url('contact.php')) ?>"><?= sec_icon('pin') ?><span><b>Send a message</b><small>We reply soon</small></span></a>
    </div>
  </div>
</section>
<?php }

/** Closing call to action. */
function sec_cta(string $title = 'Ready to move freely?', string $text = 'Order today. Dispatched within 1–2 business days.'): void
{ ?>
<section class="screen" id="cta">
  <div class="container">
    <div class="cta">
      <div><h2><?= e($title) ?></h2><p><?= e($text) ?></p></div>
      <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Shop now</a>
    </div>
  </div>
</section>
<?php }

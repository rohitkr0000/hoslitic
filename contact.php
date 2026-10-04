<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = mb_substr(post('name'), 0, 100);
    $email = strtolower(post('email'));
    $phone = substr(preg_replace('/\D+/', '', post('phone')), -10);
    $message = mb_substr(post('message'), 0, 3000);
    if (post('website') !== '') {
        redirect('contact.php'); // honeypot field filled in by a bot
    }
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
        flash('error', 'Please fill in your name, a valid email and your message.');
    } elseif (throttle_hit('contact', 4, 3600)) {
        flash('error', 'You have sent several messages already. Please try again later.');
    } else {
        q('INSERT INTO messages (name, email, phone, message) VALUES (?, ?, ?, ?)', [$name, $email, $phone, $message]);
        send_mail(setting('email'), 'New website enquiry from ' . $name, '<p><strong>' . e($name) . '</strong> (' . e($email) . ' ' . e($phone) . ')</p><p>' . nl2br(e($message)) . '</p>');
        flash('success', 'Thank you! We have received your message and will get back to you soon.');
    }
    redirect('contact.php');
}

// Map: search text from Settings (falls back to the business address). No API key needed.
$mapQuery = setting('map_query') !== '' ? setting('map_query') : setting('site_name') . ', ' . setting('address');
$pageTitle = 'Contact us';
$activeNav = 'contact';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container"><?= breadcrumbs(['Contact us' => null]) ?><h1>Contact us</h1><p>Questions about your order or the product? We are happy to help.</p></div></section>
<section class="screen">
  <div class="container contact-grid">
    <form class="card" method="post">
      <?= csrf_field() ?>
      <h3>Send us a message</h3>
      <div class="form-grid">
        <div><label for="name">Name</label><input id="name" type="text" name="name" required></div>
        <div><label for="phone">Mobile number</label><input id="phone" type="tel" name="phone" maxlength="13"></div>
        <div class="full"><label for="email">Email</label><input id="email" type="email" name="email" required></div>
        <div class="full"><label for="message">Message</label><textarea id="message" name="message" required></textarea></div>
        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <div class="full"><button class="btn btn-dark" type="submit">Send message</button></div>
      </div>
    </form>
    <div style="display:flex;flex-direction:column">
      <div class="card">
        <h3 style="margin-bottom:12px"><?= e(setting('site_name')) ?></h3>
        <div class="info-grid" style="grid-template-columns:1fr 1fr">
          <div style="grid-column:1/-1"><h4>Office</h4><?= e(setting('address')) ?></div>
          <?php if (setting('phone') !== ''): ?><div><h4>Phone</h4><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></div><?php endif; ?>
          <?php if (setting('email') !== ''): ?><div><h4>Email</h4><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></div><?php endif; ?>
          <div><h4>GSTIN</h4><?= e(setting('gstin')) ?></div>
        </div>
      </div>
      <div class="card map-card" style="flex:1">
        <iframe title="Our location on Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen
          src="https://www.google.com/maps?q=<?= e(urlencode($mapQuery)) ?>&amp;output=embed"></iframe>
        <div class="map-bar">
          <span class="muted">Find us on the map</span>
          <a class="btn btn-dark btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(urlencode($mapQuery)) ?>">Get directions</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

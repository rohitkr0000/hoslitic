<?php
$footPages = rows('SELECT slug, title FROM pages WHERE show_in_footer = 1 ORDER BY sort_order, id');
$wa = preg_replace('/\D+/', '', setting('whatsapp'));
?>
</main>
<footer class="site-footer">
  <div class="container">
    <div class="foot-grid">
      <div>
        <a class="brand" href="<?= e(url('')) ?>">
          <span class="logo-mark"><img src="<?= e(url('assets/img/logo.jpg')) ?>" alt=""></span>
          <span class="brand-text"><b><?= e(strtoupper(explode(' ', setting('site_name'), 2)[0])) ?></b><span><?= e(strtoupper(explode(' ', setting('site_name'), 2)[1] ?? '')) ?></span></span>
        </a>
        <p class="foot-legal">
          <?= e(setting('address')) ?><br>
          <?php if (setting('gstin') !== ''): ?>GSTIN: <?= e(setting('gstin')) ?><br><?php endif; ?>
          <?php if (setting('fssai') !== ''): ?>FSSAI Lic. No.: <?= e(setting('fssai')) ?><?php endif; ?>
        </p>
      </div>
      <div>
        <h4>Shop</h4>
        <ul>
          <?php foreach (rows('SELECT name, slug FROM products WHERE is_active = 1 ORDER BY sort_order, id LIMIT 6') as $fp): ?>
            <li><a href="<?= e(url('product.php?slug=' . urlencode($fp['slug']))) ?>"><?= e($fp['name']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= e(url('track-order.php')) ?>">Track your order</a></li>
          <li><a href="<?= e(url('account.php')) ?>">My account</a></li>
        </ul>
      </div>
      <div>
        <h4>Information</h4>
        <ul>
          <?php foreach ($footPages as $fp): ?>
            <li><a href="<?= e(url('page.php?slug=' . urlencode($fp['slug']))) ?>"><?= e($fp['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>Get in touch</h4>
        <ul>
          <?php if (setting('phone') !== ''): ?><li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('phone'))) ?>">☎ <?= e(setting('phone')) ?></a></li><?php endif; ?>
          <?php if (setting('email') !== ''): ?><li><a href="mailto:<?= e(setting('email')) ?>">✉ <?= e(setting('email')) ?></a></li><?php endif; ?>
          <?php if (setting('whatsapp') !== ''): ?><li><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">✆ WhatsApp us</a></li><?php endif; ?>
          <li><a href="<?= e(url('contact.php')) ?>">Send us a message</a></li>
        </ul>
      </div>
    </div>
    <?php if (setting('disclaimer') !== ''): ?>
      <div class="foot-disclaimer"><?= e(setting('disclaimer')) ?></div>
    <?php endif; ?>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> <?= e(setting('site_name')) ?>. All rights reserved.</span>
      <span>Secure payments · Delivery across India</span>
    </div>
  </div>
</footer>
<?php if ($wa !== ''): ?>
  <a class="wa-float" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">✆</a>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>?v=4"></script>
<script src="<?= e(url('assets/js/motion.js')) ?>?v=4"></script>
<script src="<?= e(url('assets/js/fx.js')) ?>?v=3"></script>
<?php if (!empty($heroScene)): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="<?= e(url('assets/js/hero3d.js')) ?>?v=5"></script>
<?php endif; ?>
</body>
</html>

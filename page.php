<?php
require __DIR__ . '/includes/init.php';

if (($_GET['slug'] ?? '') === 'about') { redirect('about.php'); }
$page = row('SELECT * FROM pages WHERE slug = ?', [(string) ($_GET['slug'] ?? '')]);
if (!$page) {
    http_response_code(404);
    $page = ['title' => 'Page not found', 'content' => '<p>The page you are looking for does not exist.</p>', 'slug' => ''];
}
$pageTitle = $page['title'];
$bodyClass = 'static-page'; // policy text: no tilt / reveal animations
$activeNav = $page['slug'] === 'about' ? 'about' : '';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container"><?= breadcrumbs([$page['title'] => null]) ?><h1><?= e($page['title']) ?></h1></div></section>
<section class="section" style="padding-top:40px">
  <div class="narrow content card">
    <?= page_fill($page['content']) /* HTML authored by the store admin */ ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/includes/init.php';

$activeNav = 'blog';
$slug = (string) ($_GET['slug'] ?? '');

if ($slug !== '') {
    $post = row('SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1', [$slug]);
    if (!$post) {
        http_response_code(404);
        $post = ['id' => 0, 'title' => 'Article not found', 'content' => '<p>The article you are looking for does not exist.</p>', 'image' => '', 'excerpt' => '', 'created_at' => null];
    }
    $more = rows('SELECT title, slug, image, excerpt, created_at FROM blog_posts WHERE is_published = 1 AND id <> ? ORDER BY created_at DESC, id DESC LIMIT 3', [$post['id']]);
    $pageTitle = $post['title'];
    if ($post['excerpt'] !== '') { $metaDescription = $post['excerpt']; }
    $bodyClass = 'static-page';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-head"><div class="container">
      <?= breadcrumbs(['Blog' => url('blog.php'), $post['title'] => null]) ?>
      <h1><?= e($post['title']) ?></h1>
      <?php if ($post['created_at']): ?><p><?= e(date('d M Y', strtotime($post['created_at']))) ?></p><?php endif; ?>
    </div></section>
    <section class="section" style="padding-top:40px">
      <div class="narrow content card blog-post">
        <?php if ($post['image'] !== ''): ?><img class="blog-cover" src="<?= e(media_url($post['image'])) ?>" alt=""><?php endif; ?>
        <?= $post['content'] /* HTML authored by the store admin */ ?>
      </div>
    </section>
    <?php if ($more): ?>
    <section class="screen tint">
      <div class="container">
        <div class="screen-head"><span class="kicker">Blogs</span><h2>More to read</h2></div>
        <div class="blog-grid"><?php foreach ($more as $bp) { blog_card($bp); } ?></div>
      </div>
    </section>
    <?php endif;
    require __DIR__ . '/includes/footer.php';
    exit;
}

$posts = rows('SELECT title, slug, image, excerpt, created_at FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC, id DESC');
$pageTitle = 'Blog';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <div class="container">
    <?= breadcrumbs(['Blog' => null]) ?>
    <h1>Blog</h1>
    <p>Wellness tips, Ayurveda and how to get the most from our products.</p>
  </div>
</section>
<section class="screen">
  <div class="container">
    <div class="blog-grid"><?php foreach ($posts as $bp) { blog_card($bp); } ?></div>
    <?php if (!$posts): ?><p class="muted center">Articles will appear here soon.</p><?php endif; ?>
  </div>
</section>
<?php
sec_cta();
require __DIR__ . '/includes/footer.php';

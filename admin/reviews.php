<?php
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = (int) post('id');
    if (post('action') === 'delete') {
        q('DELETE FROM reviews WHERE id = ?', [$rid]);
        flash('success', 'Review deleted.');
    } elseif (in_array(post('action'), ['approved', 'pending'], true)) {
        q('UPDATE reviews SET status = ? WHERE id = ?', [post('action'), $rid]);
        flash('success', post('action') === 'approved' ? 'Review approved and now visible on the site.' : 'Review hidden.');
    }
    redirect('admin/reviews.php');
}
$reviews = rows("SELECT r.*, p.name AS product_name FROM reviews r JOIN products p ON p.id = r.product_id ORDER BY (r.status = 'pending') DESC, r.id DESC LIMIT 200");
admin_header('Reviews', 'reviews');
?>
<div class="top"><h1>Reviews</h1></div>
<div class="card table-wrap">
  <table>
    <thead><tr><th>Customer</th><th>Product</th><th>Rating</th><th>Review</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($reviews as $r): ?>
        <tr>
          <td><?= e($r['name']) ?><br><span class="muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></span></td>
          <td><?= e($r['product_name']) ?></td>
          <td><?= (int) $r['rating'] ?> ★</td>
          <td style="max-width:380px"><?= nl2br(e($r['comment'])) ?></td>
          <td><?= badge($r['status']) ?></td>
          <td style="white-space:nowrap">
            <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <?php if ($r['status'] === 'pending'): ?>
                <button class="btn sm" name="action" value="approved" type="submit">Approve</button>
              <?php else: ?>
                <button class="btn alt sm" name="action" value="pending" type="submit">Hide</button>
              <?php endif; ?>
              <button class="btn warn sm" name="action" value="delete" type="submit" onclick="return confirm('Delete this review?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$reviews): ?><tr><td colspan="6" class="muted">No reviews yet. Reviews submitted on product pages appear here for approval.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>

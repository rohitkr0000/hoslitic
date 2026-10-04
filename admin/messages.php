<?php
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'delete') {
        q('DELETE FROM messages WHERE id = ?', [(int) post('id')]);
        flash('success', 'Message deleted.');
    }
    redirect('admin/messages.php');
}
$messages = rows('SELECT * FROM messages ORDER BY id DESC LIMIT 200');
q('UPDATE messages SET is_read = 1 WHERE is_read = 0');
admin_header('Messages', 'messages');
?>
<div class="top"><h1>Messages</h1></div>
<?php foreach ($messages as $m): ?>
  <div class="card">
    <div class="top" style="margin-bottom:8px">
      <div><strong><?= e($m['name']) ?></strong> <?= $m['is_read'] ? '' : '<span class="badge badge-shipped">New</span>' ?><br>
        <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] !== '' ? ' · ' . e($m['phone']) : '' ?>
        <span class="muted"> · <?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?></span></div>
      <form method="post" onsubmit="return confirm('Delete this message?')"><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
        <button class="btn warn sm" type="submit">Delete</button>
      </form>
    </div>
    <?= nl2br(e($m['message'])) ?>
  </div>
<?php endforeach; ?>
<?php if (!$messages): ?><div class="card muted">No messages yet. Enquiries from the Contact page appear here.</div><?php endif; ?>
<?php admin_footer(); ?>

<?php
require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect('account.php');
}
$f = ['name' => '', 'email' => '', 'phone' => ''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = ['name' => mb_substr(post('name'), 0, 100), 'email' => strtolower(post('email')), 'phone' => substr(preg_replace('/\D+/', '', post('phone')), -10)];
    $pass = (string) ($_POST['password'] ?? '');
    if (mb_strlen($f['name']) < 3) { $errors[] = 'Please enter your full name.'; }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (!preg_match('/^[6-9]\d{9}$/', $f['phone'])) { $errors[] = 'Please enter a valid 10-digit mobile number.'; }
    if (strlen($pass) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
    if (!$errors && val('SELECT id FROM users WHERE email = ?', [$f['email']])) { $errors[] = 'An account with this email already exists. Please log in.'; }
    if (!$errors && !email_verified($f['email'])) { $errors[] = 'Please verify your email address with the code we email you.'; }
    if (!$errors) {
        q('INSERT INTO users (name, email, email_verified_at, phone, password_hash) VALUES (?, ?, ?, ?, ?)',
            [$f['name'], $f['email'], email_verify_required() ? date('Y-m-d H:i:s') : null, $f['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();
        // Attach earlier guest orders placed with the same email.
        q('UPDATE orders SET user_id = ? WHERE user_id IS NULL AND email = ?', [$_SESSION['user_id'], $f['email']]);
        flash('success', 'Your account has been created.');
        $next = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login']);
        header('Location: ' . ($next !== '' && $next[0] === '/' && ($next[1] ?? '') !== '/' ? $next : url('account.php')));
        exit;
    }
}
$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="auth">
    <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
    <form class="card" method="post">
      <?= csrf_field() ?>
      <h2>Create your account</h2>
      <div><label for="name">Full name <span class="req">*</span></label><input id="name" type="text" name="name" value="<?= e($f['name']) ?>" autocomplete="name" required></div>
      <?= email_verify_field($f['email'], 'register') ?>
      <div><label for="phone">Mobile number <span class="req">*</span></label><input id="phone" type="tel" name="phone" value="<?= e($f['phone']) ?>" maxlength="13" autocomplete="tel" required></div>
      <div><label for="password">Password (min 8 characters) <span class="req">*</span></label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
      <button class="btn btn-dark btn-block" type="submit">Create account</button>
      <p class="center muted">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

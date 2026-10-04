<?php
require __DIR__ . '/includes/init.php';

if (($_GET['next'] ?? '') === 'checkout') {
    $_SESSION['after_login'] = url('checkout.php');
}
if (current_user()) {
    redirect('account.php');
}
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(post('email'));
    $u = row('SELECT * FROM users WHERE email = ?', [$email]);
    if (throttle_hit('login')) {
        flash('error', 'Too many attempts. Please wait a few minutes and try again.');
    } elseif ($u && password_verify((string) ($_POST['password'] ?? ''), $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        $next = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login'], $_SESSION['throttle']['login']);
        flash('success', 'Welcome back, ' . explode(' ', $u['name'])[0] . '!');
        header('Location: ' . ($next !== '' && $next[0] === '/' && ($next[1] ?? '') !== '/' ? $next : url('account.php')));
        exit;
    } else {
        flash('error', 'Incorrect email or password.');
    }
    redirect('login.php');
}
$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="auth">
    <form class="card" method="post">
      <?= csrf_field() ?>
      <h2>Welcome back</h2>
      <div><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></div>
      <div><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
      <button class="btn btn-dark btn-block" type="submit">Log in</button>
      <p class="center muted">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require dirname(__DIR__) . '/includes/init.php';

if (current_admin()) {
    redirect('admin/index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = row('SELECT * FROM admins WHERE email = ?', [strtolower(post('email'))]);
    if (throttle_hit('admin_login', 6, 900)) {
        $error = 'Too many attempts. Please wait 15 minutes and try again.';
    } elseif ($a && password_verify((string) ($_POST['password'] ?? ''), $a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        unset($_SESSION['throttle']['admin_login']);
        redirect('admin/index.php');
    } else {
        $error = 'Incorrect email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Admin login</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=1">
</head>
<body>
<div class="login">
  <form class="card" method="post">
    <?= csrf_field() ?>
    <h1 style="margin-bottom:4px"><?= e(setting('site_name')) ?></h1>
    <p class="muted" style="margin-top:0">Store admin</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <p><label>Email</label><input type="email" name="email" required autofocus></p>
    <p><label>Password</label><input type="password" name="password" required></p>
    <button class="btn" type="submit">Log in</button>
  </form>
</div>
</body>
</html>

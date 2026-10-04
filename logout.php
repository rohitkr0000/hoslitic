<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    unset($_SESSION['user_id'], $_SESSION['my_orders']);
    session_regenerate_id(true);
    flash('info', 'You have been logged out.');
}
redirect('');

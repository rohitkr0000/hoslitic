<?php
require dirname(__DIR__) . '/includes/init.php';
unset($_SESSION['admin_id']);
session_regenerate_id(true);
redirect('admin/login.php');

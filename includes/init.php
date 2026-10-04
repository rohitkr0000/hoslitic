<?php
// Bootstrap: loaded by every page.
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));

// Base URL path, so the site works at the domain root or inside a sub-folder.
$docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$base = '';
if ($docRoot !== '' && stripos(ROOT_PATH, $docRoot) === 0) {
    $base = substr(ROOT_PATH, strlen($docRoot));
}
define('BASE_URL', rtrim($base, '/'));

if (!is_file(ROOT_PATH . '/config/config.php')) {
    header('Location: ' . BASE_URL . '/install.php');
    exit;
}
require ROOT_PATH . '/config/config.php';

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', defined('APP_DEBUG') && APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('hgs_session');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $secure, 'samesite' => 'Lax']);
session_start();

require ROOT_PATH . '/includes/db.php';
require ROOT_PATH . '/includes/functions.php';
require ROOT_PATH . '/includes/cart.php';
require ROOT_PATH . '/includes/migrate.php';
migrate_database();

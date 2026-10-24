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

// Old ".php" addresses (bookmarks, search results, shared links) move permanently to the clean ones.
// Only GET/HEAD: form posts and the Razorpay webhook keep working at their .php address.
$reqPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true) && substr($reqPath, -4) === '.php') {
    $reqQuery = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    header('Location: ' . url(substr($reqPath, strlen(BASE_URL)) . ($reqQuery !== '' ? '?' . $reqQuery : '')), true, 301);
    exit;
}
require ROOT_PATH . '/includes/cart.php';
require ROOT_PATH . '/includes/migrate.php';
migrate_database();

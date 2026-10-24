<?php
// Local development only: `php -S localhost:8000 router.php` gives the same clean URLs as .htaccess.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$root = __DIR__;
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#^/(includes|config|database)(/|$)#', $path)) {
    http_response_code(403);
    exit;
}
if (is_file($root . $path)) {
    return false; // assets and direct .php requests are served as usual
}

if (preg_match('#^/(product|blog|page)/([^/]+)/?$#', $path, $m)) {
    $_GET['slug'] = $m[2];
    $file = '/' . $m[1] . '.php';
} else {
    $p = rtrim($path, '/');
    if (is_dir($root . $p)) {
        $file = $p . '/index.php';
    } elseif (is_file($root . $p . '.php')) {
        $file = $p . '.php';
    } else {
        return false;
    }
}

$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $file;
$_SERVER['SCRIPT_FILENAME'] = $root . $file;
chdir(dirname($root . $file));
require $root . $file;

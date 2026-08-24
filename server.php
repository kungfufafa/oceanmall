<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

/*
 * public/cpanel is a symlink to Shopper's public assets. Once vendor exists the
 * symlink is a real directory, and PHP's built-in server would 404 GET /cpanel
 * as a directory listing instead of hitting the Laravel panel routes.
 * Serve real files under /cpanel/*; send /cpanel itself to index.php.
 */
if ($uri === '/cpanel/') {
    header('Location: /cpanel', true, 301);
    header('Content-Type: text/html; charset=utf-8');

    return true;
}

if ($uri === '/cpanel') {
    require_once $publicPath.'/index.php';

    return true;
}

if ($uri !== '/' && file_exists($publicPath.$uri) && ! is_dir($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$remoteAddress = ($_SERVER['REMOTE_ADDR'] ?? '-').':'.($_SERVER['REMOTE_PORT'] ?? '-');

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';

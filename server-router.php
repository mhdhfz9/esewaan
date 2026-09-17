<?php

/**
 * Router for PHP's built-in server (composer run serve:network).
 * Same behaviour as Laravel's server.php, but avoids Windows errno=22
 * when writing logs to php://stdout via file_put_contents().
 */
$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$remoteAddress = ($_SERVER['REMOTE_ADDR'] ?? 'unknown').':'.($_SERVER['REMOTE_PORT'] ?? '0');
$logLine = "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n";

if (defined('STDOUT') && is_resource(STDOUT)) {
    fwrite(STDOUT, $logLine);
}

require_once $publicPath.'/index.php';

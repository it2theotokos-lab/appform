<?php
/**
 * PHP Built-in Server Router Script
 *
 * This file is used ONLY when running the PHP built-in web server in development:
 *   php -S 0.0.0.0:8080 -t public public/router.php
 *
 * It ensures that requests for non-existent files (e.g. dynamic routes that
 * look like static files, such as /admin/pdf-designer/assets/img_xxx.png) are
 * forwarded to index.php instead of being served as a native 404 by the server.
 *
 * The built-in server natively serves real static files (CSS, JS, images in
 * public/assets/) without going through this router. Only requests for paths
 * that do NOT map to a real file on disk reach this script.
 */

// Resolve the requested path relative to the document root
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// If the file exists as a real static file, let the built-in server serve it
if ($uri !== '/' && is_file($file)) {
    return false; // serve the static file directly
}

// Otherwise, forward everything to the front controller
require __DIR__ . '/index.php';

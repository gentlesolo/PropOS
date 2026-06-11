<?php
/**
 * VillaCRM Platform — Bridge index.php for Shared Hosting
 *
 * Place this file in your public_html/ (web root) when your Laravel app
 * lives outside the web root (e.g. ~/VillaCRM/).
 *
 * The installer will automatically rewrite APP_PATH to the correct absolute path.
 *
 * Layout assumed:
 *   /home/username/VillaCRM/   â† Laravel root (APP_PATH)
 *   /home/username/public_html/    â† Web root (this file lives here)
 */

// â”€â”€ Configure this path â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Absolute path to your Laravel root directory (NOT the public/ folder inside it).
// The installer rewrites this line automatically — do not change the string format.
define('APP_PATH', is_dir(dirname(__DIR__) . '/VillaCRM/vendor') ? dirname(__DIR__) . '/VillaCRM' : (is_dir(dirname(__DIR__) . '/villacrm/vendor') ? dirname(__DIR__) . '/villacrm' : dirname(__DIR__) . '/VillaCRM'));

// â”€â”€ Sanity check â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (! is_dir(APP_PATH . '/vendor')) {
    http_response_code(503);
    die('Application not yet deployed. Upload all files (including vendor/) first.');
}

// â”€â”€ Storage file proxy (shared hosting symlink bypass) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// When a shared host disables symlinks, public_html/storage â†’ storage/app/public
// cannot be created. Instead we intercept any /storage/ request here and stream
// the real file straight from the app's storage/app/public directory.
//
// This is the PRIMARY fix for broken profile image uploads on cPanel.
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

if ($path !== null && str_starts_with($path, '/storage/')) {
    // Strip the /storage/ prefix to get the relative path inside storage/app/public
    $relativePath = ltrim(substr($path, strlen('/storage')), '/');

    // Security: reject any path traversal attempts
    if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
        http_response_code(400);
        exit('Bad request.');
    }

    $filePath = APP_PATH . '/storage/app/public/' . $relativePath;

    if (! is_file($filePath)) {
        http_response_code(404);
        exit('File not found.');
    }

    // Determine MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    // Only serve safe media/document types — block PHP, scripts, etc.
    $allowed = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm', 'video/ogg',
        'audio/mpeg', 'audio/ogg', 'audio/wav',
        'application/pdf',
        'application/octet-stream', // generic binary
    ];

    if (! in_array($mimeType, $allowed, true)) {
        http_response_code(403);
        exit('Forbidden file type.');
    }

    // Cache headers — 7 days for media
    $etag    = '"' . md5_file($filePath) . '"';
    $lastMod = gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT';

    header('ETag: ' . $etag);
    header('Last-Modified: ' . $lastMod);
    header('Cache-Control: public, max-age=604800, immutable');

    // Respond with 304 if browser already has it
    if (
        (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) ||
        (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $lastMod)
    ) {
        http_response_code(304);
        exit;
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
}

// â”€â”€ Bootstrap â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
define('LARAVEL_START', microtime(true));

// Maintenance mode check (reads from Laravel's storage, not public/)
$maintenance = APP_PATH . '/storage/framework/maintenance.php';
if (file_exists($maintenance)) {
    require $maintenance;
}

// Tell Laravel where its "public" directory really is when serving from here
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

// Register the Composer autoloader from the real app root
require APP_PATH . '/vendor/autoload.php';

// Bootstrap and handle the request
/** @var \Illuminate\Foundation\Application $app */
$app = require_once APP_PATH . '/bootstrap/app.php';

$app->handleRequest(\Illuminate\Http\Request::capture());

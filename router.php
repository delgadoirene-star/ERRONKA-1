<?php
require_once __DIR__ . '/bootstrap.php';

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH) ?: '/';

// Normalize root and trailing slash to home
if ($path === '/' || rtrim($path, '/') === '') {
    include 'views/home.php';
    exit;
}

// Avoid directory traversal; keep only basename
$base = basename($path); // includes .php if present
$page = basename($path, '.php') ?: 'home';

// Legacy without Hashids
global $hashids;
if ($hashids === null) {
    if ($page === '' || $page === 'router' || $page === 'home') {
        include 'views/home.php';
        exit;
    }
    $candidate = __DIR__ . "/views/{$page}.php";
    if (file_exists($candidate)) {
        include $candidate;
        exit;
    }
    http_response_code(404);
    include 'views/404.php';
    exit;
}

// With Hashids
if ($page === '' || $page === 'router' || $page === 'home') {
    include 'views/home.php';
    exit;
}

// Guard: only try decoding if basename has no dots other than optional .php
if (strpos($base, '.') === false || substr($base, -4) === '.php') {
    $decoded = $hashids->decode($page);
    if ($decoded && isset($decoded[0])) {
        $pageId = (int)$decoded[0];
        $realPage = match($pageId) {
            1 => 'dashboard',
            2 => 'langileak',
            3 => 'produktuak',
            4 => 'salmentak',
            5 => 'nire_salmentak',
            6 => 'profile',
            7 => 'salmenta_berria',
            8 => 'langilea_kudeaketa',
            9 => 'home',
            default => 'home'
        };
        include "views/{$realPage}.php";
        exit;
    }
}

// Fallback: literal view name
$candidate = __DIR__ . "/views/{$page}.php";
if (file_exists($candidate)) {
    include $candidate;
    exit;
}
http_response_code(404);
error_log("Invalid page decode: $page");
include 'views/404.php';
exit;
?>
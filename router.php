<?php
require_once __DIR__ . '/bootstrap.php';

// Use the global $hashids initialized in bootstrap (if available)
global $hashids;
// $hashids may be null when library not available; bootstrap handles initialization

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH) ?: '/';
$page = basename($path, '.php') ?: 'home';

// If no Hashids library, try direct view file mapping (keep legacy behavior)
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

if ($page === '' || $page === 'router' || $page === 'home') {
    include 'views/home.php';
    exit;
}

$decoded = $hashids->decode($page);
if ($decoded && isset($decoded[0])) {
    $pageId = $decoded[0];
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
} else {
    // Fallback: maybe the incoming basename was a literal view name (e.g. profile)
    $candidate = __DIR__ . "/views/{$page}.php";
    if (file_exists($candidate)) {
        include $candidate;
        exit;
    }

    http_response_code(404);
    error_log("Invalid page decode: $page");
    include 'views/404.php';
    exit;
}
?>
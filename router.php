<?php
require_once __DIR__ . '/bootstrap.php';

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH) ?: '/';

if ($path === '/' || rtrim($path, '/') === '') {
    include 'views/home.php';
    exit;
}

$base = basename($path);
$page = basename($path, '.php') ?: 'home';

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

if ($page === '' || $page === 'router' || $page === 'home') {
    include 'views/home.php';
    exit;
}

if (strpos($base, '.') === false || substr($base, '-4') === '.php') {
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
            10 => 'denda',
            11 => 'saskia',
            12 => 'checkout',
            13 => 'denda-login',
            14 => 'denda-erregistratu',
            default => 'home'
        };
        include "views/{$realPage}.php";
        exit;
    }
}

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
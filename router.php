<?php
require_once __DIR__ . '/bootstrap.php';
$hashids = (class_exists('\\Hashids\\Hashids')) ? new \Hashids\Hashids('ZAB_IGAI_PLAT_GEN', 8) : null;

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH) ?: '/';
$page = basename($path, '.php') ?: 'home';

if ($hashids === null) {
    include 'views/home.php';
    exit;
}

if ($page === '' || $page === 'router') {
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
    http_response_code(404);
    error_log("Invalid page decode: $page");
    include 'views/404.php';
    exit;
}
?>
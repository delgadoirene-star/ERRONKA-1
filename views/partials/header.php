<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../model/usuario.php';

if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
}

$cssHref   = "/public/assets/style.css";
$pageTitle = $pageTitle ?? '';
$active    = $active ?? '';

$dashboardLink     = function_exists('page_link') ? page_link(1, 'dashboard')      : '/views/dashboard.php';
$langileakLink     = function_exists('page_link') ? page_link(2, 'langileak')       : '/views/langileak.php';
$produktuakLink    = function_exists('page_link') ? page_link(3, 'produktuak')      : '/views/produktuak.php';
$salmentakLink     = function_exists('page_link') ? page_link(4, 'salmentak')       : '/views/salmentak.php';
$nireSalmentakLink = function_exists('page_link') ? page_link(5, 'nire_salmentak')  : '/views/nire_salmentak.php';

$userName = '';
if (!empty($_SESSION['usuario_id']) && isset($conn)) {
    $ud = Usuario::lortuIdAgatik($conn, (int)$_SESSION['usuario_id']) ?: ['izena'=>'','abizena'=>''];
    $userName = trim(($ud['izena'] ?? '') . ' ' . ($ud['abizena'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ? ($pageTitle . ' - ' . EMPRESA_IZENA) : EMPRESA_IZENA) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref) ?>">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <h2>🏭 <?= htmlspecialchars(EMPRESA_IZENA) ?></h2>
        </div>
        <div class="navbar-menu">
            <a href="<?= htmlspecialchars($dashboardLink) ?>" class="nav-link <?= $active==='dashboard'?'active':'' ?>">📊 Dashboard</a>
            <a href="<?= htmlspecialchars($langileakLink) ?>" class="nav-link <?= $active==='langileak'?'active':'' ?>">👥 Langileak</a>
            <a href="<?= htmlspecialchars($produktuakLink) ?>" class="nav-link <?= $active==='produktuak'?'active':'' ?>">📦 Produktuak</a>
            <a href="<?= htmlspecialchars($salmentakLink) ?>" class="nav-link <?= $active==='salmentak'?'active':'' ?>">💰 Salmentak</a>
            <a href="<?= htmlspecialchars($nireSalmentakLink) ?>" class="nav-link <?= $active==='nire_salmentak'?'active':'' ?>">📋 Nire salmentak</a>
            <?php if ($userName): ?>
                <span class="navbar-user"><?= htmlspecialchars($userName) ?></span>
                <a href="/logout.php" class="nav-link logout">🚪 Itxi saioa</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
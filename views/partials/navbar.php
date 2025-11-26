<?php
// Navbar partial: expects $active (string) and $usuario_datos (array) optionally provided by caller.
// It will compute router-safe links with page_link() / encode_id().
$dashboardLink     = function_exists('page_link') ? page_link(1, 'dashboard') : '/dashboard.php';
$langileakLink      = function_exists('page_link') ? page_link(2, 'langileak') : '/langileak.php';
$produktuakLink    = function_exists('page_link') ? page_link(3, 'produktuak') : '/produktuak.php';
$salmentakLink     = function_exists('page_link') ? page_link(4, 'salmentak') : '/salmentak.php';
$nireSalmentakLink = function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/nire_salmentak.php';
$profileLink       = function_exists('page_link') ? page_link(6, 'profile') : '/profile.php';
$usuario_display   = htmlspecialchars(($usuario_datos['izena'] ?? '') . (isset($usuario_datos['abizena']) ? " " . $usuario_datos['abizena'] : ''));

// minimal active helper
function nav_active($name, $active) {
    return ($active === $name) ? 'nav-link active' : 'nav-link';
}
?>
<nav class="navbar">
    <div class="navbar-brand"><h2>🏭 <?php echo htmlspecialchars(EMPRESA_IZENA ?? 'Xabala'); ?></h2></div>
    <div class="navbar-menu">
        <a href="<?php echo htmlspecialchars($dashboardLink); ?>" class="<?php echo nav_active('dashboard', $active ?? ''); ?>">📊 Dashboard</a>
        <a href="<?php echo htmlspecialchars($langileakLink); ?>" class="<?php echo nav_active('langileak', $active ?? ''); ?>">👥 Langileak</a>
        <a href="<?php echo htmlspecialchars($produktuakLink); ?>" class="<?php echo nav_active('produktuak', $active ?? ''); ?>">📦 Produktuak</a>
        <a href="<?php echo htmlspecialchars($salmentakLink); ?>" class="<?php echo nav_active('salmentak', $active ?? ''); ?>">💰 Salmentak</a>
        <a href="<?php echo htmlspecialchars($nireSalmentakLink); ?>" class="<?php echo nav_active('nire_salmentak', $active ?? ''); ?>">📋 Nire salmentak</a>
        <a href="<?php echo htmlspecialchars($profileLink); ?>" class="nav-link">👤 <?php echo $usuario_display; ?></a>
        <a href="<?php echo htmlspecialchars('/logout.php'); ?>" class="nav-link logout">🚪 Itxi saioa</a>
    </div>
</nav>
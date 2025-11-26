<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';

if (empty($_SESSION['usuario_id'])) { redirect_to('/index.php'); }
global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }

$userId = (int)$_SESSION['usuario_id'];
$isAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';

$totalProduktu = $conn->query("SELECT COUNT(*) c FROM produktua")->fetch_assoc()['c'] ?? 0;
$totalSalmenta = $conn->query("SELECT COUNT(*) c FROM salmenta")->fetch_assoc()['c'] ?? 0;
$nireSalmenta  = $conn->query("SELECT COUNT(*) c FROM salmenta WHERE langile_id=".$userId)->fetch_assoc()['c'] ?? 0;

// Build encoded links using helper
$lnk_salmenta_berria = function_exists('page_link') ? page_link(7, 'salmenta_berria') : '/views/salmenta_berria.php';
$lnk_nire_salmentak  = function_exists('page_link') ? page_link(5, 'nire_salmentak')  : '/views/nire_salmentak.php';
$lnk_produktuak      = function_exists('page_link') ? page_link(3, 'produktuak')      : '/views/produktuak.php';
$lnk_langileak       = function_exists('page_link') ? page_link(2, 'langileak')       : '/views/langileak.php';
$lnk_profile         = function_exists('page_link') ? page_link(6, 'profile')         : '/views/profile.php';

$pageTitle = "Dashboard";
$active = 'dashboard';
require __DIR__ . '/partials/header.php';
?>
<div class="page-header"><h1>📊 Dashboard</h1></div>

<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div class="card" style="flex:1;min-width:180px;"><h3>Produktuak</h3><p><?=$totalProduktu?></p></div>
    <div class="card" style="flex:1;min-width:180px;"><h3>Salmentak</h3><p><?=$totalSalmenta?></p></div>
    <div class="card" style="flex:1;min-width:180px;"><h3>Nire salmentak</h3><p><?=$nireSalmenta?></p></div>
    <?php if($isAdmin):?>
    <div class="card" style="flex:1;min-width:180px;"><h3>Admin</h3><p>Rol: Admin</p></div>
    <?php endif;?>
</div>
<div>
    <a href="<?= htmlspecialchars($lnk_salmenta_berria) ?>" class="btn">Salmenta berria</a>
    <a href="<?= htmlspecialchars($lnk_nire_salmentak) ?>" class="btn btn-secondary">Nire salmentak</a>
    <a href="<?= htmlspecialchars($lnk_produktuak) ?>" class="btn btn-secondary">Produktuak</a>
    <a href="<?= htmlspecialchars($lnk_langileak) ?>" class="btn btn-secondary">Langileak</a>
    <a href="<?= htmlspecialchars($lnk_profile) ?>" class="btn btn-secondary">Profila</a>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
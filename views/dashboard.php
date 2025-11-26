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
?>
<link rel="stylesheet" href="/style/style.css">
<div class="page-wrapper" style="max-width:1100px;margin:0 auto;padding:20px;">
    <h2>Dashboard</h2>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
        <div class="card" style="flex:1;min-width:180px;"><h3>Produktuak</h3><p><?=$totalProduktu?></p></div>
        <div class="card" style="flex:1;min-width:180px;"><h3>Salmentak</h3><p><?=$totalSalmenta?></p></div>
        <div class="card" style="flex:1;min-width:180px;"><h3>Nire salmentak</h3><p><?=$nireSalmenta?></p></div>
        <?php if($isAdmin):?>
        <div class="card" style="flex:1;min-width:180px;"><h3>Admin</h3><p>Rol: Admin</p></div>
        <?php endif;?>
    </div>
    <div>
        <a href="salmenta_berria.php" class="btn">Salmenta berria</a>
        <a href="nire_salmentak.php" class="btn btn-secondary">Nire salmentak</a>
        <a href="produktuak.php" class="btn btn-secondary">Produktuak</a>
        <a href="langileak.php" class="btn btn-secondary">Langileak</a>
        <a href="profile.php" class="btn btn-secondary">Profila</a>
    </div>
</div>
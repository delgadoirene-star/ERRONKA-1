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

$stmt = $conn->prepare("SELECT COUNT(*) c FROM salmenta WHERE langile_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$nireSalmenta = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
$stmt->close();

$pageTitle = "Dashboard";
$active = 'dashboard';
require __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <h1>📊 Dashboard</h1>
    <p>Laburmena eta egoera orokorra</p>
</div>

<div class="cards-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;">
    <div class="dashboard-card" style="border-left:4px solid #3b82f6;">
        <div class="card-icon">📦</div>
        <div class="card-content">
            <h3><?= (int)$totalProduktu ?></h3>
            <p>Produktuak</p>
        </div>
    </div>
    <div class="dashboard-card" style="border-left:4px solid #10b981;">
        <div class="card-icon">💰</div>
        <div class="card-content">
            <h3><?= (int)$totalSalmenta ?></h3>
            <p>Salmenta guztiak</p>
        </div>
    </div>
    <div class="dashboard-card" style="border-left:4px solid #f59e0b;">
        <div class="card-icon">🧾</div>
        <div class="card-content">
            <h3><?= (int)$nireSalmenta ?></h3>
            <p>Nire salmentak</p>
        </div>
    </div>
    <?php if ($isAdmin): ?>
    <div class="dashboard-card" style="border-left:4px solid #ef4444;">
        <div class="card-icon">🛡️</div>
        <div class="card-content">
            <h3>Admin</h3>
            <p>Pribilegioak aktibo</p>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
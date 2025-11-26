<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/usuario.php'; // added to use Usuario::lortuIdAgatik

global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }
if (empty($_SESSION['usuario_id'])) { redirect_to('/index.php'); }

$userId = (int)$_SESSION['usuario_id'];
$csrf = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token']=Seguritatea::generateCSRFToken());
$mezua=''; $errorea='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea='CSRF errorea.';
    } elseif ($_POST['action'] ?? ''==='delete') {
        $id=(int)($_POST['id']??0);
        $row = $id ? Salmenta::find($conn,$id) : null;
        if ($row && (int)$row['langile_id']===$userId && Salmenta::delete($conn,$id)) $mezua='Ezabatua.';
        else $errorea='Ezabaketak huts egin du.';
    }
}

// Replace raw all/filter with joined helper
$salmentak = Salmenta::lortuGuztiak($conn, $userId);
$salmenta_guztira = Salmenta::kalkulaSalmentaGuztira($conn, $userId);

// Add missing navbar link variables and absolute CSS path
$cssHref           = "/style/style.css";
$dashboardLink     = function_exists('page_link') ? page_link(1, 'dashboard') : '/views/dashboard.php';
$langileakLink     = function_exists('page_link') ? page_link(2, 'langileak') : '/views/langileak.php';
$produktuakLink    = function_exists('page_link') ? page_link(3, 'produktuak') : '/views/produktuak.php';
$salmentakLink     = function_exists('page_link') ? page_link(4, 'salmentak') : '/views/salmentak.php';
$nireSalmentakLink = function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/views/nire_salmentak.php';

// Optional: current user display
$usuario_datos = class_exists('Usuario') && isset($conn) ? (Usuario::lortuIdAgatik($conn, $userId) ?: ['izena'=>'','abizena'=>'']) : ['izena'=>'','abizena'=>''];
$pageTitle = "Nire Salmentak";
$active = 'nire_salmentak';
require __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <h1>📋 Nire Salmentak</h1>
    <p>Zure salmentak historikoa - <?= htmlspecialchars(($usuario_datos['izena'] ?? '') . " " . ($usuario_datos['abizena'] ?? '')) ?></p>
</div>

<div class="dashboard-card" style="margin-bottom: 2rem; border-left: 4px solid #10b981;">
    <div class="card-icon">💰</div>
    <div class="card-content">
        <h3><?= number_format($salmenta_guztira, 2) ?>€</h3>
        <p>Salmentaren guztira</p>
    </div>
</div>

<div class="table-section">
    <h2>Zure salmentak</h2>
    <?php if (count($salmentak) > 0): ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Produktua</th>
                <th>Kategoria</th>
                <th>Kantitatea</th>
                <th>Prezioa unitarioa</th>
                <th>Prezioa totala</th>
                <th>Bezeroa</th>
                <th>Bezeroa telefono</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($salmentak as $salmenta): ?>
                <tr>
                    <td><?= $salmenta['data_salmenta'] ?></td>
                    <td><?= htmlspecialchars($salmenta['produktu_izena']) ?></td>
                    <td><?= htmlspecialchars($salmenta['kategoria'] ?? '-') ?></td>
                    <td><?= $salmenta['kantitatea'] ?></td>
                    <td><?= number_format($salmenta['prezioa_unitarioa'], 2) ?>€</td>
                    <td><strong><?= number_format($salmenta['prezioa_totala'], 2) ?>€</strong></td>
                    <td><?= htmlspecialchars($salmenta['bezeroa_izena'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($salmenta['bezeroa_telefonoa'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-section">
            <h3>Laburpena</h3>
            <p><strong>Guztirako salmenta: <?= number_format($salmenta_guztira, 2) ?>€</strong></p>
            <p>Transakzioak: <?= count($salmentak) ?></p>
            <p>Batez bestekoa transakziokoa: <?= count($salmentak) > 0 ? number_format($salmenta_guztira / count($salmentak), 2) : '0.00' ?>€</p>
        </div>
    <?php else: ?>
        <p class="no-data">Ez duzu salmentarik egina oraindik.</p>
    <?php endif; ?>
</div>

<div class="action-buttons">
    <a href="<?= htmlspecialchars($salmentakLink) ?>" class="btn btn-secondary">← Atzera salmentetara</a>
    <a href="<?= htmlspecialchars($dashboardLink) ?>" class="btn btn-primary">Dashboarda itzuli</a>
</div>

<style>
.action-buttons{display:flex;gap:1rem;margin-top:2rem;justify-content:center;}
.action-buttons .btn{min-width:150px;}
@media (max-width:768px){.action-buttons{flex-direction:column}.action-buttons .btn{width:100%}}
</style>
<?php require __DIR__ . '/partials/footer.php'; ?>
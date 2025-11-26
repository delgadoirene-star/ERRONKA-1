<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/seguritatea.php';

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

$all = Salmenta::all($conn);
$mine = array_filter($all, fn($r)=> (int)$r['langile_id']===$userId);

// Add missing navbar link variables and absolute CSS path
$cssHref           = "/style/style.css";
$dashboardLink     = function_exists('page_link') ? page_link(1, 'dashboard') : '/views/dashboard.php';
$langileakLink     = function_exists('page_link') ? page_link(2, 'langileak') : '/views/langileak.php';
$produktuakLink    = function_exists('page_link') ? page_link(3, 'produktuak') : '/views/produktuak.php';
$salmentakLink     = function_exists('page_link') ? page_link(4, 'salmentak') : '/views/salmentak.php';
$nireSalmentakLink = function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/views/nire_salmentak.php';

// Optional: current user display
$usuario_datos = class_exists('Usuario') && isset($conn) ? (Usuario::lortuIdAgatik($conn, $userId) ?: ['izena'=>'','abizena'=>'']) : ['izena'=>'','abizena'=>''];
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nire Salmentak - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref) ?>">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <h2>🏭 <?= htmlspecialchars(EMPRESA_IZENA) ?></h2>
        </div>
        <div class="navbar-menu">
            <a href="<?= htmlspecialchars($dashboardLink) ?>" class="nav-link">📊 Dashboard</a>
            <a href="<?= htmlspecialchars($langileakLink) ?>" class="nav-link">👥 Langileak</a>
            <a href="<?= htmlspecialchars($produktuakLink) ?>" class="nav-link">📦 Produktuak</a>
            <a href="<?= htmlspecialchars($salmentakLink) ?>" class="nav-link">💰 Salmentak</a>
            <a href="<?= htmlspecialchars($nireSalmentakLink) ?>" class="nav-link active">📋 Nire salmentak</a>
            <span class="navbar-user">
                <?= htmlspecialchars(trim(($usuario_datos['izena'] ?? '') . ' ' . ($usuario_datos['abizena'] ?? ''))) ?>
            </span>
            <a href="/logout.php" class="nav-link logout">🚪 Itxi saioa</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>📋 Nire Salmentak</h1>
            <p>Zure salmentak historikoa - <?php echo htmlspecialchars($usuario_datos['izena'] . " " . $usuario_datos['abizena']); ?></p>
        </div>

        <div class="dashboard-card" style="margin-bottom: 2rem; border-left: 4px solid #10b981;">
            <div class="card-icon">💰</div>
            <div class="card-content">
                <h3><?php echo number_format($salmenta_guztira, 2); ?>€</h3>
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
                                <td><?php echo $salmenta['data_salmenta']; ?></td>
                                <td><?php echo htmlspecialchars($salmenta['produktu_izena']); ?></td>
                                <td><?php echo htmlspecialchars($salmenta['kategoria'] ?? '-'); ?></td>
                                <td><?php echo $salmenta['kantitatea']; ?></td>
                                <td><?php echo number_format($salmenta['prezioa_unitarioa'], 2); ?>€</td>
                                <td><strong><?php echo number_format($salmenta['prezioa_totala'], 2); ?>€</strong></td>
                                <td><?php echo htmlspecialchars($salmenta['bezeroa_izena'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($salmenta['bezeroa_telefonoa'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="summary-section">
                    <h3>Laburpena</h3>
                    <p><strong>Guztirako salmenta: <?php echo number_format($salmenta_guztira, 2); ?>€</strong></p>
                    <p>Transakzioak: <?php echo count($salmentak); ?></p>
                    <p>Batez bestekoa transakziokoa: <?php echo count($salmentak) > 0 ? number_format($salmenta_guztira / count($salmentak), 2) : '0.00'; ?>€</p>
                </div>
            <?php else: ?>
                <p class="no-data">Ez duzu salmentarik egina oraindik.</p>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="<?php echo htmlspecialchars($salmentakLink); ?>" class="btn btn-secondary">← Atzera salmentetara</a>
            <a href="<?php echo htmlspecialchars($dashboardLink); ?>" class="btn btn-primary">Dashboarda itzuli</a>
        </div>
    </div>

    <style>
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .action-buttons .btn {
            min-width: 150px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>
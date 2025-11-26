<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';

global $hashids;  // Access global Hashids

// Autentifikazioa egiaztatzea
if (!isset($_SESSION['usuario_id'])) {
    $home = function_exists('page_link') ? page_link(9, 'home') : '/index.php';
    redirect_to($home);
}

// Erabiltzailearen datuak lortzea
$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
if (!$usuario_datos) {
    session_destroy();
    $home = function_exists('page_link') ? page_link(9, 'home') : '/index.php';
    redirect_to($home);
}

// Langilearen datuak lortzea
$langilea = Langilea::lortuGuztiak($conn);
$langile_kopurua = count($langilea);

// Produktuen guztira
$produktuak = Produktua::lortuGuztiak($conn);
$produktu_kopurua = count($produktuak);

// Salmentaren guztira
$salmenta_guztira = Salmenta::kalkulaSalmentaGuztira($conn);

// Aurreko 10 salmentak
$salmentak_azkena = Salmenta::lortuGuztiak($conn);
$salmentak_azkena = array_slice($salmentak_azkena, 0, 10);

// When generating a referral link, e.g., for sharing or actions
$id = 123;  // Replace with actual ID
$ref = function_exists('encode_id') ? encode_id($id) : $id;
$url = "http://localhost/views/zabala?ref=" . urlencode($ref);

$dashboardLink    = function_exists('page_link') ? page_link(1, 'dashboard') : '/dashboard.php';
$langileakLink    = function_exists('page_link') ? page_link(2, 'langileak') : '/langileak.php';
$produktuakLink   = function_exists('page_link') ? page_link(3, 'produktuak') : '/produktuak.php';
$salmentakLink    = function_exists('page_link') ? page_link(4, 'salmentak') : '/salmentak.php';
$nireSalmentakLink= function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/nire_salmentak.php';
$profileLink      = function_exists('page_link') ? page_link(6, 'profile') : '/profile.php';

// Prepare user data for the navbar partial and mark active
$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
$active = 'dashboard';
include __DIR__ . '/partials/navbar.php';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Ongi etorri, <?php echo htmlspecialchars($usuario_datos['izena']); ?>!</h1>
            <p><?php echo date('Y-m-d H:i'); ?></p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">👥</div>
                <div class="card-content">
                    <h3><?php echo $langile_kopurua; ?></h3>
                    <p>Langileak</p>
                </div>
                <a href="<?php echo htmlspecialchars($langileakLink); ?>" class="card-link">Ikusi →</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">📦</div>
                <div class="card-content">
                    <h3><?php echo $produktu_kopurua; ?></h3>
                    <p>Produktuak</p>
                </div>
                <a href="<?php echo htmlspecialchars($produktuakLink); ?>" class="card-link">Ikusi →</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3><?php echo number_format($salmenta_guztira, 2); ?>€</h3>
                    <p>Guztirako salmenta</p>
                </div>
                <a href="<?php echo htmlspecialchars($salmentakLink); ?>" class="card-link">Ikusi →</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">📅</div>
                <div class="card-content">
                    <h3><?php echo date('d/m'); ?></h3>
                    <p>Gaur</p>
                </div>
            </div>
        </div>

        <div class="dashboard-section">
            <h2>📋 Aurreko salmentak</h2>
            
            <?php if (count($salmentak_azkena) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Langilea</th>
                            <th>Produktua</th>
                            <th>Kantitatea</th>
                            <th>Prezioa</th>
                            <th>Bezeroa</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salmentak_azkena as $salmenta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($salmenta['izena'] . " " . $salmenta['abizena']); ?></td>
                                <td><?php echo htmlspecialchars($salmenta['produktu_izena']); ?></td>
                                <td><?php echo $salmenta['kantitatea']; ?></td>
                                <td><?php echo number_format($salmenta['prezioa_totala'], 2); ?>€</td>
                                <td><?php echo htmlspecialchars($salmenta['bezeroa_izena'] ?? '-'); ?></td>
                                <td><?php echo $salmenta['data_salmenta']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Ez dago salmentarik</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
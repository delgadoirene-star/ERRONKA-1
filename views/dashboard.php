<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';

global $hashids;  // Access global Hashids

// Autentifikazioa egiaztatzea
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Erabiltzailearen datuak lortzea
$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
if (!$usuario_datos) {
    session_destroy();
    header("Location: ../index.php");
    exit;
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
$ref = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode($id) : $id;  // Encode if available, else use plain ID
$url = "http://localhost/views/zabala?ref=" . urlencode($ref);

$dashboardEncoded = ($hashids !== null) ? $hashids->encode(1) : 'dashboard';
$langileakEncoded = ($hashids !== null) ? $hashids->encode(2) : 'langileak';
$produktuakEncoded = ($hashids !== null) ? $hashids->encode(3) : 'produktuak';
$salmentakEncoded = ($hashids !== null) ? $hashids->encode(4) : 'salmentak';
$nireSalmentakEncoded = ($hashids !== null) ? $hashids->encode(5) : 'nire_salmentak';

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
    <div class="navbar">
        <div class="navbar-brand">
            <h2>🏭 <?php echo EMPRESA_IZENA; ?></h2>
        </div>
        <div class="navbar-menu">
            <a href="/<?php echo $dashboardEncoded; ?>.php" class="nav-link active">📊 Dashboard</a>
            <a href="/<?php echo $langileakEncoded; ?>.php" class="nav-link">👥 Langileak</a>
            <a href="/<?php echo $produktuakEncoded; ?>.php" class="nav-link">📦 Produktuak</a>
            <a href="/<?php echo $salmentakEncoded; ?>.php" class="nav-link">💰 Salmentak</a>
            <a href="/<?php echo $nireSalmentakEncoded; ?>.php" class="nav-link">📋 Nire salmentak</a>
            <span class="navbar-user">
                <?php echo htmlspecialchars($usuario_datos['izena'] . " " . $usuario_datos['abizena']); ?>
            </span>
            <a href="../logout.php" class="nav-link logout">🚪 Itxi saioa</a>
        </div>
    </div>

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
                <a href="langileak.php" class="card-link">Ikusi →</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">📦</div>
                <div class="card-content">
                    <h3><?php echo $produktu_kopurua; ?></h3>
                    <p>Produktuak</p>
                </div>
                <a href="produktuak.php" class="card-link">Ikusi →</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3><?php echo number_format($salmenta_guztira, 2); ?>€</h3>
                    <p>Guztirako salmenta</p>
                </div>
                <a href="salmentak.php" class="card-link">Ikusi →</a>
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
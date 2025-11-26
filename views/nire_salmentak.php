<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

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

// Langilearen ID lortu
$langile_info = null;
$sql = "SELECT l.id FROM langilea l JOIN usuario u ON l.usuario_id = u.id WHERE u.id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $langile_info = $result->fetch_assoc();
    $stmt->close();
}

// Langilearen salmentak
$salmentak = [];
if ($langile_info) {
    $salmentak = Salmenta::lortuGuztiak($conn, $langile_info['id']);
}

// Salmentak guztira
$salmenta_guztira = 0;
foreach ($salmentak as $salmenta) {
    $salmenta_guztira += $salmenta['prezioa_totala'];
}

// Generate encoded page names
$dashboardEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(1) : 'dashboard';
$langileakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(2) : 'langileak';
$produktuakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(3) : 'produktuak';
$salmentakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(4) : 'salmentak';
$nireSalmentakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(5) : 'nire_salmentak';
$profileEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(6) : 'profile';

?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nire Salmentak - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <h2>🏭 <?php echo EMPRESA_IZENA; ?></h2>
        </div>
        <div class="navbar-menu">
            <a href="/<?php echo $dashboardEncoded; ?>.php" class="nav-link">📊 Dashboard</a>
            <a href="/<?php echo $langileakEncoded; ?>.php" class="nav-link">👥 Langileak</a>
            <a href="/<?php echo $produktuakEncoded; ?>.php" class="nav-link">📦 Produktuak</a>
            <a href="/<?php echo $salmentakEncoded; ?>.php" class="nav-link">💰 Salmentak</a>
            <a href="/<?php echo $nireSalmentakEncoded; ?>.php" class="nav-link active">📋 Nire salmentak</a>
            <span class="navbar-user">
                <?php echo htmlspecialchars($usuario_datos['izena'] . " " . $usuario_datos['abizena']); ?>
            </span>
            <a href="../logout.php" class="nav-link logout">🚪 Itxi saioa</a>
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
            <a href="/<?php echo $salmentakEncoded; ?>.php" class="btn btn-secondary">← Atzera salmentetara</a>
            <a href="/<?php echo $dashboardEncoded; ?>.php" class="btn btn-primary">Dashboarda itzuli</a>
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
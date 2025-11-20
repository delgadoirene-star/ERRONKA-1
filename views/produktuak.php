<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

global $hashids;  // Access global Hashids

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
$errorea = "";
$arrakasta = "";

// Produktua batzea
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea = "Segurtasun-errorea.";
    } else {
        $izena = trim($_POST['izena'] ?? '');
        $deskripzioa = trim($_POST['deskripzioa'] ?? '');
        $kategoria = trim($_POST['kategoria'] ?? '');
        $prezioa = floatval($_POST['prezioa'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if (!$izena || $prezioa <= 0) {
            $errorea = "Izena eta prezioa bete behar dira.";
        } else {
            $produktua = new Produktua($izena, $deskripzioa, $kategoria, $prezioa, $stock);
            
            if ($produktua->sortu($conn)) {
                $arrakasta = "Produktua sortu da behar bezala.";
                Seguritatea::logSeguritatea($conn, "PRODUKTUA_SORTU", $izena, $_SESSION['usuario_id']);
            } else {
                $errorea = "Produktua sortzean errore bat egon da.";
            }
        }
    }
}

// Produktua eguneratzea
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea = "Segurtasun-errorea.";
    } else {
        $id = intval($_POST['id'] ?? 0);
        $izena = trim($_POST['izena'] ?? '');
        $prezioa = floatval($_POST['prezioa'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if ($id > 0 && $izena && $prezioa > 0) {
            $produktua = new Produktua($izena, '', '', $prezioa, $stock);
            $produktua->setId($id);
            
            if ($produktua->eguneratu($conn)) {
                $arrakasta = "Produktua eguneratu da.";
                Seguritatea::logSeguritatea($conn, "PRODUKTUA_EGUNERATU", $izena, $_SESSION['usuario_id']);
            } else {
                $errorea = "Produktua eguneratzean errore bat egon da.";
            }
        }
    }
}

// Produktua ezabatzea
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea = "Segurtasun-errorea.";
    } else {
        $produktu_id = intval($_POST['produktu_id'] ?? 0);
        
        if ($produktu_id > 0) {
            if (Produktua::desaktibatu($conn, $produktu_id)) {
                $arrakasta = "Produktua ezabatu da.";
                Seguritatea::logSeguritatea($conn, "PRODUKTUA_EZABATU", "ID: $produktu_id", $_SESSION['usuario_id']);
            } else {
                $errorea = "Produktua ezabatzean errore bat egon da.";
            }
        }
    }
}

$produktuak = Produktua::lortuGuztiak($conn);
$csrf_token = Seguritatea::generateCSRFToken();

// Generate encoded page names
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
    <title>Produktuak - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <h2>🏭 <?php echo EMPRESA_IZENA; ?></h2>
        </div>
        <div class="navbar-menu">
            <a href="<?php echo $dashboardEncoded; ?>.php" class="nav-link">📊 Dashboard</a>
            <a href="<?php echo $langileakEncoded; ?>.php" class="nav-link">👥 Langileak</a>
            <a href="<?php echo $produktuakEncoded; ?>.php" class="nav-link active">📦 Produktuak</a>
            <a href="<?php echo $salmentakEncoded; ?>.php" class="nav-link">💰 Salmentak</a>
            <a href="<?php echo $nireSalmentakEncoded; ?>.php" class="nav-link">📋 Nire salmentak</a>
            <span class="navbar-user">
                <?php echo htmlspecialchars($usuario_datos['izena'] . " " . $usuario_datos['abizena']); ?>
            </span>
            <a href="../logout.php" class="nav-link logout">🚪 Itxi saioa</a>
        </div>
    </div>

    <div class="container">
        <h1>📦 Produktuak kudeaketa</h1>

        <?php if ($arrakasta): ?>
            <div class="alert alert-success">✓ <?php echo $arrakasta; ?></div>
        <?php endif; ?>

        <?php if ($errorea): ?>
            <div class="alert alert-error">⚠ <?php echo $errorea; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>➕ Produktua gehitu</h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Izena*</label>
                    <input type="text" name="izena" required>
                </div>

                <div class="form-group">
                    <label>Prezioa*</label>
                    <input type="number" name="prezioa" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Kategoria</label>
                    <input type="text" name="kategoria">
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" value="0">
                </div>

                <div class="form-group" style="grid-column: 1/-1;">
                    <label>Deskripzioa</label>
                    <textarea name="deskripzioa" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="grid-column: 1/-1;">Sortu</button>
            </form>
        </div>

        <div class="table-section">
            <h2>Produktuak zerrendatua</h2>
            
            <?php if (count($produktuak) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Izena</th>
                            <th>Kategoria</th>
                            <th>Prezioa</th>
                            <th>Stock</th>
                            <th>Ekintzak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produktuak as $produktua): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produktua['izena']); ?></td>
                                <td><?php echo htmlspecialchars($produktua['kategoria'] ?? '-'); ?></td>
                                <td><?php echo number_format($produktua['prezioa'], 2); ?>€</td>
                                <td><?php echo $produktua['stock']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="produktu_id" value="<?php echo $produktua['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Ziur zaude?')">Ezabatu</button>
                                    </form>
                                    <?php
                                    $encodedId = ($hashids !== null) ? $hashids->encode($produktua['id']) : $produktua['id'];
                                    echo '<a href="produktu_edit.php?ref=' . htmlspecialchars($encodedId) . '">Editatu</a>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Ez dago produkturik.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
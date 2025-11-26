<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

global $hashids;  // Access global Hashids

// Do not call session_start(); bootstrap already started the session
if (empty($_SESSION['usuario_id'])) {
    $home = function_exists('page_link') ? page_link(9, 'home') : '/index.php';
    redirect_to($home);
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
        $prezioa = floatval($_POST['prezioa' ?? 0]);
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

// Generate CSRF only if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

// Replace encoded page generation with helper links
$dashboardLink    = function_exists('page_link') ? page_link(1, 'dashboard') : '/dashboard.php';
$langileakLink    = function_exists('page_link') ? page_link(2, 'langileak') : '/langileak.php';
$produktuakLink   = function_exists('page_link') ? page_link(3, 'produktuak') : '/produktuak.php';
$salmentakLink    = function_exists('page_link') ? page_link(4, 'salmentak') : '/salmentak.php';
$nireSalmentakLink= function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/nire_salmentak.php';
$profileLink      = function_exists('page_link') ? page_link(6, 'profile') : '/profile.php';
$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
$active = 'produktuak';
include __DIR__ . '/partials/navbar.php';
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
                                    $encodedId = function_exists('encode_id') ? encode_id((int)$produktua['id']) : (int)$produktua['id'];
                                    echo '<a href="' . htmlspecialchars($produktuakLink) . '?ref=' . htmlspecialchars($encodedId) . '">Editatu</a>';
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
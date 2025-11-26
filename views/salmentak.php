<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

global $hashids;  // Access global Hashids

// Remove session_start(); already started
if (!isset($_SESSION['usuario_id'])) {
    $home = function_exists('page_link') ? page_link(9, 'home') : '/index.php';
    redirect_to($home);
}

// Do not regenerate CSRF on POST; only on first render
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

$langilea = Langilea::lortuGuztiak($conn);
$produktuak = Produktua::lortuGuztiak($conn);

$errorea = "";
$arrakasta = "";

// Salmenta gehitzea - ERRORKO KUTXA eta TRY/CATCH gehitu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        // DEBUG (temporal): aktibatu pantailako erroreak behar izanez gero
        // ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errorea = "Segurtasun-errorea (CSRF).";
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "salmentak:add", $_SESSION['usuario_id'] ?? null);
        } else {
            // Sarrerak garbitu / cast
            $langile_id = intval($_POST['langile_id'] ?? 0);
            $produktu_id = intval($_POST['produktu_id'] ?? 0);
            $kantitatea = intval($_POST['kantitatea'] ?? 0);
            $prezioa_unitarioa = floatval($_POST['prezioa_unitarioa'] ?? 0.0);
            $bezeroa_izena = trim($_POST['bezeroa_izena'] ?? '');
            $bezeroa_nif = trim($_POST['bezeroa_nif'] ?? '');
            $bezeroa_telefonoa = trim($_POST['bezeroa_telefonoa'] ?? '');
            $oharra = trim($_POST['oharra'] ?? '');

            // Baliozkotzeak
            if ($langile_id <= 0 || $produktu_id <= 0 || $kantitatea <= 0 || $prezioa_unitarioa <= 0) {
                $errorea = "Eremu guztiak behar bezala bete behar dira.";
            } else {
                // Produktua bilatu
                $produktu = null;
                if (function_exists('Produktua::lortuIdAgatik') || method_exists('Produktua', 'lortuIdAgatik')) {
                    $produktu = Produktua::lortuIdAgatik($conn, $produktu_id);
                } else {
                    // fallback: simple query
                    $stmt = $conn->prepare("SELECT * FROM produktua WHERE id = ? LIMIT 1");
                    $stmt->bind_param("i", $produktu_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $produktu = $res->fetch_assoc() ?: null;
                    if ($stmt) $stmt->close();
                }

                if (empty($produktu)) {
                    $errorea = "Produktua ez da aurkitu.";
                } elseif (($produktu['stock'] ?? 0) < $kantitatea) {
                    $errorea = "Stock nahikoa ez dago. Aukeran: " . intval($produktu['stock'] ?? 0);
                } else {
                    // Segurtasun: Salmenta klasea existitzen dela egiaztatu
                    if (!class_exists('Salmenta')) {
                        throw new Exception("Klasea Salmenta ez da kargatuta.");
                    }

                    // Sortu objektua eta exekutatu
                    $salmenta = new Salmenta($langile_id, $produktu_id, $kantitatea, $prezioa_unitarioa, $bezeroa_izena, $bezeroa_nif, $bezeroa_telefonoa, $oharra);

                    if (!method_exists($salmenta, 'sortu')) {
                        throw new Exception("Salmenta::sortu metodoa ez dago.");
                    }

                    $ok = $salmenta->sortu($conn);

                    if ($ok) {
                        // Stocka murriztu (model edo fallback)
                        if (method_exists('Produktua', 'murriztuStocka')) {
                            Produktua::murriztuStocka($conn, $produktu_id, $kantitatea);
                        } else {
                            $stmt = $conn->prepare("UPDATE produktua SET stock = stock - ? WHERE id = ?");
                            $stmt->bind_param("ii", $kantitatea, $produktu_id);
                            $stmt->execute();
                            if ($stmt) $stmt->close();
                        }

                        $arrakasta = "Salmenta sortu da behar bezala.";
                        Seguritatea::logSeguritatea($conn, "SALMENTA_SORTU", "Produktua: $produktu_id | Kantitatea: $kantitatea", $_SESSION['usuario_id'] ?? null);
                    } else {
                        $errorea = "Salmenta sortzean errore bat egon da (DB).";
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // Erregistroatu PHP errorea eta erakutsi mezua seguruenik lagungarria izan daiteke debug-ean
        error_log("ERROR salmentak:add - " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        $errorea = "Zerbitzari-errore bat gertatu da. Begiratu error.log fitxategia.";
        // Bistaratzen baduzu garapen-ingurunean, deskomentatu:
        // $errorea .= " (" . $e->getMessage() . ")";
    }
}

// Salmentatan bilaketa
$data_hasiera = $_POST['data_hasiera'] ?? date('Y-m-01');
$data_bukaera = $_POST['data_bukaera'] ?? date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'search') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea = "Segurtasun-errorea (CSRF).";
    } else {
        $hasiera_dt = $data_hasiera . ' 00:00:00';
        $bukaera_dt = $data_bukaera . ' 23:59:59';
        $salmentak = Salmenta::lortuDataTarteAn($conn, $hasiera_dt, $bukaera_dt);
    }
} else {
    $salmentak = Salmenta::lortuGuztiak($conn);
}

// Generate router-safe links via helpers from bootstrap
$dashboardLink    = function_exists('page_link') ? page_link(1, 'dashboard') : '/dashboard.php';
$langileakLink    = function_exists('page_link') ? page_link(2, 'langileak') : '/langileak.php';
$produktuakLink   = function_exists('page_link') ? page_link(3, 'produktuak') : '/produktuak.php';
$salmentakLink    = function_exists('page_link') ? page_link(4, 'salmentak') : '/salmentak.php';
$nireSalmentakLink= function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/nire_salmentak.php';
$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
$active = 'salmentak';
include __DIR__ . '/partials/navbar.php';

?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmentak - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="container">
        <h1>💰 Salmentak kudeaketa</h1>

        <?php if ($arrakasta): ?>
            <div class="alert alert-success">✓ <?php echo $arrakasta; ?></div>
        <?php endif; ?>

        <?php if ($errorea): ?>
            <div class="alert alert-error">⚠ <?php echo $errorea; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>➕ Salmenta gehitu</h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Langilea*</label>
                    <select name="langile_id" required>
                        <option value="">-- Aukeratu --</option>
                        <?php foreach ($langilea as $lang): ?>
                            <option value="<?php echo $lang['id']; ?>">
                                <?php echo htmlspecialchars($lang['izena'] . " " . $lang['abizena']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Produktua*</label>
                    <select name="produktu_id" required onchange="eguneratuPrezioa(this)">
                        <option value="">-- Aukeratu --</option>
                        <?php foreach ($produktuak as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" data-prezioa="<?php echo $prod['prezioa']; ?>">
                                <?php echo htmlspecialchars($prod['izena']) . " (" . $prod['stock'] . " stock)"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kantitatea*</label>
                    <input type="number" name="kantitatea" min="1" required>
                </div>

                <div class="form-group">
                    <label>Prezioa unitarioa*</label>
                    <input type="number" id="prezioa_unitarioa" name="prezioa_unitarioa" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Bezeroaren izena</label>
                    <input type="text" name="bezeroa_izena">
                </div>

                <div class="form-group">
                    <label>Bezeroaren NIF</label>
                    <input type="text" name="bezeroa_nif">
                </div>

                <div class="form-group">
                    <label>Bezeroaren telefonoa</label>
                    <input type="tel" name="bezeroa_telefonoa">
                </div>

                <div class="form-group" style="grid-column: 1/-1;">
                    <label>Oharra</label>
                    <textarea name="oharra" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="grid-column: 1/-1;">Sortu</button>
            </form>
        </div>

        <div class="search-section">
            <h2>🔍 Bilaketa</h2>
            <form method="POST" class="search-form">
                <input type="hidden" name="action" value="search">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label>Hasiera data</label>
                    <input type="date" name="data_hasiera" value="<?php echo $data_hasiera; ?>">
                </div>

                <div class="form-group">
                    <label>Bukaera data</label>
                    <input type="date" name="data_bukaera" value="<?php echo $data_bukaera; ?>">
                </div>

                <button type="submit" class="btn btn-secondary">Bilatu</button>
            </form>
        </div>

        <div class="table-section">
            <h2>Salmentak zerrendatua</h2>
            
            <?php if (count($salmentak) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Langilea</th>
                            <th>Produktua</th>
                            <th>Kantitatea</th>
                            <th>Prezioa Unitarioa</th>
                            <th>Guztira</th>
                            <th>Bezeroa</th>
                            <th>Data</th>
                            <th>Ekintza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salmentak as $salmenta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($salmenta['izena'] . " " . $salmenta['abizena']); ?></td>
                                <td><?php echo htmlspecialchars($salmenta['produktu_izena']); ?></td>
                                <td><?php echo $salmenta['kantitatea']; ?></td>
                                <td><?php echo number_format($salmenta['prezioa_unitarioa'], 2); ?>€</td>
                                <td><strong><?php echo number_format($salmenta['prezioa_totala'], 2); ?>€</strong></td>
                                <td><?php echo htmlspecialchars($salmenta['bezeroa_izena'] ?? '-'); ?></td>
                                <td><?php echo $salmenta['data_salmenta']; ?></td>
                                <td>
                                    <?php
                                    $encodedId = function_exists('encode_id') ? encode_id((int)$salmenta['id']) : (int)$salmenta['id'];
                                    echo '<a href="salmenta_detalle.php?ref=' . htmlspecialchars($encodedId) . '">Ikusi</a>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="summary-section">
                    <h3>Laburpena</h3>
                    <p><strong>Salmentak guztira: <?php echo number_format(array_sum(array_column($salmentak, 'prezioa_totala')), 2); ?>€</strong></p>
                    <p>Transakzioak: <?php echo count($salmentak); ?></p>
                </div>
            <?php else: ?>
                <p class="no-data">Ez dago salmentarik.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function eguneratuPrezioa(select) {
        const prezioa = select.options[select.selectedIndex].getAttribute('data-prezioa');
        document.getElementById('prezioa_unitarioa').value = prezioa;
    }
    </script>
</body>
</html>


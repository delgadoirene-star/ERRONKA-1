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
Seguritatea::egiaztaSesioa();

$mensajea = "";
$errorea = "";

// Produktu guztiak lortu
$produktuak = Produktua::lortuGuztiak($conn);

// Langilearen ID lortu
$langile_info = null;
$sql = "SELECT l.id FROM langilea l JOIN usuario u ON l.usuario_id = u.id WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();
$langile_info = $result->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF egiaztatzea
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errorea = "Segurtasun-errorea.";
    } else {
        $produktu_id = intval($_POST['produktu_id'] ?? 0);
        $kantitatea = intval($_POST['kantitatea'] ?? 0);
        $bezeroa_izena = trim($_POST['bezeroa_izena'] ?? '');
        $bezeroa_telefono = trim($_POST['bezeroa_telefono'] ?? '');

        // Balioztapena
        if ($produktu_id <= 0 || $kantitatea <= 0) {
            $errorea = "Balioak ez diren zuzenak.";
        } elseif (empty($bezeroa_izena)) {
            $errorea = "Bezeroan izena ezin daiteke hutsik egon.";
        } else {
            // Produktua lortu
            $produktua = Produktua::lortuIdAgatik($conn, $produktu_id);
            
            if (!$produktua) {
                $errorea = "Produktua ez da aurkitu.";
            } elseif ($produktua['stock'] < $kantitatea) {
                $errorea = "Stock ez da aski. Stock duzue: " . $produktua['stock'];
            } else {
                // Salmenta sortzea
                $salmenta = new Salmenta(
                    $langile_info['id'],
                    $produktu_id,
                    $kantitatea,
                    $produktua['prezioa'],
                    $bezeroa_izena,
                    '',
                    $bezeroa_telefono
                );
                
                if ($salmenta->sortu($conn)) {
                    // Stocka eguneratzea
                    Produktua::eguneratuStocka($conn, $produktu_id, $kantitatea);
                    
                    $mensajea = "Salmenta egoki sortu da!";
                } else {
                    $errorea = "Errorea salmenta sortzean.";
                }
            }
        }
    }
}

// CSRF token sortzea
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate encoded page names
$dashboardEncoded = ($hashids !== null) ? $hashids->encode(1) : 'dashboard';
$salmentaBerriaEncoded = ($hashids !== null) ? $hashids->encode(7) : 'salmenta_berria';
$nireSalmentakEncoded = ($hashids !== null) ? $hashids->encode(8) : 'nire_salmentak';
$profileEncoded = ($hashids !== null) ? $hashids->encode(9) : 'profile';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmenta Berria - Xabala Enpresen Plataforma</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <h2>🏭 Xabala</h2>
            </div>
            <ul class="navbar-menu">
                <li><a href="<?php echo $dashboardEncoded; ?>.php">Dashboard</a></li>
                <li><a href="<?php echo $salmentaBerriaEncoded; ?>.php" class="active">Salmenta Berria</a></li>
                <li><a href="<?php echo $nireSalmentakEncoded; ?>.php">Nire Salmentak</a></li>
                <li><a href="<?php echo $profileEncoded; ?>.php">👤 <?php echo htmlspecialchars($_SESSION['usuario_izena']); ?></a></li>
                <li><a href="./logout.php">Atera</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Salmenta Berria</h1>
            <p>Erregistratu bezeroarentzat salmenta berria</p>
        </div>

        <?php if ($mensajea): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($mensajea); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorea): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($errorea); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label for="produktu_id">Produktua*</label>
                    <select id="produktu_id" name="produktu_id" required onchange="eguneratuPrezioa()">
                        <option value="">Hautatu produktua...</option>
                        <?php foreach ($produktuak as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" data-prezioa="<?php echo $prod['prezioa']; ?>" data-stock="<?php echo $prod['stock']; ?>">
                                <?php echo htmlspecialchars($prod['izena']); ?> (Stock: <?php echo $prod['stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="kantitatea">Kantitatea*</label>
                        <input type="number" id="kantitatea" name="kantitatea" min="1" required onchange="eguneratuPrezioa()">
                    </div>

                    <div class="form-group">
                        <label for="prezioa_totala">Prezioa totala</label>
                        <input type="text" id="prezioa_totala" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bezeroa_izena">Bezeroan izena*</label>
                    <input type="text" id="bezeroa_izena" name="bezeroa_izena" required>
                </div>

                <div class="form-group">
                    <label for="bezeroa_telefono">Bezeroan telefonoa</label>
                    <input type="text" id="bezeroa_telefono" name="bezeroa_telefono">
                </div>

                <button type="submit" class="btn btn-primary">Salmenta Erregistratu</button>
            </form>
        </div>
    </div>

    <script>
    function eguneratuPrezioa() {
        const select = document.getElementById('produktu_id');
        const kantitatea = document.getElementById('kantitatea').value;
        const prezioa_totala = document.getElementById('prezioa_totala');

        if (select.value && kantitatea) {
            const option = select.options[select.selectedIndex];
            const prezioa = parseFloat(option.dataset.prezioa);
            const total = (prezioa * kantitatea).toFixed(2);
            prezioa_totala.value = '€' + total;
        }
    }
    </script>
</body>
</html>
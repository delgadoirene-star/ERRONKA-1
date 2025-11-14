<?php
// DEBUG temporal: mostrar errores (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/konexioa.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/model/usuario.php';
require_once __DIR__ . '/model/langilea.php';
require_once __DIR__ . '/model/seguritatea.php';

session_start();
session_regenerate_id(true);

$errorea = "";
$arrakasta = false;

// CSRF token sortzea
$csrf_token = Seguritatea::generateCSRFToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token egiaztatzea
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea = "Segurtasun-errorea. Saioa berrezarri eta saiatu berriro.";
        Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Erregistro orrialdean", null);
    } else {
        $izena = trim($_POST['izena'] ?? '');
        $abizena = trim($_POST['abizena'] ?? '');
        $nan = trim($_POST['nan'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $departamendua = trim($_POST['departamendua'] ?? '');
        $pozisio = trim($_POST['pozisio'] ?? '');

        // Email balioztapena
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorea = "Emaila ez da zuzena.";
        }
        // NAN balioztapena
        elseif (!preg_match('/^\d{8}[A-Z]$/', strtoupper($nan))) {
            $errorea = "NANa ez da zuzena (adibidea: 12345678A).";
        }
        // Pasahitzaren balioztapena
        elseif ($pasahitz_erroak = Seguritatea::balioztaPasahitza($password)) {
            $errorea = implode("<br>", $pasahitz_erroak);
        }
        elseif (!$izena || !$abizena || !$nan || !$email || !$password || !$password2) {
            $errorea = "Eremu guztiak bete behar dira.";
        }
        elseif ($password !== $password2) {
            $errorea = "Pasahitzak ez datoz bat.";
        }
        else {
            // Erabiltzailea jada badagoen egiaztatzea
            $erabiltzailea = Usuario::lortuEmailEdoNANegatik($conn, $email, $nan);
            
            if ($erabiltzailea !== null) {
                $errorea = "Email edo NAN hori jada erregistratuta dago.";
                Seguritatea::logSeguritatea($conn, "REGISTRO_DUPLICATE", $email, null);
            } else {
                // Pasahitza enkriptatzea
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $user = explode('@', $email)[0];
                
                // Erabiltzailea sortzea
                $usuario = new Usuario($izena, $abizena, $nan, $email, $user, $hash, 'langilea');
                
                if ($usuario->sortu($conn)) {
                    // Langilea sortzea
                    $langilea = new Langilea($conn->insert_id, $departamendua, $pozisio);
                    
                    if ($langilea->sortu($conn)) {
                        $arrakasta = true;
                        Seguritatea::logSeguritatea($conn, "REGISTRO_EXITOSO", $email, $usuario->getId());
                    } else {
                        $errorea = "Errorea langilea sortzean.";
                        Seguritatea::logSeguritatea($conn, "REGISTRO_ERROR_LANGILEA", $email, $usuario->getId());
                    }
                } else {
                    $errorea = "Errorea erabiltzailea sortzean.";
                    Seguritatea::logSeguritatea($conn, "REGISTRO_ERROR_USUARIO", $email, null);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erregistratu - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="register-page">
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>🍪 Erregistratu</h1>
                <p>Sortu zure kontua <?php echo EMPRESA_IZENA; ?>-n</p>
            </div>

            <?php if ($arrakasta): ?>
                <div class="alert alert-success">
                    <strong>✓ Ongietorri!</strong><br>
                    Zure kontua sortu da behar bezala. <a href="index.php">Saioa hasteko klik egin</a>
                </div>
            <?php elseif ($errorea): ?>
                <div class="alert alert-error">
                    <strong>⚠ Errore:</strong><br>
                    <?php echo htmlspecialchars($errorea); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="register-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="izena">Izena*</label>
                        <input type="text" id="izena" name="izena" required 
                               value="<?php echo htmlspecialchars($_POST['izena'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="abizena">Abizena*</label>
                        <input type="text" id="abizena" name="abizena" required 
                               value="<?php echo htmlspecialchars($_POST['abizena'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nan">NAN* (adibidea: 12345678A)</label>
                        <input type="text" id="nan" name="nan" required placeholder="12345678A"
                               value="<?php echo htmlspecialchars($_POST['nan'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departamendua">Departamendua</label>
                        <input type="text" id="departamendua" name="departamendua"
                               value="<?php echo htmlspecialchars($_POST['departamendua'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pozisio">Pozisioa</label>
                        <input type="text" id="pozisio" name="pozisio"
                               value="<?php echo htmlspecialchars($_POST['pozisio'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Pasahitza* (gutxienez <?php echo PASSWORD_MIN_LENGTH; ?> karaktere)</label>
                        <input type="password" id="password" name="password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        <small>Maiuskulak, minuskulak, zenbakiak eta karaktere espezialaak behar ditu</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password2">Pasahitza errepikatu*</label>
                        <input type="password" id="password2" name="password2" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Erregistratu</button>
            </form>

            <div class="register-footer">
                <p>Jada duzu kontua? <a href="index.php">Saioa hasi</a></p>
            </div>
        </div>
    </div>
</body>
</html>
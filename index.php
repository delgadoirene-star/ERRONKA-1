<?php
/**
 * Login orria - ERRONKA-1
 * filepath: c:\xampp\htdocs\ariketak\ERRONKA-1 (IGAI)\ERRONKA-1\index.php
 */

// ===== ERROR REPORTING =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ===== KONEXIOA =====
try {
    require_once "config/konexioa.php";
    require_once "config/config.php";
} catch (Exception $e) {
    die("⚠️ Konfigurazioak kargan errorea: " . $e->getMessage());
}

// ===== KLASEAK =====
try {
    require_once "model/seguritatea.php";
    require_once "model/usuario.php";
} catch (Exception $e) {
    die("⚠️ Klaseak kargan errorea: " . $e->getMessage());
}

// ===== SESIOA =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}

// Jada saioan badago - redirectiona
if (isset($_SESSION['usuario_id'])) {
    header("Location: views/dashboard.php");
    exit;
}

// ===== ALDAGAIAK =====
$errorea = "";
$csrf_token = Seguritatea::generateCSRFToken();

// ===== LOGIN PROZESU =====
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // CSRF egiaztatzea
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errorea = "Segurtasun-errorea (CSRF).";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Eremu balioztapena
            if (empty($email) || empty($password)) {
                $errorea = "Email eta pasahitza bete behar dira.";
            } 
            // Rate limiting
            elseif (!Seguritatea::egiaztaLoginIntentoa($email)) {
                $errorea = "Saioak demasiado gehiak. Itxaron 15 minutu.";
            } 
            // Autentifikazioa
            else {
                $resultado = Seguritatea::egiaztaAutentifikazioa($conn, $email, $password);
                
                if ($resultado) {
                    // Login arrakastatsua
                    Seguritatea::zuritu_login_intentoak($email);
                    $_SESSION['usuario_id'] = $resultado['id'];
                    $_SESSION['usuario_rol'] = $resultado['rol'];
                    $_SESSION['last_activity'] = time();
                    
                    Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", $email, $resultado['id']);
                    
                    header("Location: views/dashboard.php");
                    exit;
                } else {
                    $errorea = "Email edo pasahitz okerra.";
                    Seguritatea::logSeguritatea($conn, "LOGIN_FALLO", $email, null);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $errorea = "Errorea login-ean. Saiatu berriro.";
    }
}
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saioa hasi - <?php echo htmlspecialchars(EMPRESA_IZENA); ?></title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <img src="style/img/zabala_logo.png" class="logo" alt="Zabala Logo">
             <!--<div class="login-header">
                
               <h1><?php echo htmlspecialchars(EMPRESA_IZENA); ?></h1>
                <p>Saioa hasi zure kontuan</p>
            </div>-->

            <?php if ($errorea): ?>
                <div class="alert alert-error">
                    <strong>⚠ Errorea:</strong> <?php echo htmlspecialchars($errorea); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="zure@email.eus">
                </div>

                <div class="form-group">
                    <label for="password">Pasahitza</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Zure pasahitza">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Saioa hasi</button>
            </form>

            <div class="login-footer">
                <p>Ez duzu kontua? <a href="signin.php">Erregistratu hemen</a></p>
            </div>
        </div>
    </div>
</body>
</html>
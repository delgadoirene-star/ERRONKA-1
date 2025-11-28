<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/model/usuario.php';
require_once __DIR__ . '/model/langilea.php';

$errorea = "";
$arrakasta = false;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

global $db_ok;
global $conn;
if (empty($db_ok)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("Signin attempt blocked: DB not available");
        $errorea = "Datu-basea momentuz ez dago eskuragarri. Saiatu berriro minuto batzuen buruan.";
    }
} 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($db_ok)) {
    $postedToken = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $izena = trim($_POST['izena'] ?? '');
    $abizena = trim($_POST['abizena'] ?? '');
    $nan = strtoupper(trim($_POST['nan'] ?? ''));
    // Auto-generate unique username from email (part before @)
    $user = !empty($email) ? explode('@', $email)[0] : '';
    // If user already exists, append random suffix to make it unique
    if (!empty($user) && !empty($conn)) {
        $checkUser = $user;
        $suffix = 0;
        while (true) {
            $stmt = $conn->prepare("SELECT id FROM usuario WHERE user = ?");
            $stmt->bind_param("s", $checkUser);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $user = $checkUser;
                $stmt->close();
                break;
            }
            $stmt->close();
            $suffix++;
            $checkUser = $user . $suffix;
        }
    }
    $password = (string)($_POST['password'] ?? '');
    $departamendua = trim($_POST['departamendua'] ?? '');
    $pozisio = trim($_POST['pozisio'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');
    
    $honeypot = trim($_POST['website'] ?? '');

    error_log("Signin attempt: email={$email}, csrf_token=" . ($postedToken ?: 'none'));

    if (!empty($honeypot)) {
        error_log("Bot detected in signup: honeypot filled for {$email}");
        if (!empty($conn)) {
            Seguritatea::logSeguritatea($conn, "BOT_SIGNUP_BLOCKED", $email, null);
        }
        sleep(2);
        $errorea = "Errorea erregistroan. Saiatu berriro.";
    } elseif (!Seguritatea::verifyCSRFToken($postedToken)) {
        error_log("CSRF failed in signin for {$email}");
        $errorea = "Segurtasun-errorea. Saiatu berriro.";
        if (!empty($conn)) {
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Erregistro orrialdean", null);
        }
    } elseif (!Seguritatea::egiaztaRateLimit('signup', $email, 3)) {
        $errorea = "Saiakera gehiegi. Itxaron 15 minutu.";
        if (!empty($conn)) {
            Seguritatea::logSeguritatea($conn, "SIGNUP_RATE_LIMIT", $email, null);
        }
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorea = "Email formatu okerra.";
    } elseif (empty($izena) || empty($abizena)) {
        $errorea = "Izena eta abizena beharrezkoak dira.";
    } elseif (!preg_match('/^[0-9]{8}[A-Z]$/', $nan)) {
        $errorea = "NAN formatu okerra (adibidea: 12345678A).";
    } elseif (!Seguritatea::balioztaPasahitza($password)) {
        $errorea = "Pasahitza ahula da.";
    } elseif (Usuario::lortuEmailAgatik($conn, $email)) {
        $errorea = "Emaila dagoeneko erregistratuta.";
    } elseif ($password !== $password2) {
        $errorea = "Pasahitzak ez datoz bat.";
    } else {
        $usuario = new Usuario($izena, $abizena, $nan, $email, $user, $password);
        if ($usuario->sortu($conn)) {
            $langilea = new Langilea($usuario->getId(), $departamendua, $pozisio);
            $langilea->sortu($conn);
            Seguritatea::zuritu_rate_limit('signup', $email);
            $_SESSION['usuario_id'] = $usuario->getId();
            $_SESSION['usuario'] = ['admin' => false, 'email' => $email];
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
            $csrf_token = $_SESSION['csrf_token'];
            if (!empty($conn)) {
                Seguritatea::logSeguritatea($conn, "USER_REGISTERED", $email, $usuario->getId());
            }
            $arrakasta = true;
        } else {
            $errorea = "Ezin izan da erabiltzailea sortu.";
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
    <link rel="stylesheet" href="/public/assets/style.css">
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
                
                <div style="position:absolute;left:-5000px;" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

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
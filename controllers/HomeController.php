<?php
try {
    require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
    require_once __DIR__ . '/../model/seguritatea.php';
    require_once __DIR__ . '/../model/usuario.php';
} catch (Exception $e) {
    die("⚠️ Error cargando configuración: " . htmlspecialchars($e->getMessage()));
}

global $hashids;  // Access global Hashids

// Redirect if logged in
if (isset($_SESSION['usuario_id'])) {
    $target = ($hashids !== null) ? ('/' . $hashids->encode(1) . '.php') : '/';
    header("Location: $target");
    exit;
}

// Variables for view
$errorea = "";
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF against session; do not regenerate here
        $posted = $_POST['csrf_token'] ?? '';
        if (!Seguritatea::verifyCSRFToken($posted)) {
            $errorea = "Segurtasun-errorea.";
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Hasiera orrialdean", null);
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $errorea = "Email eta pasahitza bete behar dira.";
            } elseif (!Seguritatea::egiaztaLoginIntentoa($email)) {
                $errorea = "Saioak demasiado gehiak. Itxaron 15 minutu.";
                Seguritatea::logSeguritatea($conn, "LOGIN_BLOQUEADO", $email, null);
            } else {
                $resultado = Seguritatea::egiaztautentifikazioa($conn, $email, $password);
                error_log("Login result: " . print_r($resultado, true));  // Move here
                    
                if ($resultado) {
                    error_log("Login success for $email");
                    Seguritatea::zuritu_login_intentoak($email);
                    $_SESSION['usuario_id'] = $resultado['id'];
                    $_SESSION['usuario_rol'] = $resultado['rol'];
                    $_SESSION['last_activity'] = time();
                    // Prevent fixation and rotate CSRF
                    session_regenerate_id(true);
                    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();

                    Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", $email, $resultado['id']);

                    $target = ($hashids !== null) ? ('/' . $hashids->encode(1) . '.php') : '/';
                    header("Location: $target");
                    exit;
                } else {
                    error_log("Login failed for $email: invalid credentials");
                    $errorea = "Email edo pasahitza okerra.";
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log("Login error: " . $e->getMessage());
    $errorea = "Errorea login-ean. Saiatu berriro.";
}

// Handle ref parameter
$ref = $_GET['ref'] ?? '';
$realId = null;
if (!empty($ref) && $hashids !== null) {
    $decoded = $hashids->decode($ref);
    $realId = $decoded[0] ?? null;
}
if (!$realId) {
    // Optional: Handle invalid ref, e.g., redirect or set default
}

// No longer render view here; handled in index.php
?>
<?php
// filepath: c:\xampp\htdocs\ariketak\ERRONKA-1_IGAI\ERRONKA-1\controllers/HomeController.php
ini_set('display_errors', 1);  // Temporary for debugging
// ... remove after testing ...
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
    header('Location: views/dashboard.php');
    exit;
}

// Variables for view
$errorea = "";
$csrf_token = Seguritatea::generateCSRFToken();

// Process POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Login attempt: email={$_POST['email']}, csrf_token=" . ($_POST['csrf_token'] ?? 'none'));
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errorea = "Segurtasun-errorea (CSRF).";
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "index:login", null);
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
                    
                    Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", $email, $resultado['id']);
                    
                    header('Location: views/dashboard.php');
                    exit;
                } else {
                    error_log("Login failed for $email: invalid credentials");
                    $errorea = "Email edo pasahitza okerra.";
                    Seguritatea::logSeguritatea($conn, "LOGIN_FALLIDO", $email, null);
                }
            }
        }
    } catch (Throwable $e) {
        error_log("Login error: " . $e->getMessage());
        $errorea = "Errorea login-ean. Saiatu berriro.";
    }
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

// Render view
require_once __DIR__ . '/../views/home.php';
?>
<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../config/konexioa.php';
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../model/seguritatea.php';
    require_once __DIR__ . '/../model/usuario.php';
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (Exception $e) {
    die("⚠️ Error cargando configuración: " . htmlspecialchars($e->getMessage()));
}

use Hashids\Hashids;
$hashids = new Hashids('ZAB_IGAI_PLAT_GEN', 8);

// Iniciar sesión temprano
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}

// Si ya tiene sesión activa, redirige al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: views/dashboard.php');
    exit;
}

// Variables para la vista (generadas antes de render)
$errorea = "";
$csrf_token = Seguritatea::generateCSRFToken();

// Procesar formulario POST de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar CSRF token
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errorea = "Segurtasun-errorea (CSRF).";
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "index:login", null);
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validar campos vacíos
            if (empty($email) || empty($password)) {
                $errorea = "Email eta pasahitza bete behar dira.";
            }
            // Validar rate limiting
            elseif (!Seguritatea::egiaztaLoginIntentoa($email)) {
                $errorea = "Saioak demasiado gehiak. Itxaron 15 minutu.";
                Seguritatea::logSeguritatea($conn, "LOGIN_BLOQUEADO", $email, null);
            }
            // Autenticar usuario
            else {
                $resultado = Seguritatea::egiaztaAutentifikazioa($conn, $email, $password);
                
                if ($resultado) {
                    // Login exitoso
                    Seguritatea::zuritu_login_intentoak($email);
                    $_SESSION['usuario_id'] = $resultado['id'];
                    $_SESSION['usuario_rol'] = $resultado['rol'];
                    $_SESSION['last_activity'] = time();
                    
                    Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", $email, $resultado['id']);
                    
                    header('Location: views/dashboard.php');
                    exit;
                } else {
                    // Login fallido
                    $errorea = "Email edo pasahitz okerra.";
                    Seguritatea::logSeguritatea($conn, "LOGIN_FALLO", $email, null);
                }
            }
        }
    } catch (Throwable $e) {
        error_log("HomeController POST error: " . $e->getMessage());
        $errorea = "Errorea login-ean. Saiatu berriro.";
    }
}

// Example: If receiving a ref parameter
$ref = $_GET['ref'] ?? '';
$decoded = $hashids->decode($ref);
$realId = $decoded[0] ?? null;
if (!$realId) { header("Location: dashboard.php"); exit; }
// Use $realId in queries

// Renderizar la vista al final, con $errorea y $csrf_token ya definidos
require_once __DIR__ . '/../views/home.php';
?>
<?php

try {
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/model/seguritatea.php';
    require_once __DIR__ . '/model/usuario.php';
} catch (Exception $e) {
    die("⚠️ Error cargando configuración: " . htmlspecialchars($e->getMessage()));
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
    header("X-XSS-Protection: 1; mode=block");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

global $hashids, $conn;

if (isset($_SESSION['usuario_id'])) {
    $target = function_exists('page_link') ? page_link(1, 'dashboard') : '/';
    redirect_to($target);
}

$errorea = "";
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $posted = $_POST['csrf_token'] ?? '';
        if (!Seguritatea::verifyCSRFToken($posted)) {
            $errorea = "Segurtasun-errorea.";
            if ($conn) Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Login orrialdean", null);
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $errorea = "Email eta pasahitza bete behar dira.";
            } elseif (!Seguritatea::egiaztaLoginIntentoa($email)) {
                $errorea = "Saioak gehiegi. Itxaron 15 minutu.";
                if ($conn) Seguritatea::logSeguritatea($conn, "LOGIN_BLOQUEADO", $email, null);
            } else {
                $resultado = Seguritatea::egiaztautentifikazioa($conn, $email, $password);
                    
                if ($resultado) {
                    Seguritatea::zuritu_login_intentoak($email);
                    $_SESSION['usuario_id'] = $resultado['id'];
                    $_SESSION['usuario_rol'] = $resultado['rol'];
                    $_SESSION['last_activity'] = time();
                    session_regenerate_id(true);
                    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();

                    if ($conn) Seguritatea::logSeguritatea($conn, "LOGIN_EXITOSO", $email, $resultado['id']);

                    $target = function_exists('page_link') ? page_link(1, 'dashboard') : '/';
                    redirect_to($target);
                } else {
                    $errorea = "Email edo pasahitza okerra.";
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log("Login error: " . $e->getMessage());
    $errorea = "Errorea login-ean. Saiatu berriro.";
}

require_once __DIR__ . '/views/home.php';
?>
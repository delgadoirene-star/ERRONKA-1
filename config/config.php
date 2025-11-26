<?php
/**
 * Xabala enpresaren konfigurazioa
 * filepath: c:\xampp\htdocs\ariketak\ERRONKA-1_IGAI\ERRONKA-1\config\config.php
 */

// ===== ENPRESEN DATUAK =====
define('EMPRESA_IZENA', 'Xabala Enpresak');
define('EMPRESA_DESKRIPZIOA', 'Enpresaren kudeaketa eta salmentaren sistema');

// ===== URL KONFIGURAZIOA =====
$forwardedProto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
$scheme = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = $scriptDir === '' ? '/' : $scriptDir . '/';

define('BASE_URL', $scheme . '://' . $host . $base);
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// ===== SEGURTASUN KONFIGURAZIOA (RA5, RA6, RA8) =====
define('CSRF_TOKEN_LIFETIME', 3600);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900);
define('SESSION_TIMEOUT', 1800);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// ===== LOGGING =====
define('SECURITY_LOG_FILE', __DIR__ . '/../logs/security.log');
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error.log');

// Direktorioak sortzea
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0755, true);
}

if (!is_dir(__DIR__ . '/../uploads')) {
    @mkdir(__DIR__ . '/../uploads', 0755, true);
}

// ===== ERROR REPORTING =====
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Produkzioan ez
ini_set('log_errors', '1');
ini_set('error_log', ERROR_LOG_FILE);

// ===== SEGURTASUN HEADERS =====
// Moved to bootstrap or controllers to avoid sending headers before ini_set
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
// header("X-Content-Type-Options: nosniff");
// header("X-Frame-Options: DENY");
// header("X-XSS-Protection: 1; mode=block");
// header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

?>
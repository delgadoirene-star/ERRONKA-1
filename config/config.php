<?php
/**
 * Zabala enpresaren konfigurazioa
 * filepath: c:\xampp\htdocs\ariketak\ERRONKA-1_IGAI\ERRONKA-1\config\config.php
 */

// ===== ENPRESEN DATUAK =====
define('EMPRESA_IZENA', 'Zabala Enpresak');
define('EMPRESA_DESKRIPZIOA', 'Enpresaren kudeaketa eta salmentaren sistema');

// ===== URL KONFIGURAZIOA =====
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = $scriptDir === '' ? '/' : $scriptDir . '/';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base);
define('ASSETS_URL', BASE_URL . 'public/assets/');
define('UPLOADS_URL', BASE_URL . 'storage/uploads/');
define('LOGS_PATH', __DIR__ . '/../storage/logs/');

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
define('SECURITY_LOG_FILE', LOGS_PATH . 'security.log');
define('ERROR_LOG_FILE', LOGS_PATH . 'error.log');

// Direktorioak sortzea
if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}

if (!is_dir(__DIR__ . '/../storage/uploads')) {
    @mkdir(__DIR__ . '/../storage/uploads', 0755, true);
}

// ===== ERROR REPORTING =====
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Produkzioan ez
ini_set('log_errors', '1');
ini_set('error_log', ERROR_LOG_FILE);
?>
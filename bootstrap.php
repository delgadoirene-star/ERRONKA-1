<?php
/**
 * Bootstrap file for common includes and setup.
 */

// Session settings before start
ini_set('session.cookie_secure', '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

require_once __DIR__ . '/vendor/autoload.php';

// Start session via Seguritatea
require_once __DIR__ . '/model/seguritatea.php';
Seguritatea::hasieratuSesioa();

// Load config first (defines constants used by views) then attempt DB connection
require_once __DIR__ . '/config/config.php';

// Attempt to load DB connection but never let a thrown exception stop bootstrap.
// konexioa.php will normally initialize $conn or set it to null on failure
try {
    require_once __DIR__ . '/config/konexioa.php';
} catch (\Throwable $e) {
    error_log("bootstrap: konexioa.php failed: " . $e->getMessage());
    // Ensure $conn exists so downstream code can check and degrade gracefully
    $conn = $conn ?? null;
}

// Expose a simple boolean flag for DB availability to views/controllers
global $db_ok;
$db_ok = (isset($conn) && $conn !== null);

// Initialize Hashids globally if available
global $hashids;
$hashids = class_exists('\\Hashids\\Hashids') ? new \Hashids\Hashids('ZAB_IGAI_PLAT_GEN', 8) : null;

/* Replace faulty helpers with correct implementations */
if (!function_exists('page_link')) {
	function page_link(int $pageId, string $fallback = ''): string {
		global $hashids;
		if ($hashids !== null && class_exists('\\Hashids\\Hashids')) {
			return '/' . $hashids->encode($pageId) . '.php';
		}
		$name = $fallback ?: 'page' . $pageId;
		return '/' . ltrim($name, '/') . '.php';
	}
}

if (!function_exists('encode_id')) {
	function encode_id(int $id): string|int {
		global $hashids;
		if ($hashids !== null && class_exists('\\Hashids\\Hashids')) {
			return $hashids->encode($id);
		}
		return $id;
	}
}

if (!function_exists('redirect_to')) {
	function redirect_to(string $path): void {
		if (!headers_sent()) {
			header('Location: ' . $path);
		} else {
			echo "<script>location.href=" . json_encode($path) . ";</script>";
		}
		exit;
	}
}

if (!function_exists('current_user')) {
	function current_user(mysqli $conn): ?array {
		$uid = $_SESSION['usuario_id'] ?? null;
		if (!$uid) return null;
		if (class_exists('Usuario') && method_exists('Usuario', 'lortuIdAgatik')) {
			return Usuario::lortuIdAgatik($conn, $uid);
		}
		return null;
	}
}
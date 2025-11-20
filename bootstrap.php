<?php
/**
 * Bootstrap file for common includes and setup.
 */

// Set session ini settings before starting session
ini_set('session.cookie_secure', 0);  // 0 for localhost
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Load configurations
require_once __DIR__ . '/config/konexioa.php';
require_once __DIR__ . '/config/config.php';

// Initialize Hashids globally if available
$hashids = null;
if (class_exists('\\Hashids\\Hashids')) {
    $hashids = new \Hashids\Hashids('ZAB_IGAI_PLAT_GEN', 8);
} else {
    error_log('Hashids class not found; continuing without Hashids.');
}

// Make $hashids available globally
global $hashids;
?>
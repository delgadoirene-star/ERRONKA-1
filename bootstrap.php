<?php
/**
 * Bootstrap file for common includes and setup.
 */

// Session settings before start
ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '1' : '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');

require_once __DIR__ . '/vendor/autoload.php';

// Start session via Seguritatea
require_once __DIR__ . '/model/seguritatea.php';
Seguritatea::hasieratuSesioa();

// Load DB and config
require_once __DIR__ . '/config/konexioa.php';
require_once __DIR__ . '/config/config.php';

// Initialize Hashids globally if available
global $hashids;
$hashids = class_exists('\\Hashids\\Hashids') ? new \Hashids\Hashids('ZAB_IGAI_PLAT_GEN', 8) : null;
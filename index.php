<?php
/**
 * Home / Landing Page - ERRONKA-1
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/controllers/HomeController.php';
} catch (Exception $e) {
    die("⚠️ Error cargando HomeController: " . htmlspecialchars($e->getMessage()));
}
?>
<?php
/**
 * Home / Landing Page - ERRONKA-1
 */

try {
    require_once __DIR__ . '/bootstrap.php';
} catch (Exception $e) {
    die("⚠️ Error cargando configuración: " . htmlspecialchars($e->getMessage()));
}

// Add security headers early
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-src 'self' https://www.google.com https://www.gstatic.com;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Render view
require_once __DIR__ . '/views/home.php';
?>
<?php
/**
 * Home / Landing Page - ERRONKA-1
 */

try {
    require_once __DIR__ . '/bootstrap.php';
} catch (Exception $e) {
    error_log("Bootstrap error: " . $e->getMessage());
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-src 'self' https://www.google.com https://www.gstatic.com;");
    header("X-XSS-Protection: 1; mode=block");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Include controller to handle logic
require_once __DIR__ . '/controllers/HomeController.php';

if (isset($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/views/dashboard.php';
} else {
    // Render view
    require_once __DIR__ . '/views/home.php';
}
?>
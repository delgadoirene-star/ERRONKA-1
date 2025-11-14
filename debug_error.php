<?php
// DEBUG: mostrar todos los errores temporalmente
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Rutas absolutas seguras usando __DIR__
echo "<h3>DEBUG: comprobando includes y conexión</h3>";

// Comprueba archivos esperados
$files = [
    __DIR__ . '/config/konexioa.php',
    __DIR__ . '/config/config.php',
    __DIR__ . '/model/seguritatea.php',
    __DIR__ . '/model/usuario.php',
];

foreach ($files as $f) {
    echo "<p>Archivo: " . htmlspecialchars($f) . " -> " . (file_exists($f) ? "OK" : "<b>MISSING</b>") . "</p>";
}

// Intentar require_once y capturar errores
try {
    require_once __DIR__ . '/config/konexioa.php';
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/model/seguritatea.php';
    require_once __DIR__ . '/model/usuario.php';
    echo "<p>Includes ok</p>";
} catch (Throwable $e) {
    echo "<pre>Include error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

// Verificar conexión $conn
if (isset($conn) && $conn instanceof mysqli) {
    echo "<p>DB: conectado. Server: " . htmlspecialchars($conn->host_info) . "</p>";
} else {
    echo "<p>DB: <b>NO conectado</b></p>";
}

// Probar duplicados de clase Seguritatea
if (class_exists('Seguritatea', false)) {
    echo "<p>Clase Seguritatea ya definida (en runtime)</p>";
} else {
    echo "<p>Clase Seguritatea NO definida aún</p>";
}

echo "<p>FIN DEBUG</p>";
?>
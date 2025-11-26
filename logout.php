<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/model/seguritatea.php';

if (isset($_SESSION['usuario_id'])) {
    // Optionally log logout event
    if (isset($conn)) {
        Seguritatea::logSeguritatea($conn, "LOGOUT", "Erabiltzaileak saioa amaitu du", (int)$_SESSION['usuario_id']);
    }
}

session_unset();
session_destroy();
header("Location: index.php");
exit;
?>
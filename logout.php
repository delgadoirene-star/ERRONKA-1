<?php
require_once __DIR__ . '/config/konexioa.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/model/seguritatea.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    Seguritatea::logSeguritatea($conn, "LOGOUT", "Saioa itxi da", $_SESSION['usuario_id']);
}

session_unset();
session_destroy();
header("Location: index.php");
exit;
?>
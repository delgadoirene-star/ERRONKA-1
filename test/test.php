<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📝 PHP funtzionando: " . phpversion() . "<br>";

// Konexioa testa
require_once "../config/konexioa.php";

if ($conn) {
    echo "✅ Datu-basea konektada<br>";
} else {
    echo "❌ Datu-base errorea: " . $conn->connect_error . "<br>";
}

// Klaseak testa - BAKARRIK BEHARREZKOAK
require_once "../config/config.php";
require_once "../model/usuario.php";
// ❌ EZABATU - Usuario-k kargatzen du
// require_once "model/seguritatea.php";

echo "✅ Usuario klasea kargatua<br>";
echo "✅ Seguritatea klasea kargatua (usuario.php-tik)<br>";

echo "<br>✅ Guztia OK!";
?>
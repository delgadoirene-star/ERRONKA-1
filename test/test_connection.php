<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 TEST DIAGNOSTIKOA\n";
echo "====================\n\n";

// PHP bertsioa
echo "✓ PHP: " . phpversion() . "\n";

// Konexioa - ✅ ZUZENDU: ../config/
echo "\n📡 Konexio testa...\n";
try {
    require_once "../config/konexioa.php";
    echo "✓ Konexioa: OK\n";
    
    // Datu-basea
    $result = $conn->query("SELECT COUNT(*) as total FROM usuario");
    $row = $result->fetch_assoc();
    echo "✓ Erabiltzaileak: " . $row['total'] . "\n";
} catch (Exception $e) {
    echo "✗ Konexio errorea: " . $e->getMessage() . "\n";
}

// Config - ✅ ZUZENDU: ../config/
echo "\n⚙️  Config testa...\n";
try {
    require_once "../config/config.php";
    echo "✓ Enpresen izena: " . EMPRESA_IZENA . "\n";
    echo "✓ Base URL: " . BASE_URL . "\n";
} catch (Exception $e) {
    echo "✗ Config errorea: " . $e->getMessage() . "\n";
}

// Klaseak - ✅ ZUZENDU: ../model/
echo "\n📦 Klaseak testa...\n";
try {
    require_once "../model/seguritatea.php";
    echo "✓ Seguritatea: kargatua\n";
    
    require_once "../model/usuario.php";
    echo "✓ Usuario: kargatua\n";
} catch (Exception $e) {
    echo "✗ Klase errorea: " . $e->getMessage() . "\n";
}

echo "\n✅ GUZTIA OK!\n";
?>
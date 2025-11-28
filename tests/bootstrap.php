<?php
/**
 * PHPUnit Bootstrap - Test Environment Setup
 */

// Define test environment
define('TESTING', true);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Database connection for tests
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'zabala_test';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'root';
$db_port = (int)(getenv('DB_PORT') ?: 3306);

try {
    $testConn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($testConn->connect_error) {
        throw new Exception("Test DB connection failed: " . $testConn->connect_errno);
    }
    
    $testConn->set_charset('utf8mb4');
    
    // Make test connection available globally
    $GLOBALS['testConn'] = $testConn;
    
} catch (Exception $e) {
    error_log("Test bootstrap error: " . $e->getMessage());
    $GLOBALS['testConn'] = null;
}

// Helper function to get test database connection
function getTestConnection(): ?mysqli {
    return $GLOBALS['testConn'] ?? null;
}

// Helper function to clean test database
function cleanTestDatabase(mysqli $conn): void {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE seguritatea_loga");
    $conn->query("TRUNCATE TABLE salmenta");
    $conn->query("TRUNCATE TABLE langilea");
    $conn->query("TRUNCATE TABLE produktua");
    $conn->query("TRUNCATE TABLE usuario");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}

// Helper function to seed test data
function seedTestUser(mysqli $conn, array $data = []): int {
    $defaults = [
        'izena' => 'Test',
        'abizena' => 'User',
        'nan' => '12345678A',
        'email' => 'test@zabala.eus',
        'user' => 'testuser',
        'password' => password_hash('Test12345!@#', PASSWORD_DEFAULT),
        'rol' => 'langilea',
        'aktibo' => 1
    ];
    
    $userData = array_merge($defaults, $data);
    
    $stmt = $conn->prepare("INSERT INTO usuario (izena, abizena, nan, email, user, password, rol, aktibo) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        "sssssssi",
        $userData['izena'],
        $userData['abizena'],
        $userData['nan'],
        $userData['email'],
        $userData['user'],
        $userData['password'],
        $userData['rol'],
        $userData['aktibo']
    );
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    
    return $id;
}
